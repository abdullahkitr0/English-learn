<?php
require_once 'config/config.php';
require_once 'functions/functions.php';

session_start();

// Giriş kontrolü
requireLogin();

try {
    $db = dbConnect();
    
    // Test türünü al
    $testType = isset($_GET['type']) ? cleanInput($_GET['type']) : 'daily';
    
    // Test verilerini al
    $words = [];
    switch ($testType) {
        case 'daily':
            // Günlük test için benzersiz rastgele kelimeler
            $stmt = $db->query("
                SELECT DISTINCT w.* 
                FROM words w 
                WHERE w.is_approved = 1 
                ORDER BY RAND()
            ");
            $words = $stmt->fetchAll();
            break;
            
        case 'review':
            // Tekrar testi için kullanıcının öğrendiği kelimeler
            $stmt = $db->prepare("
                SELECT DISTINCT w.* 
                FROM words w 
                INNER JOIN user_words uw ON w.id = uw.word_id 
                WHERE uw.user_id = ? AND w.is_approved = 1 
                ORDER BY RAND()
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $words = $stmt->fetchAll();
            break;
            
        case 'category':
            // Kategori testi için seçilen kategorideki kelimeler
            $categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
            if ($categoryId > 0) {
                $stmt = $db->prepare("
                    SELECT DISTINCT w.* 
                    FROM words w 
                    WHERE w.category_id = ? AND w.is_approved = 1 
                    ORDER BY RAND()
                ");
                $stmt->execute([$categoryId]);
                $words = $stmt->fetchAll();
            }
            break;
    }
    
    // Kategorileri al
    $categories = $db->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Veritabanı hatası: ' . $e->getMessage();
    logError('Tests error: ' . $e->getMessage());
}

// Sayfa başlığı
$pageTitle = "Testler - İngilizce Kelime Öğrenme";

// Header'ı dahil et
include 'includes/header/header.php';
?>

<div class="container-xl">
    <div class="page-header d-print-none">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">Testler</h2>
                <div class="text-muted mt-1">
                    Öğrendiğiniz kelimeleri test edin
                </div>
            </div>
        </div>
    </div>
    
    <!-- Test Ayarları -->
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">Test Ayarları</h3>
        </div>
        <div class="card-body">
            <form id="testSettingsForm" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Test Türü</label>
                    <select class="form-select" name="type" id="testType">
                        <option value="daily" <?php echo $testType == 'daily' ? 'selected' : ''; ?>>Günlük Test</option>
                        <option value="review" <?php echo $testType == 'review' ? 'selected' : ''; ?>>Tekrar Testi</option>
                        <option value="category" <?php echo $testType == 'category' ? 'selected' : ''; ?>>Kategori Testi</option>
                    </select>
                </div>
                
                <div class="col-md-4" id="categoryContainer" style="display: <?php echo $testType == 'category' ? 'block' : 'none'; ?>">
                    <label class="form-label">Kategori</label>
                    <select class="form-select" name="category" id="category">
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Soru Sayısı</label>
                    <select class="form-select" name="questionCount" id="questionCount">
                        <option value="5">5 Soru</option>
                        <option value="10" selected>10 Soru</option>
                        <option value="15">15 Soru</option>
                        <option value="20">20 Soru</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Zorluk Seviyesi</label>
                    <select class="form-select" name="difficulty" id="difficulty">
                        <option value="all">Tümü</option>
                        <option value="beginner">Başlangıç</option>
                        <option value="intermediate">Orta</option>
                        <option value="advanced">İleri</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($words)): ?>
    <div class="alert alert-info">
        Bu test türü için yeterli kelime bulunmuyor.
    </div>
    <?php else: ?>
    <!-- Test Formu -->
    <form id="testForm" class="card">
        <div class="card-body">
            <div id="questions">
                <!-- Sorular JavaScript ile doldurulacak -->
            </div>
            
            <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-primary" onclick="submitTest()">
                    Testi Tamamla
                </button>
                <button type="button" class="btn btn-secondary" onclick="resetTest()">
                    Testi Sıfırla
                </button>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>

<!-- Test Sonucu Modal -->
<div class="modal fade" id="resultModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Test Sonucu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Sonuçlar JavaScript ile doldurulacak -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="resetTest()">Yeni Test</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<script>
let words = <?php echo json_encode($words); ?>;
let startTime;
let testSettings = {};

// Test türü değiştiğinde kategori seçimini göster/gizle
document.getElementById('testType').addEventListener('change', function() {
    const categoryContainer = document.getElementById('categoryContainer');
    categoryContainer.style.display = this.value === 'category' ? 'block' : 'none';
    if (this.value === 'category') {
        loadTest();
    }
});

// Test ayarları değiştiğinde testi yeniden yükle
document.querySelectorAll('#testSettingsForm select').forEach(select => {
    select.addEventListener('change', loadTest);
});

function loadTest() {
    const form = document.getElementById('testSettingsForm');
    testSettings = {
        type: form.type.value,
        category: form.category.value,
        questionCount: parseInt(form.questionCount.value),
        difficulty: form.difficulty.value
    };
    
    // Zorluk seviyesine göre kelimeleri filtrele
    let filteredWords = words;
    if (testSettings.difficulty !== 'all') {
        filteredWords = words.filter(word => word.difficulty_level === testSettings.difficulty);
    }
    
    // Soru sayısı kadar kelime seç
    filteredWords = shuffleArray(filteredWords).slice(0, testSettings.questionCount);
    
    // Her kelime için yanlış cevap seçenekleri oluştur
    const questions = filteredWords.map((word, index) => {
        const otherWords = words.filter(w => w.id !== word.id);
        const shuffledWords = shuffleArray(otherWords);
        const options = [
            { definition: word.definition, isCorrect: true },
            ...shuffledWords.slice(0, 3).map(w => ({
                definition: w.definition,
                isCorrect: false
            }))
        ];
        
        return {
            id: word.id,
            word: word.word,
            options: shuffleArray(options)
        };
    });
    
    displayQuestions(questions);
    startTime = Date.now();
}

function displayQuestions(questions) {
    const container = document.getElementById('questions');
    container.innerHTML = questions.map((question, index) => `
        <div class="question-card mb-4" data-word-id="${question.id}">
            <h3 class="mb-3">Soru ${index + 1}: "${question.word}" kelimesinin anlamı nedir?</h3>
            <div class="question-options">
                ${question.options.map((option, optIndex) => `
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" 
                               name="q${question.id}" 
                               value="${option.definition}"
                               data-correct="${question.options.find(opt => opt.isCorrect).definition}"
                               id="q${question.id}_${optIndex}">
                        <label class="form-check-label" for="q${question.id}_${optIndex}">
                            ${option.definition}
                        </label>
                    </div>
                `).join('')}
            </div>
        </div>
    `).join('');
}

function submitTest() {
    const answers = [];
    const form = document.getElementById('testForm');
    const timeSpent = Math.floor((Date.now() - startTime) / 1000);
    
    // Cevapları topla
    const questions = document.querySelectorAll('.question-card');
    questions.forEach(question => {
        const wordId = question.dataset.wordId;
        const selectedAnswer = question.querySelector('input[type="radio"]:checked');
        if (selectedAnswer) {
            answers.push({
                word_id: parseInt(wordId),
                user_answer: selectedAnswer.value,
                correct_answer: selectedAnswer.dataset.correct
            });
        }
    });
    
    // Tüm soruların cevaplanması zorunlu değil
    if (answers.length === 0) {
        alert('Lütfen en az bir soruyu cevaplayın.');
        return;
    }
    
    // Test sonucunu gönder
    fetch('api/submit-test.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            answers: answers,
            time_spent: timeSpent
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showResults(data.result);
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Test gönderme hatası:', error);
        alert('Test gönderilirken bir hata oluştu. Lütfen tekrar deneyin.');
    });
}

function showResults(result) {
    const modal = document.getElementById('resultModal');
    const modalBody = modal.querySelector('.modal-body');
    
    modalBody.innerHTML = `
        <div class="text-center">
            <h3 class="mb-3">Test Tamamlandı!</h3>
            <div class="h1 mb-3">${result.score.toFixed(1)}%</div>
            <div class="row text-muted">
                <div class="col">
                    <div class="h3">${result.correct_count}</div>
                    <div>Doğru</div>
                </div>
                <div class="col">
                    <div class="h3">${result.incorrect_count}</div>
                    <div>Yanlış</div>
                </div>
                <div class="col">
                    <div class="h3">${formatTime(result.time_spent)}</div>
                    <div>Süre</div>
                </div>
            </div>
        </div>
    `;
    
    new bootstrap.Modal(modal).show();
}

function resetTest() {
    loadTest();
    const modal = document.getElementById('resultModal');
    const bsModal = bootstrap.Modal.getInstance(modal);
    if (bsModal) {
        bsModal.hide();
    }
}

function shuffleArray(array) {
    for (let i = array.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [array[i], array[j]] = [array[j], array[i]];
    }
    return array;
}

function formatTime(seconds) {
    const minutes = Math.floor(seconds / 60);
    seconds = seconds % 60;
    return `${minutes}:${seconds.toString().padStart(2, '0')}`;
}

// Sayfa yüklendiğinde ilk testi yükle
document.addEventListener('DOMContentLoaded', loadTest);
</script>

<?php
// Footer'ı dahil et
include 'includes/footer/footer.php';
?> 
?> 