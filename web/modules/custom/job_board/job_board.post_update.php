<?php

use Drupal\Component\Utility\Html;

/**
 * Set short summary on job roles.
 */
function job_board_post_update_geocode_and_short_description_fill() {
  $job_storage = \Drupal::entityTypeManager()->getStorage('job_role');
  foreach ($job_storage->loadMultiple(NULL) as $job_role) {
    if (!$job_role->description_summary->isEmpty()) {
      continue;
    }

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

/**
 * Set location tree things.
 */
function job_board_post_update_set_location_tree() {
  $job_storage = \Drupal::entityTypeManager()->getStorage('job_role');
  /** @var \Drupal\taxonomy\TermStorage $term_storage */
  $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

  foreach ($job_storage->loadMultiple(NULL) as $job_role) {
    if (!$job_role->location_tree->isEmpty()) {
      continue;
    }

    if ($job_role->location_geo->isEmpty()) {
      continue;
    }

    $terms = [];
    $data = json_decode($job_role->location_geo->value);
    if (!$data || !isset($data->properties->adminLevels)) {
      continue;
    }

    // First get the tem for the country.
    if (!($term = reset($term_storage->loadByProperties(['vid' => 'locations', 'name' => $data->properties->country])))) {
      $term = $term_storage->create([
        'vid' => 'locations',
        'name' => $data->properties->country,
      ]);
      $term->save();
    }
    $terms[] = $term;

    foreach ($data->properties->adminLevels as $level) {
      if (!($next_term = reset($term_storage->loadByProperties(['vid' => 'locations', 'name' => $level->name, 'parent' => $term->id()])))) {
        $next_term = $term_storage->create([
          'vid' => 'locations',
          'name' => $level->name,
          'parent' => $term->id(),
        ]);
        $next_term->save();
      }
      $terms[] = $term = $next_term;
    }

    $job_role->location_tree = $terms;
    $job_role->save();
  }
}
