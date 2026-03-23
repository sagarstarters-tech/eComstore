<?php
/**
 * TrackingAPI.php
 * Global entry point for the Order Tracking AJAX endpoints.
 * Should be placed in tracking_module_src/
 */

session_start();

require_once __DIR__ . '/src/Config/TrackingConfig.php';
require_once __DIR__ . '/src/Repositories/TrackingRepository.php';
require_once __DIR__ . '/src/Services/TrackingService.php';
require_once __DIR__ . '/src/Controllers/TrackingController.php';

// Instantiate and route the request
$controller = new \TrackingModule\Controllers\TrackingController();
$controller->handleRequest();
