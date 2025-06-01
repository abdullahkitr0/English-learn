<?php
require_once '../config/config.php';
require_once '../functions/functions.php';

session_start();

// Admin kontrolü
requireAdmin();

$pageTitle = "Kategori Yönetimi - Admin Panel";
include '../includes/header/header.php';

try {
    $db = dbConnect();
    // Kategorileri ve kelime sayılarını al
    $categories = $db->query("
        SELECT c.*, 
               (SELECT COUNT(*) FROM words WHERE category_id = c.id) as word_count,
               p.name as parent_name
        FROM categories c
        LEFT JOIN categories p ON c.parent_id = p.id
        ORDER BY c.name
    ")->fetchAll();
} catch (PDOException $e) {
    $error = "Kategoriler yüklenirken bir hata oluştu.";
}
?>

<div class="container-xl">
    <div class="page-header d-print-none">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">Kategori Yönetimi</h2>
            </div>
            <div class="col-auto ms-auto">
                <button class="btn btn-primary" onclick="showAddCategoryModal()">
                    <i class="ti ti-plus"></i> Yeni Kategori Ekle
                </button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Kategori Adı</th>
                            <th>Açıklama</th>
                            <th>Üst Kategori</th>
                            <th>Kelime Sayısı</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo $category['id']; ?></td>
                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                            <td><?php echo htmlspecialchars($category['description'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($category['parent_name'] ?? '-'); ?></td>
                            <td>
                                <span class="badge"><?php echo $category['word_count']; ?> kelime</span>
                            </td>
                            <td>
                                <div class="btn-list">
                                    <button class="btn btn-sm btn-warning" 
                                            onclick="showEditCategoryModal(<?php echo htmlspecialchars(json_encode($category)); ?>)">
                                        <i class="ti ti-edit"></i> Düzenle
                                    </button>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="deleteCategory(<?php echo $category['id']; ?>)">
                                        <i class="ti ti-trash"></i> Sil
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Kategori Ekleme/Düzenleme Modalı -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Kategori Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="categoryForm">
                    <input type="hidden" id="categoryId" name="id">
                    <div class="mb-3">
                        <label class="form-label">Kategori Adı</label>
                        <input type="text" class="form-control" id="categoryName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea class="form-control" id="categoryDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Üst Kategori</label>
                        <select class="form-select" id="parentCategory" name="parent_id">
                            <option value="">Üst Kategori Seçin</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="saveCategory()">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<script>
let categoryModal;

document.addEventListener('DOMContentLoaded', function() {
    categoryModal = new bootstrap.Modal(document.getElementById('categoryModal'));
});

function showAddCategoryModal() {
    document.getElementById('modalTitle').textContent = 'Yeni Kategori Ekle';
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryId').value = '';
    categoryModal.show();
}

function showEditCategoryModal(category) {
    document.getElementById('modalTitle').textContent = 'Kategori Düzenle';
    document.getElementById('categoryId').value = category.id;
    document.getElementById('categoryName').value = category.name;
    document.getElementById('categoryDescription').value = category.description || '';
    document.getElementById('parentCategory').value = category.parent_id || '';
    categoryModal.show();
}

function saveCategory() {
    const form = document.getElementById('categoryForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);

    // parent_id ve id alanlarını uygun tipe çevir
    data.parent_id = data.parent_id ? parseInt(data.parent_id) : null;
    if (data.id === '') delete data.id;

    const url = data.id ? '../api/update-category.php' : '../api/add-category.php';
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showToast('success', result.message);
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showToast('error', result.message);
        }
    })
    .catch(error => {
        showToast('error', 'Bir hata oluştu: ' + error.message);
    })
    .finally(() => {
        categoryModal.hide();
    });
}

function deleteCategory(id) {
    if (!confirm('Bu kategoriyi silmek istediğinizden emin misiniz?')) {
        return;
    }
    
    fetch('../api/delete-category.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showToast('success', result.message);
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showToast('error', result.message);
        }
    })
    .catch(error => {
        showToast('error', 'Bir hata oluştu: ' + error.message);
    });
}

function showToast(type, message) {
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    const container = document.createElement('div');
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.appendChild(toast);
    document.body.appendChild(container);
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => {
        container.remove();
    });
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include '../includes/footer/footer.php'; ?> 