//Base64 編碼
function encodeB64Utf8(str) {
    try {
        return btoa(unescape(encodeURIComponent(str)));
    } catch (error) {
        console.error('Base64 編碼失敗:', error);
        return str;
    }
}

//Base64 解碼
function decodeB64Utf8(str) {
    // 檢查輸入是否為有效值
    if (!str || str === null || str === undefined || str === '') {
        return '';
    }
    
    // 確保輸入是字串
    str = String(str);
    
    try {
        return decodeURIComponent(Array.prototype.map.call(
            atob(str),
            c => '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2)
        ).join(''));
    } catch (error) {
        console.error('Base64 解碼失敗:', error, '原始資料:', str);
        return str; // 返回原始值而非空字串
    }
}

//顯示錯誤訊息
function showError(msg, elementId = 'errorMessage') {
    console.error('錯誤訊息:', msg);
    const errorElement = $('#' + elementId);
    if (errorElement.length) {
        errorElement.text(msg).show();
    } else {
        alert('錯誤: ' + msg);
    }
}

//隱藏錯誤訊息
function hideError(elementId = 'errorMessage') {
    const errorElement = $('#' + elementId);
    if (errorElement.length) {
        errorElement.hide();
    }
}

// 綁定通用表單變更事件（部門、經驗等欄位的啟用/禁用邏輯）
function bindFormChangeEvents() {
    // 部門選擇變更時啟用/禁用其他欄位
    $('#personal_department').change(function(){
        $('#personal_department_other').prop('disabled', $(this).val() !== '13');
    });
    
    // 其他醫院經驗變更時啟用/禁用年資欄位
    $('#personal_other_hospital_experience').change(function(){
        $('#personal_other_hospital_years').prop('disabled', $(this).val() === '0');
    });
    
    // 急重症經驗變更時啟用/禁用年資欄位
    $('#personal_critical_care_experience').change(function(){
        $('#personal_critical_care_years').prop('disabled', $(this).val() === '0');
    });
    
    // ACLS 經驗變更時啟用/禁用證書日期欄位
    $('#personal_acls_experience').change(function(){
        $('#personal_acls_training_date').prop('disabled', $(this).val() === '0');
    });
}

// 觸發表單變更事件
function triggerFormChangeEvents() {
    $('#personal_department').trigger('change');
    $('#personal_other_hospital_experience').trigger('change');
    $('#personal_critical_care_experience').trigger('change');
    $('#personal_acls_experience').trigger('change');
}

//顯示成功訊息
function showSuccess(msg, elementId = 'successMessage') {
    console.log('成功訊息:', msg);
    const successElement = $('#' + elementId);
    if (successElement.length) {
        successElement.text(msg).show();
    } else {
        alert('成功: ' + msg);
    }
}

//隱藏成功訊息
function hideSuccess(elementId = 'successMessage') {
    const successElement = $('#' + elementId);
    if (successElement.length) {
        successElement.hide();
    }
}

//清除所有訊息
function clearAllMessages() {
    hideError();
    hideSuccess();
}

//登出
function doLogout(redirectUrl = 'index.html') {
    console.log('嘗試登出...');
    
    $.post(API_CONFIG.getUrl('getData'), { action: 'logout' })
        .done(function(response) {
            console.log('登出成功');
            window.location.href = redirectUrl;
        })
        .fail(function(xhr, status, error) {
            console.error('登出失敗:', { 
                status: xhr.status, 
                error: error, 
                response: xhr.responseText 
            });
            
            // 即使登出失敗，也強制跳轉到首頁
            showError('登出時發生錯誤，但已自動跳轉到首頁');
            setTimeout(() => {
                window.location.href = redirectUrl;
            }, 2000);
        });
}

//排序模式配置
const SortConfig = {
    mode: 0,
    labels: ["成績降序", "用時升序", "日期降序（新→舊）"],
    
    //切換到下一個排序模式
    toggleMode: function() {
        this.mode = (this.mode + 1) % this.labels.length;
        return this.mode;
    },
    
    //取得當前排序模式的標籤
    getCurrentLabel: function() {
        return this.labels[this.mode];
    },
    
    //設定排序模式
    setMode: function(mode) {
        if (mode >= 0 && mode < this.labels.length) {
            this.mode = mode;
        }
    }
};

//統一的 AJAX 錯誤處理
function handleAjaxError(xhr, defaultMessage = '伺服器連線異常') {
    console.error('AJAX 錯誤:', {
        status: xhr.status,
        statusText: xhr.statusText,
        responseText: xhr.responseText
    });
    
    // 嘗試解析回應內容
    let responseData = null;
    try {
        responseData = JSON.parse(xhr.responseText);
    } catch (e) {
        // 如果不是 JSON 格式，記錄詳細錯誤
        console.error('JSON 解析失敗:', e);
        console.error('原始回應內容:', xhr.responseText);
    }
    
    // 處理特定的 HTTP 狀態碼
    if (xhr.status === 401 || xhr.status === 403) {
        // 檢查是否為自動登出
        if (responseData && responseData.auto_logout) {
            alert('您已自動登出，請重新登入');
            doLogout();
        } else {
            showError('登入已過期，請重新登入');
            setTimeout(() => {
                doLogout();
            }, 2000);
        }
    } else if (xhr.status === 0) {
        showError('網路連線中斷，請檢查網路狀態');
    } else if (xhr.status === 200) {
        // HTTP 200 但 JSON 解析失敗的情況
        if (!responseData) {
            showError('伺服器回應格式錯誤，請檢查伺服器設定');
            console.error('HTTP 200 但收到無效的 JSON 回應:', xhr.responseText);
        } else {
            // 如果能解析 JSON，檢查是否有錯誤訊息
            if (responseData.success === false) {
                showError(responseData.error || responseData.message || defaultMessage);
            } else {
                showError('未預期的錯誤，請重試');
            }
        }
    } else {
        showError(defaultMessage + ' (錯誤代碼: ' + xhr.status + ')');
    }
}

//刪除確認對話框
function confirmDelete(message = '確定要刪除嗎？') {
    return confirm(message);
}

//驗證學號格式
function validateStudentId(studentId) {
    return studentId && studentId.trim().length > 0;
}

//驗證時間格式 (A:BC 或純秒數)
function validateTimeFormat(timeStr) {
    if (!timeStr) return false;
    
    // 檢查是否為純數字 (秒數)
    if (/^\d+$/.test(timeStr)) return true;
    
    // 檢查是否為 A:BC 格式
    if (/^\d+:\d{2}$/.test(timeStr)) return true;
    
    return false;
}

//轉換時間格式 (秒數轉為 A:BC 格式)
function formatTime(time) {
    if (!time) return '';
    
    let timeStr = time.toString().trim();
    
    // 將全形冒號轉換為半形冒號
    timeStr = timeStr.replace('：', ':');
    
    // 如果已經是 A:BC 格式，直接回傳
    if (timeStr.includes(':')) {
        return timeStr;
    }
    
    // 如果是純秒數，轉換為 A:BC 格式
    const seconds = parseInt(timeStr, 10);
    if (!isNaN(seconds)) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
    }
    
    return timeStr;
}

//轉義 HTML 特殊字符，防止 XSS 攻擊
function escapeHtml(str) {
    if (!str) return '';
    
    return String(str).replace(/[&<>"'/]/g, function(match) {
        const escapeMap = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
            '/': '&#x2F;'
        };
        return escapeMap[match];
    });
}

//通用的頁面初始化函數
function initCommonFeatures() {
    // 導航欄功能已移至 navbar.js
    console.log('Common features initialized');
}

// 當 DOM 載入完成時自動初始化
$(document).ready(function() {
    initCommonFeatures();
    
    // 設定全域 AJAX 錯誤處理器
    $(document).ajaxError(function(event, xhr, settings, error) {
        // 檢查是否為自動登出的情況
        if (xhr.status === 401) {
            let responseData = null;
            try {
                responseData = JSON.parse(xhr.responseText);
            } catch (e) {
                // 解析失敗，忽略
            }
            
            // 如果是自動登出，顯示提示並跳轉
            if (responseData && responseData.auto_logout) {
                alert('您已自動登出，請重新登入');
                doLogout();
                return; // 阻止其他錯誤處理
            }
        }
    });
});

