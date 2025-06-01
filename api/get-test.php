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
$testId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($testId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz test ID']);
    exit;
}

try {
    $db = dbConnect();
    
    // Test bilgilerini al
    $stmt = $db->prepare("
        SELECT t.*, c.name as category_name,
               (SELECT COUNT(*) FROM test_results WHERE test_id = t.id) as total_attempts,
               (SELECT AVG(score) FROM test_results WHERE test_id = t.id) as average_score,
               (SELECT MAX(score) FROM test_results WHERE test_id = t.id) as best_score
        FROM tests t
        LEFT JOIN categories c ON t.category_id = c.id
        WHERE t.id = ?
    ");
    
    $stmt->execute([$testId]);
    $test = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($test) {
        // Kullanıcının bu testteki sonuçlarını al
        $stmt = $db->prepare("
            SELECT tr.*, 
                   (SELECT COUNT(*) FROM test_answers WHERE test_result_id = tr.id AND is_correct = 1) as correct_count,
                   (SELECT COUNT(*) FROM test_answers WHERE test_result_id = tr.id AND is_correct = 0) as incorrect_count
            FROM test_results tr
            WHERE tr.test_id = ? AND tr.user_id = ?
            ORDER BY tr.created_at DESC
        ");
        
        $stmt->execute([$testId, $_SESSION['user_id']]);
        $userResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Test istatistiklerini al
        $stats = [
            'total_attempts' => $test['total_attempts'],
            'average_score' => round($test['average_score'], 2),
            'best_score' => $test['best_score'],
            'user_attempts' => count($userResults),
            'user_best_score' => !empty($userResults) ? max(array_column($userResults, 'score')) : 0,
            'user_average_score' => !empty($userResults) ? round(array_sum(array_column($userResults, 'score')) / count($userResults), 2) : 0
        ];
        
        $test['stats'] = $stats;
        $test['user_results'] = $userResults;
        
        echo json_encode(['success' => true, 'test' => $test]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Test bulunamadı']);
    }
    
} catch (PDOException $e) {
    logError('Get test error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Test bilgileri alınırken bir hata oluştu']);
}
?> 