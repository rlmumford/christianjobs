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
   * Constants for the employment levels.
   *
   * - L_CHIEF - Chief office, e.g. CEO, CTO
   * - L_SMGMT - Senior Management
   * - L_MGMT - Management
   */
  const L_CHIEF = 'chief';
  const L_SMGMT = 'smgmt';
  const L_MGMT = 'mgmt';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

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
    $fields['salary'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(new TranslatableMarkup('Salary'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['notice_period'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Notice Period'))
      ->setSetting('min', 0)
      ->setSetting('suffix', new TranslatableMarkup('weeks'))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('form', TRUE);
    $fields['level'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Level'))
      ->setSetting('allowed_values', [
        static::L_CHIEF => new TranslatableMarkup('Chief Officer'),
        static::L_SMGMT => new TranslatableMarkup('Senior Management'),
        static::L_MGMT => new TranslatableMarkup('Management')
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['function'] = BaseFieldDefinition::create('entity_reference')
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default:taxonomy_term')
      ->setSetting('handler_settings', [
        'target_bundles' => [
          'function' => 'function',
        ],
        'auto_create_bundle' => 'function',
      ])
      ->setLabel(new TranslatableMarkup('Institution'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete_tags',
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }
}
