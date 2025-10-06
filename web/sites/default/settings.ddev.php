<?php

$databases['default']['default'] = [
  'driver' => 'mysql',
  'database' => $_ENV['DB_NAME'] ?? 'db',
  'username' => $_ENV['DB_USER'] ?? 'db',
  'password' => $_ENV['DB_PASS'] ?? 'db',
  'host' => $_ENV['DB_HOST'] ?? 'db',
  'port' => (int) ($_ENV['DB_PORT'] ?? 3306),
  'prefix' => '',
  'collation' => 'utf8mb4_general_ci',
];

if (!empty($_ENV['DRUPAL_HASH_SALT'])) {
  $settings['hash_salt'] = $_ENV['DRUPAL_HASH_SALT'];
}

$settings['file_private_path'] = $app_root . '/' . $site_path . '/files-private';

if (empty($settings['trusted_host_patterns'])) {
  $settings['trusted_host_patterns'] = [];
}

if (!empty($_ENV['DDEV_PRIMARY_URL'])) {
  $host = parse_url($_ENV['DDEV_PRIMARY_URL'], PHP_URL_HOST);
  if (!empty($host)) {
    $settings['trusted_host_patterns'][] = '^' . preg_quote($host, '#') . '$';
  }
}

$settings['trusted_host_patterns'][] = '^localhost$';
