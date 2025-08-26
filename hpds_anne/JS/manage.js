$(function(){
    const zhRole={nurse:'護理師',snurse:'專科護理師',student:'護生',teacher:'教師',ta:'管理員'};
    
    // 搜尋狀態管理
    let currentSearchData = null;
    

    
    // 更新匯出按鈕狀態
    function updateExportButton() {
        const $exportBtn = $('#exportAll');
        if (currentSearchData) {
            $exportBtn.text('匯出搜尋結果').removeClass('btn-warning').addClass('btn-info');
        } else {
            $exportBtn.text('匯出全部').removeClass('btn-info').addClass('btn-warning');
        }
    }
    
    function toggleExtra(){
     const otherHospitalDisabled = $('#edit_other_hospital_experience').val()!=='1';
     const criticalCareDisabled = $('#edit_critical_care_experience').val()!=='1';
     const aclsDisabled = $('#edit_acls_experience').val()!=='1';
     
     $('#edit_other_hospital_years').prop('disabled', otherHospitalDisabled);
     $('#edit_critical_care_years').prop('disabled', criticalCareDisabled);
     $('#edit_acls_training_date').prop('disabled', aclsDisabled);
     $('#edit_department_other').prop('disabled', $('#edit_department').val()!=='13');
     
     // 當選擇"否"時，清空對應欄位的值
     if(otherHospitalDisabled) $('#edit_other_hospital_years').val('');
     if(criticalCareDisabled) $('#edit_critical_care_years').val('');
     if(aclsDisabled) $('#edit_acls_training_date').val('');
    }
    $(document).on('change','#edit_other_hospital_experience,#edit_critical_care_experience,#edit_acls_experience,#edit_department',toggleExtra);
    
    function fetchUsers(){
     $.post(API_CONFIG.getUrl('manage'),{action:'fetch'},r=>{
      if(!r.success)return alert(r.message);
      currentSearchData = null; // 清除搜尋狀態
      displayUsers(r.data);
      updateExportButton();
     },'json').fail(xhr=>handleAjaxError(xhr, '載入用戶資料失敗'));
    }

    // 顯示用戶資料（統一函式）
    function displayUsers(data) {
        const $tbody = $('#userTable tbody').empty();
        
        if (data.length === 0) {
            $tbody.append('<tr><td colspan="6" class="text-center text-muted">沒有找到符合條件的用戶</td></tr>');
        } else {
            data.forEach(u => {
                $tbody.append(`<tr>
                    <td>${escapeHtml(u.student_id || '')}</td>
                    <td>${escapeHtml(u.name ? decodeB64Utf8(u.name) : '')}</td>
                    <td>
                        <span class="password-text">
                            <span class="password-value" style="display: none;">${escapeHtml(u.password || '')}</span>
                            <button class="toggle-password" type="button">
                                <i class="bi bi-eye"></i>
                            </button>
                        </span>
                    </td>
                    <td>${escapeHtml(zhRole[u.permission] ?? u.permission)}</td>
                    <td>${escapeHtml(u.gender || '')}</td>
                    <td class="text-nowrap">
                        <button class="btn btn-sm btn-primary editBtn" data-id="${u.student_id}">編輯</button>
                        <button class="btn btn-sm btn-danger delBtn" data-id="${u.student_id}">刪除</button>
                        <button class="btn btn-sm btn-warning exportBtn" data-id="${u.student_id}">匯出</button>
                    </td>
                </tr>`);
            });
        }
        
        // 添加密碼顯示/隱藏功能
        $('#userTable').off('click', '.toggle-password').on('click', '.toggle-password', function() {
            const $passwordValue = $(this).siblings('.password-value');
            const $icon = $(this).find('i');
            
            if ($passwordValue.is(':visible')) {
                $passwordValue.hide();
                $icon.removeClass('bi-eye-slash').addClass('bi-eye');
            } else {
                $passwordValue.show();
                $icon.removeClass('bi-eye').addClass('bi-eye-slash');
            }
        });
    }
    
    // 新增用戶表單切換功能
    $('#toggleAddForm').on('click', function() {
        const $section = $('#dynamicFormSection');
        const $addButton = $(this);
        const $searchButton = $('#toggleSearchForm');
        
        if ($section.hasClass('show') && $('#addFormContent').is(':visible')) {
            // 隱藏新增表單
            $section.removeClass('show');
            $addButton.removeClass('active');
            $addButton.html('➕ 新增用戶');
            
            // 清空表單
            $('#addForm')[0].reset();
        } else {
            // 先隱藏搜尋功能
            $searchButton.removeClass('active');
            $searchButton.html('🔍 搜尋用戶');
            $('#searchForm')[0].reset();
            fetchUsers(); // 恢復顯示所有用戶
            
            // 顯示新增表單
            $section.addClass('show');
            $addButton.addClass('active');
            $addButton.html('❌ 取消新增');
            
            // 切換表單內容
            $('#searchFormContent').hide();
            $('#addFormContent').show();
            
            // 聚焦到第一個輸入欄位
            setTimeout(() => {
                $('#new_id').focus();
            }, 400);
        }
    });

    // 搜尋用戶表單切換功能
    $('#toggleSearchForm').on('click', function() {
        const $section = $('#dynamicFormSection');
        const $searchButton = $(this);
        const $addButton = $('#toggleAddForm');
        
        if ($section.hasClass('show') && $('#searchFormContent').is(':visible')) {
            // 隱藏搜尋表單
            $section.removeClass('show');
            $searchButton.removeClass('active');
            $searchButton.html('🔍 搜尋用戶');
            
            // 清空搜尋表單並恢復顯示所有用戶
            $('#searchForm')[0].reset();
            fetchUsers();
        } else {
            // 先隱藏新增功能
            $addButton.removeClass('active');
            $addButton.html('➕ 新增用戶');
            $('#addForm')[0].reset();
            
            // 顯示搜尋表單
            $section.addClass('show');
            $searchButton.addClass('active');
            $searchButton.html('❌ 取消搜尋');
            
            // 切換表單內容
            $('#addFormContent').hide();
            $('#searchFormContent').show();
            
            // 聚焦到第一個輸入欄位
            setTimeout(() => {
                $('#search_id').focus();
            }, 400);
        }
    });

    // 執行搜尋功能
    function performSearch() {
        const searchData = {
            action: 'search',
            name: $('#search_name').val().trim(),
            permission: $('#search_permission').val()
        };
        
        // 檢查是否至少有一個搜尋條件
        if (!searchData.name && !searchData.permission) {
            alert('請至少輸入一個搜尋條件');
            return;
        }
        
        // 對姓名進行 Base64 編碼
        if (searchData.name) {
            searchData.name = encodeB64Utf8(searchData.name);
        }
        
        $.post(API_CONFIG.getUrl('manage'), searchData, function(r) {
            if (r.success) {
                currentSearchData = searchData; // 保存搜尋條件
                displayUsers(r.data);
                updateExportButton();
            } else {
                alert(r.message);
            }
        }, 'json').fail(xhr=>handleAjaxError(xhr, '搜尋用戶失敗'));
    }

    // 搜尋按鈕點擊事件
    $('#searchBtn').on('click', performSearch);

    // 支援按 Enter 鍵搜尋
    $('#search_name').on('keypress', function(e) {
        if (e.which === 13) { // Enter 鍵
            performSearch();
        }
    });

    $('#addBtn').on('click',()=>{
     const sid=$('#new_id').val().trim(), nm=$('#new_name').val().trim(), pwd=$('#new_password').val().trim();
     if(!sid||!nm||!pwd)return alert('學號 / 姓名 / 密碼必填');
     
     $.post(API_CONFIG.getUrl('manage'),{
      action:'add',student_id:sid,name:encodeB64Utf8(nm),password:pwd,
      permission:$('#new_permission').val(),gender:$('#new_gender').val()
     }).done(function(r) {
        // 確保收到的是有效的回應
        if (typeof r === 'string') {
            try {
                r = JSON.parse(r);
            } catch (e) {
                console.error('JSON 解析失敗:', e, '原始回應:', r);
                alert('伺服器回應格式錯誤');
                return;
            }
        }
        
        if(r && r.success) {
            fetchUsers();
            $('#addForm')[0].reset();
            // 新增成功後自動隱藏表單
            $('#dynamicFormSection').removeClass('show');
            $('#toggleAddForm').removeClass('active').html('➕ 新增用戶');
            alert('新增用戶成功');
        } else {
            const errorMsg = r ? (r.error || r.message || '新增用戶失敗') : '新增用戶失敗';
            alert(errorMsg);
        }
     }).fail(function(xhr) {
        handleAjaxError(xhr, '新增用戶失敗');
     });
    });
    
    $('#userTable').on('click','.delBtn',function(){
     if(!confirmDelete('確定刪除？'))return;
         $.post(API_CONFIG.getUrl('manage'),{action:'delete',student_id:$(this).data('id')},r=>{
            if (r.success) {
                fetchUsers();
            } else {
                alert(r.message);
            }
         },'json').fail(xhr=>handleAjaxError(xhr, '刪除用戶失敗'));
    });
    
    $('#exportAll').on('click', function() {
        if (currentSearchData) {
            // 匯出搜尋結果
            const params = new URLSearchParams({
                type: 'search_users',
                search_name: currentSearchData.name || '',
                search_permission: currentSearchData.permission || ''
            });
            window.open(API_CONFIG.getUrl('export') + '?' + params.toString(), '_blank');
        } else {
            // 匯出全部
            window.open(API_CONFIG.getUrl('export') + '?type=users', '_blank');
        }
    });
    $('#userTable').on('click','.exportBtn',function(){
     window.open(API_CONFIG.getUrl('export') + '?type=users&student_id='+encodeURIComponent($(this).data('id')),'_blank');
    });
    
    // 新增編輯表單中密碼顯示/隱藏功能
    $(document).on('click', '.modal .toggle-password', function() {
        const $passwordInput = $(this).closest('.input-group').find('input');
        const $icon = $(this).find('i');
        
        if ($passwordInput.attr('type') === 'password') {
            $passwordInput.attr('type', 'text');
            $icon.removeClass('bi-eye').addClass('bi-eye-slash');
        } else {
            $passwordInput.attr('type', 'password');
            $icon.removeClass('bi-eye-slash').addClass('bi-eye');
        }
    });
    
    $('#userTable').on('click','.editBtn',function(){
     $.post(API_CONFIG.getUrl('manage'),{action:'get',student_id:$(this).data('id')},r=>{
      if(!r.success)return alert(r.message);
      const u=r.data;
      Object.entries(u).forEach(([k,v])=>{
        // 密碼欄位不顯示原始密碼，保持空白
        if(k === 'password') {
            $('#edit_password').val('');
            return;
        }
        // 只對有值的 Base64 欄位進行解碼
        if(k==='name' && v) v = decodeB64Utf8(v);
        if(k==='department_other' && v) v = decodeB64Utf8(v);
        if(k==='last_rescue_date' && v) v = v.slice(0,7);
        $('#edit_'+k).val(v || ''); // 確保不會設定 null/undefined
      });
      toggleExtra();
      new bootstrap.Modal('#editModal').show();
     },'json').fail(xhr=>handleAjaxError(xhr, '載入用戶資料失敗'));
    });
    
    $('#editForm').on('submit',e=>{
     e.preventDefault();
     const obj=$(e.target).serializeArray().reduce((o,i)=>(o[i.name]=i.value,o),{});
     
     // 處理月份格式的日期欄位
     if(obj.last_rescue_date && obj.last_rescue_date.match(/^\d{4}-\d{2}$/)){
         obj.last_rescue_date += '-01'; // 轉換 YYYY-MM 為 YYYY-MM-01
     }
     
     // 處理空的日期欄位
     const dateFields = ['birth_date', 'hire_date', 'bls_training_date', 'acls_training_date', 'last_rescue_date', 'case_record_date'];
     dateFields.forEach(field => {
         if(obj[field] === '') obj[field] = null;
     });
     
     obj.action='update';
     obj.student_id = $('#edit_student_id').val(); // 確保有學號
     obj.name=encodeB64Utf8($('#edit_name').val());
     obj.department_other = encodeB64Utf8($('#edit_department_other').val());
         $.post(API_CONFIG.getUrl('manage'),obj,r=>{
            if (r.success) {
                fetchUsers();
                $('#editModal').modal('hide');
            } else {
                alert(r.message);
            }
         },'json').fail(xhr=>handleAjaxError(xhr, '更新用戶失敗'));
    });

    $('#testManage').on('click',()=>{
        $.post(API_CONFIG.getUrl('manage'), {action: 'test'}, r => {
            if (r.success) {
                fetchUsers();
                $('#editModal').modal('hide');
            } else {
                alert(r.message);
            }
        }, 'json').fail(xhr=>handleAjaxError(xhr, '測試功能失敗'));
    });
    

    
    // 先嘗試呼叫需要權限的 API，這樣會觸發權限檢查和日誌記錄
    $.post(API_CONFIG.getUrl('manage'), {action: 'test'}, function(r) {
        if (r.success) {
            // 權限檢查通過，載入用戶資料
            fetchUsers();
        }
    }, 'json').fail(function(xhr) {
        // 權限檢查失敗，重定向到首頁
        console.log('權限檢查失敗:', xhr.responseText);
        location = 'index.html';
    });
    
    });