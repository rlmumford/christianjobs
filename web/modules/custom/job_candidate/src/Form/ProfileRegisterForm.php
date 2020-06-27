<?php

namespace Drupal\job_candidate\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class ProfileRegisterForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $return = parent::save($form, $form_state);

    if ($this->entity->bundle() == 'personal') {
      $next_step = 'resume';
    }
    else if ($this->entity->bundle() == 'resume') {
      $next_step = 'targets';
    }

    $form_state->setRedirect(
      'job_candidate.candidate.register.'.$next_step
    );

    return $return;
  }

}
