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
    $db = dbConnect();
    
    // Resmi olmayan kelimeleri getir
    $stmt = $db->query("SELECT id, word FROM words WHERE image_url IS NULL OR image_url = ''");
    $words = $stmt->fetchAll();
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    
    foreach ($words as $word) {
        try {
            // Pexels API'sine istek at
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://api.pexels.com/v1/search?query=' . urlencode($word['word']) . '&per_page=1',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: ' . PEXELS_API_KEY
                ]
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                throw new Exception('Pexels API hatası: ' . $word['word']);
            }
            
            $data = json_decode($response, true);
            
            if (empty($data['photos'])) {
                throw new Exception('Resim bulunamadı: ' . $word['word']);
            }
            
            $photo = $data['photos'][0];
            $imageUrl = $photo['src']['medium'];
            
            // Resmi indir
            $imageContent = file_get_contents($imageUrl);
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/words/';
            
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = $word['id'] . '_' . time() . '.jpg';
            $filePath = $uploadDir . $fileName;
            
            if (file_put_contents($filePath, $imageContent)) {
                // Veritabanını güncelle
                $imageDbPath = '/uploads/words/' . $fileName;
                $updateStmt = $db->prepare("UPDATE words SET image_url = ? WHERE id = ?");
                $updateStmt->execute([$imageDbPath, $word['id']]);
                $successCount++;
            } else {
                throw new Exception('Resim kaydedilemedi: ' . $word['word']);
            }
            
            // API limit aşımını önlemek için kısa bir bekleme
            usleep(200000); // 0.2 saniye bekle
            
        } catch (Exception $e) {
            $errorCount++;
            $errors[] = $e->getMessage();
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => sprintf(
            'İşlem tamamlandı. %d kelimeye resim eklendi, %d kelimede hata oluştu.',
            $successCount,
            $errorCount
        ),
        'details' => [
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'errors' => $errors
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    logError('Add all images error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası oluştu'
    ]);
} 