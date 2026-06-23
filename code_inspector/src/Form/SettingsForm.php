<?php

namespace Drupal\code_inspector\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends ConfigFormBase {

  public function getFormId() {
    return 'code_inspector_settings_form';
  }

  protected function getEditableConfigNames() {
    return ['code_inspector.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('code_inspector.settings');

    $form['scan_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Scan Path'),
      '#default_value' => $config->get('scan_path'),
      '#required' => TRUE,
    ];
    
    $form['ignore_paths'] = [
  '#type' => 'textarea',
  '#title' => $this->t('Ignore Paths'),
  '#description' => $this->t("One path per line."),
  '#default_value' => $config->get('ignore_paths'),
];

    $form['ignore_vendor'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ignore vendor directory'),
      '#default_value' => $config->get('ignore_vendor'),
    ];

    $form['ignore_core'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ignore Drupal core'),
      '#default_value' => $config->get('ignore_core'),
    ];

    $form['enable_csv_export'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable CSV Export'),
      '#default_value' => $config->get('enable_csv_export'),
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->configFactory()
      ->getEditable('code_inspector.settings')
      ->set('scan_path', $form_state->getValue('scan_path'))
      ->set('ignore_paths', $form_state->getValue('ignore_paths'))
      ->set('ignore_vendor', $form_state->getValue('ignore_vendor'))
      ->set('ignore_core', $form_state->getValue('ignore_core'))
      ->set('enable_csv_export', $form_state->getValue('enable_csv_export'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
