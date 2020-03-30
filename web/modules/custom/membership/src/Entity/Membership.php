<?php

namespace Drupal\cj_membership\Entity;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\SynchronizableEntityTrait;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserInterface;

/**
 * Membership Entity
 *
 * @ContentEntityType(
 *   id = "cj_membership",
 *   label = @Translation("Membership"),
 *   label_singular = @Translation("membership"),
 *   label_plural = @Translation("memberships"),
 *   label_count = @PluralTranslation(
 *     singular = "@count membership",
 *     plural = "@count memberships"
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\cj_membership\MembershipListBuilder",
 *     "storage" = "Drupal\cj_membership\MembershipStorage",
 *     "access" = "Drupal\cj_membership\MembershipAccessControlHandler",
 *     "permission_provider" = "Drupal\cj_membership\MembershipPermissionProvider",
 *     "form" = {
 *       "default" = "Drupal\cj_membership\MembershipForm"
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData"
 *   },
 *   base_table = "cj_membership",
 *   revision_table = "cj_membership_revision",
 *   data_table = "cj_membership_data",
 *   field_ui_base_route = "entity.cj_membership.admin_form",
 *   admin_permission = "administer memberships",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class Membership extends ContentEntityBase implements EntityOwnerInterface, PurchasableEntityInterface {
  use SynchronizableEntityTrait;

  const STATUS_ACTIVE = 'active';
  const STATUS_INACTIVE = 'inactive';
  const STATUS_EXPIRED = 'expired';

  const LEVEL_FULL = 100;
  const LEVEL_DIRECTORY = 50;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $definitions = parent::baseFieldDefinitions($entity_type);

    $definitions['member'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Member'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $definitions['manager'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Account Manager'))
      ->setSetting('target_type', 'user')
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $definitions['level'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(new TranslatableMarkup('Membership Level'))
      ->setSetting('allowed_values', [
        static::LEVEL_DIRECTORY => new TranslatableMarkup('Directory'),
        static::LEVEL_FULL => new TranslatableMarkup('Full'),
      ])
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $definitions['expiry'] = BaseFieldDefinition::create('datetime')
      ->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATE)
      ->setLabel(new TranslatableMarkup('Expiry Date'))
      ->setDescription(new TranslatableMarkup('The date this membership expires.'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $definitions['start'] = BaseFieldDefinition::create('datetime')
      ->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATE)
      ->setLabel(new TranslatableMarkup('Start Date'))
      ->setDescription(new TranslatableMarkup('The date this membership started.'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $definitions['extended'] = BaseFieldDefinition::create('datetime')
      ->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATE)
      ->setLabel(new TranslatableMarkup('Extended Date'))
      ->setDescription(new TranslatableMarkup('The date this membership was last extended.'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $definitions['status'] = BaseFieldDefinition::create('list_string')
      ->setSetting('allowed_values', [
        static::STATUS_INACTIVE => new TranslatableMarkup('Inactive'),
        static::STATUS_ACTIVE => new TranslatableMarkup('Active'),
        static::STATUS_EXPIRED => new TranslatableMarkup('Expired'),
      ])
      ->setDefaultValue([
        0 => ['value' => static::STATUS_INACTIVE],
      ])
      ->setLabel(new TranslatableMarkup('Status'))
      ->setDescription(new TranslatableMarkup('The status of this membership.'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $label = parent::label();

    // If no label then make one from the members username
    if (!$label) {
      $member_user = $this->member->entity;
      /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
      $profile_storage = \Drupal::entityTypeManager()->getStorage('profile');
      $profile = $profile_storage->loadDefaultByUser($member_user, 'employer');
      $name = $profile ? $profile->employer_name->value : $member_user->label();

      return (new TranslatableMarkup('@members\'s Membership', ['@member' => $name]))->render();
    }

    return $label;
  }

  /**
   * Returns the entity owner's user entity.
   *
   * @return \Drupal\user\UserInterface
   *   The owner user entity.
   */
  public function getOwner() {
    return $this->member->entity;
  }

  /**
   * Sets the entity owner's user entity.
   *
   * @param \Drupal\user\UserInterface $account
   *   The owner user entity.
   *
   * @return $this
   */
  public function setOwner(UserInterface $account) {
    $this->member = $account;
    return $this;
  }

  /**
   * Returns the entity owner's user ID.
   *
   * @return int|null
   *   The owner user ID, or NULL in case the user ID field has not been set on
   *   the entity.
   */
  public function getOwnerId() {
    return $this->member->target_id;
  }

  /**
   * Sets the entity owner's user ID.
   *
   * @param int $uid
   *   The owner user id.
   *
   * @return $this
   */
  public function setOwnerId($uid) {
    $this->member->target_id = $uid;
    return $this;
  }

  /**
   * Gets the stores through which the purchasable entity is sold.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface[]
   *   The stores.
   */
  public function getStores() {
    return $this->entityTypeManager()->getStorage('commerce_store')->loadMultiple();
  }

  /**
   * Gets the purchasable entity's order item type ID.
   *
   * Used for finding/creating the appropriate order item when purchasing a
   * product (adding it to an order).
   *
   * @return string
   *   The order item type ID.
   */
  public function getOrderItemTypeId() {
    return 'cj_membership';
  }

  /**
   * Gets the purchasable entity's order item title.
   *
   * Saved in the $order_item->title field to protect the order items of
   * completed orders against changes in the referenced purchased entity.
   *
   * @return string
   *   The order item title.
   */
  public function getOrderItemTitle() {
    return new TranslatableMarkup("Membership");
  }

  /**
   * Gets the purchasable entity's price.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The price, or NULL.
   */
  public function getPrice(Context $context = NULL) {
    $config = \Drupal::config('cj_membership.pricing');

    if ($this->level->value == static::LEVEL_FULL) {
      return new Price($config->get('full'), 'GBP');
    }
    else {
      return new Price($config->get('directory'), 'GBP');
    }
  }
}
