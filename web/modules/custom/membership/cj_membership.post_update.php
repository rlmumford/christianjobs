<?php

use Drupal\cj_membership\Entity\Membership;

/**
 * @param $sandbox
 */
function cj_membership_post_update_set_level_to_full(&$sandbox) {
  $storage = \Drupal::entityTypeManager()->getStorage('cj_membership');

  if (!isset($sandbox['max'])) {
    $sandbox['max'] = $storage->getQuery()->count()->execute();
    $sandbox['progress'] = $sandbox['last_id'] = 0;
  }

  $query = $storage->getQuery();
  $query->condition('id', $sandbox['last_id'], '>');
  $query->sort('id', 'ASC');
  $query->range(0, 20);

  /** @var \Drupal\job_board\JobBoardJobRole $job_role */
  foreach ($storage->loadMultiple($query->execute()) as $membership) {
    $sandbox['progress']++;
    $sandbox['last_id'] = $membership->id();

    $membership->level = Membership::LEVEL_FULL;
    $membership->save();
  }

  $sandbox['#finished'] = min(1, $sandbox['progress']/$sandbox['max']);
  return "Processed ".number_format($sandbox['#finished']*100,2)."%";
}
