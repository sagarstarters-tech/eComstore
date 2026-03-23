<?php
/**
 * CategoryController
 * Wraps existing manage_categories.php logic in MVC pattern.
 */
require_once __DIR__ . '/../../core/BaseController.php';

class CategoryController extends BaseController
{
    private const UPLOAD_DIR = __DIR__ . '/../../../assets/images/';

    public function handle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $this->post('action');
            match ($action) {
                'add'    => $this->doAdd(),
                'edit'   => $this->doEdit(),
                'delete' => $this->doDelete(),
                default  => null,
            };
        }
        $this->renderList();
    }

    private function doAdd(): void
    {
        $name  = $this->post('name');
        $slug  = $this->post('slug') ?: strtolower(preg_replace('/[^a-z0-9-]+/i', '-', $name));
        $image = $this->handleImageUpload();

        $stmt = $this->conn->prepare(
            "INSERT INTO categories (name, slug, image) VALUES (?, ?, ?)"
        );
        $stmt->bind_param('sss', $name, $slug, $image);
        $stmt->execute();
        $stmt->close();
        $this->flash('success', 'Category added successfully.');
    }

    private function doEdit(): void
    {
        $id    = intval($this->post('id'));
        $name  = $this->post('name');
        $slug  = $this->post('slug') ?: strtolower(preg_replace('/[^a-z0-9-]+/i', '-', $name));
        $image = $this->handleImageUpload();

        if ($image) {
            $stmt = $this->conn->prepare("UPDATE categories SET name=?, slug=?, image=? WHERE id=?");
            $stmt->bind_param('sssi', $name, $slug, $image, $id);
        } else {
            $stmt = $this->conn->prepare("UPDATE categories SET name=?, slug=? WHERE id=?");
            $stmt->bind_param('ssi', $name, $slug, $id);
        }
        $stmt->execute();
        $stmt->close();
        $this->flash('success', 'Category updated successfully.');
    }

    private function doDelete(): void
    {
        $id   = intval($this->post('id'));
        $stmt = $this->conn->prepare("DELETE FROM categories WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        $this->flash('success', 'Category deleted.');
    }

    private function handleImageUpload(): string
    {
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) return '';
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) return '';
        $name = uniqid('cat_') . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], self::UPLOAD_DIR . $name);
        return $name;
    }

    private function renderList(): void
    {
        $cats   = [];
        $result = $this->conn->query("SELECT * FROM categories ORDER BY name ASC");
        if ($result) while ($r = $result->fetch_assoc()) $cats[] = $r;

        $this->render(__DIR__ . '/views/list.php', [
            'cats'  => $cats,
            'flash' => $this->getFlash(),
        ]);
    }
}
