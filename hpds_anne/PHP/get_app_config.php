<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/env_loader.php';

try {
	EnvLoader::load();
	$baseUrl = EnvLoader::get('APP_BASE_URL');
	// 若未設定，回傳空字串讓前端自行用 window.location.origin
	echo json_encode([
		'baseUrl' => $baseUrl ?? ''
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
	http_response_code(500);
	echo json_encode([
		'error' => 'Failed to load app config',
		'message' => $e->getMessage()
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>
