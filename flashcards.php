<?php
require_once 'config/config.php';
require_once 'functions/functions.php';

session_start();

// Giriş kontrolü
requireLogin();

try {
    $db = dbConnect();
    
    // Filtreleme parametrelerini al
    $category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;
    $difficulty = isset($_GET['difficulty']) ? $_GET['difficulty'] : null;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    
    // SQL sorgusunu oluştur
    $sql = "
        SELECT w.*, uw.status, uw.last_reviewed, c.name as category_name
        FROM user_words uw
        JOIN words w ON uw.word_id = w.id
        LEFT JOIN categories c ON w.category_id = c.id
        WHERE uw.user_id = :user_id
    ";
    
    $params = ['user_id' => $_SESSION['user_id']];
    
    if ($category_id) {
        $sql .= " AND w.category_id = :category_id";
        $params['category_id'] = $category_id;
    }
    
    if ($difficulty) {
        $sql .= " AND w.difficulty_level = :difficulty";
        $params['difficulty'] = $difficulty;
    }
    
    if ($status) {
        $sql .= " AND uw.status = :status";
        $params['status'] = $status;
    }
    
    $sql .= " ORDER BY uw.last_reviewed ASC, RAND()";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $words = $stmt->fetchAll();
    
    // Kategorileri al
    $categories = $db->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Veritabanı hatası: ' . $e->getMessage();
    logError('Flashcards error: ' . $e->getMessage());
}

// Sayfa başlığı
$pageTitle = "Kelime Kartları - İngilizce Kelime Öğrenme";

// Header'ı dahil et
include 'includes/header/header.php';
?>
<style>
/* Toast stilleri */
.toast {
    position: fixed;
    bottom: 20px;
    right: 20px;
    padding: 15px 25px;
    border-radius: 4px;
    color: white;
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
    z-index: 1000;
}

.toast.show {
    opacity: 1;
}

.toast-success {
    background-color: #2fb344;
}

.toast-error {
    background-color: #d63939;
}

/* Buton stilleri */
.btn-learning.active {
    background-color: #206bc4;
    color: white;
}

.btn-learned.active {
    background-color: #2fb344;
    color: white;
}
</style>
<div class="container-xl">
    <div class="page-header d-print-none">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">Kelime Kartları</h2>
                <div class="text-muted mt-1">
                    Kelimelerinizi kartlar ile tekrar edin
                </div>
            </div>
        </div>
    </div>
    
    <?php if (empty($words)): ?>
        <div class="empty">
            <div class="empty-icon">
                <i class="ti ti-cards"></i>
            </div>
            <p class="empty-title">Henüz kelime eklenmemiş</p>
            <p class="empty-subtitle text-muted">
                Kelime kartları ile çalışmak için önce kelime listenize kelime eklemelisiniz.
            </p>
            <div class="empty-action">
                <a href="word-lists.php" class="btn btn-primary">
                    <i class="ti ti-plus"></i>
                    Kelime Ekle
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Filtreleme Kontrolleri -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-3">
                        <select name="category" class="form-select">
                            <option value="">Tüm Kategoriler</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                    <?php echo isset($_GET['category']) && $_GET['category'] == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="difficulty" class="form-select">
                            <option value="">Tüm Zorluk Seviyeleri</option>
                            <option value="beginner" <?php echo isset($_GET['difficulty']) && $_GET['difficulty'] == 'beginner' ? 'selected' : ''; ?>>Başlangıç</option>
                            <option value="intermediate" <?php echo isset($_GET['difficulty']) && $_GET['difficulty'] == 'intermediate' ? 'selected' : ''; ?>>Orta</option>
                            <option value="advanced" <?php echo isset($_GET['difficulty']) && $_GET['difficulty'] == 'advanced' ? 'selected' : ''; ?>>İleri</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">Tüm Durumlar</option>
                            <option value="learning" <?php echo isset($_GET['status']) && $_GET['status'] == 'learning' ? 'selected' : ''; ?>>Öğreniliyor</option>
                            <option value="mastered" <?php echo isset($_GET['status']) && $_GET['status'] == 'mastered' ? 'selected' : ''; ?>>Öğrenildi</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-filter"></i>
                            Filtrele
                        </button>
                    </div>
                </form>
            </div>
        </div>
          <!-- İstatistikler -->
    <div class="flashcard-stats mb-4">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="stat-item">
                    <div class="stat-value" id="currentCardNumber">0</div>
                    <div class="stat-label">Mevcut Kart</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <div class="stat-value" id="totalCards"><?php echo count($words); ?></div>
                    <div class="stat-label">Toplam Kart</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <div class="stat-value" id="viewedCards">0</div>
                    <div class="stat-label">Görüntülenen</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <div class="stat-value" id="remainingCards"><?php echo count($words); ?></div>
                    <div class="stat-label">Kalan</div>
                </div>
            </div>
        </div>
    </div>
     
        <!-- Flashcard İstatistikleri 
        <div class="flashcard-stats">
            <div class="stat-item">
                <div class="stat-value"><?php echo countWordsByStatus($words, 'learning'); ?></div>
                <div class="stat-label">Öğreniliyor</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo countWordsByStatus($words, 'reviewing'); ?></div>
                <div class="stat-label">Tekrar</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo countWordsByStatus($words, 'learned'); ?></div>
                <div class="stat-label">Öğrenildi</div>
            </div>
        </div>
                        -->
                    <!-- İlerleme Çubuğu -->
    <div class="progress-container mb-4">
        <div class="progress">
            <div class="progress-bar" role="progressbar" style="width: <?php echo calculateProgress($words); ?>%" id="progressBar"></div>
        </div>
    </div>
        <!-- Flashcard Alanı -->
        <div class="flashcard-container">
            <?php foreach ($words as $index => $word): ?>
                <div class="flashcard <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>">
                    <div class="flashcard-front">
                       <!--  <div class="flashcard-word">
                            <?php echo htmlspecialchars($word['word']); ?>
                        </div> -->
                        <div class="flashcard-word">
                        <?php echo htmlspecialchars($word['word']); ?>
                        <button class="pronunciation-btn" onclick="playPronunciation('<?php echo htmlspecialchars($word['word']); ?>')">
                            <i class="ti ti-volume"></i>
                        </button>
                    </div>
                    </div>
                    <div class="flashcard-back">
                        <div class="flashcard-definition">
                            <?php echo htmlspecialchars($word['definition']); ?>
                        </div>
                        <?php if (!empty($word['example_sentence'])): ?>
                            <div class="flashcard-example">
                                <?php echo htmlspecialchars($word['example_sentence']); ?>
                            </div>
						<br>
                        <?php endif; ?>
                        <?php if (!empty($word['image_url'])): ?>
                            <div class="flashcard-image">
                                <img src="<?php echo htmlspecialchars($word['image_url']); ?>" width="50%" height="50%" alt="Kelime Resmi">
                            </div>

                        <?php endif; ?>
                        
                        <div class="flashcard-controls">

                            <button class="control-btn btn-learning" onclick="updateWordStatus(<?php echo (int)$word['id']; ?>, 'learning')">
                                <i class="ti ti-brain me-1"></i>
                                Öğreniyorum
                            </button>
                            <button class="control-btn btn-learned" onclick="updateWordStatus(<?php echo (int)$word['id']; ?>, 'learned')">
                                <i class="ti ti-check me-1"></i>
                                Öğrendim
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <script>
            // Resim tıklandığında modal açma
            document.addEventListener('DOMContentLoaded', function() {
                const flashcardImages = document.querySelectorAll('.flashcard-image img');
                
                flashcardImages.forEach(img => {
                    img.addEventListener('click', function() {
                        showImageModal(this.src);
                    });
                });
            });

            // Resim modalını gösterme fonksiyonu
            function showImageModal(imageSrc) {
                // Varsa eski modalı kaldır
                const existingModal = document.querySelector('.image-modal');
                if (existingModal) {
                    existingModal.remove();
                }

                // Yeni modal oluştur
                const modal = document.createElement('div');
                modal.className = 'image-modal';
                modal.innerHTML = `
                    <div class="modal-content">
                        <span class="close-modal">&times;</span>
                        <img src="${imageSrc}" alt="Büyük Resim">
                    </div>
                `;

                // Modalı sayfaya ekle
                document.body.appendChild(modal);

                // Modalı göster
                modal.style.display = 'flex';

                // Kapatma düğmesi ve modal dışı tıklama
                const closeBtn = modal.querySelector('.close-modal');
                closeBtn.onclick = function() {
                    modal.style.display = 'none';
                }

                modal.onclick = function(e) {
                    if (e.target === modal) {
                        modal.style.display = 'none';
                    }
                }
            }
            </script>

            <style>
            .image-modal {
                display: none;
                position: fixed;
                z-index: 9999;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.9);
                justify-content: center;
                align-items: center;
            }

            .modal-content {
                position: relative;
                max-width: 90%;
                max-height: 90%;
            }

            .modal-content img {
                max-width: 100%;
                max-height: 90vh;
                object-fit: contain;
                border-radius: 8px;
            }

            .close-modal {
                position: absolute;
                top: -30px;
                right: -30px;
                color: #fff;
                font-size: 30px;
                font-weight: bold;
                cursor: pointer;
            }

            .close-modal:hover {
                color: #ccc;
            }
            </style>
            <style>
                .flashcard-image {
                    display: none; /* Başlangıçta gizli */
                    justify-content: center;
                    align-items: center;
                    margin: 15px 0;
                    padding: 10px;
                    background: rgba(255, 255, 255, 0.1);
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                    transition: display 0.3s ease;
                }

                .flashcard-image img {
                    max-width: 200px;
                    height: auto;
                    border-radius: 8px;
                    transition: transform 0.3s ease;
                }

                .flashcard-image img:hover {
                    transform: scale(1.05);
                    cursor: pointer;
                }

                /* Resim yüklenmediğinde gösterilecek yer tutucu */
                .flashcard-image.placeholder {
                    min-height: 150px;
                    background: linear-gradient(45deg, #f3f3f3 25%, #e6e6e6 25%, #e6e6e6 50%, #f3f3f3 50%, #f3f3f3 75%, #e6e6e6 75%, #e6e6e6 100%);
                    background-size: 20px 20px;
                    animation: placeholderAnimation 2s linear infinite;
                }

                @keyframes placeholderAnimation {
                    from {
                        background-position: 0 0;
                    }
                    to {
                        background-position: 40px 40px;
                    }
                }

                /* Resim tıklandığında açılacak modal */
                .image-modal {
                    display: none;
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.8);
                    z-index: 1000;
                    justify-content: center;
                    align-items: center;
                }

                .image-modal img {
                    max-width: 90%;
                    max-height: 90vh;
                    border-radius: 12px;
                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
                }
            </style>

            <!-- Modal -->
            <div class="image-modal" onclick="closeImageModal()">
                <img src="" alt="Büyük Resim">
            </div>

            <script>
                // Resim tıklandığında modalı aç
                document.querySelectorAll('.flashcard-image img').forEach(img => {
                    img.addEventListener('click', function() {
                        const modal = document.querySelector('.image-modal');
                        const modalImg = modal.querySelector('img');
                        modalImg.src = this.src;
                        modal.style.display = 'flex';
                    });
                });

                // Modalı kapat
                function closeImageModal() {
                    document.querySelector('.image-modal').style.display = 'none';
                }

                // ESC tuşu ile modalı kapat
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        closeImageModal();
                    }
                });
            </script>


        
        <!-- Navigasyon Kontrolleri -->
        <div class="flashcard-controls mt-4">
            <div class="btn-group">
                <button class="control-btn" onclick="previousCard()">
                    <i class="ti ti-arrow-left"></i> Önceki
                </button>
                <button class="control-btn btn-flip" onclick="flipCard()">
                    <i class="ti ti-refresh"></i> Çevir
                </button>
                <button class="control-btn" onclick="nextCard()">
                    Sonraki <i class="ti ti-arrow-right"></i>
                </button>
                <button class="control-btn" onclick="shuffleCards()">
                    <i class="ti ti-shuffle"></i> Karıştır
                </button>
                <button class="control-btn" onclick="toggleImages()">
                    <i class="ti ti-photo"></i> Resimleri Göster/Gizle
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>
<br>
<br><br><br><br><br>
<script>
let currentIndex = 0;
const totalCards = <?php echo count($words); ?>;
const cards = document.querySelectorAll('.flashcard');

function flipCard(button) {
    const card = button.closest('.flashcard');
    card.classList.toggle('flipped');
}

function showCard(index) {
    cards.forEach(card => {
        card.style.display = 'none';
        card.classList.remove('flipped');
    });
    cards[index].style.display = 'block';
    
    // Animasyon ekle
    cards[index].style.animation = 'slideIn 0.3s ease-out';
}

function nextCard() {
    currentIndex = (currentIndex + 1) % totalCards;
    showCard(currentIndex);
}

function previousCard() {
    currentIndex = (currentIndex - 1 + totalCards) % totalCards;
    showCard(currentIndex);
}

// Telaffuz fonksiyonu
function playPronunciation(word) {
    const utterance = new SpeechSynthesisUtterance(word);
    utterance.lang = 'en-US';
    utterance.rate = 0.8; // Biraz daha yavaş telaffuz
    speechSynthesis.speak(utterance);
}

// Sayfa yüklendiğinde
document.addEventListener('DOMContentLoaded', () => {
    showCard(currentIndex);
});


// İstatistik değişkenleri
let viewedCards = new Set();

// İstatistikleri güncelle
function updateStats() {
    document.getElementById('currentCardNumber').textContent = currentIndex + 1;
    document.getElementById('totalCards').textContent = totalCards;
    document.getElementById('viewedCards').textContent = viewedCards.size;
    document.getElementById('remainingCards').textContent = totalCards - viewedCards.size;
    
    // İlerleme çubuğunu güncelle
    const progress = (viewedCards.size / totalCards) * 100;
    document.getElementById('progressBar').style.width = progress + '%';
}

// Kart gösterme fonksiyonunu güncelle
function showCard(index) {
    cards.forEach(card => {
        card.style.display = 'none';
        card.classList.remove('flipped');
    });
    cards[index].style.display = 'block';
    
    // Animasyon ekle
    cards[index].style.animation = 'slideIn 0.3s ease-out';
    
    // Görüntülenen kartları kaydet ve istatistikleri güncelle
    viewedCards.add(index);
    updateStats();
}

// Sayfa yüklendiğinde istatistikleri başlat
document.addEventListener('DOMContentLoaded', () => {
    showCard(currentIndex);
    updateStats();
});

// Kelime durumunu güncelleme fonksiyonu
function updateWordStatus(wordId, status) {
    fetch('api/update-word-status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            word_id: wordId,
            status: status === 'learned' ? 'mastered' : status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Başarılı güncelleme
            showToast('success', 'Kelime durumu güncellendi');
            
            // Butonların görünümünü güncelle
            const card = document.querySelector(`.flashcard[data-index="${currentIndex}"]`);
            const learningBtn = card.querySelector('.btn-learning');
            const learnedBtn = card.querySelector('.btn-learned');
            
            if (status === 'learning') {
                learningBtn.classList.add('active');
                learnedBtn.classList.remove('active');
            } else if (status === 'learned') {
                learningBtn.classList.remove('active');
                learnedBtn.classList.add('active');
            }
            
            // İstatistikleri güncelle
            updateStats();
        } else {
            showToast('error', 'Bir hata oluştu: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'Bir hata oluştu');
    });
}

// Toast mesajı gösterme fonksiyonu
function showToast(type, message) {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 3000);
    }, 100);
}

// Resimleri göster/gizle
let imagesVisible = false;
function toggleImages() {
    const images = document.querySelectorAll('.flashcard-image');
    imagesVisible = !imagesVisible;
    
    images.forEach(image => {
        image.style.display = imagesVisible ? 'flex' : 'none';
    });
}

// Sayfa yüklendiğinde resimleri gizle
document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('.flashcard-image');
    images.forEach(image => {
        image.style.display = 'none';
    });
});

</script>



<?php
// Footer'ı dahil et

include 'includes/footer/footer.php';

// Yardımcı fonksiyonlar
function getDifficultyColor($level) {
    switch ($level) {
        case 'beginner': return 'green';
        case 'intermediate': return 'yellow';
        case 'advanced': return 'red';
        default: return 'blue';
    }
}

function getDifficultyLabel($level) {
    switch ($level) {
        case 'beginner': return 'Başlangıç';
        case 'intermediate': return 'Orta';
        case 'advanced': return 'İleri';
        default: return $level;
    }
}

function countWordsByStatus($words, $status) {
    return count(array_filter($words, function($word) use ($status) {
        return $word['status'] === $status;
    }));
}

function calculateProgress($words) {
    if (empty($words)) return 0;
    $learned = countWordsByStatus($words, 'mastered');
    return round(($learned / count($words)) * 100);
}
?> 