<?php
/**
 * Admin Helper: CSRF Protection
 *
 * Usage:
 *   In every form: <?php echo csrf_input(); ?>
 *   On POST handling: csrf_verify();
 */

/**
 * Generate and store a CSRF token in session.
 * Returns the token string.
 */
function csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        include_once __DIR__ . '/../../includes/session_setup.php';
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden CSRF input field.
 */
function csrf_input(): string
{
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return "<input type=\"hidden\" name=\"_csrf_token\" value=\"{$token}\">";
}

/**
 * Verify the CSRF token from POST data.
 * Exits with 403 if invalid.
 */
function csrf_verify(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        include_once __DIR__ . '/../../includes/session_setup.php';
    }
    $submitted = $_POST['_csrf_token'] ?? '';
    $stored    = $_SESSION['csrf_token'] ?? '';

    if (empty($stored) || !hash_equals($stored, $submitted)) {
        http_response_code(403);
        die('<h1>403 Forbidden</h1><p>Security session mismatch (CSRF). This often happens if your session timed out or you have multiple tabs open. Please refresh the page and try again.</p>');
    }
}
