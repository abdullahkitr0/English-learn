<?php
require_once 'config/config.php';
require_once 'functions/functions.php';

session_start();

// Sayfa başlığı
$pageTitle = "Hakkımızda - İngilizce Kelime Öğrenme";

// Header'ı dahil et
include 'includes/header/header.php';
?>

<div class="container-xl">
    <div class="page-header d-print-none">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">Hakkımızda</h2>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h3>İngilizce Kelime Öğrenme Platformu</h3>
                    <p class="text-muted">
                        İngilizce kelime öğrenme platformumuz, kullanıcılarımızın İngilizce kelime dağarcığını 
                        geliştirmelerine yardımcı olmak için tasarlanmış interaktif bir eğitim platformudur.
                    </p>
                    
                    <h4 class="mt-4">Misyonumuz</h4>
                    <p>
                        Kullanıcılarımıza etkili ve eğlenceli bir kelime öğrenme deneyimi sunarak, 
                        İngilizce dil becerilerini geliştirmelerine yardımcı olmak.
                    </p>
                    
                    <h4 class="mt-4">Özelliklerimiz</h4>
                    <ul class="list-unstyled">
                        <li class="d-flex align-items-center mb-3">
                            <span class="avatar bg-blue-lt me-3">
                                <i class="ti ti-cards"></i>
                            </span>
                            <div>
                                <h5 class="mb-1">Kelime Kartları</h5>
                                <p class="text-muted mb-0">
                                    İnteraktif kelime kartları ile etkili öğrenme
                                </p>
                            </div>
                        </li>
                        <li class="d-flex align-items-center mb-3">
                            <span class="avatar bg-green-lt me-3">
                                <i class="ti ti-writing"></i>
                            </span>
                            <div>
                                <h5 class="mb-1">Testler</h5>
                                <p class="text-muted mb-0">
                                    Öğrenilen kelimeleri pekiştirmek için testler
                                </p>
                            </div>
                        </li>
                        <li class="d-flex align-items-center mb-3">
                            <span class="avatar bg-yellow-lt me-3">
                                <i class="ti ti-volume"></i>
                            </span>
                            <div>
                                <h5 class="mb-1">Telaffuz Desteği</h5>
                                <p class="text-muted mb-0">
                                    Kelimelerin doğru telaffuzunu öğrenme
                                </p>
                            </div>
                        </li>
                        <li class="d-flex align-items-center">
                            <span class="avatar bg-red-lt me-3">
                                <i class="ti ti-chart-line"></i>
                            </span>
                            <div>
                                <h5 class="mb-1">İlerleme Takibi</h5>
                                <p class="text-muted mb-0">
                                    Öğrenme sürecini detaylı olarak takip etme
                                </p>
                            </div>
                        </li>
                    </ul>
                    
                    <h4 class="mt-4">Neden Biz?</h4>
                    <div class="row">
                        <div class="col-sm-6">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="ti ti-check text-success me-2"></i>
                                    Ücretsiz kullanım
                                </li>
                                <li class="mb-2">
                                    <i class="ti ti-check text-success me-2"></i>
                                    Zengin kelime havuzu
                                </li>
                                <li class="mb-2">
                                    <i class="ti ti-check text-success me-2"></i>
                                    Kategorize edilmiş içerik
                                </li>
                            </ul>
                        </div>
                        <div class="col-sm-6">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="ti ti-check text-success me-2"></i>
                                    Kişiselleştirilmiş öğrenme
                                </li>
                                <li class="mb-2">
                                    <i class="ti ti-check text-success me-2"></i>
                                    Düzenli güncellenen içerik
                                </li>
                                <li class="mb-2">
                                    <i class="ti ti-check text-success me-2"></i>
                                    7/24 destek
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <span class="avatar avatar-xl bg-blue-lt">
                                    <i class="ti ti-book"></i>
                                </span>
                            </div>
                            <h3 class="card-title mb-2">İstatistikler</h3>
                            <?php
                            try {
                                $db = dbConnect();
                                $stats = [
                                    'users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
                                    'words' => $db->query("SELECT COUNT(*) FROM words WHERE is_approved = 1")->fetchColumn(),
                                    'categories' => $db->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
                                    'tests' => $db->query("SELECT COUNT(*) FROM tests")->fetchColumn()
                                ];
                            } catch (PDOException $e) {
                                $stats = ['users' => 0, 'words' => 0, 'categories' => 0, 'tests' => 0];
                                logError('About page stats error: ' . $e->getMessage());
                            }
                            ?>
                            <div class="list-group list-group-flush">
                                <div class="list-group-item">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <i class="ti ti-users text-primary"></i>
                                        </div>
                                        <div class="col">
                                            <div class="text-truncate">Toplam Kullanıcı</div>
                                            <strong><?php echo number_format($stats['users']); ?></strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="list-group-item">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <i class="ti ti-book text-green"></i>
                                        </div>
                                        <div class="col">
                                            <div class="text-truncate">Kelime Sayısı</div>
                                            <strong><?php echo number_format($stats['words']); ?></strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="list-group-item">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <i class="ti ti-category text-yellow"></i>
                                        </div>
                                        <div class="col">
                                            <div class="text-truncate">Kategori Sayısı</div>
                                            <strong><?php echo number_format($stats['categories']); ?></strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="list-group-item">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <i class="ti ti-writing text-azure"></i>
                                        </div>
                                        <div class="col">
                                            <div class="text-truncate">Test Sayısı</div>
                                            <strong><?php echo number_format($stats['tests']); ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mt-3">
                        <div class="card-body">
                            <h3 class="card-title">İletişim</h3>
                            <p class="text-muted">
                                Sorularınız veya önerileriniz için bizimle iletişime geçebilirsiniz.
                            </p>
                            <a href="contact.php" class="btn btn-primary w-100">
                                <i class="ti ti-mail me-2"></i>
                                İletişime Geç
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Footer'ı dahil et
include 'includes/footer/footer.php';
?> 