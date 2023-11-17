<?php

namespace Drupal\iqual\Form;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Custom iqual config.
 */
class IqualSettingsForm extends ConfigFormBase {

  /**
   * The entity type manger service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The status codes for the status code settings.
   *
   * @var array
   */
  protected $statusCodes = [];

  /**
   * Constructs the form.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manger service.
   */
  public function __construct(EntityTypeManager $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->statusCodes =
    [
      403 => $this->t('403 Forbidden') . $this->t('(Default)'),
      401 => $this->t('401 Unauthorized'),
      402 => $this->t('402 Payment Required'),
      404 => $this->t('404 Not Found'),
      407 => $this->t('407 Proxy Authentication Required'),
      410 => $this->t('410 Gone'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'iqual.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'iqual_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    parent::buildForm($form, $form_state);
    $config = $this->config('iqual.settings');

    $form['iqual_settings'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Iqual Settings'),
    ];

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#open' => TRUE,
      '#group' => 'iqual_settings',
    ];
    $form['general'] = $this->addGeneralSettings($form['general'], $form_state, $config);
    return parent::buildForm($form, $form_state);

  }

  /**
   * Add fields to "General" group.
   */
  protected function addGeneralSettings(array $form, FormStateInterface $form_state, $config) {
    $form['yoast'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Yoast'),
    ];
    $form['yoast']['hide_title_slug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide title and slug input'),
      '#default_value' => $config->get('hide_title_slug'),
    ];

    // Define which content types should be shown/hidden on the node/add page.
    $nodeTypes = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    $options = [];
    if (count($nodeTypes) > 0) {
      foreach ($nodeTypes as $nodeType) {
        $options[$nodeType->id()] = $nodeType->label();
      }
    }
    $form['ux'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('UX settings'),
    ];
    $form['ux']['hide_node_add_links'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Hide Links on node/add page'),
      '#options' => $options,
      '#default_value' => $config->get('hide_node_add_links') ?: [],
    ];
    $form['ux']['entity_unpublished_status'] = [
      '#type' => 'select',
      '#title' => $this->t('Set the status code on unpublished entities and missing translations (defaults to 403).'),
      '#options' => $this->statusCodes,
      '#default_value' => $config->get('entity_unpublished_status') ?: FALSE,
      "#description" => $this->t('Changing this value may require a cache rebuild to apply.'),
    ];
    return $form;
  }

  /**
   * Add fields to "Performance" group.
   */
  protected function addPerformanceSettings(array $form, FormStateInterface $form_state, $config) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ((is_countable($values = $form_state->getValue('hide_node_add_links')) ? count($values = $form_state->getValue('hide_node_add_links')) : 0) > 0) {
      $nodeTypes = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
      if (array_values($values) == array_keys($nodeTypes)) {
        $this->messenger()->addWarning($this->t('All node types are excluded from node/add page'));
      }
    }
    $entity_unpublished_status = $form_state->getValue('entity_unpublished_status');
    if (!is_numeric($entity_unpublished_status)) {
      $form_state->setErrorByName(
        'entity_unpublished_status',
        $this->t('The status code must be numeric.')
      );
    }
    if (!in_array((int) $entity_unpublished_status, array_keys($this->statusCodes))) {
      $form_state->setErrorByName(
        'entity_unpublished_status',
        $this->t('Invalid status code selected.')
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('iqual.settings');
    $config->set('hide_title_slug', $form_state->getValue('hide_title_slug'));
    $config->set('hide_node_add_links', $form_state->getValue('hide_node_add_links'));
    $config->set('entity_unpublished_status', (int) $form_state->getValue('entity_unpublished_status'));
    $config->save();
  }

}
