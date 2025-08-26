// score.js - 成績查看頁面的 JavaScript

// 個人成績排序配置
const PersonalSortConfig = {
    mode: 0,
    labels: ["成績降序", "用時升序", "日期升序"],
    
    // 切換到下一個排序模式
    toggleMode: function() {
        this.mode = (this.mode + 1) % this.labels.length;
        return this.mode;
    },
    
    // 取得當前排序模式的標籤
    getCurrentLabel: function() {
        return this.labels[this.mode];
    },
    
    // 設定排序模式
    setMode: function(mode) {
        if (mode >= 0 && mode < this.labels.length) {
            this.mode = mode;
        }
    }
};

$(document).ready(function() {
    console.log('成績頁面載入完成');
    
    // 檢查登入狀態並載入資料
    checkAuthAndLoadData();
    
    // 綁定事件
    $('#refreshBtn').click(loadScoreData);
    $('#toggleSortBtn').click(toggleSort);
    $('#personalInfoForm').on('submit', savePersonalInfo);
    
    // 監聽來自導航欄的個人資訊編輯事件
    $(document).on('showPersonalInfoModal', showPersonalInfoModal);
    
    // 表單變更事件綁定
    bindFormChangeEvents();
});

// 檢查權限並載入資料
function checkAuthAndLoadData() {
    $.post(API_CONFIG.getUrl('getData'), {}, function(response) {
        if (response.error) {
            handleAuthError(response.error);
        } else {
            // 檢查權限
            const perm = response.permission;
            if (!['student', 'nurse', 'snurse'].includes(perm)) {
                alert('您沒有存取此頁面的權限');
                location.href = 'index.html';
                return;
            }
            
            // 載入成績資料
            if (response.data) {
                renderScoreTable(response.data);
            } else {
                loadScoreData();
            }
            
            // 初始化排序按鈕文字
            $('#toggleSortBtn').text(PersonalSortConfig.getCurrentLabel());
        }
    }, 'json').fail(xhr => {
        handleAjaxError(xhr, '檢查登入狀態失敗');
    });
}

// 處理認證錯誤
function handleAuthError(error) {
    console.error('認證失敗:', error);
    alert('登入已過期，請重新登入');
    location.href = 'index.html';
}

// 載入成績資料
function loadScoreData() {
    $.post(API_CONFIG.getUrl('getData'), { sortMode: PersonalSortConfig.mode }, function(response) {
        if (response.success !== false) {
            if (response.data) {
                renderScoreTable(response.data);
            } else {
                showNoScoreMessage();
            }
            // 更新排序按鈕文字
            $('#toggleSortBtn').text(PersonalSortConfig.getCurrentLabel());
        } else {
            showError('載入成績失敗: ' + (response.message || '未知錯誤'));
        }
    }, 'json').fail(xhr => {
        handleAjaxError(xhr, '載入成績失敗');
    });
}

// 切換排序
function toggleSort() {
    PersonalSortConfig.toggleMode();
    $('#toggleSortBtn').text(PersonalSortConfig.getCurrentLabel());
    loadScoreData();
}

// 渲染成績表格
function renderScoreTable(scores) {
    
    if (!scores || scores.length === 0) {
        showNoScoreMessage();
        return;
    }
    
    let html = '';
    scores.forEach(score => {
        html += `<tr>
            <td>${escapeHtml(score.student_id)}</td>
            <td>${escapeHtml(score.score)}</td>
            <td>${escapeHtml(score.incorrect_questions)}</td>
            <td>${escapeHtml(score.answer_time)}</td>
            <td>${escapeHtml(score.answer_date)}</td>
        </tr>`;
    });
    
    $('#scoreTableBody').html(html);
    $('#noScoreMessage').hide();
}

// 顯示無成績訊息
function showNoScoreMessage() {
    $('#scoreTableBody').empty();
    $('#noScoreMessage').show();
}

// 顯示編輯個人資訊 Modal
function showPersonalInfoModal() {
    loadPersonalInfo();
    $('#personalInfoModal').modal('show');
}

// 載入個人資訊
function loadPersonalInfo() {
    
    $.post(API_CONFIG.getUrl('search'), {
        action: 'get'
    }, function(response) {
        if (response.success && response.data) {
            populatePersonalInfoForm(response.data);
        } else {
            showError('載入個人資訊失敗: ' + (response.message || '未知錯誤'));
        }
    }, 'json').fail(xhr => {
        handleAjaxError(xhr, '載入個人資訊失敗');
    });
}

// 填入個人資訊表單
function populatePersonalInfoForm(user) {
    
    $('#personal_student_id').val(user.student_id);
    $('#personal_student_id_display').val(user.student_id);
    $('#personal_permission_display').val(getPermissionText(user.permission));
    $('#personal_name').val(decodeB64Utf8(user.name || ''));
    $('#personal_password').val(''); // 密碼欄位預設為空
    $('#personal_gender').val(user.gender || '');
    $('#personal_birth_date').val(user.birth_date || '');
    $('#personal_graduate_year').val(user.graduate_year || '');
    $('#personal_education_level').val(user.education_level || '');
    $('#personal_marital_status').val(user.marital_status || '');
    $('#personal_hire_date').val(user.hire_date || '');
    $('#personal_facility_type').val(user.facility_type || '');
    $('#personal_department').val(user.department || '');
    $('#personal_department_other').val(decodeB64Utf8(user.department_other || ''));
    $('#personal_other_hospital_experience').val(user.other_hospital_experience || '0');
    $('#personal_other_hospital_years').val(user.other_hospital_years || '');
    $('#personal_critical_care_experience').val(user.critical_care_experience || '0');
    $('#personal_critical_care_years').val(user.critical_care_years || '');
    $('#personal_bls_training_date').val(user.bls_training_date || '');
    $('#personal_acls_experience').val(user.acls_experience || '0');
    $('#personal_acls_training_date').val(user.acls_training_date || '');
    $('#personal_rescue_count').val(user.rescue_count || '');
    $('#personal_last_rescue_date').val(user.last_rescue_date ? user.last_rescue_date.substring(0, 7) : '');
    $('#personal_rescue_course_count').val(user.rescue_course_count || '');
    $('#personal_case_record_date').val(user.case_record_date || '');
    $('#personal_group_assignment').val(user.group_assignment || '');
    
    // 觸發變更事件以正確啟用/禁用相關欄位
    triggerFormChangeEvents();
}

// 觸發表單變更事件（直接調用 common.js 的實現）
function triggerFormChangeEvents() {
    $('#personal_department').trigger('change');
    $('#personal_other_hospital_experience').trigger('change');
    $('#personal_critical_care_experience').trigger('change');
    $('#personal_acls_experience').trigger('change');
}

// 儲存個人資訊
function savePersonalInfo(e) {
    e.preventDefault();
    console.log('儲存個人資訊...');
    
    const formData = $('#personalInfoForm').serializeArray().reduce((o, i) => (o[i.name] = i.value, o), {});
    
    // 處理月份格式的日期欄位
    if (formData.last_rescue_date && formData.last_rescue_date.match(/^\d{4}-\d{2}$/)) {
        formData.last_rescue_date += '-01';
    }
    
    // 處理空的日期欄位
    const dateFields = ['birth_date', 'hire_date', 'bls_training_date', 'acls_training_date', 'last_rescue_date', 'case_record_date'];
    dateFields.forEach(field => {
        if (formData[field] === '') formData[field] = null;
    });
    
    // 編碼文字欄位
    formData.action = 'update';
    formData.name = encodeB64Utf8($('#personal_name').val());
    formData.department_other = encodeB64Utf8($('#personal_department_other').val());
    
    // 處理密碼 - 如果是空的就不包含在更新中
    const password = $('#personal_password').val().trim();
    if (password === '') {
        delete formData.password;
    }
    
    $.post(API_CONFIG.getUrl('manage'), formData, function(response) {
        if (response.success) {
            $('#personalInfoModal').modal('hide');
            showSuccess('個人資訊更新成功');
        } else {
            showError('更新失敗: ' + (response.message || '未知錯誤'));
        }
    }, 'json').fail(xhr => {
        handleAjaxError(xhr, '更新個人資訊失敗');
    });
}

// 權限文字轉換
function getPermissionText(permission) {
    const permissionMap = {
        'nurse': '護理師',
        'snurse': '專科護理師',
        'student': '護生',
        'teacher': '教師',
        'ta': '管理員'
    };
    return permissionMap[permission] || permission;
}

// 從 localStorage 獲取存儲的用戶 ID
function getStoredUserId() {
    return localStorage.getItem('currentUserId') || '未知用戶';
}
