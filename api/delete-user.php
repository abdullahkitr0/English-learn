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
$userId = isset($data['id']) ? (int)$data['id'] : 0;

if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz kullanıcı ID']);
    exit;
}

// Kendini silmeye çalışıyorsa engelle
if ($userId === $_SESSION['user_id']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Kendi hesabınızı silemezsiniz']);
    exit;
}

try {
    $db = dbConnect();
    
    // İşlemi transaction içinde gerçekleştir
    $db->beginTransaction();
    
    // Kullanıcının test sonuçlarını sil
    $stmt = $db->prepare("DELETE FROM test_results WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    // Kullanıcının test cevaplarını sil
    $stmt = $db->prepare("DELETE FROM test_answers WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    // Kullanıcının kelimelerini sil
    $stmt = $db->prepare("DELETE FROM user_words WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    // Kullanıcıyı sil
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    
    if ($stmt->rowCount() > 0) {
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Kullanıcı başarıyla silindi']);
    } else {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı']);
    }
    
} catch (PDOException $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    logError('Delete user error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kullanıcı silinirken bir hata oluştu']);
}
?> 