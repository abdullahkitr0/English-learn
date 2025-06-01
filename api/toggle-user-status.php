<?php
require_once '../config/config.php';
require_once '../functions/functions.php';

header('Content-Type: application/json');

try {
    // JSON verisini al
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id']) || !isset($data['is_active'])) {
        throw new Exception('Gerekli alanlar eksik');
    }
    
    $db = dbConnect();
    
    // Kendini pasifleştirmeye çalışıyorsa engelle
    if ($data['id'] == $_SESSION['user_id']) {
        throw new Exception('Kendi hesabınızın durumunu değiştiremezsiniz');
    }
    
    // Kullanıcı durumunu güncelle
    $stmt = $db->prepare("
        UPDATE users 
        SET is_active = ?, 
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    $stmt->execute([
        $data['is_active'] ? 1 : 0,
        $data['id']
    ]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Kullanıcı bulunamadı');
    }
    
    echo json_encode([
        'success' => true,
        'message' => $data['is_active'] ? 'Kullanıcı aktifleştirildi' : 'Kullanıcı pasifleştirildi'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 