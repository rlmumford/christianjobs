<?php

namespace Drupal\job_board\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\job_board\JobBoardJobRole;

class JobExtendForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'job_extend_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, JobBoardJobRole $job_role = NULL) {
    $form_state->set('job', $job_role);

    $form['job'] = [
      '#type' => 'item',
      '#title' => new TranslatableMarkup('Job'),
      '#markup' => "<div>".$job_role->label()."</div>",
    ];
    if (!$job_role->end_date->isEmpty()) {
      $form['end_date'] = [
        '#type' => 'item',
        '#title' => new TranslatableMarkup('Current Expiry'),
        '#markup' => "<div>This job currently expires on " . $job_role->end_date->date->format("d/m/Y") . "</div>",
      ];
    }
    $form['duration'] = [
      '#type' => 'select',
      '#title' => new TranslatableMarkup('How long would you like to extend this job advert for?'),
      '#options' => [
        'P30D' => new TranslatableMarkup('30 Days'),
        'P60D' => new TranslatableMarkup('60 Days'),
      ],
    ];
    $form['application_deadline'] = [
      '#title' => new TranslatableMarkup('Application deadline'),
      '#description' => new TranslatableMarkup('You may wish to change the application deadline for your position, to reflect the extended advertisement time. Jobs will not be published beyond their application deadline.'),
      '#type' => 'datetime',
      '#default_value' => $job_role->application_deadline->value,
      '#date_increment' => 1,
      '#date_time_element' => 'none',
      '#date_timezone' => DateTimeItemInterface::STORAGE_TIMEZONE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#attributes' => [
        'class' => [ 'divider-top' ]
      ],
      'submit' => [
        '#type' => 'submit',
        '#value' => new TranslatableMarkup('Add Extension to Cart'),
      ],
      'submit_checkout' => [
        '#type' => 'submit',
        '#value' => new TranslatableMarkup('Confirm Extension & Proceed to Checkout'),
        '#submit' => [
          '::submitForm',
          '::submitFormRedirectToCheckout',
        ],
      ],
    ];

    // Don't use chrome validation.
    $form['#attributes']['novalidate'] = 'novalidate';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $job_role = $form_state->get('job');
    if ($application_deadline = $form_state->getValue('application_deadline')) {
      $job_role->application_deadline->value = $application_deadline->format('Y-m-d');
    }
    $job_role->save();

    /** @var \Drupal\job_board\JobExtensionStorage $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('job_board_job_extension');

    /** @var \Drupal\job_board\Entity\JobExtension $extension */
    $extension = $storage->create();
    $extension->job = $job_role;
    $extension->duration = $form_state->getValue('duration');
    $extension->owner = \Drupal::currentUser()->id();
    $extension->save();

    $cart_provider = \Drupal::service('commerce_cart.cart_provider');
    /** @var \Drupal\commerce_cart\CartManagerInterface $cart_manager */
    $cart_manager = \Drupal::service('commerce_cart.cart_manager');
    $cart = $cart_provider->getCart('default');
    if (!$cart) {
      $cart = $cart_provider->createCart('default');
    }
    $cart_manager->addEntity($cart, $extension);

    $form_state->setRedirect('entity.job_role.canonical', ['job_role' => $job_role->id()]);
  }

  /**
   * Submit the form and redirect to the checkout.
   */
  public function submitFormRedirectToCheckout(array &$form, FormStateInterface $form_state) {
    $cart_provider = \Drupal::service('commerce_cart.cart_provider');
    $form_state->setRedirect('commerce_checkout.form', [
      'commerce_order' => $cart_provider->getCart('default')->id(),
    ]);
  }

}
