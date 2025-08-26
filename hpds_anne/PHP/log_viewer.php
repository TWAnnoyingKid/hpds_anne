<?php
// 日誌查看系統管理
session_start();
require_once '../PHP/common.php';

// 檢查系統權限
checkSystemPermission();

$action = $_GET['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            // 取得日誌檔案列表
            $files = Logger::getLogFiles();
            jsonResponse(['success' => true, 'files' => $files]);
            break;
            
        case 'read':
            // 讀取特定日誌檔案
            $filename = $_GET['filename'] ?? '';
            $lines = intval($_GET['lines'] ?? 100);
            
            if (empty($filename)) {
                throw new Exception('缺少檔案名稱');
            }
            
            // 驗證檔案名稱安全性
            if (!preg_match('/^hpds_\d{4}-\d{2}-\d{2}(_\d{6})?\.log$/', $filename)) {
                throw new Exception('無效的檔案名稱');
            }
            
            $logs = Logger::readLogFile($filename, $lines);
            jsonResponse(['success' => true, 'logs' => $logs, 'filename' => $filename]);
            break;
            
        case 'download':
            // 下載日誌檔案
            $filename = $_GET['filename'] ?? '';
            
            if (empty($filename)) {
                throw new Exception('缺少檔案名稱');
            }
            
            // 驗證檔案名稱安全性
            if (!preg_match('/^hpds_\d{4}-\d{2}-\d{2}(_\d{6})?\.log$/', $filename)) {
                throw new Exception('無效的檔案名稱');
            }
            
            $logPath = EnvLoader::get('LOG_PATH', 'C:/xampp/logs');
            $filepath = $logPath . '/' . $filename;
            
            if (!file_exists($filepath)) {
                throw new Exception('檔案不存在');
            }
            
            // 記錄下載日誌的行為
            Logger::logSecurity('LOG_DOWNLOAD', "下載日誌檔案: {$filename}", $_SESSION['student_id']);
            
            // 設定下載標頭
            header('Content-Type: text/plain; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($filepath));
            
            // 輸出檔案內容
            readfile($filepath);
            exit;
            
        case 'search':
            // 搜尋日誌
            $keyword = $_GET['keyword'] ?? '';
            $level = $_GET['level'] ?? '';
            $category = $_GET['category'] ?? '';
            $filename = $_GET['filename'] ?? '';
            $lines = intval($_GET['lines'] ?? 500);
            
            if (empty($keyword) && empty($level) && empty($category)) {
                throw new Exception('請提供搜尋條件');
            }
            
            $searchResults = searchLogs($keyword, $level, $category, $filename, $lines);
            jsonResponse(['success' => true, 'results' => $searchResults]);
            break;
            
        case 'cleanup':
            // 刪除超過指定天數的日誌
            $days = intval($_GET['days'] ?? 30);
            if ($days < 1) {
                throw new Exception('天數必須大於0');
            }
            
            $deletedFiles = cleanupOldLogs($days);
            
            // 記錄清理操作
            Logger::logSecurity('LOG_CLEANUP', "清理超過{$days}天的日誌檔案，刪除了" . count($deletedFiles) . "個檔案", $_SESSION['student_id']);
            
            jsonResponse(['success' => true, 'deleted_files' => $deletedFiles, 'count' => count($deletedFiles)]);
            break;
            
        default:
            throw new Exception('未知的操作');
    }
    
} catch (Exception $e) {
    errorResponse($e->getMessage());
}

//搜尋日誌內容
function searchLogs($keyword, $level, $category, $filename, $maxLines) {
    $results = [];
    $files = [];
    
    if (!empty($filename)) {
        // 搜尋特定檔案
        if (preg_match('/^hpds_\d{4}-\d{2}-\d{2}(_\d{6})?\.log$/', $filename)) {
            $files[] = $filename;
        }
    } else {
        // 搜尋所有檔案
        $allFiles = Logger::getLogFiles();
        $files = array_map(function($file) { return $file['name']; }, $allFiles);
    }
    
    foreach ($files as $file) {
        $logs = Logger::readLogFile($file, $maxLines);
        
        foreach ($logs as $log) {
            $match = true;
            
            // 關鍵字搜尋
            if (!empty($keyword)) {
                $searchText = strtolower($log['message'] . ' ' . $log['user_id']);
                if (strpos($searchText, strtolower($keyword)) === false) {
                    $match = false;
                }
            }
            
            // 等級篩選
            if (!empty($level) && $log['level'] !== $level) {
                $match = false;
            }
            
            // 類別篩選
            if (!empty($category) && $log['category'] !== $category) {
                $match = false;
            }
            
            if ($match) {
                $log['file'] = $file;
                $results[] = $log;
            }
        }
    }
    
    // 按時間排序（最新的在前）
    usort($results, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });
    
    return array_slice($results, 0, 200); // 限制結果數量
}

//清理超過指定天數的日誌檔案
function cleanupOldLogs($days) {
    $logPath = EnvLoader::get('LOG_PATH', 'C:/xampp/logs');
    $files = glob($logPath . "/hpds_*.log");
    $deletedFiles = [];
    $cutoffTime = time() - ($days * 24 * 60 * 60);
    
    foreach ($files as $file) {
        if (filemtime($file) < $cutoffTime) {
            $filename = basename($file);
            if (@unlink($file)) {
                $deletedFiles[] = $filename;
            }
        }
    }
    
    return $deletedFiles;
}
