<?php
require_once '../config/config.php';
require_once '../functions/functions.php';

session_start();

// Admin kontrolü
requireAdmin();

// Sayfa numarası ve filtreleri al
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$status = isset($_GET['status']) ? cleanInput($_GET['status']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';

try {
    $db = dbConnect();
    
    // Kategorileri al
    $stmt = $db->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
    
    // Kelime listesi için sorgu oluştur
    $sql = "SELECT w.*, c.name as category_name 
            FROM words w 
            LEFT JOIN categories c ON w.category_id = c.id 
            WHERE 1=1";
    $params = [];
    
    if ($status === 'pending') {
        $sql .= " AND w.is_approved = 0";
    } elseif ($status === 'approved') {
        $sql .= " AND w.is_approved = 1";
    }
    
    if ($category > 0) {
        $sql .= " AND w.category_id = ?";
        $params[] = $category;
    }
    
    if ($search) {
        $sql .= " AND (w.word LIKE ? OR w.definition LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Toplam kelime sayısını al
    $countSql = str_replace("SELECT w.*, c.name as category_name", "SELECT COUNT(*)", $sql);
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $totalItems = $stmt->fetchColumn();
    
    // Sayfalama bilgilerini hesapla
    $pagination = getPagination($totalItems, $page);
    
    // Kelimeleri al
    $sql .= " ORDER BY w.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $pagination['per_page'];
    $params[] = $pagination['offset'];
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $words = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Veritabanı hatası: ' . $e->getMessage();
    logError('Admin words error: ' . $e->getMessage());
}

// Sayfa başlığı
$pageTitle = "Kelime Yönetimi - İngilizce Kelime Öğrenme";

// Header'ı dahil et
include '../includes/header/header.php';
?>

<div class="container-xl">
    <div class="page-header d-print-none">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">Kelime Yönetimi</h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addWordModal">
                    <i class="ti ti-plus"></i>
                    Yeni Kelime Ekle
                </button>
            </div>
        </div>
    </div>
    
    <!-- Filtreler -->
    <div class="card mb-3">
        <div class="card-body">
            <form action="" method="get" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Durum</label>
                    <select name="status" class="form-select">
                        <option value="">Tümü</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Onay Bekleyen</option>
                        <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Onaylanmış</option>
                    </select>
                </div>
                
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
                
                <div class="col-md-4">
                    <label class="form-label">Arama</label>
                    <input type="text" name="search" class="form-control" placeholder="Kelime veya anlam ara..." 
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
    
    <!-- Kelime Listesi -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Kelime Yönetimi</h3>
            <div class="card-actions">
                <button type="button" class="btn btn-primary" onclick="addWordModal()">
                    <i class="ti ti-plus"></i> Yeni Kelime Ekle
                </button>
                <button type="button" class="btn btn-success" onclick="addAllImages()">
                    <i class="ti ti-photo"></i> Tüm Kelimelere Resim Ekle
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Kelime</th>
                        <th>Anlam</th>
                        <th>Kategori</th>
                        <th>Durum</th>
                        <th>Eklenme Tarihi</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($words as $word): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($word['word']); ?>
                                <?php if ($word['pronunciation']): ?>
                                    <div class="text-muted small">
                                        <?php echo htmlspecialchars($word['pronunciation']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($word['definition']); ?>
                                <?php if ($word['example_sentence']): ?>
                                    <div class="text-muted small">
                                        <?php echo htmlspecialchars($word['example_sentence']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($word['category_name']); ?>
                            </td>
                            <td>
                                <?php if ($word['is_approved']): ?>
                                    <span class="badge bg-success">Onaylı</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Onay Bekliyor</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo date('d.m.Y H:i', strtotime($word['created_at'])); ?>
                            </td>
                            <td>
                                <div class="btn-list">
                                    <button class="btn btn-sm btn-primary" onclick="getWordImage(<?php echo $word['id']; ?>)">
                                        <i class="ti ti-photo"></i> Resim Ekle
                                    </button>
                                    <button class="btn btn-sm btn-warning" onclick="editWord(<?php echo $word['id']; ?>)">
                                        <i class="ti ti-edit"></i> Düzenle
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteWord(<?php echo $word['id']; ?>)">
                                        <i class="ti ti-trash"></i> Sil
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
                    Toplam <span><?php echo $totalItems; ?></span> kelime
                </p>
                <ul class="pagination m-0 ms-auto">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=1&status=<?php echo $status; ?>&category=<?php echo $category; ?>&search=<?php echo urlencode($search); ?>">
                                <i class="ti ti-chevrons-left"></i>
                                İlk
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo ($page - 1); ?>&status=<?php echo $status; ?>&category=<?php echo $category; ?>&search=<?php echo urlencode($search); ?>">
                                <i class="ti ti-chevron-left"></i>
                                Önceki
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($pagination['total_pages'], $page + 2);
                    
                    if ($start > 1) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                    
                    for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>&category=<?php echo $category; ?>&search=<?php echo urlencode($search); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor;
                    
                    if ($end < $pagination['total_pages']) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                    ?>
                    
                    <?php if ($page < $pagination['total_pages']): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo ($page + 1); ?>&status=<?php echo $status; ?>&category=<?php echo $category; ?>&search=<?php echo urlencode($search); ?>">
                                Sonraki
                                <i class="ti ti-chevron-right"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $pagination['total_pages']; ?>&status=<?php echo $status; ?>&category=<?php echo $category; ?>&search=<?php echo urlencode($search); ?>">
                                Son
                                <i class="ti ti-chevrons-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Kelime Ekleme Modalı -->
<div class="modal modal-blur fade" id="addWordModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Kelime Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="wordForm">
                <input type="hidden" name="word_id" id="wordId">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Kelime</label>
                            <input type="text" class="form-control" name="word" id="wordInput" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telaffuz</label>
                            <input type="text" class="form-control" name="pronunciation" id="pronunciationInput">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Anlam</label>
                        <textarea class="form-control" name="definition" id="definitionInput" rows="2" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Örnek Cümle</label>
                        <textarea class="form-control" name="example_sentence" id="exampleInput" rows="2"></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Kategori</label>
                            <select class="form-select" name="category_id" id="categoryInput" required>
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
                        <label class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_approved" id="approvedInput" value="1">
                            <span class="form-check-label">Onaylı</span>
                        </label>
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

<!-- Kelime Düzenleme Modalı -->
<div class="modal modal-blur fade" id="editWordModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kelime Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editWordForm">
                <input type="hidden" name="word_id" id="edit_word_id">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Kelime</label>
                            <input type="text" class="form-control" name="word" id="edit_word" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telaffuz</label>
                            <input type="text" class="form-control" name="pronunciation" id="edit_pronunciation">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Anlam</label>
                        <textarea class="form-control" name="definition" id="edit_definition" rows="2" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Örnek Cümle</label>
                        <textarea class="form-control" name="example_sentence" id="edit_example_sentence" rows="2"></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Kategori</label>
                            <select class="form-select" name="category_id" id="edit_category_id" required>
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
                            <select class="form-select" name="difficulty_level" id="edit_difficulty_level" required>
                                <option value="beginner">Başlangıç</option>
                                <option value="intermediate">Orta</option>
                                <option value="advanced">İleri</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_approved" id="edit_is_approved" value="1">
                            <span class="form-check-label">Onaylı</span>
                        </label>
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
// Kelime ekleme butonuna tıklama olayı ekle
document.addEventListener('DOMContentLoaded', function() {
    const addWordButton = document.querySelector('[data-bs-target="#addWordModal"]');
    if (addWordButton) {
        addWordButton.addEventListener('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('addWordModal'));
            document.getElementById('wordForm').reset();
            modal.show();
        });
    }
});

// Kelime düzenle
function editWord(wordId) {
    // Kelime bilgilerini al
    fetch(`../api/get-word.php?id=${wordId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.word) {
                const word = data.word;
                
                // Form alanlarını doldur
                document.getElementById('edit_word_id').value = word.id;
                document.getElementById('edit_word').value = word.word;
                document.getElementById('edit_pronunciation').value = word.pronunciation || '';
                document.getElementById('edit_definition').value = word.definition;
                document.getElementById('edit_example_sentence').value = word.example_sentence || '';
                document.getElementById('edit_category_id').value = word.category_id;
                document.getElementById('edit_difficulty_level').value = word.difficulty_level;
                document.getElementById('edit_is_approved').checked = word.is_approved == 1;
                
                // Modal'ı aç
                const editWordModal = new bootstrap.Modal(document.getElementById('editWordModal'));
                editWordModal.show();
            } else {
                showToast('error', data.message || 'Kelime bilgileri alınamadı');
            }
        })
        .catch(error => {
            console.error('Kelime bilgileri alınırken hata:', error);
            showToast('error', 'Kelime bilgileri alınırken bir hata oluştu');
        });
}

// Kelime sil
function deleteWord(wordId) {
    if (confirm('Bu kelimeyi silmek istediğinizden emin misiniz?')) {
        fetch('../api/delete-word.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: wordId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', 'Kelime başarıyla silindi');
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                showToast('error', data.message || 'Kelime silinirken hata oluştu');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Kelime silinirken hata oluştu');
        });
    }
}

// Kelime onayla
function approveWord(wordId) {
    fetch('../api/approve-word.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ word_id: wordId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Kelime başarıyla onaylandı');
            location.reload();
        } else {
            showToast('error', data.message || 'Kelime onaylanırken hata oluştu');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'Kelime onaylanırken hata oluştu');
    });
}

// Kelime formunu gönder
document.getElementById('wordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const wordId = formData.get('word_id');
    
    // FormData'yı JSON'a çevir
    const data = {};
    formData.forEach((value, key) => {
        if (key === 'is_approved') {
            data[key] = value === '1';
        } else {
            data[key] = value;
        }
    });
    
    const url = wordId ? '../api/update-word.php' : '../api/add-word.php';
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', wordId ? 'Kelime başarıyla güncellendi' : 'Kelime başarıyla eklendi');
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showToast('error', data.message || 'Kelime kaydedilirken hata oluştu');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'Kelime kaydedilirken hata oluştu');
    });
});

document.getElementById('editWordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = {
        word_id: document.getElementById('edit_word_id').value,
        word: document.getElementById('edit_word').value,
        pronunciation: document.getElementById('edit_pronunciation').value,
        definition: document.getElementById('edit_definition').value,
        example_sentence: document.getElementById('edit_example_sentence').value,
        category_id: document.getElementById('edit_category_id').value,
        difficulty_level: document.getElementById('edit_difficulty_level').value,
        is_approved: document.getElementById('edit_is_approved').checked
    };
    
    fetch('../api/update-word.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('editWordModal')).hide();
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showToast('error', data.message);
        }
    })
    .catch(error => {
        console.error('Kelime güncelleme hatası:', error);
        showToast('error', 'Kelime güncellenirken bir hata oluştu');
    });
});

function getWordImage(wordId) {
    if (confirm('Bu kelime için Pexels\'ten otomatik resim eklemek istiyor musunuz?')) {
        fetch(`../api/get-word-image.php?id=${wordId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', data.message);
                    // Sayfayı yenile
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast('error', data.message);
                }
            })
            .catch(error => {
                showToast('error', 'Resim eklenirken bir hata oluştu');
                console.error('Error:', error);
            });
    }
}

function addAllImages() {
    if (confirm('Tüm kelimelere otomatik olarak resim eklemek istiyor musunuz? Bu işlem biraz zaman alabilir.')) {
        // Loading göster
        const loadingToast = showToast('info', 'Resimler ekleniyor, lütfen bekleyin...', false);
        
        fetch('../api/add-all-images.php')
            .then(response => response.json())
            .then(data => {
                // Loading toast'ı kaldır
                loadingToast.hide();
                
                if (data.success) {
                    showToast('success', data.message);
                    // İşlem bitince sayfayı yenile
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    showToast('error', data.message);
                }
            })
            .catch(error => {
                // Loading toast'ı kaldır
                loadingToast.hide();
                console.error('Error:', error);
                showToast('error', 'Resimler eklenirken bir hata oluştu');
            });
    }
}
</script>

<?php
// Footer'ı dahil et
include '../includes/footer/footer.php';
?> 