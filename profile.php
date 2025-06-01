<?php
require_once 'config/config.php';
require_once 'functions/functions.php';

session_start();

// Giriş kontrolü
requireLogin();

$errors = [];
$success = false;

try {
    $db = dbConnect();
    
    // Kullanıcı bilgilerini al
    $stmt = $db->prepare("
        SELECT id, username, email, created_at
        FROM users
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    // Kelime istatistiklerini al
    $statsStmt = $db->prepare("
        SELECT 
            COUNT(*) as total_words,
            SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_words,
            SUM(CASE WHEN status = 'learning' THEN 1 ELSE 0 END) as learning_words,
            SUM(CASE WHEN status = 'mastered' THEN 1 ELSE 0 END) as learned_words
        FROM user_words
        WHERE user_id = ?
    ");
    $statsStmt->execute([$_SESSION['user_id']]);
    $stats = $statsStmt->fetch();

    // Son öğrenilen kelimeleri al
    $recentWordsStmt = $db->prepare("
        SELECT w.word, w.definition, uw.status, uw.last_reviewed
        FROM user_words uw
        JOIN words w ON uw.word_id = w.id
        WHERE uw.user_id = ? AND uw.status = 'mastered'
        ORDER BY uw.last_reviewed DESC
        LIMIT 5
    ");
    $recentWordsStmt->execute([$_SESSION['user_id']]);
    $recentWords = $recentWordsStmt->fetchAll();

    // Test istatistiklerini al
    $testStatsStmt = $db->prepare("
        SELECT 
            COUNT(*) as total_tests,
            AVG(score) as average_score,
            MAX(score) as best_score
        FROM test_results
        WHERE user_id = ?
    ");
    $testStatsStmt->execute([$_SESSION['user_id']]);
    $testStats = $testStatsStmt->fetch();

    // Son aktiviteleri al
    $stmt = $db->prepare("
        (SELECT 'word' as type, w.word as title, uw.created_at as date
         FROM user_words uw 
         JOIN words w ON uw.word_id = w.id 
         WHERE uw.user_id = ?
         ORDER BY uw.created_at DESC
         LIMIT 5)
        UNION ALL
        (SELECT 'test' as type, t.title, tr.completed_at as date
         FROM test_results tr 
         JOIN tests t ON tr.test_id = t.id 
         WHERE tr.user_id = ?
         ORDER BY tr.completed_at DESC
         LIMIT 5)
        ORDER BY date DESC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $activities = $stmt->fetchAll();
    
    // Başarıları al
    $stmt = $db->prepare("
        SELECT a.*, ua.earned_at
        FROM achievements a
        JOIN user_achievements ua ON a.id = ua.achievement_id
        WHERE ua.user_id = ?
        ORDER BY ua.earned_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $achievements = $stmt->fetchAll();
    
    // Form gönderildiğinde
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $newPasswordConfirm = $_POST['new_password_confirm'] ?? '';
        
        // Şifre değişikliği
        if (!empty($currentPassword) && !empty($newPassword)) {
            if (!password_verify($currentPassword, $user['password'])) {
                $errors[] = 'Mevcut şifreniz hatalı.';
            } elseif (strlen($newPassword) < 6) {
                $errors[] = 'Yeni şifre en az 6 karakter olmalıdır.';
            } elseif ($newPassword !== $newPasswordConfirm) {
                $errors[] = 'Yeni şifreler eşleşmiyor.';
            } else {
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
                $success = true;
            }
        }
    }
} catch (PDOException $e) {
    $errors[] = 'Bir hata oluştu.';
    logError('Profile error: ' . $e->getMessage());
}

// Sayfa başlığı
$pageTitle = "Profilim - " . htmlspecialchars($user['username']);

// Header'ı dahil et
include 'includes/header/header.php';
?>

<div class="container-xl py-4">
    <div class="row g-3">
        <!-- Profil Bilgileri -->
        <div class="col-12 col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <span class="avatar avatar-xl mb-3 rounded">
                            <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                        </span>
                        <h3 class="m-0"><?php echo htmlspecialchars($user['username']); ?></h3>
                        <p class="text-muted mb-0"><?php echo htmlspecialchars($user['email']); ?></p>
                        <small class="text-muted">
                            Üyelik: <?php echo date('d.m.Y', strtotime($user['created_at'])); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kelime İstatistikleri -->
        <div class="col-12 col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Kelime İstatistikleri</h3>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6 col-sm-3">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $stats['total_words']; ?></div>
                                <div class="stat-label">Toplam Kelime</div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $stats['new_words']; ?></div>
                                <div class="stat-label">Yeni</div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $stats['learning_words']; ?></div>
                                <div class="stat-label">Öğreniliyor</div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $stats['learned_words']; ?></div>
                                <div class="stat-label">Öğrenildi</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Son Öğrenilen Kelimeler -->
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Son Öğrenilen Kelimeler</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($recentWords)): ?>
                        <div class="text-muted">Henüz öğrenilen kelime yok.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-vcenter">
                                <thead>
                                    <tr>
                                        <th>Kelime</th>
                                        <th>Anlamı</th>
                                        <th>Son Tekrar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentWords as $word): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($word['word']); ?></td>
                                        <td><?php echo htmlspecialchars($word['definition']); ?></td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($word['last_reviewed'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Test İstatistikleri -->
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Test İstatistikleri</h3>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $testStats['total_tests'] ?? 0; ?></div>
                                <div class="stat-label">Toplam Test</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $testStats['average_score'] ? round($testStats['average_score'], 1) : 0; ?></div>
                                <div class="stat-label">Ortalama Puan</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $testStats['best_score'] ? round($testStats['best_score'], 1) : 0; ?></div>
                                <div class="stat-label">En Yüksek Puan</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stat-card {
    text-align: center;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 0.5rem;
}

.stat-value {
    font-size: 2rem;
    font-weight: bold;
    color: #206bc4;
}

.stat-label {
    color: #626976;
    font-size: 0.875rem;
}


</style>

<?php
// Footer'ı dahil et
include 'includes/footer/footer.php';
?> 