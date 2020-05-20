<?php

namespace Drupal\job_board\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Job Role Credit Entity.
 *
 * @ContentEntityType(
 *   id = "job_board_job_credit",
 *   label = @Translation("Job Credit"),
 *   label_singular = @Translation("job credit"),
 *   label_plural = @Translation("job credits"),
 *   label_count = @PluralTranslation(
 *     singular = "@count job credit",
 *     plural = "@count job credits"
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "job_credit",
 *   admin_permission = "administer job credits",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "owner",
 *   }
 * )
 */
class JobCredit extends ContentEntityBase implements EntityOwnerInterface {
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['organization'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Organization'))
      ->setSetting('target_type', 'organization')
      ->setDefaultValueCallback(static::class . '::getDefaultEntityOrganization');

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Status'))
      ->setSetting('allowed_values', [
        'draft' => new TranslatableMarkup('Draft'),
        'available' => new TranslatableMarkup('Available'),
        'expired' => new TranslatableMarkup('Expired'),
        'spent' => new TranslatableMarkup('Spent'),
      ])
      ->setDefaultValue([
        ['value' => 'draft'],
      ]);

    $fields['expires'] = BaseFieldDefinition::create('datetime')
      ->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATE)
      ->setLabel(new TranslatableMarkup('Valid Until'))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

  /**
   * Get the default organization.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   */
  public static function getDefaultEntityOrganization(EntityInterface $entity) {
    if ($entity instanceof EntityOwnerInterface && $owner = $entity->getOwner()) {
      return \Drupal::service('organization_user.organization_resolver')->getOrganization($owner);
    }

    return \Drupal::service('organization_user.organization_resolver')->getOrganization();
  }

}
