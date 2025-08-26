<?php
session_start();


require_once '../PHP/common.php';

// 檢查權限
checkSessionAndPermission(['teacher', 'ta']);

// 取得並驗證參數
$sid = trim($_POST['student_id'] ?? '');
$adate = trim($_POST['answer_date'] ?? '');

if ($sid === '' || $adate === '') {
    errorResponse('缺少必要參數');
}

try {
    $conn = getDbConnection();
    
    // 先查詢要刪除的記錄資訊（用於日誌）
    $checkStmt = $conn->prepare("SELECT student_id, score, answer_date FROM score WHERE student_id = ? AND answer_date = ?");
    if (!$checkStmt) {
        throw new Exception('準備查詢語句失敗: ' . $conn->error);
    }
    
    $checkStmt->bind_param("ss", $sid, $adate);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        $checkStmt->close();
        $conn->close();
        errorResponse('找不到要刪除的成績記錄');
    }
    
    $recordInfo = $checkResult->fetch_assoc();
    $checkStmt->close();
    
    // 執行刪除
    $stmt = $conn->prepare("DELETE FROM score WHERE student_id = ? AND answer_date = ?");
    if (!$stmt) {
        throw new Exception('準備刪除語句失敗: ' . $conn->error);
    }
    
    $stmt->bind_param("ss", $sid, $adate);
    
    if ($stmt->execute()) {
        $affectedRows = $stmt->affected_rows;
        
        // 記錄成績刪除操作日誌
        $scoreInfo = sprintf("分數: %s, 答題日期: %s", $recordInfo['score'], $recordInfo['answer_date']);
        $deleteDetails = sprintf("影響筆數: %d", $affectedRows);
        
        Logger::logScoreDelete(
            $_SESSION['student_id'] ?? 'UNKNOWN', 
            $recordInfo['student_id'], 
            $scoreInfo,
            $deleteDetails
        );
        
        $stmt->close();
        $conn->close();
        successResponse(['deleted_rows' => $affectedRows], '成績記錄已刪除');
    } else {
        $stmt->close();
        $conn->close();
        throw new Exception('執行刪除失敗: ' . $stmt->error);
    }
} catch (Exception $e) {
    errorResponse($e->getMessage(), 500);
}
