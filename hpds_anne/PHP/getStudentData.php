<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Taipei');

require_once '../PHP/common.php';

try {
    // 提取參數
    $params = extractScoreParams();
    $student_id = $params['student_id'];
    $password_input = $params['password'];
    $score = $params['score'];
    $incorrect_questions = $params['incorrect_questions'];
    $answer_time = $params['answer_time'];

    // 檢查必要參數
    if ($student_id === '' || $password_input === '' || $score === '' || $answer_time === '') {
        echo json_encode(['error' => '缺少必要參數']);
        exit;
    }

    // 驗證學號／密碼
    $passwordCheck = validatePassword($student_id, $password_input);
    if (!$passwordCheck['success']) {
        echo json_encode(['error' => $passwordCheck['error']]);
        exit;
    }

    // 正規化 answer_time（秒數轉 mm:ss）
    $answer_time = normalizeAnswerTime($answer_time);

    // 處理 incorrect_questions 為 q1~q16 的布林值
    $qValues = processIncorrectQuestions($incorrect_questions);

    // 插入成績記錄
    $result = insertScoreRecord($student_id, $score, $answer_time, $incorrect_questions, $qValues);
    
    echo json_encode(['success' => $result['message']]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
