<?php

namespace Drupal\job_board\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RecruiterMenu
 *
 * @Block(
 *   id = "job_board_recruiter_menu",
 *   admin_label = @Translation("Recruiter Menu"),
 * )
 *
 *
 * @package Drupal\job_board\Plugin\Block
 */
class RecruiterMenu extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * RecruiterMenu constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Session\AccountInterface $current_user
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    AccountInterface $current_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;

  }

  /**
   * Builds and returns the renderable array for this block plugin.
   *
   * If a block should not be rendered because it has no content, then this
   * method must also ensure to return no content: it must then only return an
   * empty array, or an empty array with #cache set (with cacheability metadata
   * indicating the circumstances for it being empty).
   *
   * @return array
   *   A renderable array representing the content of the block.
   *
   * @see \Drupal\block\BlockViewBuilder
   */
  public function build() {
    $cache = new BubbleableMetadata();
    $cache->addCacheableDependency($this->currentUser);
    $build = [];
    if (!$this->currentUser->isAuthenticated()) {
      $cache->applyTo($build);
      return $build;
    }

    $user = $this->entityTypeManager->getStorage('user')->load(
      $this->currentUser->id()
    );
    $cache->addCacheableDependency($user);
    if ($user->organization->isEmpty()) {
      $cache->applyTo($build);
      return $build;
    }

    $route_match = \Drupal::routeMatch();
    $cache->addCacheableDependency($route_match);
    $build = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => [],
      '#attributes' => [
        'class' => [
          'recruiter-menu',
        ],
      ],
    ];

    /** @var \Drupal\organization\Plugin\Field\FieldType\OrganizationMetadataReferenceItem $organization_item */
    foreach ($user->organization as $organization_item) {
      $route =  'view.job_board__recruiter_jobs.page';
      $params = [
        'arg_0' => $organization_item->target_id,
      ];
      $current_org = ($route_match->getRawParameter('arg_0') == $organization_item->target_id);

      $supported_routes = [
        'view.job_board__recruiter_jobs.page',
        'job_board.employer',
        'job_board.employer_edit',
        'job_board.recruiter.team',
      ];
      if (in_array($route_match->getRouteName(), $supported_routes)) {
        $route = $route_match->getRouteName();

        if ($route !== 'view.job_board__recruiter_jobs.page') {
          $params = [
            'organization' => $organization_item->target_id,
          ];

          $current_org = ($route_match->getRawParameter('organization') == $organization_item->target_id);
        }
      }

      $item = [
        '#type' => 'link',
        '#url' => Url::fromRoute($route, $params),
        '#title' => $organization_item->entity->label(),
        '#attributes' => [
          'class' => [],
        ],
        '#wrapper_attributes' => [
          'class' => [],
        ],
      ];

      if ($current_org) {
        $item['#wrapper_attributes']['class'][] = 'is-active';
        $item['#attributes']['class'][] = 'is-active';
      }

      $build['#items'][] = $item;
    }

    $item = [
      '#type' => 'link',
      '#url' => Url::fromRoute('job_board.recruiter.register.organization'),
      '#title' => $this->t('New Organization'),
      '#attributes' => [
        'class' => [
          'new-organization',
        ],
      ],
    ];
    $build['#items'][] = $item;
    $cache->applyTo($build);

    return $build;
  }
}
