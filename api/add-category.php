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

// POST verilerini al
$data = json_decode(file_get_contents('php://input'), true);

$name = isset($data['name']) ? cleanInput($data['name']) : '';
$description = isset($data['description']) ? cleanInput($data['description']) : '';
$parentId = isset($data['parent_id']) && $data['parent_id'] > 0 ? (int)$data['parent_id'] : null;

// Zorunlu alanları kontrol et
if (empty($name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Kategori adı zorunludur']);
    exit;
}

try {
    $db = dbConnect();
    
    // Aynı isimde kategori var mı kontrol et
    $stmt = $db->prepare("SELECT COUNT(*) FROM categories WHERE name = ?");
    $stmt->execute([$name]);
    
    if ($stmt->fetchColumn() > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Bu isimde bir kategori zaten var']);
        exit;
    }
    
    // Kategoriyi ekle
    $stmt = $db->prepare("
        INSERT INTO categories (name, description, parent_id, created_at)
        VALUES (?, ?, ?, CURRENT_TIMESTAMP)
    ");
    
    $stmt->execute([$name, $description, $parentId]);
    $categoryId = $db->lastInsertId();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Kategori başarıyla eklendi',
        'category' => [
            'id' => $categoryId,
            'name' => $name,
            'description' => $description,
            'parent_id' => $parentId
        ]
    ]);
    
} catch (PDOException $e) {
    logError('Add category error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kategori eklenirken bir hata oluştu: ' . $e->getMessage()]);
}
?> 