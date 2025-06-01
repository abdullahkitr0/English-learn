<?php
require_once 'config/config.php';
require_once 'functions/functions.php';

session_start();

// Giriş kontrolü
requireLogin();

// Sayfa başlığı
$pageTitle = "Kelimelerim - İngilizce Kelime Öğrenme";

// Header'ı dahil et
include 'includes/header/header.php';

try {
    $db = dbConnect();
    
    // Filtreleme parametreleri
    $status = isset($_GET['status']) ? cleanInput($_GET['status']) : '';
    $category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
    $difficulty = isset($_GET['difficulty']) ? cleanInput($_GET['difficulty']) : '';
    
    // Sayfalama
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = 20;
    $offset = ($page - 1) * $perPage;
    
    // Temel SQL sorgusu
    $sql = "
        SELECT w.*, c.name as category_name, uw.status, uw.last_reviewed,
               uw.correct_count, uw.incorrect_count
        FROM user_words uw
        INNER JOIN words w ON uw.word_id = w.id
        LEFT JOIN categories c ON w.category_id = c.id
        WHERE uw.user_id = :user_id
    ";
    
    // Filtreleme koşulları
    $params = ['user_id' => $_SESSION['user_id']];
    
    if ($status) {
        $sql .= " AND uw.status = :status";
        $params['status'] = $status;
    }
    
    if ($category > 0) {
        $sql .= " AND w.category_id = :category";
        $params['category'] = $category;
    }
    
    if ($difficulty) {
        $sql .= " AND w.difficulty_level = :difficulty";
        $params['difficulty'] = $difficulty;
    }
    
    // Toplam kayıt sayısını al
    $countSql = "SELECT COUNT(*) FROM (" . $sql . ") as count_table";
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetchColumn();
    
    // Ana sorguyu çalıştır
    $sql .= " ORDER BY uw.created_at DESC LIMIT :limit OFFSET :offset";
    $params['limit'] = $perPage;
    $params['offset'] = $offset;
    
    $stmt = $db->prepare($sql);
    
    // PDO parametrelerini doğru tipte belirt
    foreach ($params as $key => $value) {
        if ($key == 'limit' || $key == 'offset') {
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':' . $key, $value);
        }
    }
    
    $stmt->execute();
    $words = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Kategorileri al
    $categories = $db->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
    
    // İstatistikleri al
    $stats = [];
    
    // Toplam kelime sayısı
    $stmt = $db->prepare("SELECT COUNT(*) FROM user_words WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['total'] = $stmt->fetchColumn();
    
    // Yeni kelimeler
    $stmt = $db->prepare("SELECT COUNT(*) FROM user_words WHERE user_id = ? AND status = 'new'");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['new'] = $stmt->fetchColumn();
    
    // Öğrenilen kelimeler
    $stmt = $db->prepare("SELECT COUNT(*) FROM user_words WHERE user_id = ? AND status = 'learning'");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['learning'] = $stmt->fetchColumn();
    
    // Tamamlanan kelimeler
    $stmt = $db->prepare("SELECT COUNT(*) FROM user_words WHERE user_id = ? AND status = 'mastered'");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['mastered'] = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    logError('My words error: ' . $e->getMessage());
    $words = [];
    $categories = [];
    $totalRecords = 0;
    $stats = ['total' => 0, 'new' => 0, 'learning' => 0, 'mastered' => 0];
}

// Zorluk seviyesi rengini döndür
function getDifficultyColor($level) {
    switch ($level) {
        case 'beginner':
            return 'success';
        case 'intermediate':
            return 'warning';
        case 'advanced':
            return 'danger';
        default:
            return 'secondary';
    }
}

// Zorluk seviyesi etiketini döndür
function getDifficultyLabel($level) {
    switch ($level) {
        case 'beginner':
            return 'Başlangıç';
        case 'intermediate':
            return 'Orta';
        case 'advanced':
            return 'İleri';
        default:
            return 'Bilinmiyor';
    }
}
?>

<div class="container-xl">
    <div class="page-header d-print-none">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">Kelimelerim</h2>
            </div>
        </div>
    </div>
    
    <!-- İstatistikler -->
    <div class="row mt-3">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body p-3 text-center">
                    <div class="text-end text-primary">
                        <i class="ti ti-book"></i>
                    </div>
                    <div class="h1 m-0"><?php echo number_format($stats['total']); ?></div>
                    <div class="text-muted mb-3">Toplam Kelime</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body p-3 text-center">
                    <div class="text-end text-info">
                        <i class="ti ti-star"></i>
                    </div>
                    <div class="h1 m-0"><?php echo number_format($stats['new']); ?></div>
                    <div class="text-muted mb-3">Yeni</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body p-3 text-center">
                    <div class="text-end text-warning">
                        <i class="ti ti-brain"></i>
                    </div>
                    <div class="h1 m-0"><?php echo number_format($stats['learning']); ?></div>
                    <div class="text-muted mb-3">Öğreniliyor</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body p-3 text-center">
                    <div class="text-end text-success">
                        <i class="ti ti-check"></i>
                    </div>
                    <div class="h1 m-0"><?php echo number_format($stats['mastered']); ?></div>
                    <div class="text-muted mb-3">Öğrenildi</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filtreler -->
    <div class="card mt-3">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Durum</label>
                    <select name="status" class="form-select">
                        <option value="">Tümü</option>
                        <option value="new" <?php echo $status === 'new' ? 'selected' : ''; ?>>Yeni</option>
                        <option value="learning" <?php echo $status === 'learning' ? 'selected' : ''; ?>>Öğreniliyor</option>
                        <option value="mastered" <?php echo $status === 'mastered' ? 'selected' : ''; ?>>Öğrenildi</option>
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
                
                <div class="col-md-3">
                    <label class="form-label">Zorluk</label>
                    <select name="difficulty" class="form-select">
                        <option value="">Tümü</option>
                        <option value="beginner" <?php echo $difficulty === 'beginner' ? 'selected' : ''; ?>>Başlangıç</option>
                        <option value="intermediate" <?php echo $difficulty === 'intermediate' ? 'selected' : ''; ?>>Orta</option>
                        <option value="advanced" <?php echo $difficulty === 'advanced' ? 'selected' : ''; ?>>İleri</option>
                    </select>
                </div>
                
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ti ti-filter me-2"></i>
                        Filtrele
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Kelime Listesi -->
    <div class="row mt-3">
        <?php if (empty($words)): ?>
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-4">
                    <div class="mb-3">
                        <i class="ti ti-mood-empty text-muted" style="font-size: 3rem;"></i>
                    </div>
                    <h3 class="text-muted">Henüz kelime eklenmemiş</h3>
                    <div class="text-muted">Kelime listesinden yeni kelimeler ekleyebilirsiniz.</div>
                    <div class="mt-3">
                        <a href="word-lists.php" class="btn btn-primary">
                            <i class="ti ti-plus me-2"></i>
                            Kelime Ekle
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <?php foreach ($words as $word): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card mb-3">
                <div class="card-header">
                    <div class="card-actions">
                        <button type="button" class="btn-action" onclick="playPronunciation('<?php echo htmlspecialchars($word['word']); ?>')">
                            <i class="ti ti-volume"></i>
                        </button>
                        <button type="button" class="btn-action" onclick="removeWord(<?php echo $word['id']; ?>)">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <h3 class="card-title">
                        <?php echo htmlspecialchars($word['word']); ?>
                        <?php if ($word['pronunciation']): ?>
                        <small class="text-muted"><?php echo htmlspecialchars($word['pronunciation']); ?></small>
                        <?php endif; ?>
                    </h3>
                </div>
                <div class="card-body">
                    <p><?php echo htmlspecialchars($word['definition']); ?></p>
                    <?php if ($word['example_sentence']): ?>
                    <p class="text-muted"><em><?php echo htmlspecialchars($word['example_sentence']); ?></em></p>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-<?php echo getDifficultyColor($word['difficulty_level']); ?> me-2">
                                    <?php echo getDifficultyLabel($word['difficulty_level']); ?>
                                </span>
                                <span class="badge bg-blue-lt">
                                    <?php echo htmlspecialchars($word['category_name']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="dropdown">
                                <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <?php
                                    $statusLabel = '';
                                    $statusColor = '';
                                    switch ($word['status']) {
                                        case 'new':
                                            $statusLabel = 'Yeni';
                                            $statusColor = 'info';
                                            break;
                                        case 'learning':
                                            $statusLabel = 'Öğreniliyor';
                                            $statusColor = 'warning';
                                            break;
                                        case 'mastered':
                                            $statusLabel = 'Öğrenildi';
                                            $statusColor = 'success';
                                            break;
                                    }
                                    ?>
                                    <span class="badge bg-<?php echo $statusColor; ?>-lt"><?php echo $statusLabel; ?></span>
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $word['id']; ?>, 'new')">
                                        <span class="badge bg-info-lt me-2">Yeni</span>
                                    </a>
                                    <a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $word['id']; ?>, 'learning')">
                                        <span class="badge bg-warning-lt me-2">Öğreniliyor</span>
                                    </a>
                                    <a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $word['id']; ?>, 'mastered')">
                                        <span class="badge bg-success-lt me-2">Öğrenildi</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Sayfalama -->
    <?php if ($totalRecords > $perPage): ?>
    <div class="d-flex justify-content-center mt-4">
        <ul class="pagination">
            <?php
            $totalPages = ceil($totalRecords / $perPage);
            $range = 2;
            
            // İlk sayfa
            if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?page=1<?php echo $status ? "&status=$status" : ''; ?><?php echo $category ? "&category=$category" : ''; ?><?php echo $difficulty ? "&difficulty=$difficulty" : ''; ?>">
                    <i class="ti ti-chevrons-left"></i>
                </a>
            </li>
            <?php endif;

            // Önceki sayfa
            if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $status ? "&status=$status" : ''; ?><?php echo $category ? "&category=$category" : ''; ?><?php echo $difficulty ? "&difficulty=$difficulty" : ''; ?>">
                    <i class="ti ti-chevron-left"></i>
                </a>
            </li>
            <?php endif;

            // Sayfa numaraları
            for ($i = max(1, $page - $range); $i <= min($totalPages, $page + $range); $i++): ?>
            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $status ? "&status=$status" : ''; ?><?php echo $category ? "&category=$category" : ''; ?><?php echo $difficulty ? "&difficulty=$difficulty" : ''; ?>">
                    <?php echo $i; ?>
                </a>
            </li>
            <?php endfor;

            // Sonraki sayfa
            if ($page < $totalPages): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $status ? "&status=$status" : ''; ?><?php echo $category ? "&category=$category" : ''; ?><?php echo $difficulty ? "&difficulty=$difficulty" : ''; ?>">
                    <i class="ti ti-chevron-right"></i>
                </a>
            </li>
            <?php endif;

            // Son sayfa
            if ($page < $totalPages): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?php echo $totalPages; ?><?php echo $status ? "&status=$status" : ''; ?><?php echo $category ? "&category=$category" : ''; ?><?php echo $difficulty ? "&difficulty=$difficulty" : ''; ?>">
                    <i class="ti ti-chevrons-right"></i>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>

<script>
// Kelime durumunu güncelle
function updateStatus(wordId, status) {
    fetch('api/update-word-status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            word_id: wordId,
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.message);
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'Durum güncellenirken bir hata oluştu');
    });
}

// Kelimeyi listeden kaldır
function removeWord(wordId) {
    if (confirm('Bu kelimeyi listenizden kaldırmak istediğinize emin misiniz?')) {
        fetch('api/remove-word.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                word_id: wordId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', data.message);
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showToast('error', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Kelime kaldırılırken bir hata oluştu');
        });
    }
}

// Telaffuzu çal
function playPronunciation(word) {
    fetch(`api/pronunciation.php?word=${encodeURIComponent(word)}`)
        .then(response => response.blob())
        .then(blob => {
            const audio = new Audio(URL.createObjectURL(blob));
            audio.play();
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Telaffuz oynatılırken bir hata oluştu');
        });
}
</script>

<?php
// Footer'ı dahil et
include 'includes/footer/footer.php';
?> 