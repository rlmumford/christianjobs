<?php

namespace Drupal\cj\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

class BankDetailsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['cj.bank_details'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cj_bank_details';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['bank_name'] = [
      '#type' => 'textfield',
      '#title' => new TranslatableMarkup('Bank Name'),
      '#description' => new TranslatableMarkup('The bank name used in invoice generation.'),
      '#default_value' => $this->config('cj.bank_details')->get('bank_name'),
    ];

    $form['sort_code'] = [
      '#type' => 'textfield',
      '#title' => new TranslatableMarkup('Sort Code'),
      '#description' => new TranslatableMarkup('The sort code used in invoice generation.'),
      '#default_value' => $this->config('cj.bank_details')->get('sort_code'),
    ];

    $form['account_number'] = [
      '#type' => 'textfield',
      '#title' => new TranslatableMarkup('Account Number'),
      '#description' => new TranslatableMarkup('The account number used in invoice generation.'),
      '#default_value' => $this->config('cj.bank_details')->get('account_number'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('cj.bank_details');
    $config->set('bank_name', $form_state->getValue('bank_name'));
    $config->set('sort_code', $form_state->getValue('sort_code'));
    $config->set('account_number', $form_state->getValue('account_number'));
    $config->save();

    parent::submitForm($form, $form_state);
  }
}
