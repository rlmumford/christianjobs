<?php

namespace Drupal\job_board\Controller;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\cj_membership\Entity\Membership;
use Drupal\commerce_price\Price;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\TransactionNameNonUniqueException;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\job_role\Entity\JobRoleInterface;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class JobBoardController extends ControllerBase {

  /**
   * Page to post a new job.
   */
  public function postJob() {
    $request = \Drupal::request();
    $cookies = [
      'jobPostRegister' => TRUE,
    ];
    if ($request->query->get('membership') || $request->cookies->get('Drupal_visitor_jobPostMembership')) {
      $cookies['jobPostMembership'] = TRUE;
    }
    if ($request->query->get('rpo') || $request->cookies->get('Drupal_visitor_jobPostRpo')) {
      $cookies['jobPostRpo'] = TRUE;
    }

    $current_user = \Drupal::currentUser();
    if ($current_user->isAnonymous() && !$current_user->hasPermission('post job board jobs')) {
      user_cookie_save($cookies);
      return $this->redirect('user.register');
    }

    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = $this->entityTypeManager()->getStorage('profile');
    if (!($profile = $profile_storage->loadDefaultByUser($current_user, 'employer')) || !$profile->employer_name->value) {
      user_cookie_save($cookies);
      return $this->redirect('job_board.employer_edit', ['user' => $current_user->id()]);
    }

    if (!empty($cookies['jobPostMembership']) || $request->query->get('membership')) {
      /** @var \Drupal\commerce_cart\CartProvider $cart_provider */
      $cart_provider = \Drupal::service('commerce_cart.cart_provider');
      $cart = $cart_provider->getCart('default');
      if (!$cart) {
        $cart = $cart_provider->createCart('default');
      }

      // Add a membership options to this form.
      if (\Drupal::moduleHandler()->moduleExists('cj_membership')) {
        /** @var \Drupal\cj_membership\MembershipStorage $membership_storage */
        $membership_storage = \Drupal::entityTypeManager()->getStorage('cj_membership');
        $membership = $membership_storage->getAccountMembership($current_user);

        // Membership on current order.
        $membership_in_cart = FALSE;
        foreach ($cart->getItems() as $order_item) {
          if ($order_item->getPurchasedEntity() instanceof Membership) {
            $membership_in_cart = TRUE;
          }
        }

        if (!$membership && !$membership_in_cart) {
          $membership = $membership_storage->create()
            ->setOwnerId($current_user->id());
          $membership->start->value = date(DateTimeItemInterface::DATE_STORAGE_FORMAT);
          \Drupal::service('commerce_cart.cart_manager')->addEntity($cart, $membership);
        }
      }
    }

    $initial_values = [];
    if (!empty($cookies['jobPostRpo']) || $request->query->get('rpo')) {
      $initial_values['rpo'] = TRUE;
    }

    /** @var \Drupal\job_board\JobBoardJobRole $job */
    $job = $this->entityTypeManager()->getStorage('job_role')->create($initial_values);
    $job->organisation = $current_user->id();
    $job->setOwnerId($current_user->id());

    if ($profile->address) {
      $job->contact_address = $profile->address;
    }
    if ($profile->email) {
      $job->contact_email = $profile->email;
    }
    if ($profile->tel) {
      $job->contact_phone = $profile->tel;
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
   * Return the employer page title.
   */
  public function employerTitle(UserInterface $user) {/** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = $this->entityTypeManager()->getStorage('profile');
    $profile = $profile_storage->loadDefaultByUser($user, 'employer');

    if ($profile && $profile->employer_name->value) {
      return $profile->employer_name->value;
    }
    else if ($user->id() == \Drupal::currentUser()->id()) {
      return $this->t('Your Organisation');
    }

    return $this->t('@username\'s Organisation', [
      '@username' => $user->label(),
    ]);
  }

  /**
   * Return the employer edit page title.
   */
  public function employerEditTitle(UserInterface $user) {
    return $this->t('Edit @employer', [
      '@employer' => $this->employerTitle($user),
    ]);
  }

  /**
   * Check the employer role exist.
   */
  public function employerEditAccess(UserInterface $user) {
    if ($user->hasRole('employer')) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
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
