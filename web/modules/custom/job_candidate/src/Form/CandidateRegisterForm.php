<?php

namespace Drupal\job_candidate\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RegisterForm;

class CandidateRegisterForm extends RegisterForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    // If the user is logged in as part of registration, then
    if (\Drupal::currentUser()->isAuthenticated()) {
      $form_state->setRedirect(
        'job_candidate.candidate.register.personal');
    }
  }

}