<?php
require_once '../config/config.php';
require_once '../functions/functions.php';

session_start();

// Admin kontrolü
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bu işlem için yetkiniz yok']);
    exit;
}

// POST verilerini al
$data = json_decode(file_get_contents('php://input'), true);

$username = isset($data['username']) ? cleanInput($data['username']) : '';
$email = isset($data['email']) ? cleanInput($data['email']) : '';
$password = isset($data['password']) ? $data['password'] : '';
$role = isset($data['role']) ? cleanInput($data['role']) : 'user';
$isActive = isset($data['is_active']) ? (bool)$data['is_active'] : true;

// Zorunlu alanları kontrol et
if (empty($username) || empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Kullanıcı adı, e-posta ve şifre zorunludur']);
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
    
    // Kullanıcı adı veya e-posta kullanılıyor mu kontrol et
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Bu kullanıcı adı veya e-posta adresi zaten kullanılıyor']);
        exit;
    }
    
    // Şifreyi hashle
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Kullanıcıyı ekle
    $stmt = $db->prepare("
        INSERT INTO users (username, email, password, role, is_active, created_at)
        VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");
    
    $stmt->execute([$username, $email, $hashedPassword, $role, $isActive]);
    $userId = $db->lastInsertId();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Kullanıcı başarıyla eklendi',
        'user' => [
            'id' => $userId,
            'username' => $username,
            'email' => $email,
            'role' => $role,
            'is_active' => $isActive
        ]
    ]);
    
} catch (PDOException $e) {
    logError('Add user error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kullanıcı eklenirken bir hata oluştu']);
}
?> 