<?php
/**
 * ShippingController.php
 * Serves as the Entry Point exactly like an API. 
 * Receives POST or GET requests, calls the service, and returns structured JSON formats.
 */

namespace ShippingModule\Controllers;

use ShippingModule\Config\ShippingConfig;
use ShippingModule\Repositories\ShippingRepository;
use ShippingModule\Services\ShippingService;

class ShippingController {
    
    private $service;

    public function __construct() {
        // Wire up the dependency injection natively
        $config = new ShippingConfig();
        $db = $config->getConnection();
        
        if (!$db) {
            $this->sendJson(['error' => 'Database connection failed'], 500);
            exit;
        }

        $repository = new ShippingRepository($db);
        $this->service = new ShippingService($repository);
    }

    /**
     * Handle the AJAX request to refresh the cart shipping totals dynamically
     */
    public function calculateCart() {
        // Read cart total securely from POST context
        // In a real application, you might verify this against session cart data 
        // to prevent users from sending arbitrary valid 'cart_total' payloads.
        $cartTotal = isset($_POST['cart_total']) ? (float)$_POST['cart_total'] : 0.00;

        try {
            // Core secure execution
            $result = $this->service->getFinalOrderTotals($cartTotal);
            $this->sendJson([
                'status' => 'success',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            $this->sendJson([
                'status' => 'error',
                'message' => 'Failed to calculate shipping: ' . $e->getMessage()
            ], 400);
        }
    }


    /**
     * Helper to return standard standardized JSON arrays
     */
    private function sendJson($data, $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// -------------------------------------------------------------
// Quick Bootstrap Routing for the API endpoint directly in this file
// If accessed directly via AJAX, handle the routing
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    
    // Autoloader fallback for direct script execution since we aren't using composer here manually
    require_once __DIR__ . '/../Config/ShippingConfig.php';
    require_once __DIR__ . '/../Repositories/ShippingRepository.php';
    require_once __DIR__ . '/../Services/ShippingService.php';

    $controller = new ShippingController();

    if ($_GET['action'] === 'calculate') {
        $controller->calculateCart();
    }
}
?>
