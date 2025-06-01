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
    // Kelime ID'sini al
    $wordId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($wordId <= 0) {
        throw new Exception('Geçersiz kelime ID');
    }
    
    $db = dbConnect();
    
    // Kelimeyi getir
    $stmt = $db->prepare("SELECT word FROM words WHERE id = ?");
    $stmt->execute([$wordId]);
    $word = $stmt->fetchColumn();
    
    if (!$word) {
        throw new Exception('Kelime bulunamadı');
    }
    
    // Pexels API'sine istek at
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.pexels.com/v1/search?query=' . urlencode($word) . '&per_page=1',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: ' . PEXELS_API_KEY
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('Pexels API hatası');
    }
    
    $data = json_decode($response, true);
    
    if (empty($data['photos'])) {
        throw new Exception('Resim bulunamadı');
    }
    
    $photo = $data['photos'][0];
    $imageUrl = $photo['src']['medium']; // veya 'large' kullanabilirsiniz
    
    // Resmi indir
    $imageContent = file_get_contents($imageUrl);
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/words/';
    
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileName = $wordId . '_' . time() . '.jpg';
    $filePath = $uploadDir . $fileName;
    
    if (file_put_contents($filePath, $imageContent)) {
        // Veritabanını güncelle
        $imageDbPath = '/uploads/words/' . $fileName;
        $stmt = $db->prepare("UPDATE words SET image_url = ? WHERE id = ?");
        $stmt->execute([$imageDbPath, $wordId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Resim başarıyla eklendi',
            'image_url' => $imageDbPath
        ]);
    } else {
        throw new Exception('Resim kaydedilemedi');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    logError('Get word image error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası oluştu'
    ]);
} 