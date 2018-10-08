<?php

namespace Drupal\job_board\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

class ExecSearchInitForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'executive_search_initiate_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['class'] = ['card', 'card-form'];
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#description' => $this->t('Enter your name'),
      '#required' => TRUE,
    ];
    $form['organisation'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Organisation'),
    ];
    $form['phone_number'] = [
      '#type' => 'tel',
      '#title' => $this->t('Phone Number'),
      '#required' => TRUE,
    ];
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email Address'),
      '#required' => TRUE,
    ];
    $form['position'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Role Title'),
      '#required' => TRUE,
    ];
    $form['position_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Role Description'),
      '#required' => TRUE,
    ];
    $form['callback_time'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Callback Time'),
      '#description' => $this->t('What is the best time for us to call you back about this placement?'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#attributes' => [
        'class' => ['card-item', 'card-actions', 'divider-top']
      ],
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Request Callback'),
      ],
    ];

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $mail_manager = \Drupal::service('plugin.manager.mail');
    $mail_manager->mail('job_board', 'new_exec_search_req', 'info@christianjobs.co.uk', LANGUAGE_NONE, $form_state->getValues(), NULL, TRUE);

    \Drupal::messenger()->addStatus(new TranslatableMarkup('Thank you for your interest. An agent will be in touch shortly.'));
  }
}
