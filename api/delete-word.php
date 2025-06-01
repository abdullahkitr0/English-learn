<?php
require_once '../config/config.php';
require_once '../functions/functions.php';

session_start();

header('Content-Type: application/json');

// Admin kontrolü
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bu işlem için yetkiniz yok']);
    exit;
}

// POST verilerini al
$data = json_decode(file_get_contents('php://input'), true);
$wordId = isset($data['id']) ? (int)$data['id'] : 0;

if ($wordId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz kelime ID']);
    exit;
}

try {
    $db = dbConnect();
    
    // İşlemi transaction içinde gerçekleştir
    $db->beginTransaction();
    
    // Önce kelimeye bağlı test cevaplarını sil
    $stmt = $db->prepare("DELETE FROM test_answers WHERE word_id = ?");
    $stmt->execute([$wordId]);
    
    // Kullanıcı kelimelerini sil
    $stmt = $db->prepare("DELETE FROM user_words WHERE word_id = ?");
    $stmt->execute([$wordId]);
    
    // Kelimeyi sil
    $stmt = $db->prepare("DELETE FROM words WHERE id = ?");
    $stmt->execute([$wordId]);
    
    if ($stmt->rowCount() > 0) {
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Kelime başarıyla silindi']);
    } else {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Kelime bulunamadı']);
    }
    
} catch (PDOException $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    logError('Delete word error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kelime silinirken bir hata oluştu']);
}
?> 