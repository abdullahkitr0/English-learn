<?php
require_once '../config/config.php';
require_once '../functions/functions.php';

session_start();

// Admin kontrolü
requireAdmin();

// Sayfa başlığı
$pageTitle = "Yönetim Paneli - İngilizce Kelime Öğrenme";

try {
    $db = dbConnect();
    
    // İstatistikleri al
    $stats = [
        'total_users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'active_users' => $db->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn(),
        'total_words' => $db->query("SELECT COUNT(*) FROM words")->fetchColumn(),
        'approved_words' => $db->query("SELECT COUNT(*) FROM words WHERE is_approved = 1")->fetchColumn(),
        'total_tests' => $db->query("SELECT COUNT(*) FROM tests")->fetchColumn(),
        'total_categories' => $db->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
        'total_messages' => $db->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn(),
        'unread_messages' => $db->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetchColumn()
    ];
    
    // Son aktiviteleri al
    $activities = [
        'recent_users' => $db->query("
            SELECT id, username, email, created_at 
            FROM users 
            ORDER BY created_at DESC 
            LIMIT 5
        ")->fetchAll(),
        
        'recent_words' => $db->query("
            SELECT w.*, c.name as category_name 
            FROM words w
            LEFT JOIN categories c ON w.category_id = c.id
            ORDER BY w.created_at DESC 
            LIMIT 5
        ")->fetchAll(),
        
        'recent_tests' => $db->query("
            SELECT t.*, c.name as category_name,
                   (SELECT COUNT(*) FROM test_results WHERE test_id = t.id) as attempt_count
            FROM tests t
            LEFT JOIN categories c ON t.category_id = c.id
            ORDER BY t.created_at DESC 
            LIMIT 5
        ")->fetchAll(),
        
        'recent_messages' => $db->query("
            SELECT * FROM contact_messages 
            ORDER BY created_at DESC 
            LIMIT 5
        ")->fetchAll()
    ];
    
} catch (PDOException $e) {
    logError('Admin dashboard error: ' . $e->getMessage());
    $error = 'Veritabanı hatası oluştu';
}

// Header'ı dahil et
include '../includes/header/header.php';
?>

<div class="container-xl">
    <!-- Sayfa Başlığı -->
    <div class="page-header d-print-none">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">Yönetim Paneli</h2>
            </div>
        </div>
    </div>
    
    <!-- İstatistikler -->
    <div class="row row-deck row-cards">
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Toplam Kullanıcı</div>
                    </div>
                    <div class="h1 mb-3"><?php echo number_format($stats['total_users']); ?></div>
                    <div class="d-flex mb-2">
                        <div>Aktif Kullanıcı</div>
                        <div class="ms-auto">
                            <span class="text-green d-inline-flex align-items-center lh-1">
                                <?php echo number_format($stats['active_users']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Toplam Kelime</div>
                    </div>
                    <div class="h1 mb-3"><?php echo number_format($stats['total_words']); ?></div>
                    <div class="d-flex mb-2">
                        <div>Onaylı Kelime</div>
                        <div class="ms-auto">
                            <span class="text-green d-inline-flex align-items-center lh-1">
                                <?php echo number_format($stats['approved_words']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Toplam Test</div>
                    </div>
                    <div class="h1 mb-3"><?php echo number_format($stats['total_tests']); ?></div>
                    <div class="d-flex mb-2">
                        <div>Kategoriler</div>
                        <div class="ms-auto">
                            <span class="text-yellow d-inline-flex align-items-center lh-1">
                                <?php echo number_format($stats['total_categories']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Toplam Mesaj</div>
                    </div>
                    <div class="h1 mb-3"><?php echo number_format($stats['total_messages']); ?></div>
                    <div class="d-flex mb-2">
                        <div>Okunmamış</div>
                        <div class="ms-auto">
                            <span class="text-red d-inline-flex align-items-center lh-1">
                                <?php echo number_format($stats['unread_messages']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Son Aktiviteler -->
    <div class="row mt-4">
        <!-- Son Eklenen Kullanıcılar -->
        <div class="col-md-6 col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Son Eklenen Kullanıcılar</h3>
                </div>
                <div class="table-responsive">
                    <table class="table card-table table-vcenter">
                        <thead>
                            <tr>
                                <th>Kullanıcı Adı</th>
                                <th>E-posta</th>
                                <th>Tarih</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activities['recent_users'] as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <a href="users.php?id=<?php echo $user['id']; ?>" class="btn btn-sm">
                                        Görüntüle
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    <a href="users.php" class="btn btn-primary">Tüm Kullanıcılar</a>
                </div>
            </div>
        </div>
        
        <!-- Son Eklenen Kelimeler -->
        <div class="col-md-6 col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Son Eklenen Kelimeler</h3>
                </div>
                <div class="table-responsive">
                    <table class="table card-table table-vcenter">
                        <thead>
                            <tr>
                                <th>Kelime</th>
                                <th>Kategori</th>
                                <th>Durum</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activities['recent_words'] as $word): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($word['word']); ?></td>
                                <td><?php echo htmlspecialchars($word['category_name']); ?></td>
                                <td>
                                    <?php if ($word['is_approved']): ?>
                                        <span class="badge bg-success">Onaylı</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Onay Bekliyor</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="words.php?id=<?php echo $word['id']; ?>" class="btn btn-sm">
                                        Görüntüle
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    <a href="words.php" class="btn btn-primary">Tüm Kelimeler</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <!-- Son Eklenen Testler -->
        <div class="col-md-6 col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Son Eklenen Testler</h3>
                </div>
                <div class="table-responsive">
                    <table class="table card-table table-vcenter">
                        <thead>
                            <tr>
                                <th>Test Adı</th>
                                <th>Kategori</th>
                                <th>Deneme</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activities['recent_tests'] as $test): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($test['title']); ?></td>
                                <td><?php echo htmlspecialchars($test['category_name']); ?></td>
                                <td><?php echo number_format($test['attempt_count']); ?></td>
                                <td>
                                    <a href="tests.php?id=<?php echo $test['id']; ?>" class="btn btn-sm">
                                        Görüntüle
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    <a href="tests.php" class="btn btn-primary">Tüm Testler</a>
                </div>
            </div>
        </div>
        
        <!-- Son Gelen Mesajlar -->
        <div class="col-md-6 col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Son Gelen Mesajlar</h3>
                </div>
                <div class="table-responsive">
                    <table class="table card-table table-vcenter">
                        <thead>
                            <tr>
                                <th>Gönderen</th>
                                <th>Konu</th>
                                <th>Tarih</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activities['recent_messages'] as $message): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($message['name']); ?></td>
                                <td><?php echo htmlspecialchars($message['subject']); ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($message['created_at'])); ?></td>
                                <td>
                                    <a href="messages.php?id=<?php echo $message['id']; ?>" class="btn btn-sm">
                                        <?php if (!$message['is_read']): ?>
                                            <span class="badge bg-red"></span>
                                        <?php endif; ?>
                                        Görüntüle
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    <a href="messages.php" class="btn btn-primary">Tüm Mesajlar</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Footer'ı dahil et
include '../includes/footer/footer.php';
?> 