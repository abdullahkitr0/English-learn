<?php
session_start();
header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../functions/functions.php';

// Admin kontrolü
requireAdmin();

try {
    // JSON verisini al
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Zorunlu alanları kontrol et
    $requiredFields = ['word', 'definition', 'category_id', 'difficulty_level'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            throw new Exception("$field alanı zorunludur.");
        }
    }
    
    // Verileri temizle
    $word = cleanInput($data['word']);
    $pronunciation = !empty($data['pronunciation']) ? cleanInput($data['pronunciation']) : null;
    $definition = cleanInput($data['definition']);
    $exampleSentence = !empty($data['example_sentence']) ? cleanInput($data['example_sentence']) : null;
    $categoryId = (int)$data['category_id'];
    $difficultyLevel = cleanInput($data['difficulty_level']);
    $isApproved = isset($data['is_approved']) ? (int)$data['is_approved'] : 0;
    
    // Veritabanı bağlantısı
    $db = dbConnect();
    
    // Kelimeyi ekle
    $sql = "INSERT INTO words (word, pronunciation, definition, example_sentence, category_id, difficulty_level, is_approved, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            
    $stmt = $db->prepare($sql);
    $result = $stmt->execute([
        $word,
        $pronunciation,
        $definition,
        $exampleSentence,
        $categoryId,
        $difficultyLevel,
        $isApproved
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Kelime başarıyla eklendi.'
        ]);
    } else {
        throw new Exception('Kelime eklenirken bir hata oluştu.');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası oluştu.'
    ]);
    logError('Add word error: ' . $e->getMessage());
}
?> 