<?php

namespace Drupal\contacts_jobs_credits\Entity;

use Drupal\Core\Entity\ContentEntityBase;
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
 *   id = "cj_credit",
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

    $fields['org'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Organisation'))
      ->setSetting('target_type', 'user');

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

    $fields['quantity'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Quantity'))
      ->setSetting('min', 0)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['quantity_spent'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Quantity Spent'))
      ->setSetting('min', 0)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['expires'] = BaseFieldDefinition::create('datetime')
      ->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATE)
      ->setLabel(new TranslatableMarkup('Valid Until'))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

}
