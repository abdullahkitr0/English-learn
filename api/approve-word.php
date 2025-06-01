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
    
    // ID kontrolü
    if (!isset($data['word_id']) || !is_numeric($data['word_id'])) {
        throw new Exception('Geçersiz kelime ID\'si');
    }
    
    $wordId = (int)$data['word_id'];
    
    // Veritabanı bağlantısı
    $db = dbConnect();
    
    // Kelimeyi onayla
    $sql = "UPDATE words SET is_approved = 1, updated_at = NOW() WHERE id = ?";
    $stmt = $db->prepare($sql);
    $result = $stmt->execute([$wordId]);
    
    if ($result && $stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Kelime başarıyla onaylandı.'
        ]);
    } else {
        throw new Exception('Kelime onaylanırken bir hata oluştu veya kelime bulunamadı.');
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
    logError('Approve word error: ' . $e->getMessage());
}
?> 