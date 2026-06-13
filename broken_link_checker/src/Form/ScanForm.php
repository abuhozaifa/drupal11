<?php
namespace Drupal\broken_link_checker\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class ScanForm extends FormBase {
  public function getFormId() { return 'broken_link_checker_scan'; }
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['submit'] = ['#type' => 'submit','#value' => 'Scan'];
    return $form;
  }
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addStatus('Scan placeholder executed.');
  }
}
