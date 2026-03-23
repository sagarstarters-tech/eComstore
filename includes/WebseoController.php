<?php
require_once 'SeoRepository.php';
require_once 'SitemapGenerator.php';

class WebseoController {
    private $conn;
    private $repo;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->repo = new SeoRepository($conn);
    }

    /**
     * Save global SEO settings.
     */
    public function saveGlobalSettings($settings) {
        foreach ($settings as $key => $value) {
            $this->repo->updateGlobalSetting($key, $value);
        }
        return ['success' => true];
    }

    /**
     * Save entity-specific SEO metadata.
     */
    public function saveMetadata($data) {
        if ($this->repo->saveMetadata($data)) {
            return ['success' => true];
        }
        return ['success' => false, 'error' => $this->conn->error];
    }

    /**
     * Get SEO Audit Report.
     */
    public function getSeoAudit() {
        $report = [
            'missing_title' => [],
            'missing_description' => [],
            'total_indexed' => 0
        ];

        // Audit Pages
        $pages = $this->conn->query("SELECT id, title, slug FROM pages");
        while ($p = $pages->fetch_assoc()) {
            $meta = $this->repo->getMetadata('page', $p['id']);
            if (!$meta || empty($meta['meta_title'])) $report['missing_title'][] = 'Page: ' . $p['title'];
            if (!$meta || empty($meta['meta_description'])) $report['missing_description'][] = 'Page: ' . $p['title'];
            $report['total_indexed']++;
        }

        // Audit Products
        $prods = $this->conn->query("SELECT id, name FROM products");
        while ($p = $prods->fetch_assoc()) {
            $meta = $this->repo->getMetadata('product', $p['id']);
            if (!$meta || empty($meta['meta_title'])) $report['missing_title'][] = 'Product: ' . $p['name'];
            if (!$meta || empty($meta['meta_description'])) $report['missing_description'][] = 'Product: ' . $p['name'];
            $report['total_indexed']++;
        }

        return $report;
    }

    /**
     * Save robots.txt content.
     */
    public function saveRobotsTxt($content) {
        $filePath = BASE_PATH . '/robots.txt';
        if (file_put_contents($filePath, $content) !== false) {
            return ['success' => true];
        }
        return ['success' => false, 'error' => 'Could not write to robots.txt'];
    }

    /**
     * Get robots.txt content.
     */
    public function getRobotsTxt() {
        $filePath = BASE_PATH . '/robots.txt';
        if (file_exists($filePath)) {
            return file_get_contents($filePath);
        }
        return "User-agent: *\nDisallow: /admin/\nSitemap: https://" . $_SERVER['HTTP_HOST'] . "/sitemap.xml";
    }

    /**
     * Generate fresh XML Sitemap.
     */
    public function generateSitemap() {
        $generator = new SitemapGenerator($this->conn);
        return $generator->generate();
    }
}
