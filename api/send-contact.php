<?php
require_once '../config/config.php';
require_once '../functions/functions.php';

session_start();

// POST verilerini al
$data = json_decode(file_get_contents('php://input'), true);

$name = isset($data['name']) ? cleanInput($data['name']) : '';
$email = isset($data['email']) ? cleanInput($data['email']) : '';
$subject = isset($data['subject']) ? cleanInput($data['subject']) : '';
$message = isset($data['message']) ? cleanInput($data['message']) : '';

// Zorunlu alanları kontrol et
if (empty($name) || empty($email) || empty($subject) || empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tüm alanlar zorunludur']);
    exit;
}

// E-posta formatını kontrol et
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz e-posta adresi']);
    exit;
}

try {
    $db = dbConnect();
    
    // İletişim mesajını veritabanına kaydet
    $stmt = $db->prepare("
        INSERT INTO contact_messages (
            name, email, subject, message, 
            user_id, ip_address, created_at
        )
        VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");
    
    $stmt->execute([
        $name,
        $email,
        $subject,
        $message,
        isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null,
        $_SERVER['REMOTE_ADDR']
    ]);
    
    // E-posta gönder
    $to = ADMIN_EMAIL;
    $subject = "İletişim Formu: " . $subject;
    
    $emailBody = "
        <h2>İletişim Formu Mesajı</h2>
        <p><strong>Ad Soyad:</strong> {$name}</p>
        <p><strong>E-posta:</strong> {$email}</p>
        <p><strong>Konu:</strong> {$subject}</p>
        <p><strong>Mesaj:</strong></p>
        <p>{$message}</p>
        <hr>
        <p><small>Bu mesaj " . date('d.m.Y H:i:s') . " tarihinde gönderilmiştir.</small></p>
    ";
    
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: ' . SITE_EMAIL,
        'Reply-To: ' . $email,
        'X-Mailer: PHP/' . phpversion()
    ];
    
    if (mail($to, $subject, $emailBody, implode("\r\n", $headers))) {
        echo json_encode(['success' => true, 'message' => 'Mesajınız başarıyla gönderildi']);
    } else {
        throw new Exception('E-posta gönderilemedi');
    }
    
} catch (Exception $e) {
    logError('Send contact error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Mesajınız gönderilirken bir hata oluştu']);
}
?> 