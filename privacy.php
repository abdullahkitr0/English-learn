<?php
require_once 'config/config.php';
require_once 'functions/functions.php';

session_start();

// Sayfa başlığı
$pageTitle = "Gizlilik Politikası - İngilizce Kelime Öğrenme";

// Header'ı dahil et
include 'includes/header/header.php';
?>

<div class="container-xl">
    <div class="page-header d-print-none">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">Gizlilik Politikası</h2>
                <div class="text-muted mt-1">
                    Son güncelleme: <?php echo date('d.m.Y'); ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="markdown">
                <h3>1. Veri Toplama ve Kullanım</h3>
                <p>
                    İngilizce Kelime Öğrenme platformu olarak, kullanıcılarımızın gizliliğine önem veriyoruz. 
                    Bu politika, hangi verileri topladığımızı ve nasıl kullandığımızı açıklamaktadır.
                </p>
                
                <h4>1.1. Toplanan Veriler</h4>
                <ul>
                    <li>Kullanıcı adı ve e-posta adresi</li>
                    <li>Öğrenme istatistikleri ve ilerleme bilgileri</li>
                    <li>Test sonuçları ve performans verileri</li>
                    <li>Kelime listeleri ve çalışma durumları</li>
                    <li>Sistem kullanım logları</li>
                </ul>
                
                <h3>2. Veri Güvenliği</h3>
                <p>
                    Kullanıcı verilerinin güvenliği için aşağıdaki önlemleri alıyoruz:
                </p>
                <ul>
                    <li>Şifrelerin güvenli bir şekilde hashlenmesi</li>
                    <li>SSL/TLS ile şifreli iletişim</li>
                    <li>Düzenli güvenlik güncellemeleri</li>
                    <li>Veritabanı yedekleme ve koruma</li>
                    <li>Erişim kontrolü ve yetkilendirme</li>
                </ul>
                
                <h3>3. Çerezler ve Oturum Bilgileri</h3>
                <p>
                    Platformumuzda kullanıcı deneyimini iyileştirmek için çerezler kullanılmaktadır:
                </p>
                <ul>
                    <li>Oturum yönetimi için gerekli çerezler</li>
                    <li>Kullanıcı tercihlerini hatırlama</li>
                    <li>Platform performansını izleme</li>
                    <li>Güvenlik kontrolleri</li>
                </ul>
                
                <h3>4. Üçüncü Taraf Hizmetler</h3>
                <p>
                    Platformumuzda kullanılan üçüncü taraf hizmetler:
                </p>
                <ul>
                    <li>Pexels API (kelime resimleri için)</li>
                    <li>Text-to-Speech API (telaffuz için)</li>
                    <li>Google reCAPTCHA (güvenlik için)</li>
                </ul>
                
                <h3>5. Kullanıcı Hakları</h3>
                <p>
                    Kullanıcılarımız aşağıdaki haklara sahiptir:
                </p>
                <ul>
                    <li>Kişisel verilere erişim hakkı</li>
                    <li>Veri düzeltme ve güncelleme hakkı</li>
                    <li>Hesap silme ve veri kaldırma hakkı</li>
                    <li>Veri taşınabilirliği hakkı</li>
                    <li>İtiraz ve şikayet hakkı</li>
                </ul>
                
                <h3>6. Veri Saklama</h3>
                <p>
                    Kullanıcı verilerinin saklanması ile ilgili politikalarımız:
                </p>
                <ul>
                    <li>Aktif hesap verileri süresiz saklanır</li>
                    <li>Silinen hesap verileri 30 gün içinde tamamen kaldırılır</li>
                    <li>Sistem logları 6 ay süreyle tutulur</li>
                    <li>Yedeklemeler 3 ay süreyle saklanır</li>
                </ul>
                
                <h3>7. İletişim</h3>
                <p>
                    Gizlilik politikamız hakkında sorularınız için:
                </p>
                <ul>
                    <li>E-posta: privacy@example.com</li>
                    <li>Telefon: +90 (212) 123 45 67</li>
                    <li>Adres: İstanbul, Türkiye</li>
                </ul>
                
                <h3>8. Güncellemeler</h3>
                <p>
                    Bu gizlilik politikası periyodik olarak güncellenebilir. Önemli değişiklikler 
                    olması durumunda kullanıcılarımız e-posta yoluyla bilgilendirilecektir.
                </p>
            </div>
        </div>
    </div>
</div>

<?php
// Footer'ı dahil et
include 'includes/footer/footer.php';
?>
