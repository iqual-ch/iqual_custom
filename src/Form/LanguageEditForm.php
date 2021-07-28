<?php

namespace Drupal\iqual\Form;

use Drupal\language\Form\LanguageEditForm as LanguageEditFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Override LanguageEditForm by adding new locale field to form.
 */
class LanguageEditForm extends LanguageEditFormBase {

  /**
   * Add new field locale to form.
   */
  public function commonForm(array &$form) {
    $form = parent::commonForm($form);
    $form['locale'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Locale Code'),
      '#maxlength' => 10,
      '#default_value' => $this->entity->getThirdPartySetting('iqual', 'locale') ? $this->entity->getThirdPartySetting('iqual', 'locale') : '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->setThirdPartySetting('iqual', 'locale', $form_state->getValue('locale'));
    parent::save($form, $form_state);
  }
}
