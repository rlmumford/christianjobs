<?php

namespace Drupal\cj_membership\Entity;

use Drupal\cj_membership\MembershipStorage;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class VolunteerRoleAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * @var \Drupal\cj_membership\MembershipStorage
   */
  protected $membershipStorage;

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *
   * @return \Drupal\cj_membership\Entity\VolunteerRoleAccessControlHandler|\Drupal\Core\Entity\EntityHandlerInterface
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage('membership')
    );
  }

  /**
   * VolunteerRoleAccessControlHandler constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   * @param \Drupal\cj_membership\MembershipStorage $membership_storage
   *
   * @throws \Exception
   */
  public function __construct(EntityTypeInterface $entity_type, MembershipStorage $membership_storage) {
    parent::__construct($entity_type);

    $this->membershipStorage = $membership_storage;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $access = parent::checkCreateAccess($account, $context, $entity_bundle);

    $membership = $this->membershipStorage->getAccountMembership($account);

    $access = $access->orIf(
      AccessResult::allowedIf($membership && ($membership->status === Membership::STATUS_ACTIVE))
        ->addCacheableDependency($membership)
        ->addCacheableDependency($account)
    );

    return $access;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\cj_membership\Entity\VolunteerRole $entity */
    $access = parent::checkAccess($entity, $operation, $account);

    if ($operation == 'view') {
      $membership = $this->membershipStorage->getAccountMembership($entity->getOwner());

      $access = $access->orIf(
        AccessResult::forbiddenIf(
          $account->id() !== $entity->getOwnerId() &&
          (!$membership || $membership->status !== Membership::STATUS_ACTIVE)
        )
          ->addCacheableDependency($entity)
          ->addCacheableDependency($entity->getOwner())
          ->addCacheableDependency($membership)
      );
    }

    $access = $access->orIf(AccessResult::allowedIfHasPermission($account, $operation.' any volunteer_role'));
    $access = $access->orIf(
      AccessResult::allowedIf($entity->getOwnerId() == $account->id())
        ->andIf(AccessResult::allowedIfHasPermission($account, $operation.' own volunteer_role'))
        ->addCacheableDependency($entity)
    );

    return $access;
  }

}
