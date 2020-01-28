<?php

namespace Drupal\job_application\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Class Application
 *
 * @ContentEntityType(
 *   id = "job_application",
 *   label = @Translation("Application"),
 *   label_singular = @Translation("application"),
 *   label_plural = @Translation("applications"),
 *   label_count = @PluralTranslation(
 *     singular = "@count application",
 *     plural = "@count applications"
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "access" = "Drupal\job_application\Entity\ApplicationAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\job_application\Form\ApplicationForm"
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "job_application",
 *   revision_table = "job_application_revision",
 *   admin_permission = "administer job applications",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "uuid" = "uuid",
 *     "owner" = "applicant",
 *   },
 *   links = {
 *     "canonical" = "/job/{job_role}/application/{job_application}",
 *     "edit-form" = "/job/{job_role}/application/{job_application}/edit",
 *     "add-form" = "/job/{job_role}/apply"
 *   }
 * )
 *
 * @package Drupal\job_application\Entity
 */
class Application extends ContentEntityBase implements EntityOwnerInterface {
  use EntityOwnerTrait;

  const STATUS_DRAFT = 'draft';
  const STATUS_SUBMITTED = 'submitted';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['job'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Job'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'job_role')
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Status'))
      ->setSetting('allowed_values', [
        static::STATUS_DRAFT => new TranslatableMarkup('Draft'),
        static::STATUS_SUBMITTED => new TranslatableMarkup('Submitted'),
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['submission_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Submission Date'))
      ->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
