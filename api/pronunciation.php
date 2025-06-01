<?php
require_once '../config/config.php';
require_once '../functions/functions.php';

session_start();

// Giriş kontrolü
if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Giriş yapmanız gerekiyor']);
    exit;
}

// Kelimeyi al
$word = isset($_GET['word']) ? cleanInput($_GET['word']) : '';

if (empty($word)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Kelime parametresi gereklidir']);
    exit;
}

try {
    $db = dbConnect();
    
    // Kelimeye ait ses dosyası var mı kontrol et
    $stmt = $db->prepare("SELECT audio_url FROM words WHERE word = ?");
    $stmt->execute([$word]);
    $audioUrl = $stmt->fetchColumn();
    
    if ($audioUrl && file_exists($_SERVER['DOCUMENT_ROOT'] . $audioUrl)) {
        // Yerel ses dosyasını gönder
        $file = $_SERVER['DOCUMENT_ROOT'] . $audioUrl;
        header('Content-Type: audio/mpeg');
        header('Content-Length: ' . filesize($file));
        readfile($file);
    } else {
        // Text-to-Speech API'sini kullan
        $apiKey = TEXT_TO_SPEECH_API_KEY;
        $apiUrl = "https://api.voicerss.org/";
        
        $params = [
            'key' => $apiKey,
            'src' => $word,
            'hl' => 'en-us',
            'v' => 'Linda',
            'r' => '0',
            'c' => 'mp3',
            'f' => '44khz_16bit_stereo'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl . '?' . http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($httpCode === 200) {
            // Ses dosyasını kaydet
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/audio/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = md5($word) . '.mp3';
            $filePath = $uploadDir . $fileName;
            file_put_contents($filePath, $response);
            
            // Veritabanını güncelle
            $audioUrl = '/uploads/audio/' . $fileName;
            $stmt = $db->prepare("UPDATE words SET audio_url = ? WHERE word = ?");
            $stmt->execute([$audioUrl, $word]);
            
            // Ses dosyasını gönder
            header('Content-Type: audio/mpeg');
            header('Content-Length: ' . strlen($response));
            echo $response;
        } else {
            throw new Exception('Text-to-Speech API hatası');
        }
        
        curl_close($ch);
    }
    
} catch (Exception $e) {
    logError('Pronunciation error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Telaffuz alınırken bir hata oluştu']);
}
?> 