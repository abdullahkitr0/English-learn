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
$title = isset($data['title']) ? cleanInput($data['title']) : '';
$description = isset($data['description']) ? cleanInput($data['description']) : '';
$type = isset($data['type']) ? cleanInput($data['type']) : '';
$categoryId = isset($data['category_id']) ? (int)$data['category_id'] : 0;
$difficultyLevel = isset($data['difficulty_level']) ? cleanInput($data['difficulty_level']) : '';
$wordCount = isset($data['word_count']) ? (int)$data['word_count'] : 10;
$timeLimit = isset($data['time_limit']) ? (int)$data['time_limit'] : 0;

// Zorunlu alanları kontrol et
if ($testId <= 0 || empty($title) || empty($type) || empty($difficultyLevel)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Test ID, başlık, tür ve zorluk seviyesi zorunludur']);
    exit;
}

// Test türünü kontrol et
$validTypes = ['daily', 'review', 'category'];
if (!in_array($type, $validTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz test türü']);
    exit;
}

// Kategori testi için kategori ID zorunlu
if ($type === 'category' && $categoryId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Kategori testi için kategori seçilmelidir']);
    exit;
}

try {
    $db = dbConnect();
    
    // Testi güncelle
    $stmt = $db->prepare("
        UPDATE tests 
        SET title = ?,
            description = ?,
            type = ?,
            category_id = ?,
            difficulty_level = ?,
            word_count = ?,
            time_limit = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    $stmt->execute([
        $title,
        $description,
        $type,
        $categoryId ?: null,
        $difficultyLevel,
        $wordCount,
        $timeLimit,
        $testId
    ]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true, 
            'message' => 'Test başarıyla güncellendi',
            'test' => [
                'id' => $testId,
                'title' => $title,
                'description' => $description,
                'type' => $type,
                'category_id' => $categoryId,
                'difficulty_level' => $difficultyLevel,
                'word_count' => $wordCount,
                'time_limit' => $timeLimit
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Test bulunamadı']);
    }
    
} catch (PDOException $e) {
    logError('Update test error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Test güncellenirken bir hata oluştu']);
}
?> 