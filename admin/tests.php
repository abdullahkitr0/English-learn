<?php
require_once '../config/config.php';
require_once '../functions/functions.php';

session_start();

// Admin kontrolü
requireAdmin();

// Sayfa numarası ve filtreleri al
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$difficulty = isset($_GET['difficulty']) ? cleanInput($_GET['difficulty']) : '';
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';

try {
    $db = dbConnect();
    
    // Kategorileri al
    $stmt = $db->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
    
    // Test listesi için sorgu oluştur
    $sql = "SELECT t.*, 
                   c.name as category_name,
                   u.username as created_by_username,
                   COUNT(DISTINCT tr.id) as total_attempts,
                   AVG(tr.score) as average_score
            FROM tests t
            LEFT JOIN categories c ON t.category_id = c.id
            LEFT JOIN users u ON t.created_by = u.id
            LEFT JOIN test_results tr ON t.id = tr.test_id
            WHERE 1=1";
    $params = [];
    
    if ($category > 0) {
        $sql .= " AND t.category_id = ?";
        $params[] = $category;
    }
    
    if ($difficulty) {
        $sql .= " AND t.difficulty_level = ?";
        $params[] = $difficulty;
    }
    
    if ($search) {
        $sql .= " AND (t.title LIKE ? OR t.description LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $sql .= " GROUP BY t.id";
    
    // Toplam test sayısını al
    $countSql = "SELECT COUNT(*) FROM tests t WHERE 1=1";
    if ($category > 0) {
        $countSql .= " AND t.category_id = ?";
    }
    if ($difficulty) {
        $countSql .= " AND t.difficulty_level = ?";
    }
    if ($search) {
        $countSql .= " AND (t.title LIKE ? OR t.description LIKE ?)";
    }
    
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $totalItems = $stmt->fetchColumn();
    
    // Sayfalama bilgilerini hesapla
    $pagination = getPagination($totalItems, $page);
    
    // Testleri al
    $sql .= " ORDER BY t.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $pagination['per_page'];
    $params[] = $pagination['offset'];
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $tests = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Veritabanı hatası: ' . $e->getMessage();
    logError('Admin tests error: ' . $e->getMessage());
}

// Sayfa başlığı
$pageTitle = "Test Yönetimi - İngilizce Kelime Öğrenme";

// Header'ı dahil et
include '../includes/header/header.php';
?>

<div class="container-xl">
    <div class="page-header d-print-none">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">Test Yönetimi</h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <button class="btn btn-primary" onclick="openTestModal()">
                    <i class="ti ti-plus"></i>
                    Yeni Test Ekle
                </button>
            </div>
        </div>
    </div>
    
    <!-- Filtreler -->
    <div class="card mb-3">
        <div class="card-body">
            <form action="" method="get" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Kategori</label>
                    <select name="category" class="form-select">
                        <option value="">Tümü</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Zorluk Seviyesi</label>
                    <select name="difficulty" class="form-select">
                        <option value="">Tümü</option>
                        <option value="beginner" <?php echo $difficulty === 'beginner' ? 'selected' : ''; ?>>Başlangıç</option>
                        <option value="intermediate" <?php echo $difficulty === 'intermediate' ? 'selected' : ''; ?>>Orta</option>
                        <option value="advanced" <?php echo $difficulty === 'advanced' ? 'selected' : ''; ?>>İleri</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Arama</label>
                    <input type="text" name="search" class="form-control" placeholder="Test başlığı veya açıklama..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-search"></i>
                            Filtrele
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Test Listesi -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Test Başlığı</th>
                        <th>Kategori</th>
                        <th>Zorluk</th>
                        <th>İstatistikler</th>
                        <th>Oluşturan</th>
                        <th>Tarih</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tests as $test): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($test['title']); ?>
                                <?php if ($test['description']): ?>
                                    <div class="text-muted small">
                                        <?php echo htmlspecialchars($test['description']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo $test['category_name'] ? htmlspecialchars($test['category_name']) : '-'; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo getDifficultyColor($test['difficulty_level']); ?>-lt">
                                    <?php echo getDifficultyLabel($test['difficulty_level']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="text-muted small">
                                    <?php echo number_format($test['total_attempts']); ?> deneme<br>
                                    Ort. Puan: <?php echo round($test['average_score'] ?? 0); ?>%
                                </div>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($test['created_by_username']); ?>
                            </td>
                            <td>
                                <?php echo date('d.m.Y H:i', strtotime($test['created_at'])); ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-icon btn-outline-primary" 
                                            onclick="editTest(<?php echo $test['id']; ?>)">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                    <button class="btn btn-icon btn-outline-danger" 
                                            onclick="deleteTest(<?php echo $test['id']; ?>)">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Sayfalama -->
        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="card-footer d-flex align-items-center">
                <p class="m-0 text-muted">
                    Toplam <span><?php echo $totalItems; ?></span> test
                </p>
                <ul class="pagination m-0 ms-auto">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo ($page - 1); ?>&category=<?php echo $category; ?>&difficulty=<?php echo $difficulty; ?>&search=<?php echo urlencode($search); ?>">
                                <i class="ti ti-chevron-left"></i>
                                Önceki
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&category=<?php echo $category; ?>&difficulty=<?php echo $difficulty; ?>&search=<?php echo urlencode($search); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $pagination['total_pages']): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo ($page + 1); ?>&category=<?php echo $category; ?>&difficulty=<?php echo $difficulty; ?>&search=<?php echo urlencode($search); ?>">
                                Sonraki
                                <i class="ti ti-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Test Ekleme/Düzenleme Modalı -->
<div class="modal modal-blur fade" id="testModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Yeni Test Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="testForm">
                <input type="hidden" name="test_id" id="testId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Test Başlığı</label>
                        <input type="text" class="form-control" name="title" id="titleInput" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea class="form-control" name="description" id="descriptionInput" rows="3"></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Kategori</label>
                            <select class="form-select" name="category_id" id="categoryInput">
                                <option value="">Seçiniz</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>">
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Zorluk Seviyesi</label>
                            <select class="form-select" name="difficulty_level" id="difficultyInput" required>
                                <option value="beginner">Başlangıç</option>
                                <option value="intermediate">Orta</option>
                                <option value="advanced">İleri</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Kelime Sayısı</label>
                        <input type="number" class="form-control" name="word_count" id="wordCountInput" 
                               value="10" min="5" max="50" required>
                        <div class="form-text">
                            Her test için 5-50 arası kelime seçilebilir.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">
                        İptal
                    </button>
                    <button type="submit" class="btn btn-primary ms-auto">
                        <i class="ti ti-device-floppy"></i>
                        Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Test modalını aç
function openTestModal(title = 'Yeni Test Ekle', testId = '') {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('testId').value = testId;
    document.getElementById('testForm').reset();
    
    const modal = new bootstrap.Modal(document.getElementById('testModal'));
    modal.show();
}

// Test düzenle
function editTest(testId) {
    fetch(`../api/get-test.php?id=${testId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const test = data.test;
                document.getElementById('testId').value = test.id;
                document.getElementById('titleInput').value = test.title;
                document.getElementById('descriptionInput').value = test.description;
                document.getElementById('categoryInput').value = test.category_id || '';
                document.getElementById('difficultyInput').value = test.difficulty_level;
                document.getElementById('wordCountInput').value = test.word_count;
                
                openTestModal('Test Düzenle', test.id);
            } else {
                showToast('error', data.message || 'Test yüklenirken hata oluştu');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Test yüklenirken hata oluştu');
        });
}

// Test sil
function deleteTest(testId) {
    if (confirm('Bu testi silmek istediğinizden emin misiniz?')) {
        fetch('../api/delete-test.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ test_id: testId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', 'Test başarıyla silindi');
                location.reload();
            } else {
                showToast('error', data.message || 'Test silinirken hata oluştu');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Test silinirken hata oluştu');
        });
    }
}

// Test formunu gönder
document.getElementById('testForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const testId = formData.get('test_id');
    const url = testId ? '../api/update-test.php' : '../api/add-test.php';
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', testId ? 'Test başarıyla güncellendi' : 'Test başarıyla eklendi');
            location.reload();
        } else {
            showToast('error', data.message || 'Test kaydedilirken hata oluştu');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'Test kaydedilirken hata oluştu');
    });
});
</script>

<?php
// Footer'ı dahil et
include '../includes/footer/footer.php';

// Yardımcı fonksiyonlar
function getDifficultyColor($level) {
    switch ($level) {
        case 'beginner': return 'green';
        case 'intermediate': return 'yellow';
        case 'advanced': return 'red';
        default: return 'blue';
    }
}

function getDifficultyLabel($level) {
    switch ($level) {
        case 'beginner': return 'Başlangıç';
        case 'intermediate': return 'Orta';
        case 'advanced': return 'İleri';
        default: return $level;
    }
}
?> 