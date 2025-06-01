<?php
require_once 'config/config.php';
require_once 'functions/functions.php';


session_start();

// Sayfa başlığı
$pageTitle = "İngilizce Kelime Kartları";

try {
    $db = dbConnect();
   
	
    // Tüm onaylı kelimeleri getir
    $sql = "
        SELECT w.*, c.name as category_name
        FROM words w
        LEFT JOIN categories c ON w.category_id = c.id
        WHERE w.is_approved = 1
        ORDER BY RAND()
    ";
   
  

    $stmt = $db->query($sql);
	
    $words = $stmt->fetchAll(PDO::FETCH_ASSOC);
   
    
} catch (PDOException $e) {
    $words = [];
    logError('Public flashcards error: ' . $e->getMessage());
}

// Header'ı dahil et
include 'includes/header/header.php';
?>

<div class="container-xl py-4">
	    
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

    <!-- İlerleme Çubuğu -->
    <div class="progress-container mb-4">
        <div class="progress">
            <div class="progress-bar" role="progressbar" style="width: 0%" id="progressBar"></div>
        </div>
    </div>

    <?php if (empty($words)): ?>
        <div class="alert alert-info">
            Henüz kelime eklenmemiş.
        </div>
    <?php else: ?>
	
        <!-- Flashcard Container -->
        <div class="flashcard-container">
            <?php foreach ($words as $index => $word): ?>
            <div class="flashcard <?php echo $index > 0 ? 'd-none' : ''; ?>" data-index="<?php echo $index; ?>">
                <div class="flashcard-front">
                    <div class="flashcard-word">
                        <?php echo htmlspecialchars($word['word']); ?>
                        <button class="pronunciation-btn" onclick="playPronunciation('<?php echo htmlspecialchars($word['word']); ?>')">
                            <i class="ti ti-volume"></i>
                        </button>
                    </div>
                    <div class="flashcard-category">
                        <?php echo htmlspecialchars($word['category_name']); ?>
                    </div>
                </div>
                <div class="flashcard-back">
                    <div class="flashcard-definition">
                        <?php echo htmlspecialchars($word['definition']); ?>
                    </div>
                    <?php if (!empty($word['example_sentence'])): ?>
                    <div class="flashcard-example">
                        <strong>Örnek:</strong> <?php echo htmlspecialchars($word['example_sentence']); ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($word['image_url'])): ?>
                    <div class="flashcard-image">
                        <img src="<?php echo htmlspecialchars($word['image_url']); ?>" width="50%" height="50%" alt="Kelime Resmi">
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php endforeach; ?>
        </div>

        <!-- Kontroller -->
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
    cursor: pointer;
}

.flashcard-image img:hover {
    transform: scale(1.05);
}
</style>

<script>
let currentIndex = 0;
const totalCards = <?php echo count($words); ?>;
let viewedCards = new Set();

function updateStats() {
    document.getElementById('currentCardNumber').textContent = currentIndex + 1;
    document.getElementById('viewedCards').textContent = viewedCards.size;
    document.getElementById('remainingCards').textContent = totalCards - viewedCards.size;
    
    const progress = (viewedCards.size / totalCards) * 100;
    document.getElementById('progressBar').style.width = progress + '%';
}

function showCard(index) {
    document.querySelectorAll('.flashcard').forEach(card => {
        card.classList.add('d-none');
        card.classList.remove('flipped');
    });
    
    const card = document.querySelector(`.flashcard[data-index="${index}"]`);
    if (card) {
        card.classList.remove('d-none');
        viewedCards.add(index);
        updateStats();
    }
}

function nextCard() {
    currentIndex = (currentIndex + 1) % totalCards;
    showCard(currentIndex);
}

function previousCard() {
    currentIndex = (currentIndex - 1 + totalCards) % totalCards;
    showCard(currentIndex);
}

function flipCard() {
    const currentCard = document.querySelector(`.flashcard[data-index="${currentIndex}"]`);
    if (currentCard) {
        currentCard.classList.toggle('flipped');
    }
}

function shuffleCards() {
    const cards = document.querySelectorAll('.flashcard');
    const indexes = Array.from(cards).map((_, index) => index);
    
    // Fisher-Yates shuffle
    for (let i = indexes.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [indexes[i], indexes[j]] = [indexes[j], indexes[i]];
    }
    
    cards.forEach((card, i) => {
        card.dataset.index = indexes[i];
    });
    
    currentIndex = 0;
    viewedCards.clear();
    showCard(currentIndex);
}

// Telaffuz fonksiyonu
function playPronunciation(word) {
    const utterance = new SpeechSynthesisUtterance(word);
    utterance.lang = 'en-US';
    utterance.rate = 0.8; // Biraz daha yavaş telaffuz
    speechSynthesis.speak(utterance);
}

// Başlangıç durumu
updateStats();

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
    
    // Resim tıklandığında modal açma
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

// ESC tuşu ile modalı kapat
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.querySelector('.image-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }
});
</script>

<?php
// Footer'ı dahil et
include 'includes/footer/footer.php';
?> 