<?php

namespace Drupal\cj\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Apply Now Button block.
 *
 * @Block(
 *   id = "cj_job_org_logo",
 *   admin_label = @Translation("Job Organisation Logo"),
 *   category = @Translation("Contacts Jobs"),
 *   context = {
 *     "job" = @ContextDefinition("entity:contacts_job", label = @Translation("Job"))
 *   }
 * )
 */
class JobOrgLogo extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\contacts_jobs\Entity\Job $job */
    $job = $this->getContextValue('job');

    $cacheability = new CacheableMetadata();
    $cacheability->addCacheableDependency($job);

    $build = [];
    if (!$job->organisation->isEmpty()) {
      $organisation = $job->organisation->entity;
      $cacheability->addCacheableDependency($organisation);

      if ($profile = $organisation->profile_crm_org->entity) {
        $cacheability->addCacheableDependency($profile);

        if (!$profile->org_image->isEmpty()) {
          $build = $profile->org_image->view([
            'type' => 'image',
            'label' => 'hidden',
          ]);
        }
      }
    }

    $cacheability->applyTo($build);

    return $build;
  }
}
