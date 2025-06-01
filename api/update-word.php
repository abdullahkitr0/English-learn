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

try {
    // POST verilerini al
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Zorunlu alanları kontrol et
    if (empty($data['word_id']) || empty($data['word']) || empty($data['definition']) || empty($data['category_id'])) {
        throw new Exception('Lütfen tüm zorunlu alanları doldurun');
    }
    
    $wordId = (int)$data['word_id'];
    $word = cleanInput($data['word']);
    $pronunciation = isset($data['pronunciation']) ? cleanInput($data['pronunciation']) : '';
    $definition = cleanInput($data['definition']);
    $exampleSentence = isset($data['example_sentence']) ? cleanInput($data['example_sentence']) : '';
    $categoryId = (int)$data['category_id'];
    $difficultyLevel = cleanInput($data['difficulty_level']);
    $isApproved = isset($data['is_approved']) ? (int)$data['is_approved'] : 0;
    
    $db = dbConnect();
    
    // Kelimeyi güncelle
    $stmt = $db->prepare("
        UPDATE words 
        SET word = ?,
            pronunciation = ?,
            definition = ?,
            example_sentence = ?,
            category_id = ?,
            difficulty_level = ?,
            is_approved = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    $result = $stmt->execute([
        $word,
        $pronunciation,
        $definition,
        $exampleSentence,
        $categoryId,
        $difficultyLevel,
        $isApproved,
        $wordId
    ]);
    
    if ($result && $stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Kelime başarıyla güncellendi'
        ]);
    } else {
        throw new Exception('Kelime güncellenirken bir hata oluştu veya değişiklik yapılmadı');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    logError('Update word error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası oluştu'
    ]);
}
?> 