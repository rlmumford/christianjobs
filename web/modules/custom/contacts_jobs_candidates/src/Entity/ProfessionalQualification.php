<?php

namespace Drupal\contacts_jobs_candidates\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\entity\BundleFieldDefinition;

/**
 * Defines the Professional qualification entity.
 *
 * @ingroup contacts_jobs_apps
 *
 * @ContentEntityType(
 *   id = "professional_qualification",
 *   label = @Translation("Professional qualification"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\contacts_jobs_apps\CandidateComponentEntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "\Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "\Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "\Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "\Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\entity\EntityAccessControlHandler",
 *     "permission_provider" = "Drupal\entity\EntityPermissionProvider",
 *   },
 *   base_table = "professional_qualification",
 *   data_table = "professional_qualification_field_data",
 *   translatable = FALSE,
 *   admin_permission = "administer professional qualification entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "owner" = "user_id",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/professional_qualification/{professional_qualification}",
 *     "add-form" = "/admin/structure/professional_qualification/add",
 *     "edit-form" = "/admin/structure/professional_qualification/{professional_qualification}/edit",
 *     "delete-form" = "/admin/structure/professional_qualification/{professional_qualification}/delete",
 *     "collection" = "/admin/structure/professional_qualification",
 *   },
 *   field_ui_base_route = "entity.professional_qualification.collection"
 * )
 */
class ProfessionalQualification extends CandidateComponentBase implements CandidateComponentEntityInterface, EntityFieldStorageDefinitionInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    $term = $this->get('title')->entity;
    return $term ? $term->label() : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Overwrite the title with a taxonomy ER.
    $fields['title'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Title'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default:taxonomy_term')
      ->setSetting('handler_settings', [
        'target_bundles' => [
          'locations' => 'fmcg_qualification_titles',
        ],
        'auto_create' => TRUE,
        'auto_create_bundle' => 'fmcg_qualification_titles',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'settings' => [
          'link' => FALSE,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
      ]);

    $fields['date'] = BaseFieldDefinition::create('datetime')
      ->setLabel('Date')
      ->setDescription('The date the qualification was achieved.')
      ->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'datetime_default',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
      ]);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    $fields = parent::bundleFieldDefinitions($entity_type, $bundle, $base_field_definitions);

    foreach (static::fieldStorageDefinitions($entity_type) as $field_name => $definition) {
      $definition->setTargetBundle($bundle);
      $fields[$field_name] = $definition;
    }

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function fieldStorageDefinitions(EntityTypeInterface $entity_type): array {
    $fields = [];

    $fields['description'] = BundleFieldDefinition::create('text_long')
      ->setName('description')
      ->setTargetEntityTypeId($entity_type->id())
      ->setLabel(new TranslatableMarkup('Description'))
      ->setDescription(new TranslatableMarkup('Additional information about the qualification.'))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
      ]);

    $fields['evidence'] = BundleFieldDefinition::create('file')
      ->setName('evidence')
      ->setTargetEntityTypeId($entity_type->id())
      ->setLabel(new TranslatableMarkup('Evidence'))
      ->setDescription(new TranslatableMarkup('Upload evidence for this qualification.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('file_extensions', 'txt pdf doc docx odt')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'file_generic',
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
