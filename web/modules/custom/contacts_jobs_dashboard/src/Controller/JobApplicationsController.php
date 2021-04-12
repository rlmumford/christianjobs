<?php

namespace Drupal\contacts_jobs_dashboard\Controller;

use Drupal\contacts_jobs\Entity\Job;
use Drupal\contacts_jobs\Entity\JobInterface;
use Drupal\Core\Block\BlockManager;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Job application list controller.
 *
 * @package Drupal\contacts_jobs_dashboard\Controller
 */
class JobApplicationsController extends ControllerBase {

  /**
   * Block manager.
   *
   * @var \Drupal\Core\Block\BlockManager
   */
  protected $blockManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(BlockManager $block_manager) {
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block')
    );
  }

  /**
   * Displays applications for a job.
   *
   * @param \Drupal\user\UserInterface $user
   *   The organisation user.
   * @param \Drupal\contacts_jobs\Entity\JobInterface $contacts_job
   *   The job.
   *
   * @return array
   *   Output.
   */
  public function applications(UserInterface $user, JobInterface $contacts_job) {
    $content = [
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->applicationsTitle($contacts_job),
      ],
    ];

    $access = $contacts_job->access('view applications', NULL, TRUE);
    if (!$access->isAllowed()) {
      $content['access'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Your access to applications for this job has expired. For permanent access to applications for old job postings, please upgrade to one of our @subscriptions.', [
          '@subscriptions' => Link::createFromRoute(
            $this->t('subscriptions'),
            'contacts_jobs_subscriptions.manage',
            ['user' => $user->id()],
          )->toString(),
        ]),
      ];
      return $content;
    }

    /** @var \Drupal\Core\Block\BlockPluginInterface $block */
    $block = $this->blockManager->createInstance('views_block:contacts_job_applications-applications');

    $content['applications'] = [
      '#theme' => 'block',
      '#attributes' => [],
      '#configuration' => $block->getConfiguration(),
      '#plugin_id' => $block->getPluginId(),
      '#base_plugin_id' => $block->getBaseId(),
      '#derivative_plugin_id' => $block->getDerivativeId(),
      '#weight' => $block->getConfiguration()['weight'] ?? 0,
      'content' => $block->build(),
    ];

    return $content;
  }

  /**
   * Title callback for the manage jobs page.
   *
   * @param \Drupal\contacts_jobs\Entity\Job $contacts_job
   *   The job.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page title.
   */
  public function applicationsTitle(Job $contacts_job) {
    return $this->t('Applications for @label', ['@label' => $contacts_job->getTitle()]);
  }

  /**
   * Renders the application summary.
   *
   * This is not exposed as a route, but rendered as part of the
   * organisation dashboard, called by
   * contacts_jobs_dashboard_contacts_user_dashboard_user_summary_blocks_alter.
   *
   * @param \Drupal\user\UserInterface $organisation
   *   The current organisation being viewed.
   *
   * @return array
   *   Render array.
   */
  public function applicationSummary(UserInterface $organisation) {
    // Because this method is rendered as a block in the dashboard
    // rather than as a controller endpoint, perform an explicit access check
    // rather than relying on it coming from the route.
    if (!$organisation->access('manage_jobs', $this->currentUser())) {
      return [];
    }

    $content = [];
    /** @var \Drupal\Core\Block\BlockPluginInterface $block */
    $block = $this->blockManager->createInstance('views_block:contacts_job_applications-summary');
    $content['org_applications'] = [
      '#type' => 'user_dashboard_summary',
      '#title' => $this->t('Recent Applications'),
      '#content' => [
        '#theme' => 'block',
        '#attributes' => [],
        '#configuration' => $block->getConfiguration(),
        '#plugin_id' => $block->getPluginId(),
        '#base_plugin_id' => $block->getBaseId(),
        '#derivative_plugin_id' => $block->getDerivativeId(),
        '#weight' => $block->getConfiguration()['weight'] ?? 0,
        'content' => $block->build(),
      ],
    ];
    unset($content['org_applications']['#content']['content']['#title']);
    return $content;
  }

}
