<?php
require_once '../config/config.php';
require_once '../functions/functions.php';

header('Content-Type: application/json');

try {
    // JSON verisini al
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        throw new Exception('Kategori ID\'si gerekli');
    }
    
    $db = dbConnect();
    
    // Transaction başlat
    $db->beginTransaction();
    
    try {
        // Alt kategorileri kontrol et
        $stmt = $db->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
        $stmt->execute([$data['id']]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Bu kategorinin alt kategorileri var. Önce alt kategorileri silmelisiniz.');
        }
        
        // Kategoriye ait kelimeleri kontrol et
        $stmt = $db->prepare("SELECT COUNT(*) FROM words WHERE category_id = ?");
        $stmt->execute([$data['id']]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Bu kategoride kelimeler var. Önce kelimeleri silmelisiniz.');
        }
        
        // Kategoriyi sil
        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$data['id']]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Kategori bulunamadı');
        }
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Kategori başarıyla silindi'
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 