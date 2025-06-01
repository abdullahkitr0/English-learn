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
    $email = cleanInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validasyon
    if (empty($email)) {
        $errors[] = 'E-posta adresi gereklidir.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçerli bir e-posta adresi giriniz.';
    }
    
    if (empty($password)) {
        $errors[] = 'Şifre gereklidir.';
    }
    
    // Hata yoksa giriş işlemini gerçekleştir
    if (empty($errors)) {
        try {
            $db = dbConnect();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Giriş başarılı
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                
                // Son giriş zamanını güncelle
                $stmt = $db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                // Başarılı giriş sonrası yönlendirme
                redirect('index.php');
            } else {
                $errors[] = 'E-posta adresi veya şifre hatalı.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Giriş yapılırken bir hata oluştu.';
            logError('Login error: ' . $e->getMessage());
        }
    }
}

// Sayfa başlığı
$pageTitle = "Giriş Yap - İngilizce Kelime Öğrenme";

// Header'ı dahil et
include 'includes/header/header.php';
?>

<div class="container-xl">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card card-md">
                <div class="card-body">
                    <h2 class="card-title text-center mb-4">Hesabınıza Giriş Yapın</h2>
                    
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
                            <label class="form-label">E-posta Adresi</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                   placeholder="ornek@email.com" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                Şifre
                                <span class="form-label-description">
                                    <a href="forgot-password.php">Şifremi Unuttum</a>
                                </span>
                            </label>
                            <input type="password" name="password" class="form-control" 
                                   placeholder="Şifreniz" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-check">
                                <input type="checkbox" name="remember" class="form-check-input">
                                <span class="form-check-label">Beni hatırla</span>
                            </label>
                        </div>
                        
                        <div class="form-footer">
                            <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="text-center text-muted mt-3">
                Henüz hesabınız yok mu? <a href="register.php" tabindex="-1">Ücretsiz Kayıt Olun</a>
            </div>
        </div>
    </div>
</div>

<?php
// Footer'ı dahil et
include 'includes/footer/footer.php';
?> 