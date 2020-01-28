<?php

namespace Drupal\job_application\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Class ApplicationQuestionAnswer
 *
 * @ContentEntityType(
 *   id = "application_question_answer",
 *   label = @Translation("Answer"),
 *   label_singular = @Translation("answer"),
 *   label_plural = @Translation("answers"),
 *   label_count = @PluralTranslation(
 *     singular = "@count answer",
 *     plural = "@count answer"
 *   ),
 *   bundle_label = @Translation("Answer Type"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "access" = "Drupal\job_application\Entity\ApplicationQuestionAccessControlHandler",
 *   },
 *   base_table = "application_question_answer",
 *   revision_table = "application_question_answer_revision",
 *   admin_permission = "administer job applications",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *     "owner" = "applicant",
 *   },
 *   bundle_plugin_type = "application_question_answer_class",
 * )
 *
 * @package Drupal\job_application\Entity
 */
class ApplicationQuestionAnswer extends ContentEntityBase implements EntityOwnerInterface {
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['application'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Application'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'job_application')
      ->setDisplayConfigurable('view', TRUE);

    $fields['question'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Question'))
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Get the application this answer is about.
   *
   * @return \Drupal\job_application\Entity\Application
   */
  public function getApplication() {
    return $this->application->entity;
  }

}
