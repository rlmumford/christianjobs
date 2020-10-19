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
 *   id = "cj_front_page_partners",
 *   admin_label = @Translation("Christian Jobs Front Page Partners"),
 * )
 */
class CJFrontPagePartners extends BlockBase implements ContainerFactoryPluginInterface {

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
        'class' => ['row-wrapper', 'partners', 'section-wrapper', 'section-wrapper--grey'],
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
            '#value' => new TranslatableMarkup('Our Partners'),
          ],
        ],
        'trustedhr' => $this->buildPartnerCard(
          'Trusted HR',
          'HR support tailored to your values and needs',
          'We take time to get to know every church and/or a charity to fully match our service to them. We are passionate and understanding the aims, mission, values and needs.',
          'trustedhrlogo.png',
          Url::fromUri('https://trustedhr.co.uk/cj')
        ),
        'css' => $this->buildPartnerCard(
          'Christian Safeguarding Services',
          'Safeguarding training to churches and faith-based organisations',
          'Christian Safeguarding Service support churches and faith-based organisations with policy development, bespoke training and consultancy services. We have a support and advice line and an answerphone service.',
          'css-logo.png',
          Url::fromUri('https://christiansafeguardingservices.phasic-ltd.co.uk/')
        ),
        'cpo' => $this->buildPartnerCard(
          'CPO',
          'Design, Print, Digital & Training',
          'CPO is an organisation committed to serving churches and charities in communication and outreach through design, print & digital resources, training and support.',
          'CPO-logo.jpg',
          Url::fromUri('https://cpo.org.uk/about')
        ),
        'edwards' => $this->buildPartnerCard(
          'Edwards Insurance Brokers',
          'Specialist Insurance Brokers',
          'Edwards Insurance Brokers is a family run business that specialise in church insurance, charity insurance, commercial insurance and insurance for high value homeowners.',
          'edwardsinsurancelogo.png',
          Url::fromUri('https://www.edwardsinsurance.co.uk/')
        ),
        'kingdom' => $this->buildPartnerCard(
          'Kingdom Bank',
          'A Bank with a Difference',
          'Kingdom Bank is a truly christian bank. They care for everyone they work with - colleagues, customers & suppliers.',
          'kingdombanklogo.png',
          Url::fromUri('http://www.kingdom.bank')
        ),
        'konnect' => $this->buildPartnerCard(
          'Konnect Radio',
          'Together in Music',
          'Konnect Radio is the UK’s first mixed-format Christian Radio Station. This means you will hear the likes of Coldplay, Adele and Avicii together with Rend Collective, Crowder and Lauren Daigle.',
          'konnectlogo.png',
          Url::fromUri('http://www.konnectradio.com')
        ),
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
        'class' => ['col-xs', 'col-xs-12', 'col-md', 'col-md-4'],
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
            'class' => ['card-item', 'card-text', 'text-align-right', 'pull-bottom'],
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
