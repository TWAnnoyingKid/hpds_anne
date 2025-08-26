// 友好的錯誤訊息映射
  function getFriendlyErrorMessage(xhr, defaultMsg = '未知錯誤') {
    try {
      // 嘗試解析回應
      const response = JSON.parse(xhr.responseText || '{}');
      const errorMsg = response.error || '';
      
      // 錯誤訊息映射
      const errorMap = {
        '請先登入': '請先登入',
        '無此學號': '帳號或密碼錯誤',
        '密碼錯誤': '帳號或密碼錯誤',
        '學號已存在': '學號已存在',
        '您已自動登出': '登入已過期，請重新登入',
        '您沒有此權限': '權限不足',
        '網路連線中斷': '網路錯誤',
        '資料庫連接失敗': '系統暫時無法使用',
        '缺少必要參數': '請填寫完整資訊',
        '登入已過期，請重新登入': '登入已過期，請重新登入',
        'Session逾時': '登入已過期，請重新登入'
      };
      
      // 根據狀態碼處理
      if (xhr.status === 0) {
        return '網路錯誤，請檢查網路連線';
      } else if (xhr.status === 404) {
        return '服務不可用，請稍後再試';
      } else if (xhr.status === 500) {
        return '伺服器錯誤，請稍後再試';
      } else if (xhr.status === 403) {
        return '權限不足';
      } else if (xhr.status >= 400 && xhr.status < 500) {
        return errorMap[errorMsg] || '帳號或密碼錯誤';
      }
      
      return errorMap[errorMsg] || defaultMsg;
    } catch (e) {
      // 如果無法解析回應，根據狀態碼給出友好提示
      if (xhr.status === 0) {
        return '網路錯誤，請檢查網路連線';
      } else if (xhr.status >= 500) {
        return '伺服器錯誤，請稍後再試';
      } else if (xhr.status >= 400) {
        return '帳號或密碼錯誤';
      }
      return defaultMsg;
    }
  }

  // 顯示右上角 Toast 提示
  function showToast(message, type = 'error') {
    // 防止重複顯示相同訊息
    const existingToasts = document.querySelectorAll('.toast .toast-body');
    for (let toast of existingToasts) {
      if (toast.textContent.includes(message)) {
        return; // 如果已經有相同的訊息在顯示，就不再顯示
      }
    }
    
    const bgClass = type === 'error' ? 'bg-danger' : 'bg-success';
    const icon = type === 'error' ? '⚠️' : '✅';
    
    const toastHtml = `
      <div class="toast align-items-center text-white ${bgClass} border-0" role="alert">
        <div class="d-flex">
          <div class="toast-body">
            ${icon} ${message}
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>
    `;
    
    // 創建 toast 容器（如果不存在）
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
      toastContainer = document.createElement('div');
      toastContainer.id = 'toastContainer';
      toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
      toastContainer.style.zIndex = '9999';
      document.body.appendChild(toastContainer);
    }
    
    // 添加 toast
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    // 顯示 toast
    const toastElement = toastContainer.lastElementChild;
    const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
    toast.show();
    
    // 自動移除 toast
    toastElement.addEventListener('hidden.bs.toast', () => {
      toastElement.remove();
    });
  }

  function handleLoginResponse(res){
    if(res.error){
      // 使用友好的錯誤訊息
      const friendlyMsg = getFriendlyErrorMessage({ responseText: JSON.stringify(res) }, res.error);
      showToast(friendlyMsg, 'error');
    } else {
      const perm = res.permission;
      // 學生、護理師、專科護理師 - 跳轉到成績頁面
      if(perm==='student' || perm==='nurse' || perm==='snurse'){
        console.log('一般用戶登入，跳轉到成績頁面');
        // 儲存用戶ID以供後續使用
        localStorage.setItem('currentUserId', $('#studentIdInput').val());
        location.href = 'score.html';
      }
      // 教師/助教 - 跳轉到管理頁面
      else {
        console.log('教師/助教登入，跳轉到管理頁面');
        location.href = 'manage.html';
      }
    }
  }

  $('#loginBtn').click(doLogin);
    // 登出按鈕在 common.js 中處理
    $('#refreshBtn').click(doLogin);

  function doLogin(){
    const studentId = $('#studentIdInput').val();
    const password = $('#passwordInput').val();
    
    if (!studentId || !password) {
      showToast('請輸入帳號和密碼', 'error');
      return;
    }
    
    $.ajax({
      url: API_CONFIG.getUrl('getData'),
      method: 'POST',
      data: {
        student_id: studentId,
        password: password
      },
      dataType: 'json',
      success: function(res) {
        handleLoginResponse(res);
      },
      error: function(xhr) {
        // 嘗試解析錯誤回應中的 JSON
        try {
          const errorResponse = JSON.parse(xhr.responseText);
          if (errorResponse.error) {
            // 如果有結構化的錯誤回應，使用 handleLoginResponse 處理
            handleLoginResponse(errorResponse);
          } else {
            // 純粹的網路或伺服器錯誤
            const friendlyMsg = getFriendlyErrorMessage(xhr, '登入連線失敗');
            showToast(friendlyMsg, 'error');
          }
        } catch (e) {
          // 無法解析 JSON，純粹的網路錯誤
          const friendlyMsg = getFriendlyErrorMessage(xhr, '登入連線失敗');
          showToast(friendlyMsg, 'error');
        }
      }
    });
  }

  $(function(){
    console.log('頁面載入完成，初始化中...');
    
    // 頁面載入時，自動檢查 session
    console.log('檢查現有 session...');
    $.ajax({
      url: API_CONFIG.getUrl('getData'),
      method: 'POST',
      data: {},
      dataType: 'json',
      success: function(res) {
        handleLoginResponse(res);
      },
      error: function(xhr) {
        console.error('檢查 session 失敗:', { status: xhr.status, error: xhr.statusText, response: xhr.responseText });
        // 對於 session 檢查，400 錯誤是正常的（未登入），不顯示錯誤提示
        if (xhr.status !== 400) {
          try {
            const errorResponse = JSON.parse(xhr.responseText);
            if (errorResponse.error && !errorResponse.auto_logout) {
              // 如果不是自動登出，則顯示錯誤
              const friendlyMsg = getFriendlyErrorMessage(xhr, '系統檢查失敗');
              showToast(friendlyMsg, 'error');
            }
          } catch (e) {
            const friendlyMsg = getFriendlyErrorMessage(xhr, '系統檢查失敗');
            showToast(friendlyMsg, 'error');
          }
        }
      }
    });

    $('#loginBtn').click(doLogin);
    // 登出按鈕在 common.js 中處理
    $('#refreshBtn').click(doLogin);
    
    // 支援 Enter 鍵登入
    $('#studentIdInput, #passwordInput').keypress(function(e) {
      if (e.which === 13) { // Enter 鍵
        doLogin();
      }
    });
    
    // 使用共用的表單變更事件綁定
    //bindFormChangeEvents();
    
    // 測試按鈕
    $('#testBtn').click(function(){
      console.log('執行資料庫測試...');
      $.post(API_CONFIG.getUrl('getData'), {action: 'test'}, function(response){
        console.log('測試結果:', response);
        alert('測試結果: ' + JSON.stringify(response));
      }, 'json').fail(function(xhr){
        console.error('測試失敗:', { status: xhr.status, response: xhr.responseText });
        alert('錯誤: ' + xhr.status + ' - ' + xhr.responseText);
      });
    });
    
    console.log('事件綁定完成');
  });