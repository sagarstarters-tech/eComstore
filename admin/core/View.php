<?php
/**
 * View Renderer
 * Simple helper to load a view file with extracted data.
 * Used for standalone renders that don't go through BaseController.
 */
class View
{
    /**
     * Render a view file with extracted variables.
     *
     * @param string $viewPath  Absolute path to view file
     * @param array  $data      Associative array to extract into view scope
     */
    public static function render(string $viewPath, array $data = []): void
    {
        if (!file_exists($viewPath)) {
            trigger_error("View not found: $viewPath", E_USER_WARNING);
            return;
        }
        extract($data, EXTR_SKIP);
        include $viewPath;
    }
}
