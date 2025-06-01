<?php
require_once 'config/config.php';
require_once 'functions/functions.php';

session_start();

// Giriş kontrolü
requireLogin();

// Sayfa numarası ve filtreleri al
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$type = isset($_GET['type']) ? cleanInput($_GET['type']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;

try {
    $db = dbConnect();
    
    // Varsayılan değerleri tanımla
    $stats = [
        'total_tests' => 0,
        'average_score' => 0,
        'best_score' => 0,
        'total_correct' => 0,
        'total_incorrect' => 0,
        'average_time' => 0
    ];
    $testResults = [];
    $totalItems = 0;
    $categories = [];
    
    // Kategorileri al
    $stmt = $db->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
    
    // Test sonuçları için sorgu oluştur
    $sql = "SELECT tr.*, t.title, t.type, t.difficulty_level, c.name as category_name
            FROM test_results tr
            LEFT JOIN tests t ON tr.test_id = t.id
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE tr.user_id = :user_id";
    $params = ['user_id' => $_SESSION['user_id']];
    
    if ($type) {
        $sql .= " AND t.type = :type";
        $params['type'] = $type;
    }
    
    if ($category > 0) {
        $sql .= " AND t.category_id = :category";
        $params['category'] = $category;
    }
    
    // Debug için SQL sorgusunu ve parametreleri logla
    error_log('SQL Query: ' . $sql);
    error_log('Parameters: ' . print_r($params, true));
    
    // Toplam sonuç sayısını al
    $countSql = str_replace("SELECT tr.*, t.title, t.type, t.difficulty_level, c.name as category_name", "SELECT COUNT(*)", $sql);
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $totalItems = $stmt->fetchColumn();
    
    error_log('Total Items: ' . $totalItems);
    
    // Sayfalama bilgilerini hesapla
    $pagination = getPagination($totalItems, $page);
    
    // Sonuçları al
    $sql .= " ORDER BY tr.completed_at DESC LIMIT :limit OFFSET :offset";
    $params['limit'] = $pagination['per_page'];
    $params['offset'] = $pagination['offset'];
    
    $stmt = $db->prepare($sql);
    
    // PDO parametrelerini doğru tipte bağla
    foreach ($params as $key => $value) {
        if ($key == 'limit' || $key == 'offset') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();
    $testResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log('Test Results Count: ' . count($testResults));
    
    // Test istatistiklerini al
    $statsStmt = $db->prepare("
        SELECT 
            COUNT(*) as total_tests,
            COALESCE(AVG(score), 0) as average_score,
            COALESCE(MAX(score), 0) as best_score,
            COALESCE(SUM(correct_count), 0) as total_correct,
            COALESCE(SUM(incorrect_count), 0) as total_incorrect,
            COALESCE(AVG(time_spent), 0) as average_time
        FROM test_results
        WHERE user_id = :user_id
    ");
    
    $statsStmt->execute(['user_id' => $_SESSION['user_id']]);
    $tempStats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($tempStats) {
        $stats = $tempStats;
    }
    
    error_log('Stats: ' . print_r($stats, true));
    
} catch (PDOException $e) {
    $error = 'Veritabanı hatası: ' . $e->getMessage();
    error_log('My tests error: ' . $e->getMessage());
    logError('My tests error: ' . $e->getMessage());
}

// Sayfa başlığı
$pageTitle = "Test Sonuçlarım - İngilizce Kelime Öğrenme";

// Header'ı dahil et
include 'includes/header/header.php';
?>

<div class="container-xl">
    <div class="page-header d-print-none">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">Test Sonuçlarım</h2>
                <div class="text-muted mt-1">
                    Toplam <?php echo $totalItems; ?> test sonucu
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="tests.php" class="btn btn-primary">
                        <i class="ti ti-writing me-2"></i>
                        Yeni Test
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- İstatistikler -->
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-primary text-white avatar">
                                <i class="ti ti-writing"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">
                                <?php echo isset($stats['total_tests']) ? number_format($stats['total_tests']) : 0; ?> Test
                            </div>
                            <div class="text-muted">
                                Toplam test sayısı
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-yellow text-white avatar">
                                <i class="ti ti-chart-bar"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">
                                %<?php echo isset($stats['average_score']) ? round($stats['average_score']) : 0; ?>
                            </div>
                            <div class="text-muted">
                                Ortalama başarı
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-green text-white avatar">
                                <i class="ti ti-trophy"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">
                                %<?php echo isset($stats['best_score']) ? round($stats['best_score']) : 0; ?>
                            </div>
                            <div class="text-muted">
                                En yüksek puan
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filtreler -->
    <div class="card mb-3">
        <div class="card-body">
            <form action="" method="get" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Test Türü</label>
                    <select name="type" class="form-select">
                        <option value="">Tümü</option>
                        <option value="daily" <?php echo $type === 'daily' ? 'selected' : ''; ?>>Günlük Test</option>
                        <option value="review" <?php echo $type === 'review' ? 'selected' : ''; ?>>Tekrar Testi</option>
                        <option value="category" <?php echo $type === 'category' ? 'selected' : ''; ?>>Kategori Testi</option>
                    </select>
                </div>
                
                <div class="col-md-4">
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
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-filter"></i>
                            Filtrele
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Test Sonuçları -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Test</th>
                        <th>Kategori</th>
                        <th>Zorluk</th>
                        <th>Puan</th>
                        <th>Doğru</th>
                        <th>Yanlış</th>
                        <th>Süre</th>
                        <th>Tarih</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($testResults)): ?>
                        <?php foreach ($testResults as $result): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($result['title']); ?>
                                    <div class="text-muted">
                                        <?php echo ucfirst($result['type']); ?> Test
                                    </div>
                                </td>
                                <td>
                                    <?php echo $result['category_name'] ? htmlspecialchars($result['category_name']) : '-'; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo getDifficultyColor($result['difficulty_level']); ?>">
                                        <?php echo getDifficultyLabel($result['difficulty_level']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo getScoreColor($result['score']); ?>">
                                        %<?php echo round($result['score'], 1); ?>
                                    </span>
                                </td>
                                <td class="text-success">
                                    <?php echo $result['correct_count']; ?>
                                </td>
                                <td class="text-danger">
                                    <?php echo $result['incorrect_count']; ?>
                                </td>
                                <td>
                                    <?php echo formatDuration($result['time_spent']); ?>
                                </td>
                                <td>
                                    <?php echo date('d.m.Y H:i', strtotime($result['completed_at'])); ?>
                                </td>
                                <td>
                                    <a href="test-result.php?id=<?php echo $result['id']; ?>" 
                                       class="btn btn-icon" title="Detaylar">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <div class="empty">
                                    <div class="empty-icon">
                                        <i class="ti ti-mood-sad text-muted" style="font-size: 3rem;"></i>
                                    </div>
                                    <p class="empty-title">Henüz test sonucunuz bulunmuyor</p>
                                    <p class="empty-subtitle text-muted">
                                        Yeni bir test çözerek performansınızı takip etmeye başlayabilirsiniz.
                                    </p>
                                    <div class="empty-action">
                                        <a href="tests.php" class="btn btn-primary">
                                            <i class="ti ti-writing me-2"></i>
                                            Test Çöz
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Sayfalama -->
        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="card-footer d-flex align-items-center">
                <p class="m-0 text-muted">
                    Toplam <span><?php echo $totalItems; ?></span> sonuç
                </p>
                <ul class="pagination m-0 ms-auto">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo ($page - 1); ?>&type=<?php echo $type; ?>&category=<?php echo $category; ?>">
                                <i class="ti ti-chevron-left"></i>
                                Önceki
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&type=<?php echo $type; ?>&category=<?php echo $category; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $pagination['total_pages']): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo ($page + 1); ?>&type=<?php echo $type; ?>&category=<?php echo $category; ?>">
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

<!-- Test Detay Modalı -->
<div class="modal modal-blur fade" id="testDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Test Detayı</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="testDetailContent">
                <!-- Test detayları JavaScript ile eklenecek -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<script>
// Test detaylarını göster
function showTestDetails(resultId) {
    fetch(`api/get-test-result.php?id=${resultId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const result = data.result;
                const content = document.getElementById('testDetailContent');
                
                let html = `
                    <div class="mb-3">
                        <h3>${result.title}</h3>
                        <div class="text-muted">${result.description || ''}</div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="text-muted">Puan</div>
                            <div class="h3">%${Math.round(result.score)}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted">Doğru</div>
                            <div class="h3 text-success">${result.correct_count}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted">Yanlış</div>
                            <div class="h3 text-danger">${result.incorrect_count}</div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-vcenter">
                            <thead>
                                <tr>
                                    <th>Kelime</th>
                                    <th>Doğru Cevap</th>
                                    <th>Senin Cevabın</th>
                                    <th>Sonuç</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                result.answers.forEach(answer => {
                    html += `
                        <tr>
                            <td>${answer.word}</td>
                            <td>${answer.correct_answer}</td>
                            <td>${answer.user_answer}</td>
                            <td>
                                ${answer.is_correct 
                                    ? '<span class="badge bg-success">Doğru</span>'
                                    : '<span class="badge bg-danger">Yanlış</span>'}
                            </td>
                        </tr>
                    `;
                });
                
                html += `
                            </tbody>
                        </table>
                    </div>
                `;
                
                content.innerHTML = html;
                
                const modal = new bootstrap.Modal(document.getElementById('testDetailModal'));
                modal.show();
            } else {
                showToast('error', data.message || 'Test detayları yüklenirken hata oluştu');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Test detayları yüklenirken hata oluştu');
        });
}
</script>

<?php
// Footer'ı dahil et
include 'includes/footer/footer.php';

// Yardımcı fonksiyonlar
function getDifficultyColor($level) {
    switch ($level) {
        case 'beginner': return 'success';
        case 'intermediate': return 'warning';
        case 'advanced': return 'danger';
        default: return 'secondary';
    }
}

function getDifficultyLabel($level) {
    switch ($level) {
        case 'beginner': return 'Başlangıç';
        case 'intermediate': return 'Orta';
        case 'advanced': return 'İleri';
        default: return ucfirst($level);
    }
}

function getScoreColor($score) {
    if ($score >= 90) return 'success';
    if ($score >= 70) return 'info';
    if ($score >= 50) return 'warning';
    return 'danger';
}

function formatDuration($seconds) {
    if ($seconds < 60) {
        return $seconds . ' sn';
    }
    $minutes = floor($seconds / 60);
    $seconds = $seconds % 60;
    return $minutes . ' dk ' . $seconds . ' sn';
}
?> 