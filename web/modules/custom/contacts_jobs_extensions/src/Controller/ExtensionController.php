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
   * @param \Drupal\contacts_jobs\Entity\JobInterface $job
   *   The job being extended.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The page title for the extend page.
   */
  public function extendJobTitle(JobInterface $job) {
    return new TranslatableMarkup('Extend @job', ['@job' => $job->label()]);
  }

}
