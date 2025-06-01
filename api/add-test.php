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

$title = isset($data['title']) ? cleanInput($data['title']) : '';
$description = isset($data['description']) ? cleanInput($data['description']) : '';
$type = isset($data['type']) ? cleanInput($data['type']) : '';
$categoryId = isset($data['category_id']) ? (int)$data['category_id'] : 0;
$difficultyLevel = isset($data['difficulty_level']) ? cleanInput($data['difficulty_level']) : '';
$wordCount = isset($data['word_count']) ? (int)$data['word_count'] : 10;
$timeLimit = isset($data['time_limit']) ? (int)$data['time_limit'] : 0;

// Zorunlu alanları kontrol et
if (empty($title) || empty($type) || empty($difficultyLevel)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Başlık, tür ve zorluk seviyesi zorunludur']);
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
    
    // Testi ekle
    $stmt = $db->prepare("
        INSERT INTO tests (
            title, description, type, category_id,
            difficulty_level, word_count, time_limit, created_at
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");
    
    $stmt->execute([
        $title,
        $description,
        $type,
        $categoryId ?: null,
        $difficultyLevel,
        $wordCount,
        $timeLimit
    ]);
    
    $testId = $db->lastInsertId();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Test başarıyla eklendi',
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
    
} catch (PDOException $e) {
    logError('Add test error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Test eklenirken bir hata oluştu']);
}
?> 