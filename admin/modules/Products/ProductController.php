<?php
require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/ProductRepository.php';
require_once __DIR__ . '/ProductService.php';

class ProductController extends BaseController
{
    private const UPLOAD_DIR = __DIR__ . '/../../../assets/images/';
    private ProductRepository $repo;
    private ProductService $service;

    public function __construct(mysqli $conn, array $global_settings, string $global_currency)
    {
        parent::__construct($conn, $global_settings, $global_currency);
        $this->repo    = new ProductRepository($conn);
        $this->service = new ProductService($conn, $this->repo);
    }

    public function handle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost();
        }
        $this->renderList();
    }

    private function handlePost(): void
    {
        $action = $this->post('action');

        switch ($action) {
            case 'add':             $this->doAdd();           break;
            case 'edit':            $this->doEdit();          break;
            case 'delete':          $this->doDelete();        break;
            case 'delete_gallery_image': $this->doDeleteGalleryImage(); break;
        }
    }

    private function doAdd(): void
    {
        $slug    = $this->service->generateSlug($this->post('slug'), $this->post('name'));
        $image   = '';
        if (isset($_FILES['image'])) {
            $image = $this->service->handleImageUpload($_FILES['image'], self::UPLOAD_DIR);
        }

        $data = [
            'name'             => $this->post('name'),
            'slug'             => $slug,
            'description'      => $this->post('description'),
            'meta_description' => $this->post('meta_description'),
            'category_id'      => intval($this->post('category_id')),
            'price'            => floatval($this->post('price')),
            'stock'            => intval($this->post('stock')),
            'image'            => $image,
            'image_fit'        => $this->post('image_fit', 'cover'),
        ];

        if ($this->repo->insert($data)) {
            $product_id = $this->repo->getLastInsertId();
            $this->service->saveSeoMetadata($_POST, $product_id);

            // Gallery
            if (isset($_FILES['gallery'])) {
                $gallery_files = $this->service->handleGalleryUpload($_FILES['gallery'], self::UPLOAD_DIR);
                foreach ($gallery_files as $gf) {
                    $this->repo->insertGalleryImage($product_id, $gf);
                }
            }
            $this->flash('success', 'Product added successfully.');
        }
    }

    private function doEdit(): void
    {
        $id = intval($this->post('id'));

        $image = '';
        if (isset($_FILES['image'])) {
            $new_image = $this->service->handleImageUpload($_FILES['image'], self::UPLOAD_DIR);
            if ($new_image) {
                // Delete old image
                $existing = $this->repo->getById($id);
                if ($existing) $this->service->deleteImageFile($existing['image'], self::UPLOAD_DIR);
                $image = $new_image;
            }
        }

        $data = [
            'name'             => $this->post('name'),
            'slug'             => $this->service->generateSlug($this->post('slug'), $this->post('name')),
            'description'      => $this->post('description'),
            'meta_description' => $this->post('meta_description'),
            'category_id'      => intval($this->post('category_id')),
            'price'            => floatval($this->post('price')),
            'stock'            => intval($this->post('stock')),
            'image'            => $image,
            'image_fit'        => $this->post('image_fit', 'cover'),
        ];

        if ($this->repo->update($id, $data)) {
            $this->service->saveSeoMetadata($_POST, $id);

            if (isset($_FILES['gallery'])) {
                $gallery_files = $this->service->handleGalleryUpload($_FILES['gallery'], self::UPLOAD_DIR);
                foreach ($gallery_files as $gf) {
                    $this->repo->insertGalleryImage($id, $gf);
                }
            }
            $this->flash('success', 'Product updated successfully.');
        }
    }

    private function doDelete(): void
    {
        $id      = intval($this->post('id'));
        $product = $this->repo->getById($id);

        if ($product) {
            $this->service->deleteImageFile($product['image'], self::UPLOAD_DIR);
            foreach ($this->repo->getGalleryImages($id) as $gi) {
                $this->service->deleteImageFile($gi['image'], self::UPLOAD_DIR);
            }
        }
        $this->repo->delete($id);
        $this->flash('success', 'Product deleted successfully.');
    }

    private function doDeleteGalleryImage(): void
    {
        $img_id = intval($this->post('image_id'));
        $img    = $this->repo->getGalleryImageById($img_id);
        if ($img) {
            $this->service->deleteImageFile($img['image'], self::UPLOAD_DIR);
            $this->repo->deleteGalleryImage($img_id);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    private function renderList(): void
    {
        $page   = max(1, intval($this->get('page', '1')));
        $limit  = 10;
        $offset = ($page - 1) * $limit;

        $cats_result = $this->conn->query("SELECT * FROM categories ORDER BY name ASC");
        $cats        = [];
        if ($cats_result) while ($c = $cats_result->fetch_assoc()) $cats[] = $c;

        $data = [
            'products'       => $this->repo->getAll($limit, $offset),
            'total_pages'    => ceil($this->repo->getCount() / $limit),
            'page'           => $page,
            'cats'           => $cats,
            'has_cats'       => !empty($cats),
            'product_images' => $this->repo->getAllGalleryGrouped(),
            'product_seo'    => $this->repo->getAllSeoGrouped(),
            'flash'          => $this->getFlash(),
        ];

        $this->render(__DIR__ . '/views/list.php', $data);
    }
}
