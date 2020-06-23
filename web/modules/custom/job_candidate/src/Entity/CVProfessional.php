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
 * Class CVProfessional
 *
 * @ContentEntityType(
 *   id = "cv_professional",
 *   label = @Translation("Professional"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "access" = "Drupal\job_candidate\Entity\CVInlineEntityAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm"
 *     },
 *     "views_data" = "Drupal\entity\EntityViewsData"
 *   },
 *   base_table = "cv_professional",
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
class CVProfessional extends ContentEntityBase implements EntityOwnerInterface {
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('entity_reference')
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default:taxonomy_term')
      ->setSetting('handler_settings', [
        'target_bundles' => [
          'professional_qualification' => 'professional_qualification',
        ],
        'auto_create' => TRUE,
        'auto_create_bundle' => 'professional_qualification',
      ])
      ->setLabel(new TranslatableMarkup('Title'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'weight' => -6,
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
