<?php
require_once 'config/config.php';
require_once 'functions/functions.php';

session_start();

// Giriş kontrolü
requireLogin();

// Sayfa başlığı
$pageTitle = "Kelime Listeleri - İngilizce Kelime Öğrenme";

// Header'ı dahil et
include 'includes/header/header.php';

try {
    $db = dbConnect();
    
    // Filtreleme parametreleri
    $category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
    $difficulty = isset($_GET['difficulty']) ? cleanInput($_GET['difficulty']) : '';
    $search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
    
    // Sayfalama
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = 20;
    $offset = ($page - 1) * $perPage;
    
    // Kategorileri getir
    $categories = $db->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
    
    // SQL sorgusu
    $sql = "
        SELECT w.*, c.name as category_name,
               CASE WHEN uw.word_id IS NOT NULL THEN 1 ELSE 0 END as is_in_list
        FROM words w
        LEFT JOIN categories c ON w.category_id = c.id
        LEFT JOIN user_words uw ON w.id = uw.word_id AND uw.user_id = :user_id
        WHERE w.is_approved = 1
    ";
    
    $params = [':user_id' => $_SESSION['user_id']];
    
    if ($category > 0) {
        $sql .= " AND w.category_id = :category";
        $params[':category'] = $category;
    }
    
    if ($difficulty) {
        $sql .= " AND w.difficulty_level = :difficulty";
        $params[':difficulty'] = $difficulty;
    }
    
    if ($search) {
        $sql .= " AND (w.word LIKE :search OR w.definition LIKE :search)";
        $params[':search'] = "%{$search}%";
    }
    
    // Toplam kayıt sayısı
    $countStmt = $db->prepare(str_replace('w.*,', 'COUNT(*) as total,', $sql));
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'];
    
    // Sayfalama için limit
    $sql .= " ORDER BY w.word LIMIT :offset, :limit";
    $params[':offset'] = $offset;
    $params[':limit'] = $perPage;
    
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    $words = $stmt->fetchAll();
    
} catch (PDOException $e) {
    logError('Word list error: ' . $e->getMessage());
    $error = 'Kelimeler yüklenirken bir hata oluştu';
}
?>

<div class="container-xl">
    <div class="page-header d-print-none">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">Kelime Listeleri</h2>
            </div>
        </div>
    </div>
    
    <!-- Filtreler -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Kategori</label>
                    <select name="category" class="form-select">
                        <option value="">Tümü</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Zorluk</label>
                    <select name="difficulty" class="form-select">
                        <option value="">Tümü</option>
                        <option value="beginner" <?php echo $difficulty === 'beginner' ? 'selected' : ''; ?>>Başlangıç</option>
                        <option value="intermediate" <?php echo $difficulty === 'intermediate' ? 'selected' : ''; ?>>Orta</option>
                        <option value="advanced" <?php echo $difficulty === 'advanced' ? 'selected' : ''; ?>>İleri</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Arama</label>
                    <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>" placeholder="Kelime veya tanım ara...">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Filtrele</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <?php echo $error; ?>
    </div>
    <?php else: ?>
    
    <!-- Kelime Listesi -->
    <div class="row row-cards">
        <?php foreach ($words as $word): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">
                            <?php echo htmlspecialchars($word['word']); ?>
                            <button class="btn btn-icon btn-sm" onclick="playPronunciation('<?php echo htmlspecialchars($word['word']); ?>')">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M15 8a5 5 0 0 1 0 8" />
                                    <path d="M17.7 5a9 9 0 0 1 0 14" />
                                    <path d="M6 15h-2a1 1 0 0 1 -1 -1v-4a1 1 0 0 1 1 -1h2l3.5 -4.5a.8 .8 0 0 1 1.5 .5v14a.8 .8 0 0 1 -1.5 .5l-3.5 -4.5" />
                                </svg>
                            </button>
                        </h3>
                        <?php if (!$word['is_in_list']): ?>
                        <button class="btn btn-primary btn-sm" onclick="addWord(<?php echo $word['id']; ?>, this)">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <line x1="12" y1="5" x2="12" y2="19" />
                                <line x1="5" y1="12" x2="19" y2="12" />
                            </svg>
                            Ekle
                        </button>
                        <?php else: ?>
                        <span class="badge bg-success">Listenizde</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-muted mb-2">
                        <?php echo htmlspecialchars($word['definition']); ?>
                    </p>
                    <?php if ($word['example_sentence']): ?>
                    <p class="text-muted small mb-2">
                        <em>"<?php echo htmlspecialchars($word['example_sentence']); ?>"</em>
                    </p>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <span class="badge bg-blue-lt">
                            <?php echo htmlspecialchars($word['category_name']); ?>
                        </span>
                        <span class="badge bg-<?php echo getDifficultyColor($word['difficulty_level']); ?>-lt">
                            <?php echo getDifficultyLabel($word['difficulty_level']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Sayfalama -->
    <?php if ($total > $perPage): ?>
    <div class="d-flex justify-content-center mt-4">
        <ul class="pagination">
            <?php
            $totalPages = ceil($total / $perPage);
            $range = 2;
            
            // İlk sayfa
            if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?page=1<?php echo $category ? "&category=$category" : ''; ?><?php echo $difficulty ? "&difficulty=$difficulty" : ''; ?><?php echo $search ? "&search=$search" : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <polyline points="11 7 6 12 11 17" />
                        <polyline points="17 7 12 12 17 17" />
                    </svg>
                </a>
            </li>
            <?php endif;
            
            // Sayfa numaraları
            for ($i = max(1, $page - $range); $i <= min($totalPages, $page + $range); $i++): ?>
            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $category ? "&category=$category" : ''; ?><?php echo $difficulty ? "&difficulty=$difficulty" : ''; ?><?php echo $search ? "&search=$search" : ''; ?>">
                    <?php echo $i; ?>
                </a>
            </li>
            <?php endfor;
            
            // Son sayfa
            if ($page < $totalPages): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?php echo $totalPages; ?><?php echo $category ? "&category=$category" : ''; ?><?php echo $difficulty ? "&difficulty=$difficulty" : ''; ?><?php echo $search ? "&search=$search" : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <polyline points="7 7 12 12 7 17" />
                        <polyline points="13 7 18 12 13 17" />
                    </svg>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <?php endif; ?>
</div>

<script>
function addWord(wordId, button) {
    // Butonu devre dışı bırak
    button.disabled = true;
    
    fetch('api/add-to-list.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: JSON.stringify({ word_id: wordId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Başarılı mesajı göster
            showToast('success', data.message);
            
            // Butonu "Listenizde" badge'i ile değiştir
            const parent = button.parentElement;
            button.remove();
            parent.innerHTML = '<span class="badge bg-success">Listenizde</span>';
        } else {
            // Hata mesajı göster
            showToast('error', data.message);
            // Butonu tekrar aktif et
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'Bir hata oluştu. Lütfen tekrar deneyin.');
        // Butonu tekrar aktif et
        button.disabled = false;
    });
}
// Telaffuz fonksiyonu
function playPronunciation(word) {
    const utterance = new SpeechSynthesisUtterance(word);
    utterance.lang = 'en-US';
    utterance.rate = 0.8; // Biraz daha yavaş telaffuz
    speechSynthesis.speak(utterance);
}
/*
function playPronunciation(word) {
    const button = event.currentTarget;
    button.disabled = true;

    // Yükleniyor göstergesi ekle
    const loadingIcon = document.createElement('div');
    loadingIcon.className = 'loading-spinner';
    button.appendChild(loadingIcon);

    fetch(`api/get-pronunciation.php?word=${encodeURIComponent(word)}`)
        .then(response => {
            // Yükleniyor göstergesini kaldır
            button.removeChild(loadingIcon);
            
            if (!response.ok) {
                throw new Error('Telaffuz alınamadı');
            }
            return response.blob();
        })
        .then(blob => {
            const audio = new Audio(URL.createObjectURL(blob));
            audio.play();
            audio.onended = () => {
                button.disabled = false;
            };
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Telaffuz alınamadı. Lütfen tekrar deneyin.');
            button.disabled = false;
        });
}
*/
function showToast(type, message) {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    // İkon ekle
    const icon = document.createElement('i');
    icon.className = type === 'success' ? 'ti ti-check' : 'ti ti-x';
    
    // Mesaj container'ı
    const messageDiv = document.createElement('div');
    messageDiv.textContent = message;
    
    // Toast içeriğini oluştur
    toast.appendChild(icon);
    toast.appendChild(messageDiv);
    
    const container = document.getElementById('toastContainer');
    if (!container) {
        const newContainer = document.createElement('div');
        newContainer.id = 'toastContainer';
        newContainer.className = 'toast-container';
        document.body.appendChild(newContainer);
    }
    
    document.getElementById('toastContainer').appendChild(toast);
    
    // Animasyon ile kaldır
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
</script>

<?php
// Footer'ı dahil et
include 'includes/footer/footer.php';

// Yardımcı fonksiyonlar
function getDifficultyColor($level) {
    switch ($level) {
        case 'beginner':
            return 'green';
        case 'intermediate':
            return 'yellow';
        case 'advanced':
            return 'red';
        default:
            return 'gray';
    }
}

function getDifficultyLabel($level) {
    switch ($level) {
        case 'beginner':
            return 'Başlangıç';
        case 'intermediate':
            return 'Orta';
        case 'advanced':
            return 'İleri';
        default:
            return 'Belirsiz';
    }
}
?> 