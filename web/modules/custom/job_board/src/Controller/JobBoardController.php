<?php

namespace Drupal\job_board\Controller;

use Drupal\Core\Controller\ControllerBase;

class JobBoardController extends ControllerBase {

  /**
   * Page to post a new job.
   */
  public function postJob() {
    /** @var \Drupal\job_board\JobBoardJobRole $job */
    $job = $this->entityTypeManager()->getStorage('job_role')->create([]);
    $job->setOwnerId(\Drupal::currentUser()->id());
    return $this->entityFormBuilder()->getForm($job, 'post');
  }
}
