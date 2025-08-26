<?php
// 確保不會有意外的輸出影響 JSON 回應
ob_start();
error_reporting(E_ERROR | E_PARSE); // 只報告嚴重錯誤
ini_set('display_errors', '0'); // 關閉錯誤顯示

session_start();

require_once '../PHP/common.php';

$action = $_POST['action'] ?? '';

// 權限檢查 - 根據操作類型決定權限
if ($action === 'update') {
    // 更新操作 - 允許用戶更新自己的資料
    checkSessionAndPermission(['teacher', 'ta', 'student', 'nurse', 'snurse']);
    
    $currentId = $_SESSION['student_id'] ?? '';
    $targetId = $_POST['student_id'] ?? '';
    $currentPermission = $_SESSION['permission'] ?? '';
    
    // 非管理員只能修改自己的資料
    if (!in_array($currentPermission, ['teacher', 'ta']) && $targetId !== $currentId) {
        errorResponse('您只能修改自己的個人資料');
    }
    
    // 非管理員不能修改權限欄位
    if (!in_array($currentPermission, ['teacher', 'ta']) && isset($_POST['permission'])) {
        errorResponse('您沒有權限修改用戶權限');
    }
    
} else {
    // 其他操作 (新增、刪除、查詢) 需要管理員權限
    checkSessionAndPermission(['teacher', 'ta']);
}

/* 欄位 */
$cols=[
  'name','password','permission','gender','birth_date','graduate_year','education_level','marital_status',
  'hire_date','department','department_other','facility_type',
  'other_hospital_experience','other_hospital_years','critical_care_experience','critical_care_years',
  'bls_training_date','acls_experience','acls_training_date','rescue_count','last_rescue_date',
  'rescue_course_count','case_record_date','group_assignment'
];

/* 使用 common.php 中的統一回應函數：successResponse() 和 errorResponse() */

$action=$_REQUEST['action']??'fetch';

/* ---------- test ---------- */
if($action==='test'){
 $result = performSystemTest();
 successResponse(['test_result'=>$result]);
}

/* ---------- fetch ---------- */
if($action==='fetch'){
 $mysqli = getDbConnection();
 $systemUserId = EnvLoader::get('SPECIAL_ADMIN_ID');
 $stmt = $mysqli->prepare("SELECT student_id,".implode(',',$cols)." FROM users WHERE student_id != ? ORDER BY id DESC");
 if(!$stmt) errorResponse($mysqli->error);
 $stmt->bind_param('s', $systemUserId);
 $stmt->execute();
 $res = $stmt->get_result();
 if(!$res) errorResponse($mysqli->error);
 $out=[];while($r=$res->fetch_assoc())$out[]=$r;
 successResponse(['data'=>$out]);
}

/* ---------- get ---------- */
if($action==='get'){
 $mysqli = getDbConnection();
 $sid = $_POST['student_id'] ?? '';
 
 // 檢查系統用戶權限
 $systemUserId = EnvLoader::get('SPECIAL_ADMIN_ID');
 if($sid === $systemUserId) {
     errorResponse('無權限查看此用戶資料');
 }
 
 $stmt = $mysqli->prepare("SELECT student_id,".implode(',',$cols)." FROM users WHERE student_id=?");
 if(!$stmt) errorResponse($mysqli->error);
 $stmt->bind_param('s', $sid);
 $stmt->execute();
 $res = $stmt->get_result();
 if(!$res||!$res->num_rows)errorResponse('找不到資料');
 successResponse(['data'=>$res->fetch_assoc()]);
}

/* ---------- add ---------- */
if ($action === 'add') {
    $mysqli = getDbConnection();
    // 基本必填欄位檢查
    $sid = trim($_POST['student_id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $pwd = trim($_POST['password'] ?? '');
    $perm = trim($_POST['permission'] ?? 'student');
    $gender = trim($_POST['gender'] ?? 'Male');
    
    if($sid === '' || $name === '' || $pwd === '') {
        errorResponse('學號、姓名、密碼為必填欄位');
    }
    
    // 直接使用固定的 SQL 語句插入基本欄位
    $sql = "INSERT INTO users (student_id, name, password, permission, gender) VALUES (?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        errorResponse('Prepare failed: ' . $mysqli->error);
    }
    $stmt->bind_param('sssss', $sid, $name, $pwd, $perm, $gender);
    if ($stmt->execute()) {
        Logger::logUserManagement($_SESSION['student_id'], 'ADD', $sid, "新增用戶: {$name}");
        successResponse();
    } else {
        errorResponse($stmt->error);
    }
}

/* ---------- update ---------- */
if($action==='update'){
    $mysqli = getDbConnection();
 
 // 檢查系統用戶權限
 $systemUserId = EnvLoader::get('SPECIAL_ADMIN_ID');
 $targetStudentId = $_POST['student_id'] ?? '';
 if($targetStudentId === $systemUserId) {
     errorResponse('無權限修改此用戶資料');
 }
 
 $p=[];
 foreach($cols as $c){
     $p[$c]=$_POST[$c]??'';
 }
 
 // 處理條件性欄位：當主選項為"否"時，將對應欄位設為 NULL
 if($p['other_hospital_experience'] !== '1'){
     $p['other_hospital_years'] = null;
 }
 if($p['critical_care_experience'] !== '1'){
     $p['critical_care_years'] = null;
 }
 if($p['acls_experience'] !== '1'){
     $p['acls_training_date'] = null;
 }
 if($p['department'] !== '13'){
     $p['department_other'] = '';
 }
 
 // 將空字串轉換為 NULL（針對整數和日期欄位）
 $intFields = ['graduate_year', 'other_hospital_years', 'critical_care_years', 'rescue_count', 'rescue_course_count'];
 $dateFields = ['birth_date', 'hire_date', 'bls_training_date', 'acls_training_date', 'last_rescue_date', 'case_record_date'];
 
 foreach($intFields as $field){
     if($p[$field] === '' || $p[$field] === '0') $p[$field] = null;
 }
 
 // 日期欄位處理：驗證格式並轉換
 foreach($dateFields as $field){
     if($p[$field] === '') {
         $p[$field] = null;
     } else if($p[$field] !== null) {
         // 檢查日期格式是否正確 (YYYY-MM-DD)
         if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $p[$field])) {
             // 如果只有年份，設為 null
             if(preg_match('/^\d{4}$/', $p[$field])) {
                 $p[$field] = null;
             } else {
                 errorResponse("日期格式錯誤 ($field): {$p[$field]}，請使用 YYYY-MM-DD 格式");
             }
         } else {
             // 驗證日期是否有效
             $dateParts = explode('-', $p[$field]);
             if(!checkdate($dateParts[1], $dateParts[2], $dateParts[0])) {
                 errorResponse("無效的日期 ($field): {$p[$field]}");
             }
         }
     }
 }
 
 // 建立動態 SQL 和參數
 $updateFields = [];
 $updateValues = [];
 $updateTypes = '';
 
 // 必要欄位（除了密碼）
 $requiredFields = ['name', 'gender', 'birth_date', 'graduate_year', 'education_level', 'marital_status', 
                   'hire_date', 'department', 'department_other', 'facility_type',
                   'other_hospital_experience', 'other_hospital_years', 'critical_care_experience', 'critical_care_years',
                   'bls_training_date', 'acls_experience', 'acls_training_date', 'rescue_count',
                   'last_rescue_date', 'rescue_course_count', 'case_record_date', 'group_assignment'];
 
 // 添加必要欄位
 foreach($requiredFields as $field) {
     $updateFields[] = "$field=?";
     $updateValues[] = $p[$field];
     $updateTypes .= 's';
 }
 
 // 處理密碼欄位（只有提供且不為空時才更新）
 if(isset($_POST['password']) && trim($_POST['password']) !== '') {
     $updateFields[] = "password=?";
     $updateValues[] = trim($_POST['password']); // 明文儲存
     $updateTypes .= 's';
 }
 
 // 處理權限欄位（只有管理員能修改）
 $currentPermission = $_SESSION['permission'] ?? '';
 if(in_array($currentPermission, ['teacher', 'ta']) && isset($_POST['permission']) && $_POST['permission'] !== '') {
     $updateFields[] = "permission=?";
     $updateValues[] = $_POST['permission'];
     $updateTypes .= 's';
 }
 
 // 添加 WHERE 條件的參數
 $updateValues[] = $_POST['student_id'] ?? '';
 $updateTypes .= 's';
 
 $sql = "UPDATE users SET " . implode(',', $updateFields) . " WHERE student_id=?";
 $stmt = $mysqli->prepare($sql);
 if(!$stmt) errorResponse($mysqli->error);
 
 $stmt->bind_param($updateTypes, ...$updateValues);
 
 // 除錯：如果執行失敗，提供詳細錯誤資訊
 if(!$stmt->execute()) {
     $error = $stmt->error;
     // 找出有問題的日期欄位
     $dateInfo = [];
     foreach($dateFields as $field) {
         if($p[$field] !== null) {
             $dateInfo[] = "$field: {$p[$field]}";
         }
     }
     errorResponse("執行失敗: $error | 日期欄位: " . implode(', ', $dateInfo));
 }

 Logger::logUserManagement($_SESSION['student_id']??'', 'UPDATE', $_POST['student_id']??'', "更新用戶資料");
 successResponse();
}

/* ---------- delete ---------- */
if($action==='delete'){
 $mysqli = getDbConnection();
 $sid = $_POST['student_id'] ?? '';
 
 // 檢查系統用戶權限
 $systemUserId = EnvLoader::get('SPECIAL_ADMIN_ID');
 if($sid === $systemUserId) {
     errorResponse('無權限刪除此用戶');
 }
 
 $stmt = $mysqli->prepare("DELETE FROM users WHERE student_id=?");
 if(!$stmt) errorResponse($mysqli->error);
 $stmt->bind_param('s', $sid);
 if ($stmt->execute()) {
     Logger::logUserManagement($_SESSION['student_id'], 'DELETE', $sid, "刪除用戶");
     successResponse();
 } else {
     errorResponse($stmt->error);
 }
}

/* ---------- search ---------- */
if($action==='search'){
    $mysqli = getDbConnection();
 $conditions = [];
 $params = [];
 $types = '';
 
 // 排除系統用戶
 $systemUserId = EnvLoader::get('SPECIAL_ADMIN_ID');
 $conditions[] = "student_id != ?";
 $params[] = $systemUserId;
 $types .= 's';
 
 // 處理姓名搜尋
 if(!empty($_POST['name'])) {
     $conditions[] = "name LIKE ?";
     $params[] = '%' . $_POST['name'] . '%';
     $types .= 's';
 }
 
 // 處理職稱搜尋
 if(!empty($_POST['permission'])) {
     $conditions[] = "permission = ?";
     $params[] = $_POST['permission'];
     $types .= 's';
 }
 
 // 檢查是否有搜尋條件
 if(count($conditions) <= 1) {
     errorResponse('請提供至少一個搜尋條件');
 }
 
 $sql = "SELECT student_id,".implode(',',$cols)." FROM users WHERE " . implode(' AND ', $conditions) . " ORDER BY id DESC";
 $stmt = $mysqli->prepare($sql);
 
 if(!$stmt) errorResponse($mysqli->error);
 
 if(!empty($params)) {
     $stmt->bind_param($types, ...$params);
 }
 
 $stmt->execute();
 $res = $stmt->get_result();
 
 if(!$res) errorResponse($mysqli->error);
 
 $out = [];
 while($r = $res->fetch_assoc()) {
     $out[] = $r;
 }
 
 successResponse(['data' => $out]);
}





errorResponse('未知動作');