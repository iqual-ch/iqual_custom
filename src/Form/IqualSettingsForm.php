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
   * Constructs the form.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manger service.
   */
  public function __construct(EntityTypeManager $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
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
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('iqual.settings');
    $config->set('hide_title_slug', $form_state->getValue('hide_title_slug'));
    $config->set('hide_node_add_links', $form_state->getValue('hide_node_add_links'));
    $config->save();
  }

}
