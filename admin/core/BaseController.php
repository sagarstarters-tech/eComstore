<?php
/**
 * BaseController
 * Abstract base class for all admin module controllers.
 * Provides shared utilities: DB connection, settings, view rendering.
 */
abstract class BaseController
{
    /** @var mysqli */
    protected $conn;

    /** @var array */
    protected $global_settings;

    /** @var string */
    protected $global_currency;

    /** @var string */
    protected $current_page;

    public function __construct(mysqli $conn, array $global_settings, string $global_currency)
    {
        $this->conn            = $conn;
        $this->global_settings = $global_settings;
        $this->global_currency = $global_currency;
        $this->current_page    = basename($_SERVER['PHP_SELF']);
    }

    /**
     * Main dispatcher: routes GET/POST to the correct method.
     * Subclasses must implement this.
     */
    abstract public function handle(): void;

    /**
     * Render a module view file, injecting variables into its scope.
     *
     * @param string $viewPath  Absolute path to the view file
     * @param array  $data      Variables to extract into view scope
     */
    protected function render(string $viewPath, array $data = []): void
    {
        // Always inject shared globals into the view
        $data['conn']            = $this->conn;
        $data['global_settings'] = $this->global_settings;
        $data['global_currency'] = $this->global_currency;
        $data['current_page']    = $this->current_page;

        extract($data, EXTR_SKIP);
        include $viewPath;
    }

    /**
     * Redirect to a URL and exit.
     */
    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Get a POST value safely with optional default.
     */
    protected function post(string $key, $default = ''): string
    {
        return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default;
    }

    /**
     * Get a GET value safely with optional default.
     */
    protected function get(string $key, $default = ''): string
    {
        return isset($_GET[$key]) ? trim((string)$_GET[$key]) : $default;
    }

    /**
     * Set a flash message in session.
     */
    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    /**
     * Get and clear the flash message from session.
     */
    protected function getFlash(): ?array
    {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
}
