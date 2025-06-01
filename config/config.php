<?php
// Veritabanı bağlantı bilgileri
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'eng');

// Site URL ve yol sabitleri
define('BASE_URL', 'http://localhost');
define('ROOT_PATH', dirname(__DIR__));

// API Anahtarları
define('RECAPTCHA_SITE_KEY', 'YOUR_RECAPTCHA_SITE_KEY');
define('RECAPTCHA_SECRET_KEY', 'YOUR_RECAPTCHA_SECRET_KEY');
define('UNSPLASH_API_KEY', 'YOUR_UNSPLASH_API_KEY');
define('TEXT_TO_SPEECH_API_KEY', 'TEXT_TO_SPEECH_API_KEY');
define('PEXELS_API_KEY', 'PEXELS_API_KEY');

// Oturum ayarları
define('SESSION_LIFETIME', 3600); // 1 saat
define('COOKIE_LIFETIME', 604800); // 1 hafta

// Güvenlik ayarları
define('HASH_COST', 12); // Password hash maliyeti
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 dakika

// Dosya yükleme ayarları
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('UPLOAD_PATH', ROOT_PATH . '/uploads');

// Cache ayarları
define('CACHE_ENABLED', true);
define('CACHE_LIFETIME', 3600);
define('CACHE_PATH', ROOT_PATH . '/cache');

// Hata raporlama ayarları
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', ROOT_PATH . '/logs/error.log');

// Zaman dilimi ayarı
date_default_timezone_set('Europe/Istanbul');

// Dil ayarları
define('DEFAULT_LANGUAGE', 'tr');
define('AVAILABLE_LANGUAGES', ['tr', 'en']);

// Pagination ayarları
define('ITEMS_PER_PAGE', 20);

// Kelime öğrenme ayarları
define('DAILY_WORD_LIMIT', 20);
define('REVIEW_INTERVALS', [
    1 => 24, // 1 gün
    2 => 72, // 3 gün
    3 => 168, // 1 hafta
    4 => 336, // 2 hafta
    5 => 720  // 1 ay
]);

// Site e-posta ayarları
define('SITE_EMAIL', 'info@example.com');
define('ADMIN_EMAIL', 'admin@example.com');

// CSRF koruması için token oluşturma
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?> 
