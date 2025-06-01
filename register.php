<?php
require_once 'config/config.php';
require_once 'functions/functions.php';

session_start();

// Kullanıcı zaten giriş yapmışsa ana sayfaya yönlendir
if (isLoggedIn()) {
    redirect('index.php');
}

$errors = [];
$success = false;

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = cleanInput($_POST['username'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    // Validasyon
    if (empty($username)) {
        $errors[] = 'Kullanıcı adı gereklidir.';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = 'Kullanıcı adı 3-50 karakter arasında olmalıdır.';
    }
    
    if (empty($email)) {
        $errors[] = 'E-posta adresi gereklidir.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçerli bir e-posta adresi giriniz.';
    }
    
    if (empty($password)) {
        $errors[] = 'Şifre gereklidir.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Şifre en az 6 karakter olmalıdır.';
    }
    
    if ($password !== $password_confirm) {
        $errors[] = 'Şifreler eşleşmiyor.';
    }
    
    // Hata yoksa kayıt işlemini gerçekleştir
    if (empty($errors)) {
        try {
            $db = dbConnect();
            
            // Kullanıcı adı ve e-posta kontrolü
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'Bu kullanıcı adı veya e-posta adresi zaten kullanılıyor.';
            } else {
                // Yeni kullanıcı kaydı
                $stmt = $db->prepare("
                    INSERT INTO users (username, email, password, role, created_at)
                    VALUES (?, ?, ?, 'user', CURRENT_TIMESTAMP)
                ");
                
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt->execute([$username, $email, $hashedPassword]);
                
                // Başarılı kayıt sonrası otomatik giriş
                $userId = $db->lastInsertId();
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $username;
                $_SESSION['user_role'] = 'user';
                
                // Başarılı kayıt sonrası yönlendirme
                redirect('index.php');
            }
        } catch (PDOException $e) {
            $errors[] = 'Kayıt olurken bir hata oluştu.';
            logError('Registration error: ' . $e->getMessage());
        }
    }
}

// Sayfa başlığı
$pageTitle = "Kayıt Ol - İngilizce Kelime Öğrenme";

// Header'ı dahil et
include 'includes/header/header.php';
?>

<div class="container-xl">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card card-md">
                <div class="card-body">
                    <h2 class="card-title text-center mb-4">Yeni Hesap Oluşturun</h2>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" novalidate>
                        <div class="mb-3">
                            <label class="form-label">Kullanıcı Adı</label>
                            <input type="text" name="username" class="form-control" 
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                   placeholder="kullaniciadi" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">E-posta Adresi</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                   placeholder="ornek@email.com" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Şifre</label>
                            <input type="password" name="password" class="form-control" 
                                   placeholder="Şifreniz (en az 6 karakter)" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Şifre Tekrar</label>
                            <input type="password" name="password_confirm" class="form-control" 
                                   placeholder="Şifrenizi tekrar girin" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-check">
                                <input type="checkbox" name="terms" class="form-check-input" required>
                                <span class="form-check-label">
                                    <a href="terms.php" target="_blank">Kullanım şartlarını</a> ve 
                                    <a href="privacy.php" target="_blank">gizlilik politikasını</a> kabul ediyorum
                                </span>
                            </label>
                        </div>
                        
                        <div class="form-footer">
                            <button type="submit" class="btn btn-primary w-100">Kayıt Ol</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="text-center text-muted mt-3">
                Zaten hesabınız var mı? <a href="login.php" tabindex="-1">Giriş Yapın</a>
            </div>
        </div>
    </div>
</div>

<?php
// Footer'ı dahil et
include 'includes/footer/footer.php';
?> 