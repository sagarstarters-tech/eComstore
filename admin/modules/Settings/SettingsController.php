<?php
/**
 * SettingsController
 * Wraps manage_settings.php tabs.
 * Delegates all tab-specific UI rendering to the original view.
 */
require_once __DIR__ . '/../../core/BaseController.php';

class SettingsController extends BaseController
{
    public function handle(): void
    {
        // All POST handling exists in the original manage_settings.php which
        // this controller wraps. We delegate rendering to the existing view file
        // which already works correctly and has all the tabbed UI.
        // This controller's role: enforce auth and provide the module entry point.
        $this->render(__DIR__ . '/views/index.php', []);
    }
}
