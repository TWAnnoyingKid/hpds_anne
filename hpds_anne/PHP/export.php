<?php
/**
 * 統一的匯出端點
 * 整合所有匯出功能到一個檔案
 */

session_start();
require_once __DIR__ . '/common.php';

// 檢查權限
checkSessionAndPermission(['teacher', 'ta']);

// 取得匯出類型和參數
$exportType = $_GET['type'] ?? '';
$sortMode = intval($_GET['sortMode'] ?? 0);
$studentId = $_GET['student_id'] ?? '';

$orderBy = getSortOrder($sortMode);

// 共用的用戶資料映射表和欄位定義
$USER_COLS = [
    'name','password','permission','gender','birth_date','graduate_year','education_level','marital_status',
    'hire_date','department','department_other','facility_type',
    'other_hospital_experience','other_hospital_years','critical_care_experience','critical_care_years',
    'bls_training_date','acls_experience','acls_training_date','rescue_count','last_rescue_date',
    'rescue_course_count','case_record_date','group_assignment'
];

$USER_MAPS = [
    'gen' => ['Male'=>'男','Female'=>'女'],
    'role' => ['nurse'=>'護理師','snurse'=>'專科護理師','student'=>'護生','teacher'=>'教師','ta'=>'管理員'],
    'edu' => ['1'=>'專科','2'=>'大學','3'=>'碩士','4'=>'博士'],
    'mar' => ['1'=>'未婚','2'=>'同居','3'=>'已婚','4'=>'分居','5'=>'離婚','6'=>'喪偶'],
    'dept' => [1=>'內科',2=>'外科',3=>'骨科',4=>'泌尿科',5=>'皮膚科',6=>'眼科',7=>'耳鼻喉科',8=>'婦產科',9=>'兒科',10=>'ICU',11=>'ER',12=>'OR',13=>'其他'],
    'fac' => [1=>'醫學中心',2=>'區域醫院',3=>'地區醫院',4=>'學校']
];

$USER_CSV_HEADER = ['姓名','職稱','性別',
   '出生年月日(西元)','護理科系畢業年份','教育程度',
   '婚姻狀況','本院到職日','服務單位部門','服務單位部門(其他)','目前所在單位類型',
   '是否曾在其他醫院服務','其他醫院年資(年)',
   '是否有急重症經驗','急重症年資(年)',
   '上次 BLS 日期','是否參加 ACLS','ACLS 日期',
   '參與急救次數','上次急救年月','急救課程次數',
   '收案日期','分組(1~6=實驗組 7=對照組)'];

// 共用的用戶資料格式化函式
function formatUserDataToCsv($r, $maps) {
    $decodedName = $r['name'] ? decodeName($r['name']) : '';
    $decodedDeptOther = $r['department_other'] ? decodeName($r['department_other']) : '';
    
    return [
        $decodedName,
        $maps['role'][$r['permission']] ?? $r['permission'] ?? '',
        $maps['gen'][$r['gender']] ?? $r['gender'] ?? '',
        $r['birth_date'] ?? '',
        $r['graduate_year'] ?? '',
        $maps['edu'][$r['education_level']] ?? $r['education_level'] ?? '',
        $maps['mar'][$r['marital_status']] ?? $r['marital_status'] ?? '',
        $r['hire_date'] ?? '',
        $maps['dept'][$r['department']] ?? $r['department'] ?? '',
        $decodedDeptOther,
        $maps['fac'][$r['facility_type']] ?? $r['facility_type'] ?? '',
        ($r['other_hospital_experience'] ?? '') == '1' ? '是' : '否',
        $r['other_hospital_years'] ?? '',
        ($r['critical_care_experience'] ?? '') == '1' ? '是' : '否',
        $r['critical_care_years'] ?? '',
        $r['bls_training_date'] ?? '',
        ($r['acls_experience'] ?? '') == '1' ? '是' : '否',
        $r['acls_training_date'] ?? '',
        $r['rescue_count'] ?? '',
        $r['last_rescue_date'] ? substr($r['last_rescue_date'], 0, 7) : '',
        $r['rescue_course_count'] ?? '',
        $r['case_record_date'] ?? '',
        $r['group_assignment'] ?? ''
    ];
}

// 共用的用戶資料 CSV 匯出函式
function outputUsersCsv($result, $filename, $header, $maps) {
    // 準備資料陣列
    $data = [];
    while ($r = $result->fetch_assoc()) {
        $data[] = formatUserDataToCsv($r, $maps);
    }
    
    // 使用 common.php 的統一匯出函式
    exportToCsv($data, $header, $filename);
}



try {
    switch ($exportType) {
        case 'all_scores':
            // 匯出所有成績（含職稱篩選）
            $permission = $_GET['permission'] ?? '';
            $logDetail = !empty($permission) ? "職稱篩選: {$permission}" : '全部';
            Logger::logScoreExport($_SESSION['student_id'], $_SESSION['permission'], '所有成績', $logDetail);
            $sql = getScoreSelectSqlWithPermission($orderBy, $permission);
            exportScoresToCsv($sql, [], "所有成績_" . date('Y_m_d_H_i_s') . ".csv", $sortMode);
            break;
            
        case 'search_scores':
            // 匯出搜尋結果 (特定學號的成績)
            if (empty($studentId)) {
                throw new Exception('缺少學號參數');
            }
            
            Logger::logScoreExport($_SESSION['student_id'], $_SESSION['permission'], '特定學號成績', "學號: {$studentId}");
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
                ORDER BY {$orderBy}";
            exportScoresToCsv($sql, [$studentId], "成績_{$studentId}_" . date('Y_m_d_H_i_s') . ".csv", $sortMode);
            break;
            
        case 'users':
            if (empty($studentId)) {
                Logger::logUserExport($_SESSION['student_id'], $_SESSION['permission'], '全部');
            } else {
                Logger::logUserExport($_SESSION['student_id'], $_SESSION['permission'], "學號: {$studentId}");
            }
            exportUsers($studentId);
            break;
            
        case 'search_users':
            $searchConditions = [];
            if (!empty($_GET['search_name'])) $searchConditions[] = "姓名: {$_GET['search_name']}";
            if (!empty($_GET['search_permission'])) $searchConditions[] = "權限: {$_GET['search_permission']}";
            $searchStr = empty($searchConditions) ? '全部' : implode(', ', $searchConditions);

            Logger::logUserExport($_SESSION['student_id'], $_SESSION['permission'], $searchStr);
            exportSearchUsers();
            break;
            
        default:
            throw new Exception('未知的匯出類型');
    }
    
} catch (Exception $e) {
    errorResponse($e->getMessage());
}

// 匯出用戶資料
function exportUsers($studentId = '') {
    global $USER_COLS, $USER_MAPS, $USER_CSV_HEADER;
    
    $conn = getDbConnection();
    $systemUserId = EnvLoader::get('SPECIAL_ADMIN_ID');
    
    if (!empty($studentId)) {
        // 檢查系統用戶權限
        if ($studentId === $systemUserId) {
            $conn->close();
            echo "無權限匯出此用戶資料";
            exit;
        }
        
        $stmt = $conn->prepare("SELECT student_id," . implode(',', $USER_COLS) . " FROM users WHERE student_id=?");
        $stmt->bind_param('s', $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $stmt = $conn->prepare("SELECT student_id," . implode(',', $USER_COLS) . " FROM users WHERE student_id != ?");
        $stmt->bind_param('s', $systemUserId);
        $stmt->execute();
        $result = $stmt->get_result();
    }
    
    if (!$result || $result->num_rows === 0) {
        $conn->close();
        echo "沒有找到用戶資料";
        exit;
    }
    
    // 決定檔名
    if (!empty($studentId)) {
        $first = $result->fetch_assoc();
        $result->data_seek(0);
        $filename = decodeName($first['name']) . '_' . date('Y_m_d_H_i_s') . '.csv';
    } else {
        $filename = '全部用戶_' . date('Y_m_d_H_i_s') . '.csv';
    }
    
    outputUsersCsv($result, $filename, $USER_CSV_HEADER, $USER_MAPS);
    $conn->close();
}

// 匯出搜尋的用戶資料
function exportSearchUsers() {
    global $USER_COLS, $USER_MAPS, $USER_CSV_HEADER;
    
    $conditions = [];
    $params = [];
    $searchCriteria = [];
    
    $conn = getDbConnection();
    $systemUserId = EnvLoader::get('SPECIAL_ADMIN_ID');
    
    // 先加入排除系統用戶的條件
    $conditions[] = "student_id != ?";
    $params[] = $systemUserId;
    
    // 處理搜尋條件
    if (!empty($_GET['search_name'])) {
        $conditions[] = "name LIKE ?";
        $params[] = '%' . $_GET['search_name'] . '%';
        $searchCriteria['name'] = $_GET['search_name'];
    }
    
    if (!empty($_GET['search_permission'])) {
        $conditions[] = "permission = ?";
        $params[] = $_GET['search_permission'];
        $searchCriteria['permission'] = $_GET['search_permission'];
    }
    
    // 修正條件判斷：只有系統用戶過濾條件時視為無搜尋條件
    if (count($conditions) <= 1) {
        // 沒有搜尋條件時匯出全部（排除系統用戶）
        $stmt = $conn->prepare("SELECT student_id," . implode(',', $USER_COLS) . " FROM users WHERE student_id != ?");
        $stmt->bind_param('s', $systemUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        $searchType = 'all_users';
    } else {
        $sql = "SELECT student_id," . implode(',', $USER_COLS) . " FROM users WHERE " . implode(' AND ', $conditions) . " ORDER BY id DESC";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            $conn->close();
            throw new Exception('SQL prepare failed: ' . $conn->error);
        }
        
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $searchType = 'filtered_search';
    }
    
    if (!$result || $result->num_rows === 0) {
        $conn->close();
        echo "沒有找到符合條件的用戶資料";
        exit;
    }
    
    $filename = '搜尋結果_' . date('Y_m_d_H_i_s') . '.csv';
    
    
    outputUsersCsv($result, $filename, $USER_CSV_HEADER, $USER_MAPS);
    $conn->close();
}
