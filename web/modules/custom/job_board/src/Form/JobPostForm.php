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
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

class JobPostForm extends JobForm {

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
        '#default_value' => !empty($this->entity->rpo->value),
        '#attributes' => [
          'class' => ['rpo-checkbox'],
        ],
      ],
    ];


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

      if (!$membership_in_cart && $membership && !in_array($membership->status->value, [Membership::STATUS_ACTIVE, Membership::STATUS_EXPIRED])) {
        $membership->delete();
        $membership = FALSE;
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
   * Validate that both part of the range are filled if any are.
   */
  public static function salaryWidgetValidate($element, FormStateInterface $form_state, $complete_form) {
    $values = $form_state->getValue($element['#parents']);
    if ((!empty($values['from']) && empty($values['to'])) || (!empty($values['to']) && empty($values['from']) && $values['from'] !== '0')) {
      $form_state->setError($element, 'Please provide both parts of the salary range.');
    }
    if (!empty($values['from']) && !empty($values['to']) && ($values['from'] > $values['to'])) {
      $form_state->setError($element, 'Please ensure that the salary from value is less than the salary to value.');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    $actions['submit']['#submit'][] = '::submitFormAddToCart';

    $actions['submit_another'] = $actions['submit'];
    $actions['submit_another']['#value'] = $this->t('Save & Post Another Job');
    $actions['submit_another']['#submit'][] = '::submitFormRedirectToJobPost';

    $actions['submit']['#value'] = t('Proceed to Payment');
    $actions['submit']['#submit'][] = '::submitFormRedirectToCheckout';

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // If this job has been upgraded to a RPO ser values appropriately.
    if ($form_state->getValue(['duration_upsell', 'extend'])) {
      $this->entity->initial_duration = 'P60D';
    }
    else {
      $this->entity->initial_duration = 'P30D';
    }
    if ($form_state->getValue(['rpo_upsell', 'rpo'])) {
      $this->entity->rpo = TRUE;
    }
    else {
      $this->entity->rpo = FALSE;
    }
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitFormAddToCart(array $form, FormStateInterface $form_state) {
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
      $membership_values = $form_state->getValue(['membership']);
      if (isset($membership_values['new']) && !empty($membership_values['new'])) {
        $membership = $membership_storage->create()->setOwnerId($current_user->id());
        $membership->start->value = date(DateTimeItemInterface::DATE_STORAGE_FORMAT);
      }
      elseif (isset($membership_values['extend']) && !empty($membership_values['extend'])) {
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
