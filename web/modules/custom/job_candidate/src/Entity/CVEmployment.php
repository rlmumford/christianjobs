<?php

namespace Drupal\job_candidate\Entity;


use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Class CVEmployment
 *
 * @ContentEntityType(
 *   id = "cv_employment",
 *   label = @Translation("Employment"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "access" = "Drupal\job_candidate\Entity\CVInlineEntityAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm"
 *     },
 *     "views_data" = "Drupal\entity\EntityViewsData"
 *   },
 *   base_table = "cv_employment",
 *   admin_permission = "administer candidates",
 *   entity_keys = {
 *     "id" = "id",
 *     "owner" = "candidate",
 *     "uuid" = "uuid"
 *   }
 * )
 *
 * @package Drupal\job_candidate\Entity
 */
class CVEmployment extends ContentEntityBase implements EntityOwnerInterface {
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // @todo: Employer Name
    $fields['employer'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Employer'))
      ->setDisplayOptions('view', [
        'type' => 'string_default',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE);
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Job Title'))
      ->setDisplayOptions('view', [
        'type' => 'string_default',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE);
    $fields['start_date'] = BaseFieldDefinition::create('datetime')
      ->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATE)
      ->setLabel(new TranslatableMarkup('Start Date'))
      ->setDisplayOptions('view', [
        'type' => 'datetime_default',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
      ])
      ->setDisplayConfigurable('form', TRUE);
    $fields['end_date'] = BaseFieldDefinition::create('datetime')
      ->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATE)
      ->setLabel(new TranslatableMarkup('End Date'))
      ->setDisplayOptions('view', [
        'type' => 'datetime_default',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
      ])
      ->setDisplayConfigurable('form', TRUE);
    // @todo: Salary (price field?) (salary field without range)
    // @todo: Notice Period (time period field)
    // @todo: Level (select options)
    // @todo: Function tags.

    return $fields;
  }
}
