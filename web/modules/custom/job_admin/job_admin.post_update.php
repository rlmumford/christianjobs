<?php

/**
 * Add 'organisation' role and profile to all organisations.
 */
function job_admin_post_update_add_organisation_role(&$sandbox = NULL) {
  $storage = \Drupal::entityTypeManager()->getStorage('user');
  $query = $storage->getQuery();
  $query->condition('roles', 'employer');

  if (!isset($sandbox['max'])) {
    $sandbox['max'] = $storage->getQuery()->condition('roles', 'employer')->count()->execute();
    $sandbox['progress'] = $sandbox['last_id'] = 0;
  }

  $query->condition('uid', $sandbox['last_id'], '>');
  $query->sort('uid', 'ASC');
  $query->range(0, 50);

  /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
  $profile_storage = \Drupal::entityTypeManager()->getStorage('profile');
  /** @var \Drupal\user\UserInterface $user */
  foreach ($storage->loadMultiple($query->execute()) as $user) {
    $name = NULL;
    if ($profile = $profile_storage->loadDefaultByUser($user, 'employer')) {
      $name = $profile->employer_name->value;
    }

    $profile_storage->create([
      'type' => 'organisation',
      'uid' => $user->id(),
      'organisation_name' => $name,
    ])->save();

    $user->addRole('organisation');
    $user->save();

    $sandbox['last_id'] = $user->id();
    $sandbox['progress']++;
  }

  $sandbox['#finished'] = !empty($sandbox['max']) ? min(1, $sandbox['progress'] / $sandbox['max']) : 1;
}
