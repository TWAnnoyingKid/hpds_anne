$(document).ready(function() {
    $('#studentDataForm').submit(function(e) {
        e.preventDefault();

        // 使用共用的時間格式轉換函數
        let answerTime = formatTime($('#answer_time').val());
        $('#answer_time').val(answerTime);

        // 提交表單
        $.ajax({
            url: API_CONFIG.getUrl('enterScore'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.error) {
                    showError(response.error);
                    hideSuccess();
                } else if(response.success) {
                    showSuccess(response.success);
                    hideError();
                }
            },
            error: function(xhr) {
                handleAjaxError(xhr, '提交成績時發生錯誤');
                hideSuccess();
            }
        });
    });
});