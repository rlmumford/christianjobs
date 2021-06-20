<?php

namespace Drupal\contacts_jobs_candidates\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;

/**
 * Defines the Employment history entity.
 *
 * @ingroup contacts_jobs_apps
 *
 * @ContentEntityType(
 *   id = "employment_history",
 *   label = @Translation("Employment history"),
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
 *   base_table = "employment_history",
 *   translatable = FALSE,
 *   admin_permission = "administer employment history entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "owner" = "user_id",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/employment_history/{employment_history}",
 *     "add-form" = "/admin/structure/employment_history/add",
 *     "edit-form" = "/admin/structure/employment_history/{employment_history}/edit",
 *     "delete-form" = "/admin/structure/employment_history/{employment_history}/delete",
 *     "collection" = "/admin/structure/employment_history",
 *   },
 *   field_ui_base_route = "entity.employment_history.collection"
 * )
 */
class EmploymentHistory extends CandidateComponentBase implements CandidateComponentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title']->setLabel(new TranslatableMarkup('Job title'));

    $fields['employer'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Employer'))
      ->setSettings([
        'text_processing' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ]);

    $fields['start_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Start date'))
      ->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'datetime_default',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
      ]);

    $fields['end_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('End date'))
      ->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'datetime_default',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
      ]);

    $fields['salary'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Salary'))
      ->setSettings([
        'text_processing' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ]);

    $fields['notice'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Notice period'))
      ->setSettings([
        'text_processing' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ]);

    $fields['level'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Level'))
      ->setDescription(new TranslatableMarkup('Please select the rough level of this employment. "Graduate" is intended for graduate schemes or graduate entry level roles. If unsure, select "Associate".'))
      ->setSetting('allowed_values', [
        'apprentice' => new TranslatableMarkup('Apprentice'),
        'internship' => new TranslatableMarkup('Internship'),
        'graduate' => new TranslatableMarkup('Graduate'),
        'entry_level' => new TranslatableMarkup('Entry Level'),
        'associate' => new TranslatableMarkup('Associate'),
        'management' => new TranslatableMarkup('Management'),
        'senior_management' => new TranslatableMarkup('Senior Management'),
        'senior_leadership' => new TranslatableMarkup('Senior Leadership'),
        'board' => new TranslatableMarkup('Board'),
      ])
      ->setDefaultValue('associate')
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 1,
      ]);

    $fields['function'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Function'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default:taxonomy_term')
      ->setSetting('handler_settings', [
        'target_bundles' => [
          'locations' => 'job_function',
        ],
        'auto_create' => FALSE,
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
        'type' => 'options_select',
      ]);

    $fields['employment_status'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Employment status'))
      ->setSettings([
        'text_processing' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ]);

    return $fields;
  }

}
