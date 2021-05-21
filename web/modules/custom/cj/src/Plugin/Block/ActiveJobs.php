<?php

namespace Drupal\cj\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block to show number of currently live jobs.
 *
 * @Block(
 *   id = "cj_active_jobs",
 *   admin_label = @Translation("Active Jobs"),
 * )
 */
class ActiveJobs extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The job query service.
   *
   * @var \Drupal\cj\JobQueries
   */
  protected $jobQueries;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->jobQueries = $container->get('cj.job_queries');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $number = $this->getCount();
    if ($number >= 100) {
      $text = new TranslatableMarkup('Search %number open jobs for your next opportunity', [
        '%number' => number_format($number),
      ]);
    }
    else {
      $text = new TranslatableMarkup('Search all open jobs for your next opportunity');
    }
    return [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $text,
      '#attributes' => [
        'class' => [
          'font-weight-bold',
          'w-100',
          'text-white',
        ],
      ],
      '#cache' => [
        'tags' => ['contacts_job_list'],
      ],
    ];
  }

  /**
   * Gets the job count.
   *
   * @return int
   *   The count of the jobs available.
   */
  protected function getCount() {
    return $this->jobQueries->getActiveCount();
  }

}
