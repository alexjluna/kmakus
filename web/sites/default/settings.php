<?php

/**
 * @file
 * The settings file.
 */

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '../../../../');
$dotenv->load();

$settings['config_sync_directory'] = '../config';
$settings['hash_salt'] = $_SERVER['HASH_SALT'];
$settings['update_free_access'] = FALSE;
$settings['allow_authorize_operations'] = FALSE;
$settings['file_private_path'] = '../private';
$settings['file_temp_path'] = $_SERVER['TMP_FOLDER'] ?? '/tmp';
$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';
$settings['trusted_host_patterns'] = [$_SERVER['TRUSTED_HOST']];
$settings['class_loader_auto_detect'] = FALSE;
$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];
$settings['entity_update_batch_size'] = 50;
$settings['entity_update_backup'] = TRUE;
$settings['migrate_node_migrate_type_classic'] = FALSE;
$isolationLevel = "SET SESSION transaction_isolation='READ-COMMITTED'";


$databases['default']['default'] = [
  'database' => $_SERVER['DB_NAME'],
  'username' => $_SERVER['DB_USERNAME'],
  'password' => $_SERVER['DB_PASSWORD'],
  'host' => $_SERVER['DB_HOST'],
  'driver' => "mysql",
  'port' => $_SERVER['DB_PORT'],
  'prefix' => "",
  'init_commands' => [
    'isolation_level' => $isolationLevel,
  ],
];

// Configure SSL if needed.
$rdsCertPath = $_SERVER['DB_CERT_PATH'];
if ($_SERVER['DB_SSL_ENABLED'] === '1' && is_readable($rdsCertPath)) {
  $databases['default']['default']['pdo'][PDO::MYSQL_ATTR_SSL_CA] = $rdsCertPath;
  $databases['default']['default']['pdo'][PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = FALSE;
}

if ($_SERVER['REDIS_ENABLED'] === '1') {

  $settings['redis.connection']['interface']  = 'PhpRedis';
  $settings['redis.connection']['host']       = $_SERVER['REDIS_HOST'];
  $settings['redis.connection']['port']       = $_SERVER['REDIS_PORT'];
  $settings['redis.connection']['persistent'] = TRUE;
  $settings['cache']['default']               = 'cache.backend.redis';
  $settings['queue_default']                  = 'queue.redis_reliable';
  $settings['redis_compress_length']          = 100;
  $settings['redis_compress_level']           = 6;
  $settings['redis.connection']['base']       = 0;
  $settings['cache_prefix']['default']        = $_SERVER['REDIS_PREFIX'];
  $settings['container_yamls'][]              = 'modules/redis/example.services.yml';

  // Put queues into default database.
  $settings['queue_default'] = 'queue.database';

  if (isset($_SERVER['REDIS_PREFIX'])) {
    $settings['cache_prefix']['default'] = $_SERVER['REDIS_PREFIX'];
  }

  if ($_SERVER['REDIS_PASSWORD']) {
    $settings['redis.connection']['password'] = $_SERVER['REDIS_PASSWORD'];
  }

  $settings['container_yamls'][] = 'modules/contrib/redis/example.services.yml';
}

$config['symfony_mailer.mailer_transport.smtp']['plugin'] = 'smtp';
$config['symfony_mailer.mailer_transport.smtp']['configuration']['user'] = $_SERVER['MAILER_USER'];
$config['symfony_mailer.mailer_transport.smtp']['configuration']['pass'] = $_SERVER['MAILER_PASSWORD'];
$config['symfony_mailer.mailer_transport.smtp']['configuration']['host'] = $_SERVER['MAILER_HOST'];
$config['symfony_mailer.mailer_transport.smtp']['configuration']['query']['local_domain'] = $_SERVER['MAILER_HOST'];
$config['symfony_mailer.mailer_transport.smtp']['configuration']['query']['verify_peer'] = ($_SERVER['MAILER_TLS'] ?? FALSE) === 'true';
$config['symfony_mailer.mailer_transport.smtp']['configuration']['port'] = $_SERVER['MAILER_PORT'];

// DDEV-specific settings. Only modify if required by DDEV.
if (isset($_SERVER['IS_DDEV_PROJECT']) && $_SERVER['IS_DDEV_PROJECT'] === 'true') {
  // $settings['config_readonly'] = FALSE;
  $settings['skip_permissions_hardening'] = TRUE;
  $settings['trusted_host_patterns'] = ['.*'];
  if (empty($_SERVER['DDEV_PHP_VERSION'])) {
    $host = "127.0.0.1";
    $port = 32796;
  }

  $config['system.performance']['cache']['page']['max_age'] = 0;
  $config['system.performance']['css']['preprocess'] = FALSE;
  $config['system.performance']['js']['preprocess'] = FALSE;
  $config['advagg.settings']['enabled'] = FALSE;
}

// Report all errors in dev and preproduction server but not in pro.
if ($_SERVER['ENVIRONMENT'] === 'pro') {
  ini_set('error_reporting', 0);
} else {
  ini_set('error_reporting', E_ALL);
}

if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}

// Automatically generated include for settings managed by ddev.
$ddev_settings = dirname(__FILE__) . '/settings.ddev.php';
if (getenv('IS_DDEV_PROJECT') == 'true' && is_readable($ddev_settings)) {
  require $ddev_settings;
}
