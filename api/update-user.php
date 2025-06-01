<?php
require_once '../config/config.php';
require_once '../functions/functions.php';

session_start();

// Admin kontrolü veya kendi profilini güncelleme kontrolü
$data = json_decode(file_get_contents('php://input'), true);
$userId = isset($data['id']) ? (int)$data['id'] : 0;

if (!isAdmin() && $_SESSION['user_id'] !== $userId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bu işlem için yetkiniz yok']);
    exit;
}

// POST verilerini al
$username = isset($data['username']) ? cleanInput($data['username']) : '';
$email = isset($data['email']) ? cleanInput($data['email']) : '';
$password = isset($data['password']) ? $data['password'] : '';
$role = isset($data['role']) ? cleanInput($data['role']) : null;
$isActive = isset($data['is_active']) ? (bool)$data['is_active'] : null;

// Zorunlu alanları kontrol et
if ($userId <= 0 || empty($username) || empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Kullanıcı ID, kullanıcı adı ve e-posta zorunludur']);
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
    
    // Kullanıcı adı veya e-posta başka kullanıcı tarafından kullanılıyor mu kontrol et
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $stmt->execute([$username, $email, $userId]);
    
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Bu kullanıcı adı veya e-posta adresi başka bir kullanıcı tarafından kullanılıyor']);
        exit;
    }
    
    // SQL sorgusunu ve parametreleri hazırla
    $sql = "UPDATE users SET username = ?, email = ?";
    $params = [$username, $email];
    
    // Şifre değiştirilecekse ekle
    if (!empty($password)) {
        $sql .= ", password = ?";
        $params[] = password_hash($password, PASSWORD_DEFAULT);
    }
    
    // Admin ise rol ve aktiflik durumunu güncelleyebilir
    if (isAdmin()) {
        if ($role !== null) {
            $sql .= ", role = ?";
            $params[] = $role;
        }
        if ($isActive !== null) {
            $sql .= ", is_active = ?";
            $params[] = $isActive;
        }
    }
    
    $sql .= ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    $params[] = $userId;
    
    // Kullanıcıyı güncelle
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    if ($stmt->rowCount() > 0) {
        // Güncel kullanıcı bilgilerini al
        $stmt = $db->prepare("SELECT id, username, email, role, is_active FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Kullanıcı başarıyla güncellendi',
            'user' => $user
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı veya değişiklik yapılmadı']);
    }
    
} catch (PDOException $e) {
    logError('Update user error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kullanıcı güncellenirken bir hata oluştu']);
}
?> 