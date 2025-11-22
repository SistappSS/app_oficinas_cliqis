import {alert, alertError} from "./alert.js";

$(document).ready(function() {
    $('#postalCode').on('blur', function() {
        var cep = $(this).val().replace(/\D/g, '');

        if (cep.length === 8) {
            $.ajax({
                url: `https://viacep.com.br/ws/${cep}/json/`,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (!("erro" in data)) {
                        $('#address').val(data.logradouro).prop('disabled', false);
                        $('#province').val(data.bairro).prop('disabled', false);
                        $('#cityName').val(data.localidade).prop('disabled', false);
                        $('#state').val(data.uf).prop('disabled', false);
                    } else {
                        alert("error", "Erro ao realizar a busca por esse CEP.");
                    }
                },
                error: function() {
                    alert("error", "Erro ao realizar a busca por esse CEP.");
                }
            });
        } else {
            alert("error", "Por favor, insira um CEP válido.");
        }
    });
});
