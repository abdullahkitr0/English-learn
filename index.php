<?php
require_once 'config/config.php';
require_once 'functions/functions.php';

// Oturum başlat
session_start();

// Günlük kelimeleri al
$dailyWords = getDailyWords(8);

// Sayfa başlığı
$pageTitle = "İngilizce Kelime Öğrenme - Ana Sayfa";

// Header'ı dahil et
include 'includes/header/header.php';
?>

<!-- Ana Sayfa İçeriği -->
<div class="container-xl">
    <!-- Hoş Geldiniz Bölümü -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <h2 class="page-title">
                                İngilizce Kelime Öğrenmeye Hoş Geldiniz!
                            </h2>
                            <div class="page-pretitle">
                                Kelime haznenizi geliştirin, testlerle kendinizi ölçün
                            </div>
                        </div>
                        <?php if (!isLoggedIn()): ?>
                        <div class="col-auto">
                            <a href="register.php" class="btn btn-primary">
                                Hemen Üye Ol
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<style>
  .image-index-card {
    height: 140px;
    width: 100%;
    object-fit: cover;
    border-radius: 8px;
    background: #f3f3f3;
  }
</style>
    <!-- Günün Kelimeleri -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Günün Kelimeleri</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($dailyWords as $word): ?>
                        <div class="col-md-4 col-lg-3">
                            <div class="card">
                                <?php if ($word['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($word['image_url']); ?>" 
                                     class="card-img-top image-index-card"  alt="<?php echo htmlspecialchars($word['word']); ?>">
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <?php echo htmlspecialchars($word['word']); ?>
                                        <button class="btn btn-icon btn-sm" onclick="playPronunciation('<?php echo htmlspecialchars($word['word']); ?>')">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M15 8a5 5 0 0 1 0 8" />
                                                <path d="M17.7 5a9 9 0 0 1 0 14" />
                                                <path d="M6 15h-2a1 1 0 0 1 -1 -1v-4a1 1 0 0 1 1 -1h2l3.5 -4.5a.8 .8 0 0 1 1.5 .5v14a.8 .8 0 0 1 -1.5 .5l-3.5 -4.5" />
                                            </svg>
                                        </button>
                                    </h5>
                                    <p class="card-text"><?php echo htmlspecialchars($word['definition']); ?></p>
                                    <p class="card-text"><small class="text-muted">
                                        <?php echo htmlspecialchars($word['example_sentence']); ?>
                                    </small></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Özellikler -->
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title">Kelime Listeleri</h3>
                    <p class="text-muted">
                        Kategorilere ayrılmış kelime listeleri ile öğrenmeyi kolaylaştırın.
                    </p>
                    <a href="word-lists.php" class="btn btn-primary">
                        Listeleri Görüntüle
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title">Testler</h3>
                    <p class="text-muted">
                        Öğrendiklerinizi test edin ve ilerlemenizi takip edin.
                    </p>
                    <a href="tests.php" class="btn btn-primary">
                        Testlere Başla
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title">Flashcards</h3>
                    <p class="text-muted">
                        Etkileşimli flashcard'lar ile kelimeleri tekrar edin.
                    </p>
                    <a href="flashcards.php" class="btn btn-primary">
                        Flashcard'ları Aç
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Telaffuz için JavaScript -->
<script>
function playPronunciation(word) {
    fetch(`api/pronunciation.php?word=${encodeURIComponent(word)}`)
        .then(response => response.blob())
        .then(blob => {
            const audio = new Audio(URL.createObjectURL(blob));
            audio.play();
        })
        .catch(error => {
            console.error('Telaffuz yüklenirken hata:', error);
            alert('Telaffuz şu anda kullanılamıyor.');
        });
}
</script>

<?php
// Footer'ı dahil et
include 'includes/footer/footer.php';
?> 