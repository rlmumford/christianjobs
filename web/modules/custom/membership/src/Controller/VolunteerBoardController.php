<?php

namespace Drupal\cj_membership\Controller;

use Drupal\cj_membership\Entity\Membership;
use Drupal\Core\Controller\ControllerBase;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

class VolunteerBoardController extends ControllerBase {

  /**
   * Page to post a new job.
   */
  public function postRole() {
    $cookies = [
      'volunteerPostRegister' => TRUE,
    ];

    $current_user = \Drupal::currentUser();
    if ($current_user->isAnonymous() && !$current_user->hasPermission('post volunteer board roles')) {
      user_cookie_save($cookies);
      return $this->redirect('user.register');
    }

    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = $this->entityTypeManager()->getStorage('profile');
    if (!($profile = $profile_storage->loadDefaultByUser($current_user, 'employer')) || !$profile->employer_name->value) {
      user_cookie_save($cookies);
      return $this->redirect('job_board.employer_edit', ['user' => $current_user->id()]);
    }

    /** @var \Drupal\cj_membership\Entity\VolunteerRole $volunteer */
    $volunteer = $this->entityTypeManager()->getStorage('volunteer_role')->create();
    $volunteer->organisation = $current_user->id();
    $volunteer->setOwnerId($current_user->id());

    if ($profile->address) {
      $volunteer->contact_address = $profile->address;
    }
    if ($profile->email) {
      $volunteer->contact_email = $profile->email;
    }
    if ($profile->tel) {
      $volunteer->contact_phone = $profile->tel;
    }

    return $this->entityFormBuilder()->getForm($volunteer, 'post');
  }
}
