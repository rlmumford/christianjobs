<?php

use Drupal\Core\Installer\InstallerKernel;

$settings['config_sync_directory'] = '../config/sync';

// Load sensitive details from the environment.
if ($stripe_publishable_key = getenv('STRIPE_PUBLISHABLE_KEY')) {
  $config['commerce_payment.commerce_payment_gateway.stripe']['configuration']['publishable_key'] = $stripe_publishable_key;
}
if ($stripe_secret_key = getenv('STRIPE_SECRET_KEY')) {
  $config['commerce_payment.commerce_payment_gateway.stripe']['configuration']['secret_key'] = $stripe_secret_key;
}
if ($google_maps_api_key = getenv('GOOGLE_MAPS_API_KEY')) {
  $config['geocoder.settings']['plugins_options']['googlemaps']['apikey'] = $google_maps_api_key;
}
if ($google_recaptcha_site_key = getenv('GOOGLE_RECAPTCHA_SITE_KEY')) {
  $config['simple_recaptcha.config']['site_key'] = $google_recaptcha_site_key;
}
if ($google_recaptcha_secret_key = getenv('GOOGLE_RECAPTCHA_SECRET_KEY')) {
  $config['simple_recaptcha.config']['secret_key'] = $google_recaptcha_secret_key;
}

// Set up the license for civiccookiecontrol.
if ($civic_cookie_api_key = getenv('CIVIC_COOKIE_API_KEY')) {
  $config['civiccookiecontrol.settings']['civiccookiecontrol_api_key'] = $civic_cookie_api_key;
}

/**
 * Configure Redis for caching, lock and flood.
 *
 * We don't want to use it during installation or when the extension is not
 * available.
 */
if (!InstallerKernel::installationAttempted() && extension_loaded('redis')) {
  $settings['redis.connection']['interface'] = 'PhpRedis';
  $settings['redis.connection']['host'] = getenv('REDIS_HOST') ?: 'localhost';

  // Use redis for everything, including typically chained fast backend bins.
  $settings['cache']['bins']['bootstrap'] = 'cache.backend.redis';
  $settings['cache']['bins']['discovery'] = 'cache.backend.redis';
  $settings['cache']['bins']['config'] = 'cache.backend.redis';
  $settings['cache']['default'] = 'cache.backend.redis';

  // Allow redis services to work before redis is enabled.
  $settings['container_yamls'][] = 'modules/contrib/redis/redis.services.yml';
  $class_loader->addPsr4('Drupal\\redis\\', 'modules/contrib/redis/src');

  // Use the redis backends for checksums, locking and flood.
  $settings['container_yamls'][] = 'modules/contrib/redis/example.services.yml';

  // Use redis for the container cache.
  $settings['bootstrap_container_definition'] = [
    'parameters' => [],
    'services' => [
      'redis.factory' => [
        'class' => 'Drupal\redis\ClientFactory',
      ],
      'cache.backend.redis' => [
        'class' => 'Drupal\redis\Cache\CacheBackendFactory',
        'arguments' => [
          '@redis.factory',
          '@cache_tags_provider.container',
          '@serialization.phpserialize'
        ],
      ],
      'cache.container' => [
        'class' => '\Drupal\redis\Cache\PhpRedis',
        'factory' => ['@cache.backend.redis', 'get'],
        'arguments' => ['container'],
      ],
      'cache_tags_provider.container' => [
        'class' => 'Drupal\redis\Cache\RedisCacheTagsChecksum',
        'arguments' => ['@redis.factory'],
      ],
      'serialization.phpserialize' => [
        'class' => 'Drupal\Component\Serialization\PhpSerialize',
      ],
    ],
  ];
}

if (file_exists($app_root . '/' . $site_path . '/settings.ddev.php') && getenv('IS_DDEV_PROJECT') == 'true') {
  include $app_root . '/' . $site_path . '/settings.ddev.php';
  $settings['container_yamls'][] = $app_root . '/' . $site_path . '/ddev.services.yml';

  // Configure the SOLR connection for DDev installs.
  $config['search_api.server.solr_local']['backend_config']['connector_config']['host'] = getenv('CI_REVIEW_SOLR_HOST') ?: 'fmcg.ddev.site';
  $config['search_api.server.solr_local']['backend_config']['connector_config']['core'] = 'dev';

  // General Debug.
  $config['views.settings']['ui']['show']['sql_query']['enabled'] = TRUE;

  // Set up the filesystem.
  $settings['file_temp_path'] = '/tmp';
  $settings['file_private_path'] = '../.ddev/private';

  // Make sure communication module's emails go through mailsystem rather than
  // using MailGun directly.
  $config['communication.mode.email']['enabled_op_variants']['send'] = [
    'send_email_mailsystem' => 'send_email_mailsystem',
    'send_email_mailgun' => FALSE,
  ];

  // Override the mailsystem sender to use swiftmailer sending to mailhog.
  $config['mailsystem.settings']['defaults']['sender'] = 'swiftmailer';
  $config['mailsystem.settings']['modules']['communication']['none']['sender'] = 'swiftmailer';
  $config['swiftmailer.transport']['transport'] = 'smtp';
  $config['swiftmailer.transport']['smtp_port'] = '1025';

  // Configuration for DDev visual review.
  $settings['gitlab_review'] = [
    'project_id' => getenv('CI_PROJECT_ID'),
    'project_path' => getenv('CI_PROJECT_PATH'),
    'merge_request' => getenv('CI_MERGE_REQUEST_IID'),
    'url' => getenv('CI_SERVER_URL')
  ];

  $config['devel.settings']['devel_dumper'] = 'var_dumper';
}

if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}

if (file_exists($app_root . '/' . $site_path . '/services.local.yml')) {
  $settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.local.yml';
}

// In committed config we wont have analytics. If we add it via overrides, we
// need to make sure that EU Cookie Compliance adds the correct disabled JS.
if (isset($config['google_analytics.settings']['account'])) {
  $config['eu_cookie_compliance.settings']['disabled_javascripts'] = "analytics:https://www.googletagmanager.com/gtag/js?id={$config['google_analytics.settings']['account']}";
}
