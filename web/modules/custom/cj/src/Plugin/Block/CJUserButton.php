<?php

namespace Drupal\cj\Plugin\Block;

use Drupal\commerce_cart\CartProviderInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block that displays page header.
 *
 * @Block(
 *   id = "cj_user_button",
 *   admin_label = @Translation("Christian Jobs User Button"),
 * )
 */
class CJUserButton extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('commerce_cart.cart_provider'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * CJUserButton constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user, CartProviderInterface $cart_provider, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->currentUser = $current_user;
    $this->cartProvider = $cart_provider;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $class = $this->currentUser->isAuthenticated() ? 'user-authenticated' : 'user-anonymous';
    $title = $this->currentUser->isAuthenticated() ? 'Your Dashboard' : 'Log-in';
    $markup = '<a title="'.$title.'" href="/user" rel="nofollow"><i class="material-icons navbar-icon '.$class.'">account_circle</i></a>';

    if ($this->currentUser->isAuthenticated()) {
      /** @var \Drupal\user\UserInterface $user */
      $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
      if ($user->hasRole('recruiter')) {
        $markup = '<a title="'.$this->t('Organisation Dashboard').'" href="'.Url::fromRoute('contacts_jobs_dashboard.recruiter_organisation')->toString().'" rel="nofollow">
            <i class="material-icons navbar-icon">groups</i>
          </a>' . $markup;
      }
    }

    if ($cart = $this->cartProvider->getCart('default')) {
      /** @var \Drupal\commerce_order\Entity\OrderInterface $cart */
      $item_count = count($cart->getItems());
      $items_class = $item_count > 0 ? 'has-items has-'.$item_count.'-items' : 'no-items';
      $markup = '<a title="Your Cart" href="/cart" rel="nofollow"><i item-count="'.$item_count.'" class="material-icons navbar-icon cart-icon '.$items_class.'">shopping_basket</i></a>' . $markup;
    }

    return [
      '#prefix' => '<div id="navbar-user-button">',
      '#markup' => $markup,
      '#suffix' => '</div>',
      '#cache' => [
        'contexts' => ['cart'],
      ],
    ];
  }

}
