<?php
require_once '../config/config.php';
require_once '../functions/functions.php';

header('Content-Type: application/json');

session_start();

// Admin kontrolü
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bu işlem için yetkiniz yok']);
    exit;
}

try {
    // JSON verisini al
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id']) || !isset($data['name'])) {
        throw new Exception('Gerekli alanlar eksik');
    }
    
    $db = dbConnect();
    
    // Aynı isimde başka kategori var mı kontrol et
    $stmt = $db->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
    $stmt->execute([$data['name'], $data['id']]);
    if ($stmt->fetch()) {
        throw new Exception('Bu isimde başka bir kategori zaten var');
    }
    
    // Kategoriyi güncelle
    $stmt = $db->prepare("
        UPDATE categories 
        SET name = ?, 
            description = ?, 
            parent_id = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $data['name'],
        $data['description'] ?? null,
        empty($data['parent_id']) ? null : $data['parent_id'],
        $data['id']
    ]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Kategori güncellenemedi veya değişiklik yapılmadı');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Kategori başarıyla güncellendi'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 