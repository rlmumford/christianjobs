<?php

namespace Drupal\cj_material;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\LocalTaskManager;
use Drupal\Core\Menu\LocalTaskManagerInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Helper class for managing the dashboard.
 *
 * @package Drupal\jobboard_base
 */
class DashboardHelper implements ContainerInjectionInterface {

  /**
   * The instantiated instance.
   *
   * @var static
   */
  protected static $instance;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The local task manager.
   *
   * @var \Drupal\Core\Menu\LocalTaskManagerInterface
   */
  protected $localTaskManager;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * What type of dashboard we are on, or NULL if not at all.
   *
   * @var string|null
   */
  protected $dashboard;

  /**
   * The user who's dashboard we're on.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The content wrapper classes.
   *
   * @var string[]|null
   */
  protected $contentWrapper;

  /**
   * the request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Dashboard helper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   * @param \Drupal\Core\Menu\LocalTaskManagerInterface $local_task_manager
   *   The local task manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, RouteMatchInterface $routeMatch, LocalTaskManagerInterface $local_task_manager, AccountProxyInterface $current_user, RequestStack $request_stack) {
    $this->routeMatch = $routeMatch;
    $this->entityTypeManager = $entityTypeManager;
    $this->localTaskManager = $local_task_manager;
    $this->currentUser = $current_user;
    $this->requestStack = $request_stack;
    $this->init();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('plugin.manager.menu.local_task'),
      $container->get('current_user'),
      $container->get('request_stack')
    );
  }

  /**
   * Get the instance of dashboard helper.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface|null $container
   *   The container.
   *
   * @return static
   */
  public static function getInstance(ContainerInterface $container = NULL) {
    return self::$instance ??
      self::$instance = self::create($container ?? \Drupal::getContainer());
  }

  /**
   * Initialise the helper.
   */
  protected function init(): void {
    // Check if our path is in the user dashboard.
    $route_object = $this->routeMatch->getRouteObject();
    if ($route_object && preg_match('/\/job\/{contacts_job}\/(applications|extend|withdraw|edit)/i', $route_object->getPath())) {
      $this->dashboard = 'org';
      $this->user = $this->routeMatch->getParameter('contacts_job')->organisation->entity;
    }
    else if ($route_object && substr($route_object->getPath(), 0, 9) === '/job/post' && $this->requestStack->getCurrentRequest()->query->has('organisation')) {
      $this->dashboard = 'org';
      $this->user = User::load($this->requestStack->getCurrentRequest()->query->get('organisation'));
    }
    else if (!$route_object || substr($route_object->getPath(), 0, 13) !== '/user/{user}/') {
      return;
    }

    // Get hold of our user.
    $user = $this->routeMatch->getParameter('user');
    if (!$user) {
      return;
    }

    // Load a full user if necessary.
    if (!is_object($user)) {
      $user = User::load($user);
    }
    /** @var \Drupal\user\UserInterface|null $user */
    if (!$user) {
      return;
    }

    // Check we have the organisation role.
    if ($user->hasRole('crm_org')) {
      $this->dashboard = 'org';
      $this->user = $user;
    }
    elseif ($user->hasRole('candidate')) {
      $this->dashboard = 'candidate';
      $this->user = $user;
    }
  }

  /**
   * Whether we are on the dashboard.
   *
   * @return bool
   *   Whether we are on the dashboard.
   */
  public function isDashboard(): bool {
    return $this->dashboard !== NULL;
  }

  /**
   * Get the dashboard type.
   *
   * @return string|null
   *   The dashboard type, or NULL if not any.
   */
  public function getDashboard(): ?string {
    return $this->dashboard;
  }

  /**
   * Get the user who's dashboard we are on.
   *
   * @return \Drupal\user\UserInterface|null
   *   The user who's dashboard we're viewing.
   *
   * @throws \BadMethodCallException
   *   Thrown if called when not on a dashboard.
   */
  public function getUser(): ?UserInterface {
    if (!$this->isDashboard()) {
      throw new \BadMethodCallException('Not on a dashboard. Check DashboardHelper::isDashboard before calling other methods.');
    }
    return $this->user;
  }

  /**
   * Implement dashboard specific theme suggestion alters.
   *
   * @param array $suggestions
   *   The theme suggestionss.
   * @param array $variables
   *   The variables.
   * @param string $hook
   *   The base hook.
   */
  public function alterThemeSuggestions(array &$suggestions, array $variables, string $hook): void {
    if (!$this->isDashboard()) {
      return;
    }

    if (in_array($hook, ['page'])) {
      $suggestions[] = $hook . '__dashboard';
      $suggestions[] = $hook . '__dashboard__' . $this->dashboard;
    }
  }

  /**
   * Preprocess html.
   *
   * @param array $variables
   *   The variables array.
   */
  public function preprocessHtml(array &$variables): void {
    if ($this->isDashboard()) {
      $variables['html_attributes']->addClass('dashboard');
    }
  }

  /**
   * Preprocess page dashboard.
   *
   * @param array $variables
   *   The variables array.
   */
  public function preprocessPageDashboard(array &$variables): void {
    $variables['container'] = 'container-fluid';

    if (!$variables['content_attributes'] instanceof Attribute) {
      $variables['content_attributes'] = new Attribute($variables['content_attributes']);
    }

    $variables['content_attributes']->removeClass('col');
    $variables['content_attributes']->addClass($this->getContentClasses());

    $cacheability = new CacheableMetadata();
    $cacheability->addCacheableDependency($this->localTaskManager);

    $links = $this->localTaskManager->getLocalTasks($this->routeMatch->getRouteName(), 0);

    // If the dashboard summary is not one of the links, we are outside of the
    // normal dashboard routing context, so we need to force getting the correct
    // tabs. We don't do this normally as we'd lose the active trail.
    if (!isset($links['tabs']['contacts_user_dashboard_tab:user_summary'])) {
      // This is error prone so wrap in a try catch.
      try {
        $route_match = new RouteMatch(
          'contacts_user_dashboard.summary',
          \Drupal::service('router.route_provider')->getRouteByName('contacts_user_dashboard.summary'),
          ['user' => $this->user],
          ['user' => $this->user->id()]
        );
        $local_task_manager = new LocalTaskManager(
          \Drupal::service('http_kernel.controller.argument_resolver'),
          \Drupal::requestStack(),
          $route_match,
          \Drupal::service('router.route_provider'),
          \Drupal::service('module_handler'),
          \Drupal::service('cache.discovery'),
          \Drupal::service('language_manager'),
          \Drupal::service('access_manager'),
          \Drupal::service('current_user')
        );
        $links = $local_task_manager->getLocalTasks('contacts_user_dashboard.summary', 0);
      } catch (\Exception $exception) {  dpm($exception->getTrace(), $exception->getMessage()); }
    }

    $cacheability->merge($links['cacheability']);
    $variables['dashboard_nav'] = [
      '#theme' => 'menu__dashboard',
      '#items' => [],
      '#md_icons' => [
        'contacts_user_dashboard_tab:user_summary' => 'dashboard',
        'logout' => 'exit_to_app',
      ],
    ];
    foreach ($links['tabs'] as $id => $tab) {
      if ($tab['#access'] instanceof RefinableCacheableDependencyInterface) {
        $cacheability->addCacheableDependency($tab['#access']);
      }

      if (!$tab['#access']->isAllowed()) {
        continue;
      }

      $variables['dashboard_nav']['#items'][$id] = $tab['#link'] + [
        'in_active_trail' => $tab['#active'],
        'weight' => $tab['#weight'],
        'attributes' => new Attribute(),
      ];
    }

    // Add a logout link.
    $variables['dashboard_nav']['#items']['logout'] = [
      'title' => new TranslatableMarkup('Logout'),
      'url' => Url::fromRoute('user.logout'),
      'in_active_trail' => FALSE,
      'weight' => 99,
      'attributes' => new Attribute(),
    ];

    uasort(
      $variables['dashboard_nav']['#items'],
      [SortArray::class, 'sortByWeightElement'],
    );

    $cacheability->applyTo($variables['dashboard_nav']);

    switch ($this->dashboard) {
      case 'org':
        $this->preprocessPageDashboardOrg($variables);
        break;

      case 'candidate':
        $this->preprocessPageDashboardCandidate($variables);
        break;
    }
  }

  /**
   * Preprocess page dashboard.
   *
   * @param array $variables
   *   The variables array.
   */
  public function preprocessLocalTasksBlock(array &$variables): void {
    if (!$this->isDashboard()) {
      return;
    }

    // If the summary tab is in the local tasks, we want to remove the primary
    // items and make the secondary (if any) primaries.
    if (isset($variables['content']['#primary']['contacts_user_dashboard_tab:user_summary'])) {
      if (!empty($variables['content']['#secondary'])) {
        $variables['content']['#primary'] = $variables['content']['#secondary'];
        $variables['content']['#secondary'] = [];
      }
      else {
        $variables['content'] = [];
      }
    }
  }

  /**
   * Get the content wrapper to use.
   *
   * @return string[]
   *   The classes for the content wrapper.
   */
  protected function getContentClasses(): array {
    if (isset($this->contentWrapper)) {
      return $this->contentWrapper;
    }

    $default_classes = ['card', 'card-body', 'border-left', 'border-warning'];

    // Check the route options.
    $route = $this->routeMatch->getRouteObject();
    if ($route->hasOption('_jobboard_content_wrapper')) {
      $content_wrapper = $route->getOption('_jobboard_content_wrapper');
      if (is_bool($content_wrapper)) {
        return $this->contentWrapper = $default_classes;
      }
      else {
        return $this->contentWrapper = $content_wrapper;

      }
    }

    // Check a predefined list of excluded route names.
    $excluded_route_names = [
      'contacts_user_dashboard.summary',
    ];
    if (!in_array($this->routeMatch->getRouteName(), $excluded_route_names)) {
      return $this->contentWrapper = $default_classes;
    }

    return $this->contentWrapper = [];
  }

  /**
   * Preprocess the page for the organisation dashboard.
   *
   * @param array $variables
   *   The page variables.
   */
  protected function preprocessPageDashboardOrg(array &$variables): void {
    /** @var \Drupal\profile\Entity\ProfileInterface|null $profile */
    $profile = $this->user->get('profile_crm_org')->entity;
    if ($profile) {
      /** @var \Drupal\Core\Field\FieldItemListInterface $logo */
      $logo = $profile->get('org_image');
      if (!$logo->isEmpty()) {
        $variables['dashboard_logo'] = $logo->view([
          'label' => 'hidden',
          'settings' => ['image_style' => 'large'],
        ])[0] ?? NULL;
      }
    }

    /** @var \Drupal\user\Entity\User $current_user */
    $current_user = $this->entityTypeManager->getStorage('user')
      ->load($this->currentUser->id());

    // If user has multiple organisations, show button to switch.
    if ($current_user->get('organisations')->count() > 1) {
      $variables['#attached']['library'][] = 'core/drupal.dialog.ajax';
      $icon = [
        '#type' => 'html_tag',
        '#tag' => 'i',
        '#value' => 'swap_horiz',
        '#attributes' => [
          'class' => [
            'material-icons',
            'text-white',
          ],
          'title' => new TranslatableMarkup('Switch organisation'),
        ],
      ];

      $variables['switch_org'] = [
        '#type' => 'link',
        '#title' => $icon,
        '#options' => [
          'attributes' => [
            'class' => [
              'use-ajax',
              'hexagon',
              'hexagon-success',
              'hexagon-small',
            ],
            'data-progress-type' => 'fullscreen',
          ],
        ],
        '#url' => Url::fromRoute('contacts_jobs_dashboard.org_switch_modal', [
          'user' => $current_user->id(),
        ]),
      ];
    }

    $variables['dashboard_nav']['#md_icons'] = [
      'entity.user.canonical' => 'remove_red_eye',
      'contacts_jobs_dashboard.user.team' => 'people',
      'profile.user_page:crm_org' => 'create',
      'contacts_jobs_dashboard.user.jobs' => 'work',
      'views_view:view.fmcg_billing_history.page_1' => 'history_edu',
      'contacts_jobs_subscriptions.manage' => 'highlight_off',
    ] + $variables['dashboard_nav']['#md_icons'];
  }

  /**
   * Preprocess the page for the candidate dashboard.
   *
   * @param array $variables
   *   The page variables.
   */
  protected function preprocessPageDashboardCandidate(array &$variables): void {
    /** @var \Drupal\profile\Entity\ProfileInterface|null $profile */
    $profile = $this->user->get('profile_crm_indiv')->entity;
    if ($profile) {
      /** @var \Drupal\Core\Field\FieldItemListInterface $photo */
      $photo = $profile->get('crm_photo');
      if (!$photo->isEmpty()) {
        $variables['dashboard_logo'] = $photo->view([
          'label' => 'hidden',
          'settings' => ['image_style' => 'large'],
        ])[0] ?? NULL;
      }
    }

    $variables['dashboard_nav']['#md_icons'] = [
      'profile.user_page:crm_indiv' => 'perm_identity',
      'entity.user.edit_form' => 'lock',
      'contacts_jobs_apps.user.job_apps' => 'work',
      'fmcg_candidate.cv_resume' => 'contact_page',
      'fmcg_candidate.user.profile' => 'perm_identity',
      'fmcg_candidate.user.targets' => 'gps_fixed',
    ] + $variables['dashboard_nav']['#md_icons'];
  }

}
