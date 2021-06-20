<?php

namespace Drupal\contacts_jobs_candidates\Entity;

use CommerceGuys\Addressing\AddressFormat\AddressField;
use CommerceGuys\Addressing\AddressFormat\FieldOverride;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity\BundleFieldDefinition;

/**
 * Defines the Job target entity.
 *
 * @ingroup contacts_jobs_candidates
 *
 * @ContentEntityType(
 *   id = "job_target",
 *   label = @Translation("Job target"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\contacts_jobs_candidates\CandidateComponentEntityListBuilder",
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
 *   base_table = "job_target",
 *   translatable = FALSE,
 *   admin_permission = "administer job target entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "owner" = "user_id",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/job_target/{job_target}",
 *     "add-form" = "/admin/structure/job_target/add",
 *     "edit-form" = "/admin/structure/job_target/{job_target}/edit",
 *     "delete-form" = "/admin/structure/job_target/{job_target}/delete",
 *     "collection" = "/admin/structure/job_target",
 *   }
 * )
 */
class JobTarget extends CandidateComponentBase implements CandidateComponentEntityInterface, EntityFieldStorageDefinitionInterface {

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

    $fields['function'] = BundleFieldDefinition::create('entity_reference')
      ->setName('function')
      ->setTargetEntityTypeId($entity_type->id())
      ->setLabel(new TranslatableMarkup('Function'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default:taxonomy_term')
      ->setSetting('handler_settings', [
        'target_bundles' => [
          'job_function' => 'job_function',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'entity_reference_label',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
      ]);

    $fields['location'] = BundleFieldDefinition::create('address')
      ->setName('location')
      ->setTargetEntityTypeId($entity_type->id())
      ->setLabel(new TranslatableMarkup('Location'))
      ->setCardinality(1)
      ->setSetting('field_overrides', [
        AddressField::GIVEN_NAME => ['override' => FieldOverride::HIDDEN],
        AddressField::ADDITIONAL_NAME => ['override' => FieldOverride::HIDDEN],
        AddressField::FAMILY_NAME => ['override' => FieldOverride::HIDDEN],
        AddressField::ADDRESS_LINE1 => ['override' => FieldOverride::HIDDEN],
        AddressField::ADDRESS_LINE2 => ['override' => FieldOverride::HIDDEN],
        AddressField::LOCALITY => ['override' => FieldOverride::HIDDEN],
        AddressField::DEPENDENT_LOCALITY => ['override' => FieldOverride::HIDDEN],
        AddressField::POSTAL_CODE => ['override' => FieldOverride::HIDDEN],
        AddressField::SORTING_CODE => ['override' => FieldOverride::HIDDEN],
        AddressField::ORGANIZATION => ['override' => FieldOverride::HIDDEN],
        AddressField::ADMINISTRATIVE_AREA => ['override' => FieldOverride::REQUIRED],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'address_default',
      ]);

    $fields['salary'] = BundleFieldDefinition::create('contacts_jobs_salary')
      ->setName('salary')
      ->setTargetEntityTypeId($entity_type->id())
      ->setLabel(new TranslatableMarkup('Salary'))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'contacts_jobs_salary_default',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'contacts_jobs_salary_default',
      ])
      ->setSetting('is_range', FALSE)
      ->setSetting('has_description', FALSE);

    $fields['category'] = BundleFieldDefinition::create('entity_reference')
      ->setName('category')
      ->setTargetEntityTypeId($entity_type->id())
      ->setLabel(new TranslatableMarkup('Category'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default:taxonomy_term')
      ->setSetting('handler_settings', [
        'target_bundles' => [
          'job_category' => 'job_category',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'entity_reference_label',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
      ]);

    return $fields;
  }

}
