<?php

namespace Drupal\job_board\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\job_role\Entity\JobRole;

/**
 * Volunteer Role Entity.
 *
 * @ContentEntityType(
 *   id = "volunteer_role",
 *   label = @Translation("Voluntary Position"),
 *   label_singular = @Translation("voluntary position"),
 *   label_plural = @Translation("volunteer"),
 *   label_count = @PluralTranslation(
 *     singular = "@count voluntary position",
 *     plural = "@count voluntary positions"
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\job_board\JobRoleListBuilder",
 *     "storage" = "Drupal\job_role\JobRoleStorage",
 *     "access" = "Drupal\job_board\JobBoardJobRoleAccessControlHandler",
 *     "permission_provider" = "Drupal\job_role\JobRolePermissionProvider",
 *     "form" = {
 *       "default" = "Drupal\job_role\JobRoleForm"
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "volunteer_role",
 *   revision_table = "volunteer_role_revision",
 *   data_table = "volunteer_role_data",
 *   field_ui_base_route = "entity.volunteer_role.admin_form",
 *   revision_data_table = "volunteer_role_revision_data",
 *   admin_permission = "administer volunteer roles",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "uuid" = "uuid",
 *     "label" = "label"
 *   }
 * )
 */
class VolunteerRole extends JobRole {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    unset($fields['salary']);

    $fields['keywords'] = BaseFieldDefinition::create('entity_reference')
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default:taxonomy_term')
      ->setSetting('handler_settings', [
        'target_bundles' => [
          'keywords' => 'keywords',
        ],
        'auto_create' => TRUE,
        'auto_create_bundle' => 'keywords',
      ])
      ->setLabel(t('Keywords'))
      ->setDescription(t('Select up to 10 keywords to describe this job.'))
      ->setCardinality(10)
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'weight' => -6,
        'type' => 'entity_reference_autocomplete_tags',
      ])
      ->setDisplayConfigurable('form', TRUE);
    $fields['category'] = BaseFieldDefinition::create('entity_reference')
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default:taxonomy_term')
      ->setSetting('handler_settings', [
        'target_bundles' => [
          'industries' => 'industries',
        ],
        'auto_create' => TRUE,
        'auto_create_bundle' => 'industries',
      ])
      ->setLabel(t('Category'))
      ->setDescription(t('Select an category.'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'weight' => -7,
        'type' => 'entity_reference_autocomplete',
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['contact_phone'] = BaseFieldDefinition::create('telephone')
      ->setLabel(t('Contact Telephone'))
      ->setRevisionable(TRUE)
      ->setDefaultValueCallback('job_board_job_role_contact_default_value')
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'telephone_default',
      ])
      ->setDisplayConfigurable('form', TRUE);
    $fields['contact_address'] = BaseFieldDefinition::create('address')
      ->setLabel(t('Contact Address'))
      ->setRevisionable(TRUE)
      ->setDefaultValueCallback('job_board_job_role_contact_default_value')
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'address_default',
      ])
      ->setDisplayConfigurable('form', TRUE);
    $fields['contact_email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Contact E-mail Address'))
      ->setRevisionable(TRUE)
      ->setDefaultValueCallback('job_board_job_role_contact_default_value')
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'email_default',
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

}
