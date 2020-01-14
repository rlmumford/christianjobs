<?php

use Drupal\cj_membership\Entity\Membership;
use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Locale\CountryManager;
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
  $query->condition('profile_id', $sandbox['last_id'], '>');
  $query->sort('profile_id', 'ASC');
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
    $job_query->condition('publish_date', (new DrupalDateTime())->format('Y-m-d'), '<=');

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

/**
 * Set location tree things.
 */
function job_board_post_update_set_employer_address_geo_and_tree() {
  /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
  $profile_storage = \Drupal::entityTypeManager()->getStorage('profile');
  /** @var \Drupal\taxonomy\TermStorage $term_storage */
  $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

  if (!isset($sandbox['max'])) {
    $sandbox['max'] = $profile_storage->getQuery()->condition('type','employer')->count()->execute();
    $sandbox['progress'] = $sandbox['last_id'] = 0;
  }

  $query = $profile_storage->getQuery();
  $query->condition('type', 'employer');
  $query->condition('profile_id', $sandbox['last_id'], '>');
  $query->sort('profile_id', 'ASC');
  $query->range(0, 20);

  /** @var \Drupal\profile\Entity\Profile $profile */
  foreach ($profile_storage->loadMultiple($query->execute()) as $profile) {
    $sandbox['progress']++;
    $sandbox['last_id'] = $profile->id();

    if (!isset($profile->address_tree) || !$profile->address_tree->isEmpty()) {
      continue;
    }

    if (!isset($profile->address) || $profile->address->isEmpty()) {
      continue;
    }

    /** @var \Drupal\geocoder_field\PreprocessorPluginManager $preprocessor_manager */
    $preprocessor_manager = \Drupal::service('plugin.manager.geocoder.preprocessor');
    /** @var \Drupal\geocoder\DumperPluginManager $dumper_manager */
    $dumper_manager = \Drupal::service('plugin.manager.geocoder.dumper');

    $address = $profile->address;

    // First we need to Pre-process field.
    // Note: in case of Address module integration this creates the
    // value as formatted address.
    $preprocessor_manager->preprocess($address);

    /** @var \Drupal\geocoder\DumperBase $dumper */
    $dumper = $dumper_manager->createInstance('geojson');
    $result = [];

    foreach ($address->getValue() as $delta => $value) {
      if ($address->getFieldDefinition()->getType() == 'address_country') {
        $value['value'] = CountryManager::getStandardList()[$value['value']];
      }

      $address_collection = isset($value['value']) ? \Drupal::service('geocoder')->geocode($value['value'], ['googlemaps', 'googlemaps_business']) : NULL;
      if ($address_collection) {
        $result[$delta] = $dumper->dump($address_collection->first());

        // We can't use DumperPluginManager::fixDumperFieldIncompatibility
        // because we do not have a FieldConfigInterface.
        // Fix not UTF-8 encoded result strings.
        // https://stackoverflow.com/questions/6723562/how-to-detect-malformed-utf-8-string-in-php
        if (is_string($result[$delta])) {
          if (!preg_match('//u', $result[$delta])) {
            $result[$delta] = utf8_encode($result[$delta]);
          }
        }
      }
    }

    $profile->set('address_geo', $result);

    $terms = [];
    $data = json_decode($profile->address_geo->value);
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

    $profile->address_tree = $terms;
    $profile->save();
  }

  $sandbox['#finished'] = min(1, $sandbox['progress']/$sandbox['max']);
  return "Processed ".number_format($sandbox['#finished']*100,2)."%";
}

/**
 * Set the description summary
 */
function job_board_post_update_set_employer_description_summary(&$sandbox = NULL) {
  $storage = \Drupal::entityTypeManager()->getStorage('profile');

  if (!isset($sandbox['max'])) {
    $sandbox['max'] = $storage->getQuery()->condition('type','employer')->count()->execute();
    $sandbox['progress'] = $sandbox['last_id'] = 0;
  }

  $query = $storage->getQuery();
  $query->condition('type', 'employer');
  $query->condition('profile_id', $sandbox['last_id'], '>');
  $query->sort('profile_id', 'ASC');
  $query->range(0, 20);

  /** @var \Drupal\profile\Entity\Profile $profile */
  foreach ($storage->loadMultiple($query->execute()) as $profile) {
    $sandbox['progress']++;
    $sandbox['last_id'] = $profile->id();

    if (!$profile->employer_description->isEmpty() && $profile->employer_description_summary->isEmpty()) {
      $cut_point = strpos($profile->employer_description->value, ' ', 500);

      $summary = [
        'value' => strip_tags(substr($profile->employer_description->value, 0, $cut_point)),
        'format' => 'restricted_html',
      ];
      $profile->employer_description_summary = $summary;
      $profile->save();
    }

    $sandbox['#finished'] = min(1, $sandbox['progress']/$sandbox['max']);
    return "Processed ".number_format($sandbox['#finished']*100,2)."%";
  }
}
