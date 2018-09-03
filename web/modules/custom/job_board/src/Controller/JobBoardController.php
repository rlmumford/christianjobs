<?php

namespace Drupal\job_board\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\TransactionNameNonUniqueException;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class JobBoardController extends ControllerBase {

  /**
   * Page to post a new job.
   */
  public function postJob() {
    $current_user = \Drupal::currentUser();
    if ($current_user->isAnonymous() && !$current_user->hasPermission('post job board jobs')) {
      user_cookie_save([
        'jobPostRegister' => TRUE,
      ]);
      return $this->redirect('user.register');
    }

    $user = entity_load('user', $current_user->id());
    if (!$user || !$user->profile_employer->entity || !$user->profile_employer->entity->employer_name->value) {
      return $this->redirect('job_board.employer_edit', ['user' => $current_user->id()]);
    }

    /** @var \Drupal\job_board\JobBoardJobRole $job */
    $job = $this->entityTypeManager()->getStorage('job_role')->create([]);
    $job->setOwnerId($current_user->id());
    return $this->entityFormBuilder()->getForm($job, 'post');
  }

  /**
   * Return the employer page title.
   */
  public function employerTitle(UserInterface $user) {
    $profile = $user->profile_employer->entity;

    if ($profile->employer_name->value) {
      return $profile->employer_name->value;
    }

    return $this->t('@username\'s Organisation', [
      '@username' => $user->label(),
    ]);
  }

  /**
   * Return the employer edit page title.
   */
  public function employerEditTitle(UserInterface $user) {
    return $this->t('Edit @employer', [
      '@employer' => $this->employerTitle($user),
    ]);
  }
}
