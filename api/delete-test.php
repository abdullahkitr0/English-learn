<?php
require_once '../config/config.php';
require_once '../functions/functions.php';

session_start();

// Admin kontrolü
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bu işlem için yetkiniz yok']);
    exit;
}

// POST verilerini al
$data = json_decode(file_get_contents('php://input'), true);
$testId = isset($data['id']) ? (int)$data['id'] : 0;

if ($testId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz test ID']);
    exit;
}

try {
    $db = dbConnect();
    
    // İşlemi transaction içinde gerçekleştir
    $db->beginTransaction();
    
    // Test cevaplarını sil
    $stmt = $db->prepare("DELETE FROM test_answers WHERE test_id = ?");
    $stmt->execute([$testId]);
    
    // Test sonuçlarını sil
    $stmt = $db->prepare("DELETE FROM test_results WHERE test_id = ?");
    $stmt->execute([$testId]);
    
    // Testi sil
    $stmt = $db->prepare("DELETE FROM tests WHERE id = ?");
    $stmt->execute([$testId]);
    
    if ($stmt->rowCount() > 0) {
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Test başarıyla silindi']);
    } else {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Test bulunamadı']);
    }
    
} catch (PDOException $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    logError('Delete test error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Test silinirken bir hata oluştu']);
}
?> 