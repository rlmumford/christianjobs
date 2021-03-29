<?php

namespace Drupal\contacts_jobs_extensions\Form;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\contacts_jobs\Entity\Job;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Job Extend Form
 *
 * @package Drupal\contacts_jobs_extensions\Form
 */
class JobExtendForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The currency formatter service.
   *
   * @var \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface
   */
  protected $currencyFormatter;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('commerce_cart.cart_provider'),
      $container->get('commerce_cart.cart_manager'),
      $container->get('commerce_price.currency_formatter')
    );
  }

  /**
   * Job extend form constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   * @param \Drupal\commerce_cart\CartManagerInterface $cart_manager
   *   The cart manager.
   * @param \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface $currency_formatter
   *   The currency formatter service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    CartProviderInterface $cart_provider,
    CartManagerInterface $cart_manager,
    CurrencyFormatterInterface $currency_formatter
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->cartProvider = $cart_provider;
    $this->cartManager = $cart_manager;
    $this->currencyFormatter = $currency_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'job_extend_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Job $job_role = NULL) {
    $form_state->set('job', $job_role);

    $form['job'] = [
      '#type' => 'item',
      '#title' => new TranslatableMarkup('Job'),
      '#markup' => "<div>".$job_role->label()."</div>",
    ];
    if (!$job_role->end_date->isEmpty()) {
      $form['publish_end'] = [
        '#type' => 'item',
        '#title' => new TranslatableMarkup('Current Expiry'),
        '#markup' => "<div>This job currently expires on " . $job_role->publish_end->date->format("d/m/Y") . "</div>",
      ];
    }

    $product_variation_storage = $this->entityTypeManager->getStorage('commerce_product_variation');
    $query = $product_variation_storage->getQuery();
    $query->condition('type', 'job_extension');
    $options = [];
    foreach ($product_variation_storage->loadMultiple($query->execute()) as $variation) {
      /** @var \Drupal\commerce_price\Price $price */
      $price = $variation->price->get(0)->toPrice();
      try {
        $options[$variation->id()] = $this->t(
          '@duration - @price',
          [
            '@duration' => (new \DateInterval($variation->extension_duration->value))->format('%d days'),
            '@price' => $this->currencyFormatter->format($price->getNumber(), $price->getCurrencyCode())
          ]
        );
      }
      catch (\Exception $exception) {
        // Do nothing if the extension duration is not available or valid.
      }
    }

    $form['product'] = [
      '#type' => 'radios',
      '#options' => $options,
      '#required' => TRUE,
    ];

    $form['closing'] = [
      '#title' => new TranslatableMarkup('Application deadline'),
      '#description' => new TranslatableMarkup('You may wish to change the application deadline for your position, to reflect the extended advertisement time. Jobs will not be published beyond their application deadline.'),
      '#type' => 'datetime',
      '#default_value' => $job_role->closing->date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
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
    if ($closing = $form_state->getValue('closing')) {
      $job_role->closing->value = $closing->format('U');
    }
    $job_role->save();

    /** @var \Drupal\contacts_jobs_extensions\JobExtensionStorage $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('cj_extension');

    /** @var \Drupal\contacts_jobs_extensions\Entity\JobExtension $extension */
    $extension = $storage->create();
    $extension->job = $job_role;
    $extension->product = $form_state->getValue('product');
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

    $form_state->setRedirect('entity.contacts_job.canonical', ['contacts_job' => $job_role->id()]);
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
