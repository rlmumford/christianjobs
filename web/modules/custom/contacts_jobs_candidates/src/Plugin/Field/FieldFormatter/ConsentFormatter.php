<?php

namespace Drupal\contacts_jobs_candidates\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\gdpr_consent\Plugin\Field\FieldFormatter\ConsentFormatter as GdprConsentFormatter;

/**
 * Plugin implementation of the fmcg_consent_formatter formatter.
 *
 * @FieldFormatter(
 *   id = "cj_candidates_consent_formatter",
 *   label = @Translation("CJ Consent Formatter"),
 *   field_types = {
 *    "gdpr_user_consent"
 *   }
 * )
 */
class ConsentFormatter extends GdprConsentFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $output = [];

    foreach ($items as $delta => $item) {
      $output[$delta] = [
        'name' => [
          '#markup' => $this->t(
            '@title: @agreed',
            [
              '@title' => $items->getFieldDefinition()->getLabel(),
              '@agreed' => $item->agreed ? $this->t('Agreed') : $this->t('Not agreed'),
            ]
          ),
        ],
      ];

    }

    return $output;
  }

}
