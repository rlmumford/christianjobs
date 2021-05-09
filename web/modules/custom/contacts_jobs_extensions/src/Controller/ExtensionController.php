<?php

namespace Drupal\contacts_jobs_extensions\Controller;

use Drupal\contacts_jobs\Entity\JobInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Controller for extensions.
 *
 * @package Drupal\contacts_jobs_extensions\Controller
 */
class ExtensionController extends ControllerBase {

  /**
   * Extend job title.
   *
   * @param \Drupal\contacts_jobs\Entity\JobInterface $contacts_job
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The page title for the extend page.
   */
  public function extendJobTitle(JobInterface $contacts_job) {
    return new TranslatableMarkup('Extend @job', ['@job' => $contacts_job->label()]);
  }

}
