<?php
session_start();
header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../functions/functions.php';

// Giriş kontrolü
requireLogin();

try {
    // JSON verisini al
    $data = json_decode(file_get_contents('php://input'), true);
    
    // word_id kontrolü
    if (empty($data['word_id'])) {
        throw new Exception("Kelime ID'si gereklidir.");
    }
    
    $wordId = (int)$data['word_id'];
    $userId = $_SESSION['user_id'];
    
    // Veritabanı bağlantısı
    $db = dbConnect();
    
    // Kelime var mı kontrol et
    $stmt = $db->prepare("SELECT id FROM words WHERE id = ? AND is_approved = 1");
    $stmt->execute([$wordId]);
    if (!$stmt->fetch()) {
        throw new Exception("Kelime bulunamadı.");
    }
    
    // Kelime zaten eklenmiş mi kontrol et
    $stmt = $db->prepare("SELECT word_id FROM user_words WHERE user_id = ? AND word_id = ?");
    $stmt->execute([$userId, $wordId]);
    if ($stmt->fetch()) {
        throw new Exception("Bu kelime zaten listenizde bulunuyor.");
    }
    
    // Kelimeyi kullanıcının listesine ekle
    $sql = "INSERT INTO user_words (user_id, word_id, status, created_at) VALUES (?, ?, 'new', NOW())";
    $stmt = $db->prepare($sql);
    $result = $stmt->execute([$userId, $wordId]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Kelime listenize eklendi.'
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
    logError('Add to list error: ' . $e->getMessage());
}
?> 