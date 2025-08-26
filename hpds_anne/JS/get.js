$(document).ready(function() {
    // 查詢按鈕功能
    $("#enterScoreButton").click(fetchData);

    // 刪除按鈕功能 (多字段組合刪除)
    $("#deleteSelected").click(function() {
        const selectedRecords = [];
        
        // 收集所有被勾選的記錄數據
        $("input[type='checkbox']:checked").each(function() {
            const recordData = $(this).data('record');
            selectedRecords.push({
                student_id: recordData.student_id,
                answer_date: recordData.answer_date,
                answer_time: recordData.answer_time,
                score: recordData.score
            });
        });

        if (selectedRecords.length === 0) {
            alert("請先選擇要刪除的記錄");
            return;
        }

        if (confirm("確定要刪除選中的 " + selectedRecords.length + " 筆記錄嗎？")) {
            $.post(API_CONFIG.getUrl('deleteScores'), {
                records: JSON.stringify(selectedRecords),  // 轉換為JSON格式
                password: $("#passwordInput").val()       // 傳送管理密碼
            }, function(response) {
                if (response.success) {
                    alert("已刪除 " + response.deleted + " 筆記錄");
                    fetchData();  // 刷新數據
                } else {
                    alert("刪除失敗: " + (response.error || '未知錯誤'));
                }
            }, "json").fail(xhr=>handleAjaxError(xhr, '刪除操作失敗'));
        }
    });
});

// 數據獲取與渲染函數
function fetchData() {
    const studentId = $("#studentIdInput").val();
    const password = $("#passwordInput").val();

    $.post(API_CONFIG.getUrl('get'), { 
        student_id: studentId,
        password: password
    }, function(response) {
        if (response.error) {
            $("#errorMessage").text(response.error).show();
            $("#studentData").hide();
        } else {
            $("#errorMessage").hide();
            const tbody = $("#studentData tbody");
            tbody.empty();

            // 動態生成表格內容
            $.each(response.data, function(i, item) {
                // 使用共用的 HTML 轉義函數
                tbody.append(`
                    <tr>
                        <td>${escapeHtml(item.student_id)}</td>
                        <td>${escapeHtml(item.name)}</td>
                        <td>${escapeHtml(item.gender)}</td>
                        <td>${escapeHtml(item.score)}</td>
                        <td>${escapeHtml(item.incorrect_questions)}</td>
                        <td>${escapeHtml(item.answer_time)}</td>
                        <td>${escapeHtml(item.answer_date)}</td>
                    </tr>
                `);
            });

            $("#studentData").show();
        }
    }, "json").fail(xhr=>handleAjaxError(xhr, '數據獲取失敗'));
}