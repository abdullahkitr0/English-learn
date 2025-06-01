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

// POST verilerini al
$data = json_decode(file_get_contents('php://input'), true);
$wordId = isset($data['word_id']) ? (int)$data['word_id'] : 0;

if ($wordId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz kelime ID']);
    exit;
}

try {
    $db = dbConnect();
    
    // Kelimeyi kullanıcının listesinden kaldır
    $stmt = $db->prepare("DELETE FROM user_words WHERE user_id = ? AND word_id = ?");
    $stmt->execute([$_SESSION['user_id'], $wordId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Kelime başarıyla kaldırıldı']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Kelime bulunamadı']);
    }
    
} catch (PDOException $e) {
    logError('Remove word error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kelime kaldırılırken bir hata oluştu']);
}
?> 