<?php

namespace Drupal\job_board\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

class JobBoardController extends ControllerBase {

  /**
   * Page to post a new job.
   */
  public function postJob() {
    $current_user = \Drupal::currentUser();
    if ($current_user->isAnonymous() && !$current_user->hasPermission('post job board jobs')) {
      return new RedirectResponse(
        Url::fromRoute('user.register', [], ['query' => ['destination' => 'jobs/post']])->setAbsolute()->toString()
      );
    }

    /** @var \Drupal\job_board\JobBoardJobRole $job */
    $job = $this->entityTypeManager()->getStorage('job_role')->create([]);
    $job->setOwnerId($current_user->id());
    return $this->entityFormBuilder()->getForm($job, 'post');
  }
}
