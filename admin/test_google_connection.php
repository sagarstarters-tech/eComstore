<?php
require_once 'core/AuthMiddleware.php';
if (!AuthMiddleware::isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Session Role: ' . ($_SESSION['role'] ?? 'NONE')]);
    exit;
}
header('Content-Type: application/json');
require_once '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$client_id = $_POST['google_client_id'] ?? '';
$client_secret = $_POST['google_client_secret'] ?? '';

if (empty($client_id)) {
    echo json_encode(['success' => false, 'message' => 'Client ID is missing.']);
    exit;
}

// Just checking if we can fetch Google's discovery doc is a basic network test
// A true validation of Client ID & Secret requires an active OAuth token exchange, but we can't do that without a user consent code here.
// But we can check if it forms a valid request to Google's Token endpoint when trying to fetch a dummy token, resulting in specific expected Google Error.

$url = 'https://oauth2.googleapis.com/token';

$data = [
    'client_id' => $client_id,
    'client_secret' => $client_secret,
    'grant_type' => 'authorization_code',
    'code' => 'dummy_code',
    'redirect_uri' => 'http://localhost'
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($response === false) {
    echo json_encode(['success' => false, 'message' => 'cURL Error: ' . $error]);
    exit;
}

$json = json_decode($response, true);

// If client_id is completely invalid format, Google returns 400 with error 'invalid_client'.
// If client_id is valid but secret is wrong, Google returns 401 'invalid_client'.
// If both are valid, Google returns 400 'invalid_grant' because 'dummy_code' is not a real code.

if (isset($json['error'])) {
    if ($json['error'] === 'invalid_grant') {
        echo json_encode(['success' => true, 'message' => 'Connection Successful! (Client ID and Secret appear valid)']);
    } else if ($json['error'] === 'invalid_client') {
        echo json_encode(['success' => false, 'message' => 'Configuration Error: Invalid Client ID or Secret.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Configuration Error: ' . $json['error_description']]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Unexpected response from Google.']);
}
?>
