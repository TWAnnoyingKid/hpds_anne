// API 配置文件 (移除硬編 IP，動態載入 APP_BASE_URL；若未取得則使用相對路徑)
const API_CONFIG = {
    // 後端 API 基礎 URL (預設留空 -> 代表使用相對路徑，如 /PHP/getData.php)
    BASE_URL: '',

    // API 端點 (全部使用以 / 開頭的絕對相對路徑，方便直接呼叫)
    ENDPOINTS: {
        getData: '/PHP/getData.php',
        get: '/PHP/get.php',
        manage: '/PHP/manage.php',
        search: '/PHP/search.php',
        allScore: '/PHP/all_score.php',
        enterScore: '/PHP/enterScore.php',
        deleteScores: '/PHP/delete_scores.php',
        export: '/PHP/export.php',
        logViewer: '/PHP/log_viewer.php'
    },

    // 取得完整 URL；若 BASE_URL 為空則直接回傳端點 (瀏覽器會對同站發送請求)
    getUrl(endpoint) {
        if (!this.ENDPOINTS[endpoint]) {
            console.warn('未知的 API 端點:', endpoint);
            return endpoint; // 允許直接傳入完整路徑或其他 URL
        }
        if (!this.BASE_URL) {
            return this.ENDPOINTS[endpoint];
        }
        return this.BASE_URL.replace(/\/$/, '') + this.ENDPOINTS[endpoint];
    }
};

// 嘗試從後端取得 APP_BASE_URL（由 config.env 提供）
(function loadAppConfig() {
    // 使用相對路徑呼叫，避免再硬編主機位址
    fetch('/PHP/get_app_config.php', { cache: 'no-store' })
        .then(resp => resp.ok ? resp.json() : null)
        .then(data => {
            if (data && data.baseUrl) {
                API_CONFIG.BASE_URL = data.baseUrl.trim().replace(/\/$/, '');
            } else {
                // 後端未提供或 baseUrl 空，用目前頁面來源 (避免跨站)
                API_CONFIG.BASE_URL = window.location.origin;
            }
        })
        .catch(err => {
            console.warn('載入 APP_BASE_URL 失敗，改用 window.location.origin', err);
            API_CONFIG.BASE_URL = window.location.origin;
        })
        .finally(() => {
            // 通知其他腳本設定已準備好 (若它們需要等待)
            document.dispatchEvent(new Event('appConfigReady'));
        });
})();

// 全域 AJAX 設置 (如將來需要附加認證標頭，可在此加入)
$.ajaxSetup({
    crossDomain: true,
    beforeSend: function(xhr) {
        // 可在此追加自定義 header，例如 token
        // const token = sessionStorage.getItem('authToken');
        // if (token) xhr.setRequestHeader('Authorization', 'Bearer ' + token);
    }
});
