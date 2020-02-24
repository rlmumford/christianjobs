<?php

namespace Drupal\cj_membership\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\job_board\Form\JobForm;

class VolunteerRoleForm extends JobForm {

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Remove salary & hours.
    unset($form['salary']); unset($form['hours']); unset($form['compensation']);
    $form['files']['#weight'] = 55;

    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return int
   */
  public function save(array $form, FormStateInterface $form_state) {
    $return = parent::save($form, $form_state);
    $form_state->setRedirect('entity.volunteer_role.canonical', ['volunteer_role' => $this->entity->id()]);
    return $return;
  }

}
