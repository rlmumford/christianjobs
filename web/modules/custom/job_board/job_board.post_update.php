<?php

use Drupal\Component\Utility\Html;

/**
 * Set short summary on job roles.
 */
function job_board_post_update_geocode_and_short_description_fill() {
  $job_storage = \Drupal::entityTypeManager()->getStorage('job_role');
  foreach ($job_storage->loadMultiple(NULL) as $job_role) {
    $summary = [
      'value' => strip_tags(substr($job_role->description->value, 0, 400)),
      'format' => 'restricted_html',
    ];
    if ($summary['value']) {
      $job_role->description_summary = $summary;
    }

    // Geocoding happens on presave.
    $job_role->save();
  }
}
