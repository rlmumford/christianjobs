<?php

namespace Drupal\job_board\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class JobStructuredData
 *
 * @Block(
 *   id = "job_role_application_info",
 *   admin_label = @Translation("Job Application Info"),
 *   context = {
 *     "job" = @ContextDefinition("entity:job_role", label = @Translation("Job"))
 *   }
 * )
 */
class JobApplicationInfo extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\job_board\JobBoardJobRole $job */
    $job = $this->getContext('job');

    $build = [];
    $build['show_application_info'] = [
      '#type' => 'link',
      '#title' => $this->t('Show Info'),
      '#url' => Url::fromRoute('job_board.log.view', ['job_role' => $job->id()]),
      '#attributes' => [
        'class' => [
          'btn', 'button', 'show-application-info-toggle',
        ],
      ],
      '#attached' => [
        'library' => [
          'job_board/show-application-info',
        ]
      ]
    ];

    $build['application_info'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'application-info',
        ],
        'style' => 'display: none;'
      ],
    ];
    foreach (['contact_phone', 'contact_email', 'contact_address'] as $field_name) {
      if (!$job->{$field_name}->isEmpty()) {
        $build[$field_name] = $job->{$field_name}->view([
          'label' => 'hidden',
        ]);
      }
    }

    // @todo: Apply button.

    return $build;
  }

}
