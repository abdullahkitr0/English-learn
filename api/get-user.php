<?php
require_once '../config/config.php';
require_once '../functions/functions.php';

session_start();

// Giriş kontrolü
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Giriş yapmanız gerekiyor']);
    exit;
}

// GET parametresini al
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ID belirtilmemişse kendi bilgilerini göster
if ($userId <= 0) {
    $userId = $_SESSION['user_id'];
}

// Başka kullanıcının bilgilerini görüntülemek için admin olmalı
if ($userId !== $_SESSION['user_id'] && !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bu işlem için yetkiniz yok']);
    exit;
}

try {
    $db = dbConnect();
    
    // Kullanıcı bilgilerini al
    $stmt = $db->prepare("
        SELECT id, username, email, role, is_active, created_at, updated_at
        FROM users
        WHERE id = ?
    ");
    
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Kullanıcı istatistiklerini al
        $stats = [
            'total_words' => $db->prepare("
                SELECT COUNT(*) FROM user_words WHERE user_id = ?
            ")->execute([$userId])->fetchColumn(),
            
            'learning_words' => $db->prepare("
                SELECT COUNT(*) FROM user_words WHERE user_id = ? AND status = 'learning'
            ")->execute([$userId])->fetchColumn(),
            
            'mastered_words' => $db->prepare("
                SELECT COUNT(*) FROM user_words WHERE user_id = ? AND status = 'mastered'
            ")->execute([$userId])->fetchColumn(),
            
            'total_tests' => $db->prepare("
                SELECT COUNT(*) FROM test_results WHERE user_id = ?
            ")->execute([$userId])->fetchColumn(),
            
            'average_score' => $db->prepare("
                SELECT AVG(score) FROM test_results WHERE user_id = ?
            ")->execute([$userId])->fetchColumn(),
            
            'best_score' => $db->prepare("
                SELECT MAX(score) FROM test_results WHERE user_id = ?
            ")->execute([$userId])->fetchColumn(),
            
            'total_correct' => $db->prepare("
                SELECT COUNT(*) FROM test_answers WHERE user_id = ? AND is_correct = 1
            ")->execute([$userId])->fetchColumn(),
            
            'total_incorrect' => $db->prepare("
                SELECT COUNT(*) FROM test_answers WHERE user_id = ? AND is_correct = 0
            ")->execute([$userId])->fetchColumn()
        ];
        
        // Son aktiviteleri al
        $activities = [
            'recent_words' => $db->prepare("
                SELECT w.word, w.definition, uw.status, uw.created_at
                FROM user_words uw
                JOIN words w ON uw.word_id = w.id
                WHERE uw.user_id = ?
                ORDER BY uw.created_at DESC
                LIMIT 5
            ")->execute([$userId])->fetchAll(),
            
            'recent_tests' => $db->prepare("
                SELECT tr.*, t.title, t.type
                FROM test_results tr
                LEFT JOIN tests t ON tr.test_id = t.id
                WHERE tr.user_id = ?
                ORDER BY tr.created_at DESC
                LIMIT 5
            ")->execute([$userId])->fetchAll()
        ];
        
        $user['stats'] = $stats;
        $user['activities'] = $activities;
        
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı']);
    }
    
} catch (PDOException $e) {
    logError('Get user error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kullanıcı bilgileri alınırken bir hata oluştu']);
}
?> 