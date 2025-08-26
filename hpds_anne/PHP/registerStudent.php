<?php
// 用戶註冊功能 - 重導向到 manage.php
// 此功能已整合到 manage.php 的 add 操作中

require_once '../PHP/common.php';

// 檢查權限 - 只有管理員可以註冊新用戶
checkSessionAndPermission(['teacher', 'ta']);

// 重新導向到 manage.php 的 add 操作
$_POST['action'] = 'add';

// 姓名需要進行 Base64 編碼
if (isset($_POST['name'])) {
    $_POST['name'] = base64_encode($_POST['name']);
}

// 包含 manage.php 來處理實際的新增操作
require_once '../PHP/manage.php';
?>
