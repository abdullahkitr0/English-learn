<?php
require_once '../config/config.php';
require_once '../functions/functions.php';

session_start();

// Admin kontrolü
requireAdmin();

$pageTitle = "Kullanıcı Yönetimi - Admin Panel";
include '../includes/header/header.php';

try {
    $db = dbConnect();
    
    // Kullanıcıları ve istatistiklerini al
    $users = $db->query("
        SELECT u.*,
               (SELECT COUNT(*) FROM user_words WHERE user_id = u.id) as total_words,
               (SELECT COUNT(*) FROM test_results WHERE user_id = u.id) as total_tests,
               (SELECT AVG(score) FROM test_results WHERE user_id = u.id) as average_score
        FROM users u
        ORDER BY u.created_at DESC
    ")->fetchAll();
} catch (PDOException $e) {
    $error = "Kullanıcılar yüklenirken bir hata oluştu.";
}
?>

<div class="container-xl">
    <div class="page-header d-print-none">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">Kullanıcı Yönetimi</h2>
            </div>
            <div class="col-auto ms-auto">
                <button class="btn btn-primary" onclick="showAddUserModal()">
                    <i class="ti ti-plus"></i> Yeni Kullanıcı Ekle
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
                            <th>Kullanıcı Adı</th>
                            <th>E-posta</th>
                            <th>Rol</th>
                            <th>Kelime Sayısı</th>
                            <th>Test Sayısı</th>
                            <th>Ortalama Başarı</th>
                            <th>Durum</th>
                            <th>Kayıt Tarihi</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge text-white <?php echo $user['role'] === 'admin' ? 'bg-red' : 'bg-blue'; ?>">
                                    <?php echo $user['role'] === 'admin' ? 'Yönetici' : 'Kullanıcı'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-azure text-white"><?php echo $user['total_words']; ?> kelime</span>
                            </td>
                            <td>
                                <span class="badge bg-purple text-white"><?php echo $user['total_tests']; ?> test</span>
                            </td>
                            <td>
                                <span class="badge text-white bg-<?php echo $user['average_score'] >= 70 ? 'success' : ($user['average_score'] >= 50 ? 'warning' : 'danger'); ?>">
                                    %<?php echo round($user['average_score'] ?? 0); ?>
                                </span>
                            </td>
                            <td>
                                <label class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" 
                                           <?php echo $user['is_active'] ? 'checked' : ''; ?>
                                           onchange="toggleUserStatus(<?php echo $user['id']; ?>, this.checked)"
                                           <?php echo $user['id'] === $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                </label>
                            </td>
                            <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div class="btn-list">
                                    <button class="btn btn-sm btn-warning" 
                                            onclick="showEditUserModal(<?php echo htmlspecialchars(json_encode($user)); ?>)"
                                            <?php echo $user['id'] === $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                        <i class="ti ti-edit"></i> Düzenle
                                    </button>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="deleteUser(<?php echo $user['id']; ?>)"
                                            <?php echo $user['id'] === $_SESSION['user_id'] ? 'disabled' : ''; ?>>
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

<!-- Kullanıcı Ekleme/Düzenleme Modalı -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Kullanıcı Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="userForm">
                    <input type="hidden" id="userId" name="id">
                    <div class="mb-3">
                        <label class="form-label">Kullanıcı Adı</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-posta</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Şifre</label>
                        <input type="password" class="form-control" id="password" name="password">
                        <small class="text-muted">Düzenleme sırasında boş bırakırsanız şifre değişmez.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rol</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="user">Kullanıcı</option>
                            <option value="admin">Yönetici</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-check">
                            <input type="checkbox" class="form-check-input" id="isActive" name="is_active" checked>
                            <span class="form-check-label">Aktif</span>
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="saveUser()">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<script>
let userModal;

document.addEventListener('DOMContentLoaded', function() {
    userModal = new bootstrap.Modal(document.getElementById('userModal'));
});

function showAddUserModal() {
    document.getElementById('modalTitle').textContent = 'Yeni Kullanıcı Ekle';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('password').required = true;
    userModal.show();
}

function showEditUserModal(user) {
    document.getElementById('modalTitle').textContent = 'Kullanıcı Düzenle';
    document.getElementById('userId').value = user.id;
    document.getElementById('username').value = user.username;
    document.getElementById('email').value = user.email;
    document.getElementById('password').required = false;
    document.getElementById('role').value = user.role;
    document.getElementById('isActive').checked = user.is_active == 1;
    userModal.show();
}

function saveUser() {
    const form = document.getElementById('userForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    data.is_active = formData.get('is_active') === 'on';
    
    const url = data.id ? '../api/update-user.php' : '../api/add-user.php';
    
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
        userModal.hide();
    });
}

function deleteUser(id) {
    if (!confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?')) {
        return;
    }
    
    fetch('../api/delete-user.php', {
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

function toggleUserStatus(id, status) {
    fetch('../api/toggle-user-status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ 
            id: id,
            is_active: status
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showToast('success', result.message);
        } else {
            showToast('error', result.message);
            // Başarısız olursa switch'i eski haline getir
            setTimeout(() => window.location.reload(), 1000);
        }
    })
    .catch(error => {
        showToast('error', 'Bir hata oluştu: ' + error.message);
        // Hata durumunda switch'i eski haline getir
        setTimeout(() => window.location.reload(), 1000);
    });
}

function showToast(type, message) {
    const toastContainer = document.getElementById('toastContainer');
    
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
    
    toastContainer.appendChild(toast);
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}
</script>

<?php include '../includes/footer/footer.php'; ?> 