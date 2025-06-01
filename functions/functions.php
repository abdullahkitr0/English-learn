<?php
require_once __DIR__ . '/../config/config.php';

// Veritabanı bağlantı fonksiyonu
function dbConnect() {
    static $conn;
    if ($conn === null) {
        try {
            $conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Veritabanı bağlantı hatası: " . $e->getMessage());
        }
    }
    return $conn;
}

// Güvenlik fonksiyonları
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generateToken() {
    return bin2hex(random_bytes(32));
}

function verifyToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Oturum yönetimi
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        redirect('index.php');
    }
}

// Yönlendirme ve URL fonksiyonları
function redirect($path) {
    header("Location: " . BASE_URL . "/" . $path);
    exit();
}

function getCurrentUrl() {
    return (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
}

// Kelime yönetimi fonksiyonları
function getWordById($wordId) {
    $db = dbConnect();
    $stmt = $db->prepare("SELECT * FROM words WHERE id = ?");
    $stmt->execute([$wordId]);
    return $stmt->fetch();
}

function getDailyWords($limit = 5) {
    $db = dbConnect();
    $stmt = $db->prepare("
        SELECT * FROM words 
        WHERE is_approved = 1 
        ORDER BY RAND() 
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function getUserProgress($userId) {
    $db = dbConnect();
    $stmt = $db->prepare("
        SELECT 
            COUNT(CASE WHEN status = 'mastered' THEN 1 END) as mastered,
            COUNT(CASE WHEN status = 'learning' THEN 1 END) as learning,
            COUNT(CASE WHEN status = 'new' THEN 1 END) as new
        FROM user_words 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

// Test yönetimi fonksiyonları
function createTest($userId, $categoryId = null, $difficulty = 'beginner') {
    $db = dbConnect();
    $stmt = $db->prepare("
        INSERT INTO tests (title, description, difficulty_level, category_id, created_by)
        VALUES (?, ?, ?, ?, ?)
    ");
    $title = "Test " . date('Y-m-d H:i:s');
    $description = "Otomatik oluşturulan test";
    $stmt->execute([$title, $description, $difficulty, $categoryId, $userId]);
    return $db->lastInsertId();
}

function getTestResults($userId, $limit = 10) {
    $db = dbConnect();
    $stmt = $db->prepare("
        SELECT tr.*, t.title
        FROM test_results tr
        JOIN tests t ON tr.test_id = t.id
        WHERE tr.user_id = ?
        ORDER BY tr.completed_at DESC
        LIMIT ?
    ");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll();
}

// Başarı sistemi fonksiyonları
function checkAndAwardAchievements($userId) {
    $progress = getUserProgress($userId);
    $db = dbConnect();
    
    // Başarıları kontrol et
    $achievements = [
        ['name' => 'Başlangıç', 'condition' => $progress['mastered'] >= 10],
        ['name' => 'Çalışkan Öğrenci', 'condition' => $progress['mastered'] >= 50],
        ['name' => 'Kelime Ustası', 'condition' => $progress['mastered'] >= 100]
    ];
    
    foreach ($achievements as $achievement) {
        if ($achievement['condition']) {
            $stmt = $db->prepare("
                INSERT IGNORE INTO user_achievements (user_id, achievement_id)
                SELECT ?, id FROM achievements WHERE name = ?
            ");
            $stmt->execute([$userId, $achievement['name']]);
        }
    }
}

// Cache fonksiyonları
function getCache($key) {
    if (!CACHE_ENABLED) return false;
    $filename = CACHE_PATH . '/' . md5($key);
    if (file_exists($filename) && (time() - filemtime($filename)) < CACHE_LIFETIME) {
        return unserialize(file_get_contents($filename));
    }
    return false;
}

function setCache($key, $data) {
    if (!CACHE_ENABLED) return false;
    $filename = CACHE_PATH . '/' . md5($key);
    return file_put_contents($filename, serialize($data));
}

// Hata ve log fonksiyonları
function logError($message, $context = []) {
    $logMessage = date('Y-m-d H:i:s') . " - " . $message . " - " . json_encode($context) . PHP_EOL;
    error_log($logMessage, 3, ROOT_PATH . '/logs/error.log');
}

// API entegrasyon fonksiyonları
function getWordPronunciation($word) {
    // Text-to-Speech API entegrasyonu
    $url = "https://api.texttospeech.com/v1/speech";
    $data = [
        'text' => $word,
        'language' => 'en-US'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . TEXT_TO_SPEECH_API_KEY,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}

function getWordImage($word) {
    // Unsplash API entegrasyonu
    $url = "https://api.unsplash.com/search/photos?query=" . urlencode($word) . "&per_page=1";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Client-ID ' . UNSPLASH_API_KEY
    ]);
    
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);
    
    return $response['results'][0]['urls']['regular'] ?? null;
}

// Pagination fonksiyonu
function getPagination($total, $page, $perPage = ITEMS_PER_PAGE) {
    $totalPages = ceil($total / $perPage);
    $page = max(1, min($page, $totalPages));
    
    return [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'per_page' => $perPage,
        'total_items' => $total,
        'offset' => ($page - 1) * $perPage
    ];
}

// Kullanıcı işlemleri

/**
 * Kullanıcı kaydı oluşturur
 * @param string $username
 * @param string $email
 * @param string $password
 * @return array
 */
function createUser($username, $email, $password) {
    global $db;
    try {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hashedPassword]);
        return ['success' => true, 'message' => 'Kullanıcı başarıyla oluşturuldu.'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Kullanıcı oluşturulurken hata: ' . $e->getMessage()];
    }
}

/**
 * Kullanıcı girişi yapar
 * @param string $email
 * @param string $password
 * @return array
 */
function loginUser($email, $password) {
    global $db;
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Son giriş zamanını güncelle
            $updateStmt = $db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
            $updateStmt->execute([$user['id']]);

            return ['success' => true, 'message' => 'Giriş başarılı.'];
        }
        return ['success' => false, 'message' => 'Geçersiz e-posta veya şifre.'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Giriş yapılırken hata: ' . $e->getMessage()];
    }
}

/**
 * Kullanıcı çıkışı yapar
 */
function logoutUser() {
    session_destroy();
    return ['success' => true, 'message' => 'Çıkış başarılı.'];
}

// Kelime işlemleri

/**
 * Yeni kelime ekler
 * @param array $wordData
 * @return array
 */
function addWord($wordData) {
    global $db;
    try {
        $stmt = $db->prepare("
            INSERT INTO words (word, pronunciation, definition, example_sentence, 
                             image_url, audio_url, category_id, difficulty_level, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $wordData['word'],
            $wordData['pronunciation'],
            $wordData['definition'],
            $wordData['example_sentence'],
            $wordData['image_url'],
            $wordData['audio_url'],
            $wordData['category_id'],
            $wordData['difficulty_level'],
            $wordData['created_by']
        ]);
        return ['success' => true, 'message' => 'Kelime başarıyla eklendi.'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Kelime eklenirken hata: ' . $e->getMessage()];
    }
}

/**
 * Kelime listesini getirir
 * @param array $filters
 * @return array
 */
function getWords($filters = []) {
    global $db;
    try {
        $sql = "SELECT w.*, c.name as category_name 
                FROM words w 
                LEFT JOIN categories c ON w.category_id = c.id 
                WHERE 1=1";
        $params = [];

        if (!empty($filters['category_id'])) {
            $sql .= " AND w.category_id = ?";
            $params[] = $filters['category_id'];
        }

        if (!empty($filters['difficulty_level'])) {
            $sql .= " AND w.difficulty_level = ?";
            $params[] = $filters['difficulty_level'];
        }

        if (isset($filters['is_approved'])) {
            $sql .= " AND w.is_approved = ?";
            $params[] = $filters['is_approved'];
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return ['success' => true, 'data' => $stmt->fetchAll()];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Kelimeler getirilirken hata: ' . $e->getMessage()];
    }
}

// Test işlemleri

/**
 * Test sonucunu kaydeder
 * @param array $resultData
 * @return array
 */
function saveTestResult($resultData) {
    global $db;
    try {
        $stmt = $db->prepare("
            INSERT INTO test_results (user_id, test_id, score, completion_time)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $resultData['user_id'],
            $resultData['test_id'],
            $resultData['score'],
            $resultData['completion_time']
        ]);
        return ['success' => true, 'message' => 'Test sonucu başarıyla kaydedildi.'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Test sonucu kaydedilirken hata: ' . $e->getMessage()];
    }
}

// Başarı sistemi

/**
 * Kullanıcının başarılarını kontrol eder ve yeni başarılar ekler
 * @param int $userId
 * @return array
 */
function checkAchievements($userId) {
    global $db;
    try {
        // Kullanıcının istatistiklerini al
        $stmt = $db->prepare("
            SELECT 
                COUNT(DISTINCT word_id) as total_words,
                COUNT(DISTINCT CASE WHEN status = 'mastered' THEN word_id END) as mastered_words,
                COUNT(DISTINCT test_id) as completed_tests
            FROM user_words uw
            LEFT JOIN test_results tr ON tr.user_id = uw.user_id
            WHERE uw.user_id = ?
        ");
        $stmt->execute([$userId]);
        $stats = $stmt->fetch();

        // Mevcut başarıları kontrol et ve yenilerini ekle
        $achievements = [];
        
        // Kelime sayısı başarıları
        if ($stats['total_words'] >= 100) {
            $achievements[] = ['name' => 'Kelime Ustası', 'description' => '100 kelime öğrendiniz!'];
        }
        
        // Test başarıları
        if ($stats['completed_tests'] >= 10) {
            $achievements[] = ['name' => 'Test Şampiyonu', 'description' => '10 test tamamladınız!'];
        }

        // Yeni başarıları ekle
        foreach ($achievements as $achievement) {
            $stmt = $db->prepare("
                INSERT IGNORE INTO user_achievements (user_id, achievement_id)
                SELECT ?, id FROM achievements WHERE name = ?
            ");
            $stmt->execute([$userId, $achievement['name']]);
        }

        return ['success' => true, 'achievements' => $achievements];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Başarılar kontrol edilirken hata: ' . $e->getMessage()];
    }
}

// Yardımcı fonksiyonlar

/**
 * Kullanıcının yetkisini kontrol eder
 * @param string $requiredRole
 * @return bool
 */
function checkUserRole($requiredRole) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $requiredRole;
}

/**
 * CSRF token kontrolü yapar
 * @param string $token
 * @return bool
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Dosya yükleme işlemini gerçekleştirir
 * @param array $file
 * @param string $targetDir
 * @return array
 */
function uploadFile($file, $targetDir) {
    try {
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $fileName = uniqid() . '_' . basename($file['name']);
        $targetPath = $targetDir . '/' . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['success' => true, 'path' => $targetPath];
        }
        return ['success' => false, 'message' => 'Dosya yüklenemedi.'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Dosya yükleme hatası: ' . $e->getMessage()];
    }
}

?> 