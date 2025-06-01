<?php
require_once '../config/config.php';
require_once '../functions/functions.php';

session_start();

// Admin kontrolü
requireAdmin();

// Sayfa başlığı
$pageTitle = "Kelime Ekle - Yönetim Paneli";

// Header'ı dahil et
include '../includes/header/header.php';

// Kategorileri getir
try {
    $db = dbConnect();
    $categories = $db->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
} catch (PDOException $e) {
    $categories = [];
    logError('Get categories error: ' . $e->getMessage());
}
?>

<div class="container-xl">
    <div class="page-header d-print-none">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">Yeni Kelime Ekle</h2>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <form id="addWordForm" class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label required">Kelime</label>
                        <input type="text" class="form-control" name="word" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Telaffuz</label>
                        <input type="text" class="form-control" name="pronunciation" 
                               placeholder="Örnek: /həˈləʊ/">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label required">Tanım</label>
                        <textarea class="form-control" name="definition" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Örnek Cümle</label>
                        <textarea class="form-control" name="example_sentence" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required">Kategori</label>
                            <select class="form-select" name="category_id" required>
                                <option value="">Seçiniz</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label required">Zorluk Seviyesi</label>
                            <select class="form-select" name="difficulty_level" required>
                                <option value="beginner">Başlangıç</option>
                                <option value="intermediate">Orta</option>
                                <option value="advanced">İleri</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Resim URL</label>
                        <input type="url" class="form-control" name="image_url" 
                               placeholder="https://example.com/image.jpg">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Ses URL</label>
                        <input type="url" class="form-control" name="audio_url" 
                               placeholder="https://example.com/audio.mp3">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_approved" checked>
                            <span class="form-check-label">Onaylı olarak ekle</span>
                        </label>
                    </div>
                </div>
                
                <div class="card-footer text-end">
                    <a href="words.php" class="btn btn-link">İptal</a>
                    <button type="submit" class="btn btn-primary">Kelimeyi Ekle</button>
                </div>
            </form>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Yardım</h3>
                </div>
                <div class="card-body">
                    <h4>Zorunlu Alanlar</h4>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="ti ti-point-filled text-primary me-2"></i>
                            Kelime
                        </li>
                        <li class="mb-2">
                            <i class="ti ti-point-filled text-primary me-2"></i>
                            Tanım
                        </li>
                        <li class="mb-2">
                            <i class="ti ti-point-filled text-primary me-2"></i>
                            Kategori
                        </li>
                        <li class="mb-2">
                            <i class="ti ti-point-filled text-primary me-2"></i>
                            Zorluk Seviyesi
                        </li>
                    </ul>
                    
                    <h4 class="mt-4">İpuçları</h4>
                    <ul class="list-unstyled text-muted">
                        <li class="mb-2">
                            <i class="ti ti-info-circle me-2"></i>
                            Telaffuz için IPA formatını kullanın
                        </li>
                        <li class="mb-2">
                            <i class="ti ti-info-circle me-2"></i>
                            Örnek cümle anlaşılır olmalı
                        </li>
                        <li class="mb-2">
                            <i class="ti ti-info-circle me-2"></i>
                            Resim ve ses için geçerli URL'ler kullanın
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('addWordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {};
    
    formData.forEach((value, key) => {
        if (key === 'is_approved') {
            data[key] = true;
        } else {
            data[key] = value;
        }
    });
    
    // is_approved checkbox işaretli değilse false olarak ayarla
    if (!formData.has('is_approved')) {
        data.is_approved = false;
    }
    
    fetch('../api/add-word.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.message);
            setTimeout(() => {
                window.location.href = 'words.php';
            }, 1500);
        } else {
            showToast('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'Kelime eklenirken bir hata oluştu');
    });
});
</script>

<?php
// Footer'ı dahil et
include '../includes/footer/footer.php';
?> 