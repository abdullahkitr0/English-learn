<?php
require_once 'config/config.php';
require_once 'functions/functions.php';

session_start();

// Sayfa başlığı
$pageTitle = "Kullanım Şartları - İngilizce Kelime Öğrenme";

// Header'ı dahil et
include 'includes/header/header.php';
?>

<div class="container-xl">
    <div class="page-header d-print-none">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">Kullanım Şartları</h2>
                <div class="text-muted mt-1">
                    Son güncelleme: <?php echo date('d.m.Y'); ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="markdown">
                <h3>1. Genel Hükümler</h3>
                <p>
                    Bu kullanım şartları, İngilizce Kelime Öğrenme platformunun kullanımına ilişkin kuralları 
                    ve koşulları belirlemektedir. Platformu kullanarak bu şartları kabul etmiş sayılırsınız.
                </p>
                
                <h3>2. Hesap Oluşturma ve Güvenlik</h3>
                <p>Kullanıcılar aşağıdaki kurallara uymakla yükümlüdür:</p>
                <ul>
                    <li>Doğru ve güncel bilgiler sağlamak</li>
                    <li>Hesap güvenliğini korumak</li>
                    <li>Şifre ve hesap bilgilerini gizli tutmak</li>
                    <li>Yetkisiz erişimi bildirmek</li>
                    <li>Hesap paylaşımı yapmamak</li>
                </ul>
                
                <h3>3. Kullanım Kuralları</h3>
                <p>Platform kullanımında aşağıdaki kurallara uyulmalıdır:</p>
                <ul>
                    <li>Telif haklarına saygı göstermek</li>
                    <li>Diğer kullanıcılara saygılı davranmak</li>
                    <li>Spam ve zararlı içerik paylaşmamak</li>
                    <li>Platformun güvenliğini tehdit etmemek</li>
                    <li>Yasa dışı faaliyetlerde bulunmamak</li>
                </ul>
                
                <h3>4. İçerik ve Telif Hakları</h3>
                <p>
                    Platform içeriğine ilişkin kurallar:
                </p>
                <ul>
                    <li>Tüm içerik telif hakları ile korunmaktadır</li>
                    <li>İçeriğin izinsiz kopyalanması ve dağıtılması yasaktır</li>
                    <li>Kullanıcı içerikleri platformla paylaşılabilir</li>
                    <li>Platform, uygunsuz içeriği kaldırma hakkına sahiptir</li>
                </ul>
                
                <h3>5. Hizmet Kullanımı</h3>
                <p>
                    Platform hizmetlerinin kullanımına ilişkin kurallar:
                </p>
                <ul>
                    <li>Hizmetler kişisel kullanım içindir</li>
                    <li>Ticari amaçla kullanım yasaktır</li>
                    <li>API ve sistem kötüye kullanımı yasaktır</li>
                    <li>Otomatik araçlar ve botlar kullanılamaz</li>
                </ul>
                
                <h3>6. Sorumluluk Reddi</h3>
                <p>
                    Platform aşağıdaki konularda sorumluluk kabul etmemektedir:
                </p>
                <ul>
                    <li>Kullanıcı hatalarından kaynaklanan sorunlar</li>
                    <li>Servis kesintileri ve teknik arızalar</li>
                    <li>Üçüncü taraf hizmetlerden kaynaklanan sorunlar</li>
                    <li>Veri kayıpları ve güvenlik ihlalleri</li>
                </ul>
                
                <h3>7. Hesap Sonlandırma</h3>
                <p>
                    Platform aşağıdaki durumlarda hesapları sonlandırma hakkına sahiptir:
                </p>
                <ul>
                    <li>Kullanım şartlarının ihlali</li>
                    <li>Yasadışı faaliyetler</li>
                    <li>Spam ve kötüye kullanım</li>
                    <li>Uzun süreli inaktiflik</li>
                </ul>
                
                <h3>8. Değişiklikler</h3>
                <p>
                    Platform, kullanım şartlarını önceden haber vermeksizin değiştirme hakkına sahiptir. 
                    Önemli değişiklikler kullanıcılara bildirilecektir.
                </p>
                
                <h3>9. İletişim</h3>
                <p>
                    Kullanım şartları hakkında sorularınız için iletişim bilgileri:
                </p>
                <ul>
                    <li>E-posta: terms@example.com</li>
                    <li>Telefon: +90 (212) 123 45 67</li>
                    <li>Adres: İstanbul, Türkiye</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
// Footer'ı dahil et
include 'includes/footer/footer.php';
?> 