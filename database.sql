-- Veritabanını oluştur
CREATE DATABASE IF NOT EXISTS english_learning CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE english_learning;

-- Kullanıcılar tablosu
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    reset_token VARCHAR(100) NULL,
    reset_token_expires TIMESTAMP NULL
) ENGINE=InnoDB;

-- Kategoriler tablosu
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    parent_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Kelimeler tablosu
CREATE TABLE words (
    id INT PRIMARY KEY AUTO_INCREMENT,
    word VARCHAR(100) NOT NULL,
    pronunciation VARCHAR(100),
    definition TEXT NOT NULL,
    example_sentence TEXT,
    image_url VARCHAR(255),
    audio_url VARCHAR(255),
    category_id INT,
    difficulty_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    created_by INT,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Kullanıcı Kelime İlişki tablosu
CREATE TABLE user_words (
    user_id INT,
    word_id INT,
    status ENUM('new', 'learning', 'mastered') DEFAULT 'new',
    last_reviewed TIMESTAMP NULL,
    next_review TIMESTAMP NULL,
    correct_count INT DEFAULT 0,
    incorrect_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, word_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (word_id) REFERENCES words(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Testler tablosu
CREATE TABLE tests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    type ENUM('daily', 'review', 'category') NOT NULL,
    difficulty_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    category_id INT,
    word_count INT DEFAULT 10,
    time_limit INT DEFAULT 0,
    created_by INT,
    is_public BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Test Soruları tablosu
CREATE TABLE test_questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    test_id INT,
    word_id INT,
    question_type ENUM('multiple_choice', 'fill_blank', 'writing') NOT NULL,
    question_text TEXT NOT NULL,
    correct_answer TEXT NOT NULL,
    options JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE,
    FOREIGN KEY (word_id) REFERENCES words(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Test Sonuçları tablosu
CREATE TABLE test_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    test_id INT,
    score INT NOT NULL,
    correct_count INT DEFAULT 0,
    incorrect_count INT DEFAULT 0,
    time_spent INT DEFAULT 0,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Test Cevapları tablosu
CREATE TABLE test_answers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    test_result_id INT,
    test_id INT,
    user_id INT,
    word_id INT,
    user_answer TEXT NOT NULL,
    correct_answer TEXT NOT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (test_result_id) REFERENCES test_results(id) ON DELETE CASCADE,
    FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (word_id) REFERENCES words(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Başarılar tablosu
CREATE TABLE achievements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon_url VARCHAR(255),
    required_score INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Kullanıcı Başarıları tablosu
CREATE TABLE user_achievements (
    user_id INT,
    achievement_id INT,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, achievement_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- İletişim Mesajları tablosu
CREATE TABLE contact_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    user_id INT NULL,
    ip_address VARCHAR(45) NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- İndeksler
CREATE INDEX idx_words_category ON words(category_id);
CREATE INDEX idx_user_words_status ON user_words(status);
CREATE INDEX idx_tests_category ON tests(category_id);
CREATE INDEX idx_test_results_user ON test_results(user_id);
CREATE INDEX idx_words_difficulty ON words(difficulty_level);
CREATE INDEX idx_contact_messages_user ON contact_messages(user_id);
CREATE INDEX idx_contact_messages_is_read ON contact_messages(is_read);

-- Admin kullanıcısı oluştur
INSERT INTO users (username, email, password, role, is_active) 
VALUES ('admin', 'admin@example.com', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN.jf/5Kz.OLXQNmPGGDW', 'admin', 1);

-- Örnek kategoriler
INSERT INTO categories (name, description) VALUES
('Temel Kelimeler', 'Günlük hayatta en sık kullanılan temel kelimeler'),
('İş İngilizcesi', 'İş hayatında kullanılan kelime ve terimler'),
('Akademik İngilizce', 'Akademik metinlerde sık kullanılan kelimeler'),
('Seyahat', 'Seyahat ve turizm ile ilgili kelimeler'),
('Teknoloji', 'Bilim ve teknoloji alanında kullanılan terimler');

-- Örnek başarılar
INSERT INTO achievements (name, description, required_score) VALUES
('Başlangıç', 'İlk kelimeyi öğrendiniz', 1),
('Çalışkan Öğrenci', '50 kelime öğrendiniz', 50),
('Kelime Ustası', '100 kelime öğrendiniz', 100),
('Test Uzmanı', '10 test tamamladınız', 10),
('Mükemmel Skor', 'Bir testten 100 puan aldınız', 100); 