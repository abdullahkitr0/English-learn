<?php
require_once 'config/config.php';
require_once 'functions/functions.php';

session_start();

// Sayfa başlığı
$pageTitle = "İletişim - İngilizce Kelime Öğrenme";

// Header'ı dahil et
include 'includes/header/header.php';
?>

<div class="container-xl">
    <div class="page-header d-print-none">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">İletişim</h2>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title">İletişim Formu</h3>
                    <form id="contactForm">
                        <div class="mb-3">
                            <label class="form-label required">Ad Soyad</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label required">E-posta</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label required">Konu</label>
                            <select class="form-select" name="subject" required>
                                <option value="">Seçiniz</option>
                                <option value="general">Genel Bilgi</option>
                                <option value="support">Teknik Destek</option>
                                <option value="suggestion">Öneri</option>
                                <option value="complaint">Şikayet</option>
                                <option value="other">Diğer</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label required">Mesaj</label>
                            <textarea class="form-control" name="message" rows="6" required></textarea>
                        </div>
                        
                        <div class="form-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-send me-2"></i>
                                Gönder
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title">İletişim Bilgileri</h3>
                    
                    <div class="list-group list-group-flush">
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="avatar bg-blue-lt">
                                        <i class="ti ti-mail"></i>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="text-truncate">E-posta</div>
                                    <strong>info@example.com</strong>
                                </div>
                            </div>
                        </div>
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="avatar bg-green-lt">
                                        <i class="ti ti-phone"></i>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="text-truncate">Telefon</div>
                                    <strong>+90 (212) 123 45 67</strong>
                                </div>
                            </div>
                        </div>
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="avatar bg-yellow-lt">
                                        <i class="ti ti-map-pin"></i>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="text-truncate">Adres</div>
                                    <strong>İstanbul, Türkiye</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-body">
                    <h3 class="card-title">Sosyal Medya</h3>
                    
                    <div class="list-group list-group-flush">
                        <a href="#" class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="avatar bg-facebook">
                                        <i class="ti ti-brand-facebook"></i>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="text-truncate">Facebook</div>
                                    <strong>@englishlearning</strong>
                                </div>
                            </div>
                        </a>
                        <a href="#" class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="avatar bg-twitter">
                                        <i class="ti ti-brand-twitter"></i>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="text-truncate">Twitter</div>
                                    <strong>@englishlearning</strong>
                                </div>
                            </div>
                        </a>
                        <a href="#" class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="avatar bg-instagram">
                                        <i class="ti ti-brand-instagram"></i>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="text-truncate">Instagram</div>
                                    <strong>@englishlearning</strong>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-body">
                    <h3 class="card-title">SSS</h3>
                    <p class="text-muted">
                        Sıkça sorulan sorular için SSS sayfamızı ziyaret edebilirsiniz.
                    </p>
                    <a href="#" class="btn btn-primary w-100">
                        <i class="ti ti-help me-2"></i>
                        SSS'ye Git
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// İletişim formunu gönder
document.getElementById('contactForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('api/send-contact.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Mesajınız başarıyla gönderildi');
            this.reset();
        } else {
            showToast('error', data.message || 'Mesaj gönderilirken hata oluştu');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'Mesaj gönderilirken hata oluştu');
    });
});
</script>

<?php
// Footer'ı dahil et
include 'includes/footer/footer.php';
?> 