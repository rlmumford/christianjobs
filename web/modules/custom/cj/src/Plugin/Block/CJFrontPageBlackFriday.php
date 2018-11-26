<?php

namespace Drupal\cj\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block that displays page contemt.
 *
 * @Block(
 *   id = "cj_front_page_black_friday",
 *   admin_label = @Translation("Christian Jobs Front Page Black Friday"),
 * )
 */
class CJFrontPageBlackFriday extends BlockBase implements ContainerFactoryPluginInterface {

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
      '#type' => 'container',
      '#attributes' => [
        'class' => ['row-wrapper', 'partners', 'section-wrapper', 'section-wrapper--black'],
      ],
      'row' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['row', 'section'],
        ],
        'title' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['col-xs-12', 'section-header-wrapper'],
          ],
          'title' => [
            '#type' => 'html_tag',
            '#tag' => 'h2',
            '#attributes' => [
              'class' => [ 'section-header' ]
            ],
            '#value' => new TranslatableMarkup('Black Friday Deals'),
          ],
        ],
        'membership' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => [ 'col-xs-12', 'col-md-6', 'hero' ],
          ],
          'title' => [
            '#type' => 'html_tag',
            '#tag' => 'h2',
            '#attributes' => [
              'class' => [ 'hero--title' ],
            ],
            '#value' => '40% off Community Membership',
          ],
          'description' => [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#attributes' => [
              'class' => [ 'hero--text' ],
            ],
            '#value' => 'Become a Community Member and get access to exclusive member benefits:',
          ],
          'list' => [
            '#type' => 'html_tag',
            '#tag' => 'ul',
            '#value' => '<li>1 <strong>free</strong> 60 day advert worth £100</li>
              <li>25% off all adverts for 1 year</li>
              <li>1 <strong>free</strong> ticket to each of our quarterly training & networking events</li>
              <li>1 <strong>free</strong> copy of Every Good Endeavour by Tim Keller</li>
              <li><strong>£500</strong> off a new website project with <a href="https://churchinsight.co.uk" title="ChurchInsight" alt="Church Insight Homepage">ChurchInsight</a></li>
              <li><strong>10%</strong> off all Team Building days at any one of <a href="https://rockuk.org" title="RockUK" alt="Rock UK Homepage">Rock UK\'s</a> four nationwide centres</li>
              <li>Exclusive discounts on specialist insurance products from <a href="https://www.edwardsinsurance.co.uk/" title="Edwards Insurance" alt="Edwards Insurance Homepage">Edwards Insurance</a></li>
              <li>Much, much more...</li>',
          ],
          'follow' => [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#attributes' => [
              'class' => [ 'hero--text' ],
            ],
            '#value' => 'Usually <del>£349+vat</del> <strong>NOW £209+vat</strong>',
          ],
          'cta' => [
            '#type' => 'html_tag',
            '#tag' => 'a',
            '#attributes' => [
              'class' => ['button', 'button-cta', 'button-orange'],
              'href' => '/jobs/post?membership=membership',
              'title' => 'Join Christian Jobs',
              'alt' => 'Join Christian Jobs',
            ],
            '#value' => new TranslatableMarkup('Join Now')
          ],
        ],
        'jobs' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => [ 'col-xs-12', 'col-md-6', 'hero' ],
          ],
          'title' => [
            '#type' => 'html_tag',
            '#tag' => 'h2',
            '#attributes' => [
              'class' => [ 'hero--title' ],
            ],
            '#value' => '30 days free',
          ],
          'description' => [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#attributes' => [
              'class' => [ 'hero--text' ],
            ],
            '#value' => 'Advertise your opportunity with us and get <strong>30 Days extra exposure for free!</strong>',
          ],
          'description_2' => [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#attributes' => [
              'class' => [ 'hero--text' ],
            ],
            '#value' => 'There are no hidden extra costs and social media sharing is included. Volunteer, salaried and self-funded roles are all catered for.',
          ],
          'cta' => [
            '#type' => 'html_tag',
            '#tag' => 'a',
            '#attributes' => [
              'class' => ['button', 'button-cta', 'button-orange'],
              'href' => '/jobs/post',
              'title' => 'Post a Job',
              'alt' => 'Post a Job',
            ],
            '#value' => new TranslatableMarkup('Post a Job'),
          ],
        ],
      ]
    ];

    return $build;
  }

  /**
   * Build a partner card.
   */
  protected function buildPartnerCard($name, $title, $description, $image_file, Url $url) {
    return [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['col-xs-12', 'col-md-4'],
      ],
      'content' => [
        '#type' => 'html_tag',
        '#tag' => 'a',
        '#attributes' => [
          'class' => ['card', 'partner-card', 'partner-card--'.str_replace(' ', '-', strtolower($name))],
          'href' => $url->toString(),
          'target' => '_blank',
        ],
        'logo' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['card-item', 'card-media', 'media-cover'],
          ],
          'image' => [
            '#theme' => 'image',
            '#uri' => drupal_get_path('module', 'cj').'/assets/'.$image_file,
            '#alt' => new TranslatableMarkup('@name Logo', ['@name' => $name]),
            '#title' => $name,
          ],
        ],
        'title' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['card-item', 'card-title'],
          ],
          'content' => [
            '#type' => 'html_tag',
            '#tag' => 'h3',
            '#value' => $title,
          ],
        ],
        'description' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['card-item', 'card-text'],
          ],
          'content' => [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => $description,
          ],
        ],
        'cta' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['card-item', 'card-text', 'text-align-right'],
          ],
          'content' => [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#attributes' => [
              'class' => ['material-icons', 'material-icons-extended', 'parter-cta'],
            ],
            '#value' => 'arrow_forward',
          ],
        ],
      ],
    ];
  }

}
