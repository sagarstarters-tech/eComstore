<?php
include 'admin_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $title = $conn->real_escape_string($_POST['title']);
        $slug = $conn->real_escape_string($_POST['slug']);
        
        // If slug is empty, generate it
        if (empty($slug)) {
            $slug = time() . '-' . substr(preg_replace('/[^a-z0-9]+/i', '-', strtolower($title)), 0, 50);
        } else {
            $slug = substr(preg_replace('/[^a-z0-9]+/i', '-', strtolower($slug)), 0, 100);
        }

        $content = $conn->real_escape_string($_POST['content']);
        $meta_title = $conn->real_escape_string($_POST['meta_title']);
        $meta_desc = $conn->real_escape_string($_POST['meta_description']);
        
        $conn->query("INSERT INTO pages (slug, title, content, meta_title, meta_description) VALUES ('$slug', '$title', '$content', '$meta_title', '$meta_desc')");
        $success = "Page added successfully.";
    } elseif ($action === 'edit') {
        $id = intval($_POST['id']);
        $title = $conn->real_escape_string($_POST['title']);
        $slug = $conn->real_escape_string($_POST['slug']);
        $slug = substr(preg_replace('/[^a-z0-9]+/i', '-', strtolower($slug)), 0, 100);

        $content = $conn->real_escape_string($_POST['content']);
        $meta_title = $conn->real_escape_string($_POST['meta_title']);
        $meta_desc = $conn->real_escape_string($_POST['meta_description']);
        
        $conn->query("UPDATE pages SET title='$title', slug='$slug', content='$content', meta_title='$meta_title', meta_description='$meta_desc' WHERE id=$id");
        $success = "Page updated successfully.";
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM pages WHERE id=$id");
        $success = "Page deleted successfully.";
    }
}

$pages = $conn->query("SELECT * FROM pages ORDER BY id DESC");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Manage Pages</h4>
    <button class="btn btn-primary btn-custom px-4" data-mdb-toggle="modal" data-mdb-target="#addPageModal">
        <i class="fas fa-plus me-2"></i>Add Page
    </button>
</div>

<?php if(isset($success)): ?>
    <div class="alert alert-success py-2"><?php echo $success; ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Title</th>
                        <th>Slug / URL</th>
                        <th>Updated Date</th>
                        <th class="pe-4 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($pages && $pages->num_rows > 0): ?>
                        <?php while($p = $pages->fetch_assoc()): ?>
                        <tr>
                            <td class="ps-4 fw-bold">#<?php echo $p['id']; ?></td>
                            <td class="fw-bold text-primary"><?php echo htmlspecialchars($p['title']); ?></td>
                            <td><span class="badge bg-secondary"><?php echo store_url('page.php?slug=' . htmlspecialchars($p['slug'])); ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($p['created_at'])); ?></td>
                            <td class="pe-4 text-end">
                                <a href="<?php echo store_url('page.php?slug=' . $p['slug']); ?>" target="_blank" class="btn btn-info btn-sm btn-custom mb-1 px-3 me-1" title="View Page">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                                <button class="btn btn-primary btn-sm btn-custom mb-1 px-3 me-1 edit-page-btn" 
                                    data-id="<?php echo $p['id']; ?>"
                                    data-title="<?php echo htmlspecialchars($p['title']); ?>"
                                    data-slug="<?php echo htmlspecialchars($p['slug']); ?>"
                                    data-meta-title="<?php echo htmlspecialchars($p['meta_title']); ?>"
                                    data-meta-desc="<?php echo htmlspecialchars($p['meta_description']); ?>"
                                    data-content="<?php echo htmlspecialchars($p['content']); ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" class="d-inline-block" onsubmit="return confirm('Delete this custom page permanently?');">
    <?php echo csrf_input(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm btn-custom mb-1 px-3"><i class="fas fa-trash-alt"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No custom pages found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Page Modal -->
<div class="modal fade" id="addPageModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold">Create New Page</h5>
                <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
            </div>
            <form method="POST">
    <?php echo csrf_input(); ?>
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold">Page Title</label>
                            <input type="text" name="title" class="form-control form-control-lg bg-light" placeholder="e.g. Terms & Conditions" required>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold">Slug / URL <small class="text-muted">(Auto-generated if empty)</small></label>
                            <input type="text" name="slug" class="form-control form-control-lg bg-light" placeholder="e.g. terms-conditions">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <label class="form-label fw-bold">Meta Title (SEO) <small class="text-muted">Optional</small></label>
                            <input type="text" name="meta_title" class="form-control form-control-lg bg-light">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">Meta Description (SEO) <small class="text-muted">Optional</small></label>
                        <textarea name="meta_description" class="form-control bg-light" rows="2"></textarea>
                        <small class="text-muted">150-160 characters recommended.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Page Content (HTML supported)</label>
                        <textarea name="content" class="form-control tinymce-editor" rows="15"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light btn-custom" data-mdb-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-custom px-4">ADD PAGE</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Page Modal -->
<div class="modal fade" id="editPageModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold">Edit Page</h5>
                <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
            </div>
            <form method="POST">
    <?php echo csrf_input(); ?>
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_page_id">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold">Page Title</label>
                            <input type="text" name="title" id="edit_page_title" class="form-control form-control-lg bg-light" required>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold">Slug / URL</label>
                            <input type="text" name="slug" id="edit_page_slug" class="form-control form-control-lg bg-light" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <label class="form-label fw-bold">Meta Title (SEO) <small class="text-muted">Optional</small></label>
                            <input type="text" name="meta_title" id="edit_page_meta_title" class="form-control form-control-lg bg-light">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">Meta Description (SEO) <small class="text-muted">Optional</small></label>
                        <textarea name="meta_description" id="edit_page_meta_desc" class="form-control bg-light" rows="2"></textarea>
                        <small class="text-muted">150-160 characters recommended.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Page Content (HTML supported)</label>
                        <textarea name="content" id="edit_page_content" class="form-control tinymce-editor" rows="15"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light btn-custom" data-mdb-dismiss="modal">Cancel</button>
                    <a id="preview_page_link" href="#" target="_blank" class="btn btn-info btn-custom px-4 ms-2">
                        <i class="fas fa-eye me-2"></i>PREVIEW
                    </a>
                    <button type="submit" class="btn btn-primary btn-custom px-4 ms-2">UPDATE PAGE</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.tiny.cloud/1/qn6rzypwpowh62jc3ief34t52mgy2fxxiy87zxzp3ps6a03l/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<style>
    /* Hide the TinyMCE API Key Warning Notice */
    .tox-notifications-container {
        display: none !important;
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize TinyMCE
    tinymce.init({
        selector: '.tinymce-editor',
        height: 400,
        plugins: 'advlist autolink lists link image charmap preview anchor pagebreak',
        toolbar_mode: 'floating',
        toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image'
    });

    const editBtns = document.querySelectorAll('.edit-page-btn');
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const title = this.dataset.title;
            const slug = this.dataset.slug;
            
            document.getElementById('edit_page_id').value = id;
            document.getElementById('edit_page_title').value = title;
            document.getElementById('edit_page_slug').value = slug;
            document.getElementById('edit_page_meta_title').value = this.dataset.metaTitle;
            document.getElementById('edit_page_meta_desc').value = this.dataset.metaDesc;
            
            // Set Preview link
            const previewBtn = document.getElementById('preview_page_link');
            if (previewBtn) {
                previewBtn.href = '<?php echo store_url("page.php?slug="); ?>' + slug;
            }

            // Set TinyMCE content
            if (tinymce.get('edit_page_content')) {
                tinymce.get('edit_page_content').setContent(this.dataset.content);
            } else {
                document.getElementById('edit_page_content').value = this.dataset.content;
            }
            
            var modal = new mdb.Modal(document.getElementById('editPageModal'));
            modal.show();
        });
    });
});
</script>

<?php include 'admin_footer.php'; ?>
