// 使用共用的 SortConfig 和 common.js 函數

  function fetchGrades(){
    hideError();
    $('#exportBtn').hide();
    const sid = $('#studentIdInput').val().trim();
    if(!sid){ showError('請輸入學號'); return; }
    $.post(API_CONFIG.getUrl('search'),{
      student_id: sid,
      sortMode: SortConfig.mode
    }, res=>{
      if(res.error){
        showError(res.error);
      } else {
        const tbody = $('#resultTable tbody').empty();
        if(res.data.length>0){
          $('#exportBtn').show();
        }
        res.data.forEach(r=>{
          const name = r.name ? decodeB64Utf8(r.name) : '';
          let qHtml = '';
          for(let i=1; i<=16; i++){
            qHtml += `<td class="${r['q'+i]? 'text-success':'text-danger'}">
              ${r['q'+i]? '&#10004;':'&#10008;'}
            </td>`;
          }
          tbody.append(`
            <tr>
              <td>${escapeHtml(r.student_id)}</td>
              <td>${escapeHtml(name)}</td>
              <td>${escapeHtml(r.score)}</td>
              <td>${escapeHtml(r.incorrect_questions||'')}</td>
              <td>${escapeHtml(r.answer_time)}</td>
              ${qHtml}
              <td>${escapeHtml(r.answer_date)}</td>
              <td>
                <button class="btn btn-sm btn-danger delBtn">刪除</button>
              </td>
            </tr>
          `).find('tr:last .delBtn').click(function(){
            if(!confirmDelete('確定刪除這筆成績？')) return;
            $.post(API_CONFIG.getUrl('deleteScores'),{
              student_id: r.student_id,
              answer_date: r.answer_date
            }, dres=>{
              if(dres.success) fetchGrades();
              else showError(dres.error||'刪除失敗');
            }, 'json');
          });
        });
      }
    }, 'json')
    .fail(xhr=>handleAjaxError(xhr, '查詢成績失敗'));
  }

  $(function(){
    // 驗證權限 - 直接呼叫需要權限的 API 來觸發權限檢查和日誌記錄
    $.post(API_CONFIG.getUrl('search'),{}, res=>{
      if(res.error) {
        console.log('權限檢查失敗:', res.error);
        location='index.html';
      } else {
        // 權限檢查通過
        checkSystemAccess();
      }
    }, 'json').fail(function(xhr) {
      console.log('API 呼叫失敗:', xhr.responseText);
      location='index.html';
    });
    
    // 使用 common.js 中的統一函數

    $('#searchBtn').click(fetchGrades);
    $('#toggleSort').click(()=>{
      SortConfig.toggleMode();
      $('#toggleSort').text(SortConfig.getCurrentLabel());
      fetchGrades();
    });
    $('#exportBtn').click(()=>{
      const sid = encodeURIComponent($('#studentIdInput').val().trim());
      window.open(`../PHP/export.php?type=search_scores&student_id=${sid}&sortMode=${SortConfig.mode}`, '_blank');
    });
  });