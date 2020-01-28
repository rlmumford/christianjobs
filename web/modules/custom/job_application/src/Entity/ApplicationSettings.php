<?php

namespace Drupal\job_application\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

class ApplicationSettings extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // @todo: Destination (plugin with options, Email, JobAdder ATS, None

    // @todo: Questions Field.

    return $fields;
  }

}
