<?php

namespace Drupal\cj_membership\Form;

use Drupal\cj_membership\Entity\Membership;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

class VolunteerRolePostForm extends VolunteerRoleForm {

  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\commerce_cart\CartProvider $cart_provider */
    $cart_provider = \Drupal::service('commerce_cart.cart_provider');
    $cart = $cart_provider->getCart('default');
    if (!$cart) {
      $cart = $cart_provider->createCart('default');
    }

    // Add a membership options to this form.
    /** @var \Drupal\cj_membership\MembershipStorage $membership_storage */
    $membership_storage = \Drupal::entityTypeManager()->getStorage('cj_membership');
    $membership = $membership_storage->getAccountMembership(\Drupal::currentUser());
    if ($membership) {
      $form_state->set('membership', $membership);
    }

    // Membership on current order.
    $membership_in_cart = FALSE;
    foreach ($cart->getItems() as $order_item) {
      if ($order_item->getPurchasedEntity() instanceof Membership) {
        $membership_in_cart = $order_item->getPurchasedEntity();
        $form_state->set('membership_in_cart', $membership_in_cart);
      }
    }

    if (!$membership_in_cart && $membership && !in_array($membership->status->value, [Membership::STATUS_ACTIVE, Membership::STATUS_EXPIRED])) {
      $membership->delete();
      $membership = FALSE;
    }

    /** @var \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface $formatter */
    $formatter = \Drupal::service('commerce_price.currency_formatter');
    $membership_pricing = \Drupal::config('cj_membership.pricing');
    $full_price = new Price(
      $membership_pricing->get('full'),
      'GBP'
    );
    $full_month_price = new Price(
      $membership_pricing->get('full_monthly'),
      'GBP'
    );
    $dir_price = new Price(
      $membership_pricing->get('directory'),
      'GBP'
    );
    $dir_month_price = new Price(
      $membership_pricing->get('directory_monthly'),
      'GBP'
    );

    if (!$membership_in_cart && !$membership) {
      $form['membership']['new'] = [
        '#type' => 'checkbox',
        '#title' => $this->t(
          '<span class="directory-membership-cost">@dir_price</span><span class="vat">+VAT</span><span class="or"> OR </span><span class="directory-monthly-cost">@dir_month_price</span><span class="per-month">+VAT/MONTH</span><span class="directory-description"> Become a Christian Jobs Directory Member</span>',
          [
            '@dir_price' => $formatter->format(
              $dir_price->getNumber(),
              $dir_price->getCurrencyCode()
            ),
            '@dir_month_price' => $formatter->format(
              $dir_month_price->getNumber(),
              $dir_month_price->getCurrencyCode()
            )
          ]),
        '#default_value' => TRUE,
        '#attributes' => [
          'class' => ['membership-checkbox'],
        ],
      ];
    }
    else if (!$membership_in_cart && $membership && $membership->status->value == Membership::STATUS_EXPIRED) {
      $form['membership']['renew'] = [
        '#type' => 'checkbox',
        '#title' => $this->t(
          'Renew Christian Jobs @type Membership <span class="upsell-price pull-right orange-triangle">@price<span class="tax">+VAT</span></span>',
          [
            '@type' => $membership->level->value > Membership::LEVEL_DIRECTORY ? 'Directory' : 'Community',
            '@price' => $formatter->format(
              $membership->level->value > Membership::LEVEL_DIRECTORY ? $full_price->getNumber() : $dir_price->getNumber(),
              $full_price->getCurrencyCode()
            ),
          ]
        ),
        '#default_value' => TRUE,
        '#attributes' => [
          'class' => ['membership-checkbox'],
        ],
      ];
    }

    if (!empty($form['membership'])) {
      if (
        ($membership && ($membership->level->value < Membership::LEVEL_FULL)) ||
        ($membership_in_cart && ($membership_in_cart->level->value < Membership::LEVEL_FULL)) ||
        (!$membership && !$membership_in_cart)
      ) {
        $form['membership']['upgrade'] = [
          '#type' => 'checkbox',
          '#title' => $this->t(
            '<span class="directory-membership-cost">@full_price</span><span class="vat">+VAT</span><span class="or"> OR </span><span class="directory-monthly-cost">@full_month_price</span><span class="per-month">+VAT/MONTH</span><span class="directory-description"> Upgrade to full Christian Jobs Community Membership</span>',
            [
              '@full_price' => $formatter->format(
                $full_price->getNumber(),
                $full_price->getCurrencyCode()
              ),
              '@full_month_price' => $formatter->format(
                $full_month_price->getNumber(),
                $full_month_price->getCurrencyCode()
              )
            ]
          ),
          '#attributes' => [
            'class' => ['membership-checkbox'],
          ],
        ];
      }

      $form['membership']['#type'] = 'container';
      $form['membership']['#tree'] = TRUE;
      $form['membership']['#weight'] = 60;
      $form['membership']['#attributes']['class']= ['divider-top'];
      $form['membership']['title'] = [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $this->t('Membership'),
        '#weight' => -5,
      ];
      $form['membership']['description'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Volunteer roles are included in any Christian Jobs Membership.'),
        '#attributes' => [
          'class' => ['section-summary'],
        ],
        '#weight' => -4,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    $actions['submit']['#submit'][] = '::submitFormAddToCart';
    $actions['submit']['#attributes']['formnovalidate'] = 'formnovalidate';

    $actions['submit_another'] = $actions['submit'];
    $actions['submit_another']['#weight'] = 20;
    $actions['submit_another']['#value'] = $this->t('Save & Post Another Role');
    $actions['submit_another']['#submit'][] = '::submitFormRedirectToVolunteerPost';

    if ($form_state->get('membership_in_cart')) {
      $actions['submit']['#value'] = t('Save & Proceed to Payment');
    }
    $actions['submit']['#submit'][] = '::submitFormRedirectToCheckout';
    $actions['submit']['#states']['invisible'] = [
      '.membership-checkbox' => ['checked' => TRUE],
    ];

    $actions['submit_pay'] = $actions['submit'];
    $actions['submit_pay']['#value'] = $this->t('Save & Proceed to Payment');
    $actions['submit_pay']['#states'] = [
      'visible' => [
        '.membership-checkbox' => ['checked' => TRUE],
      ]
    ];

    return $actions;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->get('membership') && !$form_state->get('membership_in_cart') && !$form_state->getValue(['membership', 'new'], FALSE)) {
      $form_state->setError($form, new TranslatableMarkup(
        'Posting voluntary roles is only available to Christian Jobs Members. Please tick the box to become a member today.'
      ));
    }
    else if ($form_state->get('membership') && $form_state->get('membership')->status->value !== Membership::STATUS_ACTIVE && !$form_state->getValue(['membership', 'renew'], FALSE)) {
      $form_state->setError($form, new TranslatableMarkup(
        'Posting voluntary roles is only available to Christian Jobs Members. Please tick the box to renew your membership today.'
      ));
    }

    return parent::validateForm($form, $form_state);
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

    $current_user = \Drupal::currentUser();
    /** @var \Drupal\cj_membership\MembershipStorage $membership_storage */
    $membership_storage = \Drupal::service('entity_type.manager')->getStorage('cj_membership');
    $current_membership = $membership_storage->getAccountMembership($current_user);
    $membership_values = $form_state->getValue(['membership']);
    if (isset($membership_values['new']) && !empty($membership_values['new'])) {
      $membership = $membership_storage->create(['level' => Membership::LEVEL_DIRECTORY])->setOwnerId($current_user->id());
      $membership->start->value = date(DateTimeItemInterface::DATE_STORAGE_FORMAT);
    }
    elseif (isset($membership_values['renew']) && !empty($membership_values['renew'])) {
      $membership = $current_membership;
    }

    if (!empty($membership_values['upgrade'])) {
      $membership->level = Membership::LEVEL_FULL;
    }

    if (!empty($membership)) {
      $form_state->set('membership_in_cart', $membership);
      $cart_manager->addEntity($cart, $membership);
    }
  }

  public function submitFormRedirectToCheckout(array $form, FormStateInterface $form_state) {
    if ($form_state->get('membership_in_cart')) {
      user_cookie_delete('volunteerPostRegister');

      $cart_provider = \Drupal::service('commerce_cart.cart_provider');
      $form_state->setRedirect('commerce_checkout.form', [
        'commerce_order' => $cart_provider->getCart('default')->id(),
      ]);
    }
  }

  public function submitFormRedirectToVolunteerPost(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('volunteer_board.post');
  }

}
