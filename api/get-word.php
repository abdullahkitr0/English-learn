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
    // GET parametresini al
    $wordId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($wordId <= 0) {
        throw new Exception('Geçersiz kelime ID');
    }

    $db = dbConnect();
    
    // Kelime bilgilerini al
    $stmt = $db->prepare("
        SELECT w.*, c.name as category_name
        FROM words w
        LEFT JOIN categories c ON w.category_id = c.id
        WHERE w.id = ?
    ");
    
    $stmt->execute([$wordId]);
    $word = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($word) {
        echo json_encode([
            'success' => true,
            'word' => $word
        ]);
    } else {
        throw new Exception('Kelime bulunamadı');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    logError('Get word error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası oluştu'
    ]);
}
?> 