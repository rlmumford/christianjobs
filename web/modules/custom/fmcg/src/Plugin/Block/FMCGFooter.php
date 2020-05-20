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
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
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
    $build = [
      '#markup' => '<div><div class="pull-center">
      <div class="footer-info"><p>© FMCG Jobs 2020  I  <a href="mailto:info@fmcgjobs.com">info@fmcgjobs.com</a></p></div>
      </div></div>
      <div class="row">
      <div class="footer-contact footer-item pull-center">
        <a href="https://www.linkedin.com/company/fmcgjobs-com/" class="services-icons icon-primary" data-icon="linkedin"></a>     <a href="https://twitter.com/GlobalFMCGJobs" class="services-icons icon-primary" data-icon="twitter"></a>
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
