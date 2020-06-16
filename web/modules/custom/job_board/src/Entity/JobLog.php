<?php

namespace Drupal\job_board\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Job Role Log Entity.
 *
 * @ContentEntityType(
 *   id = "job_board_job_log",
 *   label = @Translation("Job Log"),
 *   label_singular = @Translation("job log"),
 *   label_plural = @Translation("job logs"),
 *   label_count = @PluralTranslation(
 *     singular = "@count job log",
 *     plural = "@count job logs"
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "job_log",
 *   admin_permission = "administer job logs",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   }
 * )
 */
class JobLog extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['job'] = BaseFieldDefinition::create('entity_reference')
      ->setSetting('target_type', 'job_role')
      ->setLabel(new TranslatableMarkup('Job'));

    $fields['user'] = BaseFieldDefinition::create('entity_reference')
      ->setSetting('target_type', 'user')
      ->setLabel(new TranslatableMarkup('User'))
      ->setDescription(new TranslatableMarkup('The user that performed the event'));

    $fields['host'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Host/IP'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'))
      ->setDescription(new TranslatableMarkup('When the event happened'));

    $fields['event'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Event'));

    return $fields;
  }
}
