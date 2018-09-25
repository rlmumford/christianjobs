<?php

namespace Drupal\cj_membership;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;

class MembershipStorage extends SqlContentEntityStorage {

  /**
   * Get the membership entity associated with an account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return \Drupal\cj_membership\Entity\Membership|NULL
   */
  public function getAccountMembership(AccountInterface $account) {
    $membership_ids = $this->getQuery()
      ->condition('member.target_id', $account->id())
      ->execute();
    return $this->load(reset($membership_ids));
  }

}
