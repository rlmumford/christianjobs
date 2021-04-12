<?php

namespace Drupal\contacts_jobs_dashboard;

use Drupal\user\UserInterface;

/**
 * Helper methods for managing organisation members.
 */
class OrganisationMemberHelper {

  /**
   * Get the members of an organisation with the given permission.
   *
   * @param \Drupal\user\UserInterface $organisation
   *   The organisation.
   * @param string $permission
   *   The permission to check on the membership.
   *
   * @return \Drupal\user\UserInterface[]
   *   The member users.
   */
  public static function getMembersWithPermission(UserInterface $organisation, string $permission): array {
    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = $organisation->get('group')->entity;
    $users = [];

    foreach ($group->getMembers() as $membership) {
      if ($membership->hasPermission($permission)) {
        $users[] = $membership->getUser();
      }
    }

    return $users;
  }

}
