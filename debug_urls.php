<?php
define('BASE_PATH', __DIR__);
require 'config/config.php';
echo "SITE_URL: '" . SITE_URL . "'\n";
echo "STORE_BASE_URL: '" . STORE_BASE_URL . "'\n";
echo "getenv('SITE_URL'): '" . getenv('SITE_URL') . "'\n";
echo "store_url('test'): '" . store_url('test') . "'\n";
