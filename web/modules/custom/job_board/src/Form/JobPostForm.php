<?php
/**
 * Created by PhpStorm.
 * User: Rob
 * Date: 28/08/2018
 * Time: 14:59
 */

namespace Drupal\job_board\Form;

use Drupal\cj_membership\Entity\Membership;
use Drupal\commerce_order\Adjustment;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

class JobPostForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function getOperation() {
    return 'post';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Move the contact_ fields into their own section.
    $form['contact_details'] = [
      '#weight' => 48,
      '#type' => 'container',
      '#attributes' => [
        'class' => ['card-item', 'card-text', 'divider-top'],
      ],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $this->t('Contact Details'),
      ],
      'description' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#attributes' => [
          'class' => ['section-summary'],
        ],
        '#value' => $this->t('Please provide any contact information an applicant may need to apply for the job.'),
      ],
    ];
    foreach (['contact_address', 'contact_email', 'contact_phone'] as $contact_field) {
      $form['contact_details'][$contact_field] = $form[$contact_field];
      unset($form[$contact_field]);
    }
    $form['contact_details']['contact_phone']['#weight'] = 100;

    $form['#attributes']['class'][] = 'card';
    $form['#attributes']['class'][] = 'card-main';

    /** @var \Drupal\commerce_cart\CartProvider $cart_provider */
    $cart_provider = \Drupal::service('commerce_cart.cart_provider');
    $cart = $cart_provider->getCart('default');
    if (!$cart) {
      $cart = $cart_provider->createCart('default');
    }

    if ($this->entity->initial_duration->isEmpty() || $this->entity->initial_duration->value == 'P30D') {
      $form['duration_upsell'] = [
        '#weight' => 49,
        '#type' => 'container',
        '#tree' => TRUE,
        '#attributes' => [
          'class' => ['card-item', 'card-text', 'divider-top'],
        ],
        'title' => [
          '#type' => 'html_tag',
          '#tag' => 'h3',
          '#value' => $this->t('More Exposure'),
        ],
        'description' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $this->t('Our standard job postings stay live for 30 days from the publish date you specify. Need more exposure? For only <strong>£25</strong> you can double this limit.'),
        ],
        'extend' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Publish for 60 days.'),
          '#states' => [
            'checked' => [
              '.rpo-checkbox, .membership-checkbox' => ['checked' => TRUE],
            ],
            'disabled' => [
              '.rpo-checkbox, .membership-checkbox' => ['checked' => TRUE],
            ]
          ]
        ],
      ];
    }

    if (!$this->entity->rpo->value) {
      $form['rpo_upsell'] = [
        '#weight' => 51,
        '#type' => 'container',
        '#tree' => TRUE,
        '#attributes' => [
          'class' => ['card-item', 'card-text', 'divider-top'],
        ],
        'title' => [
          '#type' => 'html_tag',
          '#tag' => 'h3',
          '#value' => $this->t('Outsourced Help (RPO)'),
        ],
        'description' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#attributes' => [
            'class' => ['section-summary'],
          ],
          '#value' => $this->t('Get this Job <strong>FREE</strong> when you purchase our RPO package.'),
        ],
        'rpo' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Upgrade to an Outsourced Recruitment Process <span class="upsell-price pull-right">£795<span class="tax">+VAT</span></span>'),
          '#attributes' => [
            'class' => ['rpo-checkbox'],
          ],
        ],
      ];
    }

    // Add a membership options to this form.
    if (\Drupal::moduleHandler()->moduleExists('cj_membership')) {
      /** @var \Drupal\cj_membership\MembershipStorage $membership_storage */
      $membership_storage = \Drupal::entityTypeManager()->getStorage('cj_membership');
      $membership = $membership_storage->getAccountMembership(\Drupal::currentUser());

      // Membership on current order.
      $membership_in_cart = FALSE;
      foreach ($cart->getItems() as $order_item) {
        if ($order_item->getPurchasedEntity() instanceof Membership) {
          $membership_in_cart = TRUE;
        }
      }

      if (!$membership_in_cart && !$membership) {
        $form['membership']['new'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Become a Christian Jobs Community Member <span class="upsell-price pull-right">£349<span class="tax">+VAT</span></span>'),
          '#attributes' => [
            'class' => ['membership-checkbox'],
          ],
        ];
      }
      else if (!$membership_in_cart && $membership && $membership->status->value == Membership::STATUS_EXPIRED) {
        $form['membership']['extend'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Renew Christian Jobs Community Membership <span class="upsell-price pull-right">£349<span class="tax">+VAT</span></span>'),
          '#attributes' => [
            'class' => ['membership-checkbox'],
          ],
        ];
      }

      if (!empty($form['membership'])) {
        $form['membership']['#type'] = 'container';
        $form['membership']['#tree'] = TRUE;
        $form['membership']['#weight'] = 50;
        $form['membership']['#attributes']['class']= ['card-item', 'card-text', 'divider-top'];
        $form['membership']['title'] = [
          '#type' => 'html_tag',
          '#tag' => 'h3',
          '#value' => $this->t('Membership'),
          '#weight' => -5,
        ];
        $form['membership']['description'] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $this->t('Get this Job <strong>FREE</strong> when you become a Christian Jobs Community Member. Find out more <a href="/membership" target="_blank">here</a>'),
          '#attributes' => [
            'class' => ['section-summary'],
          ],
          '#weight' => -4,
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    $actions['#attributes']['class'][] = 'card-item';
    $actions['#attributes']['class'][] = 'card-actions';
    $actions['#attributes']['class'][] = 'divider-top';
    $actions['submit']['#submit'][] = '::submitFormAddToCart';

    $actions['submit_another'] = $actions['submit'];
    $actions['submit_another']['#value'] = $this->t('Save & Post Another Job');
    $actions['submit_another']['#submit'][] = '::submitFormRedirectToJobPost';

    $actions['submit']['#value'] = t('Proceed to Payment');
    $actions['submit']['#submit'][] = '::submitFormRedirectToCheckout';

    return $actions;
  }

  public function submitFormAddToCart(array $form, FormStateInterface $form_state) {
    // If this job has been upgraded to a RPO ser values appropriately.
    if ($form_state->getValue(['duration_upsell']['extend'])) {
      $this->getEntity()->initial_duration = 'P60D';
    }
    if ($form_state->getValue(['rpo_upsell', 'rpo'])) {
      $this->getEntity()->rpo = TRUE;
    }

    $cart_provider = \Drupal::service('commerce_cart.cart_provider');
    /** @var \Drupal\commerce_cart\CartManagerInterface $cart_manager */
    $cart_manager = \Drupal::service('commerce_cart.cart_manager');
    $cart = $cart_provider->getCart('default');
    if (!$cart) {
      $cart = $cart_provider->createCart('default');
    }
    $job_post_item = $cart_manager->addEntity($cart, $this->getEntity());

    // If the membership options have been selected then add the membership to
    // the cart.
    if (\Drupal::service('module_handler')->moduleExists('cj_membership')) {
      $current_user = \Drupal::currentUser();
      /** @var \Drupal\cj_membership\MembershipStorage $membership_storage */
      $membership_storage = \Drupal::service('entity_type.manager')->getStorage('cj_membership');
      $current_membership = $membership_storage->getAccountMembership($current_user);
      if ($form_state->getValue(['membership', 'new'])) {
        $membership = $membership_storage->create()->setOwnerId($current_user->id());
        $membership->start->value = date(DateTimeItemInterface::DATE_STORAGE_FORMAT);
      }
      elseif ($form_state->getValue(['membership', 'extend'])) {
        $membership = $current_membership;
      }

      $free_job = FALSE;
      if ($membership) {
        $cart_manager->addEntity($cart, $membership);
        $free_job = TRUE;
      }

      if ($membership || $current_membership->status->value == Membership::STATUS_ACTIVE) {
        $adjustment_amount = $job_post_item->getTotalPrice()->multiply($free_job ? '-1' : '-0.25');
        $adjustment_amount = \Drupal::service('commerce_price.rounder')->round($adjustment_amount);

        $job_post_item->addAdjustment(new Adjustment([
          'type' => 'membership_discount',
          'label' => $free_job ? $this->t('First Job Free!') : $this->t('Membership Discount'),
          'amount' => $adjustment_amount,
          'percentage' => $free_job ? '100%' : '25%',
          'source_id' => $membership ? $membership->id() : $current_membership->id(),
        ]));
      }
    }
  }

  public function submitFormRedirectToCheckout(array $form, FormStateInterface $form_state) {
    user_cookie_delete('jobPostMembership');
    user_cookie_delete('jobPostRpo');

    $cart_provider = \Drupal::service('commerce_cart.cart_provider');
    $form_state->setRedirect('commerce_checkout.form', [
      'commerce_order' => $cart_provider->getCart('default')->id(),
    ]);
  }

  public function submitFormRedirectToJobMost(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('job_board.post');
  }
}
