<?php
session_start();

require_once '../PHP/common.php';

// 檢查權限
checkSessionAndPermission(['teacher', 'ta']);

// 檢查是否為匯出請求
$isExport = isset($_GET['export']) && $_GET['export'] === '1';

if ($isExport) {
    // 匯出 CSV
    $sortMode = intval($_GET['sortMode'] ?? 0);
    $permission = $_GET['permission'] ?? '';
    $order = getSortOrder($sortMode);
    $sql = getScoreSelectSqlWithPermission($order, $permission);
    exportScoresToCsv($sql, [], "所有成績_" . date('Y_m_d_H_i_s') . ".csv", $sortMode);
} else {
    // 回傳 JSON 資料 - 使用統一的回應格式
    try {
        $conn = getDbConnection();
        
        // 排序
        $mode = intval($_POST['sortMode'] ?? 0);
        $permission = $_POST['permission'] ?? '';
        $order = getSortOrder($mode);
        
        // 使用含權限篩選的 SQL 查詢
        $sql = getScoreSelectSqlWithPermission($order, $permission);
        $res = $conn->query($sql);
        
        if (!$res) {
            throw new Exception('查詢失敗: ' . $conn->error);
        }
        
        $data = [];
        while($r = $res->fetch_assoc()){
            // q1..q16 轉 int
            for($i = 1; $i <= 16; $i++) {
                $r["q{$i}"] = (int)$r["q{$i}"];
            }
            $data[] = $r;
        }
        
        $conn->close();
        successResponse(['data' => $data]);
        
    } catch (Exception $e) {
        errorResponse($e->getMessage());
    }
}
