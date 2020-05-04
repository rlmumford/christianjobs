<?php

namespace Drupal\job_board\Controller;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\cj_membership\Entity\Membership;
use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\TransactionNameNonUniqueException;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\job_role\Entity\JobRoleInterface;
use Drupal\organization\Entity\Organization;
use Drupal\organization_user\UserOrganizationResolver;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class JobBoardController extends ControllerBase {

  /**
   * @var \Drupal\organization_user\UserOrganizationResolver
   */
  protected $organizationResolver;

  /**
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('organization_user.organization_resolver'),
      $container->get('module_handler'),
      $container->get('commerce_cart.cart_provider'),
      $container->get('commerce_cart.cart_manager')
    );
  }

  /**
   * JobBoardController constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\organization_user\UserOrganizationResolver $organization_resolver
   */
  public function __construct(
    AccountInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager,
    UserOrganizationResolver $organization_resolver,
    ModuleHandlerInterface $module_handler,
    CartProviderInterface $cart_provider,
    CartManagerInterface $cart_manager
  ) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->organizationResolver = $organization_resolver;
    $this->moduleHandler = $module_handler;
    $this->cartProvider = $cart_provider;
    $this->cartManager = $cart_manager;
  }

  /**
   * Repost job.
   *
   * @param \Drupal\job_role\Entity\JobRoleInterface $job_role
   *
   * @return array
   */
  public function repostJob(JobRoleInterface $job_role) {
    /** @var \Drupal\job_board\JobBoardJobRole $repost_job */
    $repost_job = $this->entityTypeManager()->getStorage('job_role')->create([]);

    foreach ($job_role->getFields() as $field_name => $item_list) {
      if (!in_array($field_name, [
        'id', 'uuid', 'vid', 'publish_date', 'end_date', 'initial_duration',
        'paid', 'paid_to_date', 'path', 'boost_start_date', 'boost_end_date',
      ])) {
        $repost_job->set($field_name, $item_list->getValue());
      }
    }
    $repost_job->publish_date = (new DrupalDateTime())->format('Y-m-d');

    return $this->entityFormBuilder()->getForm($repost_job, 'post');
  }

  /**
   * Repost job title.
   */
  public function repostJobTitle(JobRoleInterface $job_role) {
    return new TranslatableMarkup('Re-post @job', ['@job' => $job_role->label()]);
  }

  /**
   * Page to post a new job.
   */
  public function postJob(Request $request) {
    $cookies = [
      'jobPostRegister' => TRUE,
    ];
    if ($request->query->get('membership') || $request->cookies->get('Drupal_visitor_jobPostMembership')) {
      $cookies['jobPostMembership'] = TRUE;
    }
    if ($request->query->get('rpo') || $request->cookies->get('Drupal_visitor_jobPostRpo')) {
      $cookies['jobPostRpo'] = TRUE;
    }

    if ($this->currentUser->isAnonymous() && !$this->currentUser->hasPermission('post job board jobs')) {
      user_cookie_save($cookies);
      return $this->redirect('user.register');
    }

    if (!($organization = $this->organizationResolver->getOrganization($this->currentUser))) {
      user_cookie_save($cookies);
      return $this->redirect('job_board.post.organization');
    }

    if (!empty($cookies['jobPostMembership']) || $request->query->get('membership')) {
      $cart = $this->cartProvider->getCart('default');
      if (!$cart) {
        $cart = $this->cartProvider->createCart('default');
      }

      // Add a membership options to this form.
      if ($this->moduleHandler()->moduleExists('cj_membership')) {
        /** @var \Drupal\cj_membership\MembershipStorage $membership_storage */
        $membership_storage = $this->entityTypeManager()->getStorage('cj_membership');
        $membership = $membership_storage->getAccountMembership($this->currentUser);

        // Membership on current order.
        $membership_in_cart = FALSE;
        foreach ($cart->getItems() as $order_item) {
          if ($order_item->getPurchasedEntity() instanceof Membership) {
            $membership_in_cart = TRUE;
          }
        }

        if (!$membership && !$membership_in_cart) {
          $membership = $membership_storage->create()
            ->setOwnerId($this->currentUser->id());
          $membership->start->value = date(DateTimeItemInterface::DATE_STORAGE_FORMAT);
          $this->cartManager->addEntity($cart, $membership);
        }
      }
    }

    $initial_values = [];
    if (!empty($cookies['jobPostRpo']) || $request->query->get('rpo')) {
      $initial_values['rpo'] = TRUE;
    }

    /** @var \Drupal\job_board\JobBoardJobRole $job */
    $job = $this->entityTypeManager()->getStorage('job_role')->create($initial_values);
    $job->setOwnerId($this->currentUser->id());
    $job->setOrganization($organization);

    if ($organization->address) {
      $job->contact_address = $organization->address;
    }
    if ($organization->email) {
      $job->contact_email = $organization->email;
    }
    if ($organization->tel) {
      $job->contact_phone = $organization->tel;
    }

    return $this->entityFormBuilder()->getForm($job, 'post');
  }

  /**
   * Boost job title.
   */
  public function boostJobTitle(JobRoleInterface $job_role) {
    return new TranslatableMarkup('Boost @job', ['@job' => $job_role->label()]);
  }

  /**
   * Extend job title.
   */
  public function extendJobTitle(JobRoleInterface $job_role) {
    return new TranslatableMarkup('Extend @job', ['@job' => $job_role->label()]);
  }

  /**
   * Return the employer edit page title.
   */
  public function employerEditTitle(Organization $organization) {
    return $this->t('Edit @employer', [
      '@employer' => $organization->label(),
    ]);
  }

  /**
   * Return a pricing page
   */
  public function pricingInformation() {
    /** @var CurrencyFormatterInterface $currency_formatter */
    $currency_formatter = \Drupal::service('commerce_price.currency_formatter');

    $output = [
      '#type' => 'container',
      '#attached' => [
        'library' => ['job_board/pricing'],
      ],
      '#attributes' => [
        'class' => ['packages'],
      ],
    ];

    $packages = job_board_job_package_info();
    foreach ($packages as $key => $package) {
      /** @var Price $price */
      $price = $package['price'];

      $output[$key] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['package'],
        ]
      ];
      $output[$key]['title'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['package-title-container'],
        ],
        'title' => [
          '#type' => 'html_tag',
          '#tag' => 'h3',
          '#attributes' => [
            'class' => ['package-title'],
          ],
          '#value' => $package['label'],
        ],
        'description' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#attrbitues' => [
            'class' => ['package-description'],
          ],
          '#value' => $package['description'],
        ],
        'price' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => [
              'package-price',
              ($price instanceof Price) ? 'price-currency' : 'price-string',
            ],
          ],
          '#value' => ($price instanceof Price) ? $currency_formatter->format($price->getNumber(), $price->getCurrencyCode()) : $price,
        ],
        'cta' => [
          '#type' => 'link',
          '#title' => $package['cta_text'],
          '#url' => $package['cta_url'],
          '#attributes' => [
            'class' => ['button', 'package-cta'],
          ],
        ],
      ];
      $output[$key]['features'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['package-features-wrapper'],
        ],
      ];
      $output[$key]['features']['features'] = [
        '#theme' => 'item_list',
        '#title' => $this->t('Features'),
        '#list_type' => 'ul',
        '#items' => [],
      ];

      foreach ($package['features'] as $feature) {
        $classes = $feature['classes'] ?: [];

        $attributes = [
          'class' => ['package-feature-item'] + $classes,
        ];

        if (!empty($feature['description'])) {
          $attributes['class'][] = 'tooltip';
          $attributes['data-tooltip'] = $feature['description'];
        }

        $output[$key]['features']['features']['#items'][] = [
          '#markup' => $feature['title'],
          '#wrapper_attributes' => $attributes,
        ];
      }
    }

    return $output;
  }

}
