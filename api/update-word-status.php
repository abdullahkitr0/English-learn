<?php
require_once '../config/config.php';
require_once '../functions/functions.php';

header('Content-Type: application/json');

// AJAX isteği kontrolü
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['error' => 'Bu endpoint sadece AJAX istekleri için kullanılabilir']);
    exit;
}

// Oturum kontrolü
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Oturum açmanız gerekiyor']);
    exit;
}

// POST verilerini al
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['word_id']) || !isset($input['status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Geçersiz istek parametreleri']);
    exit;
}

$word_id = (int)$input['word_id'];
$status = $input['status'];

// Durum değerini kontrol et
if (!in_array($status, ['learning', 'mastered'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Geçersiz durum değeri']);
    exit;
}

try {
    $db = dbConnect();
    
    // Önce kelime kullanıcının listesinde var mı kontrol et
    $checkStmt = $db->prepare("
        SELECT user_id, word_id 
        FROM user_words 
        WHERE user_id = :user_id AND word_id = :word_id
    ");
    
    $checkStmt->execute([
        'user_id' => $_SESSION['user_id'],
        'word_id' => $word_id
    ]);
    
    if ($checkStmt->rowCount() === 0) {
        // Kelime kullanıcının listesinde yoksa ekle
        $insertStmt = $db->prepare("
            INSERT INTO user_words (user_id, word_id, status, created_at, last_reviewed)
            VALUES (:user_id, :word_id, :status, NOW(), NOW())
        ");
        
        $result = $insertStmt->execute([
            'user_id' => $_SESSION['user_id'],
            'word_id' => $word_id,
            'status' => $status
        ]);
    } else {
        // Kelime varsa durumunu güncelle
        $updateStmt = $db->prepare("
            UPDATE user_words 
            SET status = :status,
                last_reviewed = NOW()
            WHERE user_id = :user_id 
            AND word_id = :word_id
        ");
        
        $result = $updateStmt->execute([
            'status' => $status,
            'user_id' => $_SESSION['user_id'],
            'word_id' => $word_id
        ]);
    }
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Kelime durumu başarıyla güncellendi'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Güncelleme başarısız oldu'
        ]);
    }
    
} catch (PDOException $e) {
    logError('Update word status error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Kelime durumu güncellenirken bir hata oluştu: ' . $e->getMessage()
    ]);
} 