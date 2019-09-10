<?php

use Drupal\cj_membership\Entity\Membership;
use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

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

/**
 * Set the initial paid_to_date.
 */
function job_board_post_update_set_paid_to_date(&$sandbox = NULL) {
  $storage = \Drupal::entityTypeManager()->getStorage('job_role');

  if (!isset($sandbox['max'])) {
    $sandbox['max'] = $storage->getQuery()->count()->execute();
    $sandbox['progress'] = $sandbox['last_id'] = 0;
  }

  $query = $storage->getQuery();
  $query->condition('id', $sandbox['last_id'], '>');
  $query->sort('id', 'ASC');
  $query->range(0, 20);

  /** @var \Drupal\job_board\JobBoardJobRole $job_role */
  foreach ($storage->loadMultiple($query->execute()) as $job_role) {
    $sandbox['progress']++;
    $sandbox['last_id'] = $job_role->id();

    if (!$job_role->paid_to_date->isEmpty()) {
      continue;
    }

    /** @var \Drupal\Core\Datetime\DrupalDateTime $end_date */
    $end_date = $job_role->end_date->date;


    /** @var \Drupal\Core\Datetime\DrupalDateTime $paid_to_date */
    $paid_to_date = clone $job_role->publish_date->date;
    $paid_to_date->add(new \DateInterval($job_role->initial_duration->value ?: 'P30D'));

    if ($end_date > $paid_to_date) {
      $job_role->paid_to_date->value = $end_date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT);
    }
    else {
      $job_role->paid_to_date->value = $paid_to_date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT);
    }
    $job_role->save();
  }

  $sandbox['#finished'] = min(1, $sandbox['progress']/$sandbox['max']);
  return "Processed ".number_format($sandbox['#finished']*100,2)."%";
}

/**
 * Set the on directory value.
 */
function job_board_post_update_set_on_directory(&$sandbox = NULL) {
  $storage = \Drupal::entityTypeManager()->getStorage('profile');

  if (!isset($sandbox['max'])) {
    $sandbox['max'] = $storage->getQuery()->condition('type','employer')->count()->execute();
    $sandbox['progress'] = $sandbox['last_id'] = 0;
  }

  $query = $storage->getQuery();
  $query->condition('type', 'employer');
  $query->condition('id', $sandbox['last_id'], '>');
  $query->sort('id', 'ASC');
  $query->range(0, 20);

  $job_storage = \Drupal::entityTypeManager()->getStorage('job_role');
  /** @var \Drupal\profile\Entity\Profile $profile */
  foreach ($storage->loadMultiple($query->execute()) as $profile) {
    $sandbox['progress']++;
    $sandbox['last_id'] = $profile->id();

    $on_directory = FALSE;
    /** @var \Drupal\user\UserInterface $user */
    $user = $profile->getOwner();

    // Query for finding membership.
    if (\Drupal::moduleHandler()->moduleExists('cj_membership')) {
      /** @var \Drupal\cj_membership\MembershipStorage $membership_storage */
      $membership_storage = \Drupal::entityTypeManager()->getStorage('cj_membership');
      $membership = $membership_storage->getAccountMembership($user);

      if ($membership && $membership->status->value == Membership::STATUS_ACTIVE) {
        $on_directory = TRUE;
      }
    }

    // Prepare query for finding jobs.
    $job_query = $job_storage->getQuery();
    $job_query->condition('organisation', $user->id());
    $job_query->condition('paid_to_date', (new DrupalDateTime())->format('Y-m-d'), '>');
    $job_query->condition('start_date', (new DrupalDateTime())->format('Y-m-d'), '<=');

    // If the user is a member, $on_directory is true.
    if (!$on_directory && $job_query->count()->execute() > 0) {
      $on_directory = TRUE;
    }

    if ($on_directory) {
      $profile->employer_on_directory = TRUE;
      $profile->save();
    }
  }

  $sandbox['#finished'] = min(1, $sandbox['progress']/$sandbox['max']);
  return "Processed ".number_format($sandbox['#finished']*100,2)."%";
}
