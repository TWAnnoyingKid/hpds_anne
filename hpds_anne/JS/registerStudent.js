$(document).ready(function() {
    $("#registerButton").click(function() {
        const studentId = $("#studentIdInput").val();
        const password = $("#passwordInput").val();
        const name = $("#nameInput").val();
        const permission = $("#permissionSelect").val();
        const gender = $("#genderSelect").val();

        $.post("registerStudent.php", {
            student_id: studentId,
            password: password,
            name: name,
            permission: permission,
            gender: gender
        }, function(response) {
            if (response.error) {
                showError(response.error);
                hideSuccess();
            } else {
                hideError();
                showSuccess(response.success);
            }
        }, "json").fail(xhr=>handleAjaxError(xhr, '註冊失敗'));
    });
});