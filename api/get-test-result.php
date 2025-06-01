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
$resultId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($resultId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz sonuç ID']);
    exit;
}

try {
    $db = dbConnect();
    
    // Test sonucunu al
    $stmt = $db->prepare("
        SELECT tr.*, t.title, t.description, t.type, t.difficulty_level,
               c.name as category_name
        FROM test_results tr
        LEFT JOIN tests t ON tr.test_id = t.id
        LEFT JOIN categories c ON t.category_id = c.id
        WHERE tr.id = ? AND (tr.user_id = ? OR ?)
    ");
    
    $stmt->execute([$resultId, $_SESSION['user_id'], isAdmin()]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // Test cevaplarını al
        $stmt = $db->prepare("
            SELECT ta.*, w.word, w.definition
            FROM test_answers ta
            JOIN words w ON ta.word_id = w.id
            WHERE ta.test_result_id = ?
            ORDER BY ta.id
        ");
        
        $stmt->execute([$resultId]);
        $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Sonuç istatistiklerini hesapla
        $stats = [
            'total_questions' => count($answers),
            'correct_count' => array_reduce($answers, function($carry, $item) {
                return $carry + ($item['is_correct'] ? 1 : 0);
            }, 0),
            'accuracy_rate' => round(array_reduce($answers, function($carry, $item) {
                return $carry + ($item['is_correct'] ? 1 : 0);
            }, 0) / count($answers) * 100, 2),
            'time_per_question' => round($result['time_spent'] / count($answers), 2)
        ];
        
        $result['stats'] = $stats;
        $result['answers'] = $answers;
        
        echo json_encode(['success' => true, 'result' => $result]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Test sonucu bulunamadı']);
    }
    
} catch (PDOException $e) {
    logError('Get test result error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Test sonucu alınırken bir hata oluştu']);
}
?> 