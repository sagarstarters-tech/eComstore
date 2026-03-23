<?php
/**
 * Admin Helper: URL Generation
 *
 * Centralizes admin URL building for clean, consistent links.
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2)); // /store/admin/helpers -> /store
}
if (!defined('CONFIG_PATH')) {
    define('CONFIG_PATH', BASE_PATH . '/config/');
}
require_once CONFIG_PATH . 'config.php';

/**
 * Generate an admin URL.
 *
 * @param string $page    Admin PHP filename (e.g. 'manage_products.php')
 * @param array  $params  Query parameters
 * @return string
 */
function admin_url(string $page = '', array $params = []): string
{
    $url = ADMIN_BASE_URL . ltrim($page, '/');
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    return $url;
}

/**
 * Generate a store (frontend) URL.
 */
function store_url(string $path = ''): string
{
    return STORE_BASE_URL . ltrim($path, '/');
}
