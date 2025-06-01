// DOM yüklendiğinde
document.addEventListener('DOMContentLoaded', function() {
    // Flashcard işlevselliği
    initFlashcards();
    
    // Form doğrulama
    initFormValidation();
    
    // Telaffuz düğmeleri
    initPronunciationButtons();
    
    // Dark mode toggle
    initDarkMode();
    
    // Tooltips
    initTooltips();
});

// Flashcard işlevselliği
function initFlashcards() {
    const flashcards = document.querySelectorAll('.flashcard');
    flashcards.forEach(card => {
        card.addEventListener('click', () => {
            card.classList.toggle('flipped');
        });
    });
}

// Form doğrulama
function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
}

// Telaffuz düğmeleri
function initPronunciationButtons() {
    const buttons = document.querySelectorAll('.pronunciation-btn');
    buttons.forEach(button => {
        button.addEventListener('click', async (e) => {
            e.preventDefault();
            const word = button.dataset.word;
            try {
                const response = await fetch(`api/pronunciation.php?word=${encodeURIComponent(word)}`);
                if (!response.ok) throw new Error('Telaffuz yüklenemedi');
                
                const blob = await response.blob();
                const audio = new Audio(URL.createObjectURL(blob));
                audio.play();
            } catch (error) {
                console.error('Telaffuz hatası:', error);
                showToast('error', 'Telaffuz şu anda kullanılamıyor.');
            }
        });
    });
}

// Dark mode toggle
function initDarkMode() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    const icon = darkModeToggle.querySelector('i');
    
    function updateIcon(theme) {
        icon.className = theme === 'dark' ? 'ti ti-sun' : 'ti ti-moon';
    }
    
    if (darkModeToggle) {
        // Sayfa yüklendiğinde tema tercihi kontrolü
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
        updateIcon(savedTheme);
        
        darkModeToggle.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateIcon(newTheme);
        });
    }
}

// Tooltips
function initTooltips() {
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(tooltip => {
        new bootstrap.Tooltip(tooltip);
    });
}

// Toast bildirimleri
function showToast(type, message) {
    const toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.style.cssText = 'position: fixed; top: 1rem; right: 1rem; z-index: 1050;';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-black bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Kapat"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

// AJAX form gönderimi
function submitFormAjax(formElement, successCallback, errorCallback) {
    const form = formElement instanceof HTMLFormElement ? formElement : document.querySelector(formElement);
    if (!form) return;
    
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const submitButton = form.querySelector('[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Gönderiliyor...';
        }
        
        try {
            const formData = new FormData(form);
            const response = await fetch(form.action, {
                method: form.method,
                body: formData
            });
            
            const data = await response.json();
            
            if (response.ok) {
                if (successCallback) successCallback(data);
                else showToast('success', data.message || 'İşlem başarılı');
            } else {
                throw new Error(data.message || 'Bir hata oluştu');
            }
        } catch (error) {
            if (errorCallback) errorCallback(error);
            else showToast('error', error.message);
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = submitButton.dataset.originalText || 'Gönder';
            }
        }
    });
}

// Kelime listesi filtreleme
function initWordListFilter() {
    const searchInput = document.getElementById('wordSearch');
    const categorySelect = document.getElementById('categoryFilter');
    const difficultySelect = document.getElementById('difficultyFilter');
    const wordList = document.getElementById('wordList');
    
    if (!searchInput || !wordList) return;
    
    function filterWords() {
        const searchTerm = searchInput.value.toLowerCase();
        const category = categorySelect ? categorySelect.value : '';
        const difficulty = difficultySelect ? difficultySelect.value : '';
        
        const words = wordList.getElementsByClassName('word-card');
        
        Array.from(words).forEach(word => {
            const wordText = word.querySelector('.word-text').textContent.toLowerCase();
            const wordCategory = word.dataset.category;
            const wordDifficulty = word.dataset.difficulty;
            
            const matchesSearch = wordText.includes(searchTerm);
            const matchesCategory = !category || wordCategory === category;
            const matchesDifficulty = !difficulty || wordDifficulty === difficulty;
            
            word.style.display = matchesSearch && matchesCategory && matchesDifficulty ? '' : 'none';
        });
    }
    
    searchInput.addEventListener('input', filterWords);
    if (categorySelect) categorySelect.addEventListener('change', filterWords);
    if (difficultySelect) difficultySelect.addEventListener('change', filterWords);
}

// Sonsuz kaydırma
function initInfiniteScroll(container, loadMoreCallback, options = {}) {
    if (!container || typeof loadMoreCallback !== 'function') return;
    
    const defaultOptions = {
        threshold: 100, // piksel
        debounceDelay: 250 // milisaniye
    };
    
    const settings = { ...defaultOptions, ...options };
    let isLoading = false;
    let hasMoreItems = true;
    
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    const handleScroll = debounce(() => {
        if (isLoading || !hasMoreItems) return;
        
        const { scrollTop, scrollHeight, clientHeight } = document.documentElement;
        if (scrollHeight - scrollTop - clientHeight < settings.threshold) {
            isLoading = true;
            
            loadMoreCallback()
                .then(moreItemsAvailable => {
                    hasMoreItems = moreItemsAvailable;
                })
                .finally(() => {
                    isLoading = false;
                });
        }
    }, settings.debounceDelay);
    
    window.addEventListener('scroll', handleScroll);
    
    // Cleanup function
    return () => window.removeEventListener('scroll', handleScroll);
}

// Dışa aktarılan yardımcı fonksiyonlar
window.helpers = {
    showToast,
    submitFormAjax,
    initInfiniteScroll
}; 