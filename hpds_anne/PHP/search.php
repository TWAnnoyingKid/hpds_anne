<?php
session_start();

require_once '../PHP/common.php';

// 檢查是否為匯出請求
$isExport = isset($_GET['export']) && $_GET['export'] === '1';

if ($isExport) {
    // 檢查權限 - 匯出需要管理員權限
    checkSessionAndPermission(['teacher', 'ta']);
    
    // 匯出 CSV
    $sid = trim($_GET['student_id'] ?? '');
    if ($sid === '') {
        echo '缺少 student_id';
        exit;
    }
    
    $sortMode = intval($_GET['sortMode'] ?? 0);
    $order = getSortOrder($sortMode);
    
    $sql = "SELECT
        s.student_id,
        u.name,
        s.score,
        s.incorrect_questions,
        s.answer_time,
        s.answer_date," . 
        implode(',', array_map(fn($i) => "s.q{$i}", range(1, 16))) .
        " FROM score s
        JOIN users u ON s.student_id = u.student_id
        WHERE s.student_id = ?
        ORDER BY {$order}";
    
    exportScoresToCsv($sql, [$sid], "成績_{$sid}_" . date('Y_m_d_H_i_s') . ".csv", $sortMode);
} else {
    // 回傳 JSON 資料 - 使用統一的回應格式
    try {
        $action = $_POST['action'] ?? 'search';
        
        if ($action === 'get') {
            // 個人資訊查詢
            checkSessionAndPermission(['teacher', 'ta', 'student', 'nurse', 'snurse']);
            
            $requestedId = trim($_POST['student_id'] ?? '');
            $currentId = $_SESSION['student_id'] ?? '';
            $currentPermission = $_SESSION['permission'] ?? '';
            
            // 如果沒有指定要查詢的ID，預設查詢自己
            if ($requestedId === '') {
                $requestedId = $currentId;
            }
            
            // 只有管理員可以查詢別人的資料，一般用戶只能查詢自己的資料
            if (!in_array($currentPermission, ['teacher', 'ta']) && $requestedId !== $currentId) {
                errorResponse('您只能查詢自己的個人資訊');
            }
            
            $conn = getDbConnection();
            $stmt = $conn->prepare("SELECT * FROM users WHERE student_id = ?");
            $stmt->bind_param('s', $requestedId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $stmt->close();
                $conn->close();
                successResponse(['data' => $row]);
            } else {
                $stmt->close();
                $conn->close();
                errorResponse('找不到用戶資料');
            }
            
        } else {
            // 原本的成績查詢功能 - 需要管理員權限
            checkSessionAndPermission(['teacher', 'ta']);
            
            $conn = getDbConnection();
            
            // student_id 參數
            $sid = trim($_POST['student_id'] ?? '');
            if ($sid === '') {
                // 讓前端知道 session 有效
                successResponse(['data' => []]);
            }
            
            // 排序
            $mode = intval($_POST['sortMode'] ?? 0);
            $order = getSortOrder($mode);

            // 撈資料（JOIN users 拿 Base64 姓名 + Q1–Q16）
            $sql = "SELECT
                s.student_id,
                u.name,
                s.score,
                s.incorrect_questions,
                s.answer_time,
                s.answer_date," . 
                implode(',', array_map(fn($i) => "s.q{$i}", range(1, 16))) .
                " FROM score s
                JOIN users u ON s.student_id = u.student_id
                WHERE s.student_id = ?
                ORDER BY {$order}";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $conn->close();
                throw new Exception('查詢準備失敗: ' . $conn->error);
            }
            
            $stmt->bind_param('s', $sid);
            $stmt->execute();
            $res = $stmt->get_result();
            
            $data = [];
            while($row = $res->fetch_assoc()){
                // 將 q1..q16 轉成整數 0/1
                for($i = 1; $i <= 16; $i++){
                    $row["q{$i}"] = (int)$row["q{$i}"];
                }
                $data[] = $row;
            }
            
            $stmt->close();
            $conn->close();
            successResponse(['data' => $data]);
        }
        
    } catch (Exception $e) {
        errorResponse($e->getMessage());
    }
}
