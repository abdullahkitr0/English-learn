<?php
require_once '../config/config.php';
require_once '../functions/functions.php';

session_start();

// Giriş kontrolü
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Giriş yapmanız gerekiyor']);
    exit;
}

// POST verilerini al
$data = json_decode(file_get_contents('php://input'), true);

// Verileri kontrol et
if (!isset($data['answers']) || !is_array($data['answers'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz cevap formatı']);
    exit;
}

$answers = $data['answers'];
$timeSpent = isset($data['time_spent']) ? (int)$data['time_spent'] : 0;

try {
    $db = dbConnect();
    
    // İşlemi transaction içinde gerçekleştir
    $db->beginTransaction();
    
    // Test sonucunu ekle
    $stmt = $db->prepare("
        INSERT INTO test_results (
            user_id, score, correct_count, incorrect_count,
            time_spent, completed_at
        )
        VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");
    
    // Doğru ve yanlış sayısını hesapla
    $correctCount = 0;
    $totalAnswered = count($answers);
    
    foreach ($answers as $answer) {
        if (strtolower(trim($answer['user_answer'])) === strtolower(trim($answer['correct_answer']))) {
            $correctCount++;
        }
    }
    
    $incorrectCount = $totalAnswered - $correctCount;
    $score = $totalAnswered > 0 ? ($correctCount / $totalAnswered) * 100 : 0;
    
    // Test sonucunu kaydet
    $stmt->execute([
        $_SESSION['user_id'],
        $score,
        $correctCount,
        $incorrectCount,
        $timeSpent
    ]);
    
    $resultId = $db->lastInsertId();
    
    // Test cevaplarını kaydet
    $stmt = $db->prepare("
        INSERT INTO test_answers (
            test_result_id, user_id, word_id,
            user_answer, correct_answer, is_correct,
            created_at
        )
        VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");
    
    foreach ($answers as $answer) {
        $isCorrect = strtolower(trim($answer['user_answer'])) === strtolower(trim($answer['correct_answer']));
        
        $stmt->execute([
            $resultId,
            $_SESSION['user_id'],
            $answer['word_id'],
            $answer['user_answer'],
            $answer['correct_answer'],
            $isCorrect
        ]);
        
        // Kelime durumunu güncelle
        $stmt2 = $db->prepare("
            UPDATE user_words 
            SET 
                status = CASE 
                    WHEN ? = true AND status = 'new' THEN 'learning'
                    WHEN ? = true AND status = 'learning' THEN 'mastered'
                    WHEN ? = false AND status = 'mastered' THEN 'learning'
                    ELSE status 
                END,
                last_reviewed = CURRENT_TIMESTAMP
            WHERE user_id = ? AND word_id = ?
        ");
        
        $stmt2->execute([
            $isCorrect,
            $isCorrect,
            $isCorrect,
            $_SESSION['user_id'],
            $answer['word_id']
        ]);
    }
    
    // Transaction'ı tamamla
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Test sonucu başarıyla kaydedildi',
        'result' => [
            'id' => $resultId,
            'score' => $score,
            'correct_count' => $correctCount,
            'incorrect_count' => $incorrectCount,
            'time_spent' => $timeSpent
        ]
    ]);
    
} catch (PDOException $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    logError('Submit test error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Test sonucu kaydedilirken bir hata oluştu: ' . $e->getMessage()]);
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    logError('Submit test error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Test sonucu kaydedilirken bir hata oluştu: ' . $e->getMessage()]);
}
?> 