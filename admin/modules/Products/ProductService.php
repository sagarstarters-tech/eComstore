<?php
/**
 * ProductService
 * Business logic for product management.
 */
class ProductService
{
    private $conn;
    private ProductRepository $repo;

    public function __construct(mysqli $conn, ProductRepository $repo)
    {
        $this->conn = $conn;
        $this->repo = $repo;
    }

    /**
     * Process product image upload.
     * Returns filename string or empty string.
     */
    public function handleImageUpload(array $file, string $upload_dir): string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) return '';
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) return '';
        $name = uniqid('img_') . '.' . $ext;
        move_uploaded_file($file['tmp_name'], $upload_dir . $name);
        return $name;
    }

    /**
     * Handle multiple gallery image uploads.
     * Returns array of saved filenames.
     */
    public function handleGalleryUpload(array $files, string $upload_dir): array
    {
        $saved = [];
        if (empty($files['name'][0])) return $saved;
        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
            $ext  = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
            $name = uniqid('gal_') . '.' . $ext;
            if (move_uploaded_file($files['tmp_name'][$i], $upload_dir . $name)) {
                $saved[] = $name;
            }
        }
        return $saved;
    }

    /**
     * Generate a URL slug from a string.
     */
    public function generateSlug(string $slug, string $name): string
    {
        if (!empty($slug)) {
            return strtolower(preg_replace('/[^a-z0-9-]+/', '-', strtolower($slug)));
        }
        return time() . '-' . substr(preg_replace('/[^a-z0-9-]+/', '-', strtolower($name)), 0, 50);
    }

    /**
     * Delete an image file safely.
     */
    public function deleteImageFile(string $filename, string $upload_dir): void
    {
        if ($filename && file_exists($upload_dir . $filename)) {
            @unlink($upload_dir . $filename);
        }
    }

    /**
     * Save SEO metadata for a product.
     * Uses the existing SeoRepository from includes/.
     */
    public function saveSeoMetadata(array $post_data, int $product_id): void
    {
        require_once __DIR__ . '/../../../includes/SeoRepository.php';
        $seoRepo = new SeoRepository($this->conn);
        $seoRepo->saveMetadata([
            'entity_type'      => 'product',
            'entity_id'        => $product_id,
            'meta_title'       => $post_data['seo_title'] ?? '',
            'meta_description' => $post_data['meta_description'] ?? '',
            'focus_keyword'    => $post_data['focus_keyword'] ?? '',
        ]);
    }

    public function getRepo(): ProductRepository
    {
        return $this->repo;
    }
}
