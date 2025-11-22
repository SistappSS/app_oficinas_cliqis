import { alert, alertError } from "./alert.js";

document.querySelectorAll('.deletebtn').forEach(button => {
    button.addEventListener('click', function () {

        let taskId = this.dataset.taskId;
        var token = $('meta[name="csrf-token"]').attr('content');
        let clickedButton = this;

        $.ajax({
            type: "DELETE",
            url: route + "/" + taskId,
            headers: {'X-CSRF-TOKEN': token},
            success: function (response) {
                console.log(response);

                clickedButton.closest("li[data-task-id='" + taskId + "']").remove();

                alert("alert", response.message);
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    alertError(xhr.responseJSON.errors);
                } else {
                    alert("error", "Ocorreu um erro na operação!");
                }
            }
        });
    });
});
