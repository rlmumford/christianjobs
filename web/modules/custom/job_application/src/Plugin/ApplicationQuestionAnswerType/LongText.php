<?php

namespace Drupal\job_application\Plugin\ApplicationQuestionAnswerType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity\BundleFieldDefinition;

class LongText extends ApplicationQuestionAnswerTypeBase {

  /**
   * Builds the field definitions for entities of this bundle.
   *
   * Important:
   * Field names must be unique across all bundles.
   * It is recommended to prefix them with the bundle name (plugin ID).
   *
   * @return \Drupal\entity\BundleFieldDefinition[]
   *   An array of bundle field definitions, keyed by field name.
   */
  public function buildFieldDefinitions() {
    return [
      'answer_text' => BundleFieldDefinition::create('text_long')
        ->setLabel(new TranslatableMarkup('Answer'))
        ->setDisplayConfigurable('view', TRUE)
        ->setDisplayOptions('view', [
          'type' => 'text_default',
        ])
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayOptions('form', [
          'type' => 'text_default',
        ])
        ->setRequired(TRUE)
    ];
  }
}
