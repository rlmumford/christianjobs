<?php

namespace Drupal\fmcg\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block that displays page contemt.
 *
 * @Block(
 *   id = "fmcg_footer",
 *   admin_label = @Translation("FMCG Jobs Footer"),
 * )
 */
class FMCGFooter extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Creates a CJHeaderBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *  The factory for configuration objects.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('config.factory'));
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();

    $form['linkedin_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('LinkedIn Company Id'),
      '#description' => $this->t('You can find this on the URL of your company\'s linked in page'),
      '#default_value' => $configuration['linkedin_id'],
    ];

    $form['twitter_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Twitter Company Id'),
      '#description' => $this->t('You can find this on the URL of your company\'s twitter page'),
      '#default_value' => $configuration['twitter_id'],
    ];

    $form['email_address'] = [
      '#type' => 'email',
      '#title' => $this->t('Email Address'),
      '#description' => $this->t('Your preferred company contact email address should go here'),
      '#default_value' => $configuration['email_address'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();
    $configuration['linkedin_id'] = $form_state->getValue('linkedin_id');
    $configuration['twitter_id'] = $form_state->getValue('twitter_id');
    $configuration['email_address'] = $form_state->getValue('email_address');
    $this->setConfiguration($configuration);
  }

  /**
   * Builds and returns the renderable array for this block plugin.
   *
   * If a block should not be rendered because it has no content, then this
   * method must also ensure to return no content: it must then only return an
   * empty array, or an empty array with #cache set (with cacheability metadata
   * indicating the circumstances for it being empty).
   *
   * @return array
   *   A renderable array representing the content of the block.
   *
   * @see \Drupal\block\BlockViewBuilder
   */
  public function build() {
    $configuration = $this->getConfiguration();

    $build = [
      '#markup' => '<div>
        <div class="pull-center">
          <div class="footer-info footer-text"><p>&copy; FMCG Jobs '.date('Y').'  |  <a href="mailto:'.$configuration['email_address'].'">'.$configuration['email_address'].'</a></p></div>
        </div>
      </div>
      <div class="row">
        <div class="footer-contact footer-item pull-center">
          <a href="https://www.linkedin.com/company/'.$configuration['linkedin_id'].'/" class="services-icons icon-primary" data-icon="linkedin"></a>
          <a href="https://twitter.com/'.$configuration['twitter_id'].'/" class="services-icons icon-primary" data-icon="twitter"></a>
        </div>
      </div>',
      '#cache' => [
        'contexts' => ['user.roles:authenticated'],
        'tags' => [],
        'max-age' => 60*60*24,
      ],
    ];

    return $build;
  }

}
