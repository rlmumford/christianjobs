<?php

namespace Drupal\job_board\Controller;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\TransactionNameNonUniqueException;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class JobBoardController extends ControllerBase {

  /**
   * Page to post a new job.
   */
  public function postJob() {
    $current_user = \Drupal::currentUser();
    if ($current_user->isAnonymous() && !$current_user->hasPermission('post job board jobs')) {
      user_cookie_save([
        'jobPostRegister' => TRUE,
      ]);
      return $this->redirect('user.register');
    }

    $user = entity_load('user', $current_user->id());
    if (!$user || !$user->profile_employer->entity || !$user->profile_employer->entity->employer_name->value) {
      return $this->redirect('job_board.employer_edit', ['user' => $current_user->id()]);
    }

    /** @var \Drupal\job_board\JobBoardJobRole $job */
    $job = $this->entityTypeManager()->getStorage('job_role')->create([]);
    $job->setOwnerId($current_user->id());
    return $this->entityFormBuilder()->getForm($job, 'post');
  }

  /**
   * Return the employer page title.
   */
  public function employerTitle(UserInterface $user) {
    $profile = $user->profile_employer->entity;

    if ($profile->employer_name->value) {
      return $profile->employer_name->value;
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
      /** @var Price $membership_price */
      $membership_price = $package['member_price'];

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
          '#title' => $this->t('Get Started Now'),
          '#url' => Url::fromRoute('job_board.post'),
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

        $output[$key]['features']['features']['#items'][] = [
          '#markup' => $feature['title'],
          '#wrapper_attributes' => [
            'class' => [ 'package-feature-item' ] + $classes,
          ],
        ];
      }
    }

    return $output;
  }
}
