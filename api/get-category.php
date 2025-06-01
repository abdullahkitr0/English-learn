<?php
require_once '../config/config.php';
require_once '../functions/functions.php';

session_start();

// Giriş kontrolü
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Giriş yapmanız gerekiyor']);
    exit;
}

// GET parametresini al
$categoryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($categoryId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz kategori ID']);
    exit;
}

try {
    $db = dbConnect();
    
    // Kategori bilgilerini al
    $stmt = $db->prepare("
        SELECT c.*, 
               p.name as parent_name,
               (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) as subcategory_count,
               (SELECT COUNT(*) FROM words WHERE category_id = c.id AND is_approved = 1) as word_count
        FROM categories c
        LEFT JOIN categories p ON c.parent_id = p.id
        WHERE c.id = ?
    ");
    
    $stmt->execute([$categoryId]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($category) {
        // Alt kategorileri al
        $stmt = $db->prepare("
            SELECT id, name, description,
                   (SELECT COUNT(*) FROM words WHERE category_id = c.id AND is_approved = 1) as word_count
            FROM categories c
            WHERE parent_id = ?
            ORDER BY name
        ");
        $stmt->execute([$categoryId]);
        $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Kategori istatistiklerini al
        $stats = [
            'total_words' => $category['word_count'],
            'total_subcategories' => $category['subcategory_count'],
            'learning_users' => $db->prepare("
                SELECT COUNT(DISTINCT uw.user_id)
                FROM user_words uw
                JOIN words w ON uw.word_id = w.id
                WHERE w.category_id = ? AND uw.status = 'learning'
            ")->execute([$categoryId])->fetchColumn(),
            'mastered_users' => $db->prepare("
                SELECT COUNT(DISTINCT uw.user_id)
                FROM user_words uw
                JOIN words w ON uw.word_id = w.id
                WHERE w.category_id = ? AND uw.status = 'mastered'
            ")->execute([$categoryId])->fetchColumn()
        ];
        
        $category['subcategories'] = $subcategories;
        $category['stats'] = $stats;
        
        echo json_encode(['success' => true, 'category' => $category]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Kategori bulunamadı']);
    }
    
} catch (PDOException $e) {
    logError('Get category error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kategori bilgileri alınırken bir hata oluştu']);
}
?> 