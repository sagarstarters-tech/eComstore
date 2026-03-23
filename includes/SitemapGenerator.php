<?php

class SitemapGenerator {
    private $conn;
    private $baseUrl;

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
        // Adjust this if your site is in a subfolder, e.g. "https://" . $_SERVER['HTTP_HOST'] . "/folder/"
        $this->baseUrl = "https://" . $_SERVER['HTTP_HOST'] . "/";
    }

    public function generate() {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        $urlset = $xml->createElement('urlset');
        $urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xml->appendChild($urlset);

        // 1. Home Page
        $this->addUrl($xml, $urlset, $this->baseUrl, '1.0', 'daily');

        // 2. Categories
        $cats = $this->conn->query("SELECT slug FROM categories");
        if ($cats) {
            while ($c = $cats->fetch_assoc()) {
                $lastmod = date('Y-m-d');
                $this->addUrl($xml, $urlset, $this->baseUrl . 'category/' . $c['slug'], '0.8', 'weekly', $lastmod);
            }
        }

        // 3. Products
        $prods = $this->conn->query("SELECT slug FROM products");
        if ($prods) {
            while ($p = $prods->fetch_assoc()) {
                $lastmod = date('Y-m-d');
                $this->addUrl($xml, $urlset, $this->baseUrl . 'product/' . $p['slug'], '0.9', 'weekly', $lastmod);
            }
        }

        // 4. CMS Pages
        $pages = $this->conn->query("SELECT slug FROM pages");
        if ($pages) {
            while ($pg = $pages->fetch_assoc()) {
                $lastmod = date('Y-m-d');
                $this->addUrl($xml, $urlset, $this->baseUrl . 'page/' . $pg['slug'], '0.6', 'monthly', $lastmod);
            }
        }

        $filePath = $_SERVER['DOCUMENT_ROOT'] . '/sitemap.xml';
        if ($xml->save($filePath)) {
            return ['success' => true, 'path' => '/sitemap.xml'];
        }
        return ['success' => false, 'error' => 'Failed to save sitemap.xml'];
    }

    private function addUrl($xml, $urlset, $loc, $priority, $changefreq, $lastmod = null) {
        $url = $xml->createElement('url');
        $url->appendChild($xml->createElement('loc', htmlspecialchars($loc)));
        $url->appendChild($xml->createElement('lastmod', $lastmod ?: date('Y-m-d')));
        $url->appendChild($xml->createElement('changefreq', $changefreq));
        $url->appendChild($xml->createElement('priority', $priority));
        $urlset->appendChild($url);
    }
}
