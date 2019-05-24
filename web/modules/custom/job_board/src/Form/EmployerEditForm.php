<?php

namespace Drupal\job_board\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EmployerEditForm
 *
 * @package Drupal\job_board\Form
 */
class EmployerEditForm extends ContentEntityForm {

  public function save(array $form, FormStateInterface $form_state) {
    // Add Roles
    $this->entity->addRole('employer');
    $this->entity->addRole('organisation');

    $return = parent::save($form, $form_state);

    // Create an organisation profile if it doesn't already exist.
    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = \Drupal::entityTypeManager()->getStorage('profile');
    $name = $form_state->getValue(['employer_profile', 'employer_name']);
    if ($profile = $profile_storage->loadDefaultByUser($this->entity, 'organisation')) {
      $profile->organisation_name = $name;
      $profile->save();
    }
    else {
      $profile = $profile_storage->create([
        'type' => 'organisation',
        'uid' => $this->entity,
        'organisation_name' => $name,
      ]);
      $profile->save();
    }

    return $return;
  }
}
