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
 * Class CVEducation
 *
 * @ContentEntityType(
 *   id = "cv_education",
 *   label = @Translation("Education"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "access" = "Drupal\job_candidate\Entity\CVInlineEntityAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm"
 *     },
 *     "views_data" = "Drupal\entity\EntityViewsData"
 *   },
 *   base_table = "cv_education",
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
class CVEducation extends ContentEntityBase implements EntityOwnerInterface {
  use EntityOwnerTrait;

  /**
   * Constants for qualification levels.
   *
   * - LEVEL_SCHOOL: School qualifications
   * - LEVEL_BGRAD: Bachelor's Degree or Equivalent
   * - LEVEL_MGRAD: Master's Degree or Equivalent
   * - LEVEL_DOC: Doctorate/PHD or Equivalent
   * - LEVEL_PROF: Professorship
   */
  const LEVEL_SCHOOL = 'school';
  const LEVEL_BDEG = 'bdeg';
  const LEVEL_MDEG = 'mdeg';
  const LEVEL_DOC = 'doc';
  const LEVEL_PROF = 'prof';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['level'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Level'))
      ->setSetting('allowed_values', [
        static::LEVEL_SCHOOL => new TranslatableMarkup('School'),
        static::LEVEL_BDEG => new TranslatableMarkup('Bachelor\'s Degree'),
        static::LEVEL_MDEG => new TranslatableMarkup('Master\'s Degree'),
        static::LEVEL_DOC => new TranslatableMarkup('Doctorate/PhD'),
        static::LEVEL_PROF => new TranslatableMarkup('Professor'),
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['title'] = BaseFieldDefinition::create('entity_reference')
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default:taxonomy_term')
      ->setSetting('handler_settings', [
        'target_bundles' => [
          'educational_qualification' => 'educational_qualification',
        ],
        'auto_create' => TRUE,
        'auto_create_bundle' => 'educational_qualification',
      ])
      ->setLabel(new TranslatableMarkup('Title'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'weight' => -6,
        'type' => 'entity_reference_autocomplete_tags',
      ])
      ->setDisplayConfigurable('form', TRUE);
    $fields['institution'] = BaseFieldDefinition::create('entity_reference')
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default:taxonomy_term')
      ->setSetting('handler_settings', [
        'target_bundles' => [
          'educational_institution' => 'educational_institution',
        ],
        'auto_create' => TRUE,
        'auto_create_bundle' => 'educational_institution',
      ])
      ->setLabel(new TranslatableMarkup('Institution'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'weight' => -4,
        'type' => 'entity_reference_autocomplete_tags',
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['date'] = BaseFieldDefinition::create('datetime')
      ->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATE)
      ->setLabel(new TranslatableMarkup('Date'))
      ->setDisplayOptions('view', [
        'type' => 'datetime_default',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['grade'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Grade'))
      ->setSetting('max_length', 64)
      ->setDisplayOptions('view', [
        'type' => 'string_default',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Description'))
      ->setDisplayOptions('view', [
        'type' => 'text_default',
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['evidence'] = BaseFieldDefinition::create('file')
      ->setLabel(new TranslatableMarkup('Evidence'))
      ->setRevisionable(TRUE)
      ->setSetting('file_extensions', 'pdf png jpg')
      ->setSetting('uri_scheme', 'private')
      ->setSetting('description_field', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'file_generic',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'file_default',
        'label' => 'above',
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }
}
