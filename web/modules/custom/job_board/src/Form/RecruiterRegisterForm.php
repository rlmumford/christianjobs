<?php

namespace Drupal\job_board\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RegisterForm;

class RecruiterRegisterForm extends RegisterForm {

  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    // If the user is logged in as part of registration, then
    if (\Drupal::currentUser()->isAuthenticated()) {
      $form_state->setRedirect('job_board.recruiter.register.organization');
    }
  }

}
