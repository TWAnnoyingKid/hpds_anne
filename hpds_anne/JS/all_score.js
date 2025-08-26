// 渲染表格
  function renderAll(rows){
    const tbody = $('#scoreTable tbody').empty();
    if(rows.length) $('#exportAllBtn').show();
    else $('#exportAllBtn').hide();

    rows.forEach(r=>{
      let qHtml='';
      for(let i=1; i<=16; i++){
            qHtml += `<td class="${r['q'+i]? 'text-success':'text-danger'}">
              ${r['q'+i]? '&#10004;':'&#10008;'}
            </td>`;
          }
      const name = r.name ? decodeB64Utf8(r.name) : '';
      tbody.append(`
        <tr>
          <td>${escapeHtml(r.student_id)}</td>
          <td>${escapeHtml(name)}</td>
          <td>${escapeHtml(r.score)}</td>
          <td>${escapeHtml(r.incorrect_questions||'')}</td>
          <td>${escapeHtml(r.answer_time)}</td>
          ${qHtml}
          <td>${escapeHtml(r.answer_date)}</td>
          <td><button class="btn btn-sm btn-danger delBtn">刪除</button></td>
        </tr>
      `).find('tr:last .delBtn').click(function(){
        if(!confirmDelete('確定刪除？'))return;
        $.post(API_CONFIG.getUrl('deleteScores'),{
          student_id: r.student_id,
          answer_date: r.answer_date
        }, dres=>{
          if(dres.success) fetchAll();
          else showError(dres.error||'刪除失敗');
        },'json');
      });
    });
  }

  // 取得所有成績
  function fetchAll(){
    hideError();
    const permission = $('#searchPermission').val();
    $.post(API_CONFIG.getUrl('allScore'),{ 
      sortMode: SortConfig.mode,
      permission: permission 
    }, res=>{
      if(res.error) showError(res.error);
      else renderAll(res.data);
    }, 'json').fail(xhr=>handleAjaxError(xhr, '取得成績失敗'));
  }

  $(function(){
    // 權限驗證 - 直接呼叫需要權限的 API 來觸發權限檢查和日誌記錄
    $.post(API_CONFIG.getUrl('allScore'),{}, res=>{
      if(res.error) {
        console.log('權限檢查失敗:', res.error);
        location='index.html';
      } else {
        // 權限檢查通過
        $('#toggleSort').text(SortConfig.getCurrentLabel());
        fetchAll();
      }
    }, 'json').fail(function(xhr) {
      console.log('API 呼叫失敗:', xhr.responseText);
      location='index.html';
    });
    
    // 搜尋功能
    $('#searchBtn').click(fetchAll);
    $('#clearSearchBtn').click(function(){
      $('#searchPermission').val('');
      fetchAll();
    });
    
    // 支援 Enter 鍵搜尋
    $('#searchPermission').on('keypress', function(e) {
      if (e.which === 13) { // Enter 鍵
        fetchAll();
      }
    });
    
    $('#refreshBtn').click(fetchAll);
    $('#toggleSort').click(()=>{
      SortConfig.toggleMode();
      $('#toggleSort').text(SortConfig.getCurrentLabel());
      fetchAll();
    });
    $('#exportAllBtn').click(()=>{
      const permission = $('#searchPermission').val();
      const params = new URLSearchParams({
        type: 'all_scores',
        sortMode: SortConfig.mode
      });
      if (permission) {
        params.append('permission', permission);
      }
      window.open(`../PHP/export.php?` + params.toString(), '_blank');
    });
  });