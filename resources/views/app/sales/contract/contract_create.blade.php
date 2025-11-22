<x-app-layout>
    @section('content')
        <form class="" method="post" id="contract_form">
            @csrf

            <div class="row">
                <div class="col-12">
                    <x-breadcrumb title="Novo Contrato" current="Vendas | Contrato | Novo Contrato"></x-breadcrumb>

                    <div class="top-right-button-container">
                        <a href="{{route('contract.index')}}" type="button" class="btn btn-outline-primary">VOLTAR</a>
                        <button type="submit" name="save" id="save" class="btn btn-primary">
                            <span class="mx-2">SALVAR</span>
                            <i class='iconsminds-disk' style="font-size: 16px;"></i>
                        </button>
                    </div>

                    <div class="row">
                        <x-input col="4" set="" type="text" name="name" id="name" label="Nome do contrato"
                                 placeholder="Contrato de fidelidade ..."></x-input>
                    </div>

                    <div class="row">
                        <div class="form-group mb-3 mx-3">
                            <label>Data de validade</label>
                            <div class="input-daterange input-group" id="datepicker">
                                <input type="text" class="input-sm form-control" name="start" placeholder="Ínicio" />
                                <span class="input-group-addon"></span>
                                <input type="text" class="input-sm form-control" name="end" placeholder="Fim" />
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <x-select col="4" set="" id="customer_id" name="customer_id" label="Selecionar cliente" mult=""></x-select>
                    </div>

                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="html-editor-bubble" id="contract"></div>
                            <input type="hidden" name="contract_content" id="contract_content">
                        </div>
                    </div>
                </div>
            </div>
        </form>
    @endsection
</x-app-layout>

<style>
    .data-table-rows table td, .data-table-rows table th, table td, table th {
        padding: 0 !important;
    }

    .data-table-rows table tbody tr, table tbody tr {
        box-shadow: none !important;
    }
</style>

<script src="{{asset('assets/js/vendor/quill.min.js')}}"></script>

<script>
    $(document).ready(function () {
        $('#datepicker').datepicker({
            format: 'd M yyyy',
            language: 'pt',
            autoclose: true,
            todayHighlight: true
        });

        function fetchCustomers() {
            $.ajax({
                type: "GET",
                url: "/entities/customer-api",
                success: function (response) {
                    var select = $("#customer_id");
                    select.empty();

                    if (Array.isArray(response.data)) {
                        select.append('<option value="" selected disabled>Clientes cadastrados</option>');

                        response.data.forEach(function (data) {
                            var option = $('<option></option>').attr('value', data.id).text(data.name);
                            select.append(option);
                        });

                        if (select.data('choices')) {
                            select.data('choices').destroy();
                        }

                        const choices = new Choices(select[0], {
                            removeItemButton: true,
                            searchEnabled: true,
                            shouldSort: false,
                            itemSelectText: 'Enter para selecionar'
                        });

                        select.data('choices', choices);
                    } else {
                        console.error("A propriedade 'data' não é um array na resposta da API:", response);
                    }
                },
                error: function (xhr) {
                    console.error(xhr.responseText);
                }
            });
        }

        fetchCustomers();

        var quill = new Quill('#contract', {
            theme: 'bubble',
            modules: {
                toolbar: [
                    [{header: [1, 2, false]}],
                    ['bold', 'italic', 'underline'],
                    ['blockquote', 'code-block'],
                    [{list: 'ordered'}, {list: 'bullet'}],
                    ['clean']
                ]
            }
        });

        $('#contract_form').on('submit', function (event) {
            event.preventDefault();

            // Acesso direto ao HTML do editor
            var myEditor = document.querySelector('#contract');
            var htmlContent = myEditor.children[0].innerHTML;

            console.log('Conteúdo HTML:', htmlContent); // Verificar o conteúdo no console
            $('#contract_content').val(htmlContent); // Definir no campo oculto

            $.ajax({
                url: '/sales/contract-api',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                beforeSend: function () {
                    $('#save').attr('disabled', 'disabled');
                },
                success: function (data) {
                    $('#save').attr('disabled', false);
                    if (data.error) {
                        let error_html = data.error.map(err => `<p>${err}</p>`).join('');
                        $('#result').html('<div class="alert alert-danger">' + error_html + '</div>');
                    } else {
                        window.location = '/sales/contract';
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    $('#save').attr('disabled', false);
                    $('#result').html('<div class="alert alert-danger">Erro ao enviar os dados. Por favor, tente novamente.</div>');
                }
            });
        });


    });
</script>
