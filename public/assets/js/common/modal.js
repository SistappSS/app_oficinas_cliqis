export function openModalCreate(input, value = null, text, formId, modalId, inputDisabled = null, inputDisabled2 = null) {
    $(formId)[0].reset();

    $(formId + " input").prop('disabled', false);

    if(inputDisabled) {
        $("input[name=" + inputDisabled + "]").prop('disabled', true);
    }
    if(inputDisabled2) {
        $("input[name=" + inputDisabled2 + "]").prop('disabled', true);
    }

    $("input[name=" + input + "]").val(value);

    $(modalId + " .modal-title").text("Cadastro " + text);
    $(modalId + " button[type='submit']").text("Cadastrar").show();

    $(modalId).modal("show");
}
