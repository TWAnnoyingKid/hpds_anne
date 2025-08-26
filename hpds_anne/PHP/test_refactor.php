<?php
// 測試重構後的共用函數
require_once 'common.php';

echo "=== 測試共用函數 ===\n\n";

// 測試 normalizeAnswerTime 函數
echo "1. 測試 normalizeAnswerTime:\n";
echo "   125 秒 -> " . normalizeAnswerTime('125') . "\n";
echo "   '2:30' -> " . normalizeAnswerTime('2:30') . "\n\n";

// 測試 processIncorrectQuestions 函數
echo "2. 測試 processIncorrectQuestions:\n";
$incorrect = '2,5,8,12';
$qValues = processIncorrectQuestions($incorrect);
echo "   錯誤題目: $incorrect\n";
echo "   q1=" . $qValues[1] . ", q2=" . $qValues[2] . ", q5=" . $qValues[5] . ", q8=" . $qValues[8] . "\n\n";

// 測試資料庫連線（不實際插入）
echo "3. 測試資料庫連線:\n";
try {
    $conn = getDbConnection();
    echo "   ✓ 資料庫連線成功\n";
    $conn->close();
} catch (Exception $e) {
    echo "   ✗ 資料庫連線失敗: " . $e->getMessage() . "\n";
}

echo "\n=== 測試完成 ===\n";
?>
