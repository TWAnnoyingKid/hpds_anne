<?php
// Unity 成績輸入 - 使用統一的共用函式庫
header('Content-Type: text/plain');

require_once '../PHP/common.php';

try {
    // 接收 Unity 傳遞的參數
    $student_id = $_POST['student_id'] ?? null;
    $score = $_POST['score'] ?? null;
    $incorrect_questions = $_POST['incorrect_questions'] ?? null;
    $answer_time = $_POST['answer_time'] ?? null;
    $answer_date = $_POST['answer_date'] ?? null;

    // 將 'T' 替換回空格並檢查格式
    if ($answer_date) {
        $answer_date = str_replace('T', ' ', $answer_date);
    }

    // 驗證輸入
    if (!$student_id || !$score || !$incorrect_questions || !$answer_time || !$answer_date) {
        echo "Missing required parameters.";
        exit;
    }

    // 使用統一的資料庫連線函式
    $conn = getDbConnection();

    // 插入資料 - 使用與 enterScore.php 相同的邏輯
    $stmt = $conn->prepare("INSERT INTO score (student_id, score, incorrect_questions, answer_time, answer_date) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }

    $stmt->bind_param("sssss", $student_id, $score, $incorrect_questions, $answer_time, $answer_date);

    if ($stmt->execute()) {
        // 使用統一的日誌記錄功能
        Logger::logUnityScoreImport($student_id, $score, "Answer time: {$answer_time}, Date: {$answer_date}");
        echo "New record created successfully";
    } else {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    error_log("Unity Score Enter Error: " . $e->getMessage());
    echo "Error: " . $e->getMessage();
}
?>
