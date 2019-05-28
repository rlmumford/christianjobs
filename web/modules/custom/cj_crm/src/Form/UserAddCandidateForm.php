<?php

namespace Drupal\cj_crm\Form;

use Drupal\rlmcrm\Form\UserRoleAddForm;

class UserAddCandidateForm extends UserRoleAddForm {

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    parent::prepareEntity();

    $this->entity->addRole('individual');
  }

}
