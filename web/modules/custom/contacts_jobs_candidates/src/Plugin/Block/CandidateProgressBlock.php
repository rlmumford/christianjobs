<?php

namespace Drupal\contacts_jobs_candidates\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a progress indicator for Candidate Registration.
 *
 * @Block(
 *   id = "candidate_progress",
 *   admin_label = @Translation("Candidate progress"),
 *   category = @Translation("Contacts Jobs"),
 * )
 */
class CandidateProgressBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current route.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRoute;

  /**
   * The request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $self = new static($configuration, $plugin_id, $plugin_definition);

    $self->currentRoute = $container->get('current_route_match');
    $self->request = $container->get('request_stack')->getCurrentRequest();

    return $self;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $build['items'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#attributes' => [
        'class' => ['registration-progress-block'],
      ],
      '#items' => [
        'personal' => [
          '#theme' => 'fmcg_progress_item',
          '#label' => $this->t('Personal'),
        ],
        'cv' => [
          '#theme' => 'fmcg_progress_item',
          '#label' => $this->t('CV/Resume'),
        ],
        'targets' => [
          '#theme' => 'fmcg_progress_item',
          '#label' => $this->t('Job targets'),
        ],
        'gdpr' => [
          '#theme' => 'fmcg_progress_item',
          '#label' => $this->t('GDPR'),
        ],
      ],
    ];

    switch ($this->currentRoute->getRouteName()) {
      case 'contacts_jobs_candidates.personal_profile':
        $build['items']['#items']['personal']['#active'] = TRUE;
        break;

      case 'contacts_jobs_candidates.cv_resume':
        $build['items']['#items']['cv']['#active'] = TRUE;
        break;

      case 'contacts_jobs_candidates.job_target':
        $build['items']['#items']['targets']['#active'] = TRUE;
        break;

      case 'contacts_jobs_candidates.gdpr':
        $build['items']['#items']['gdpr']['#active'] = TRUE;
        break;
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIf(!$this->request->query->has('destination'))
      ->addCacheContexts(['url']);
  }

}
