import {openModalCreate} from "./modal.js";
import {alert, alertError} from "./alert.js";
import {
    selectPartner,
    selectService,
    selectUser,
    selectCategory,
    selectSubCategory,
    selectProduct,
    selectCustomer,
    selectEntities
} from "./select.js";

export function generateCRUD(options) {
    const {
        route,
        inputId,
        modalId,
        formId,
        tableId,
        createbtn,
        viewbtn,
        editbtn,
        deletebtn,
        createTitle,
        viewTitle,
        editTitle,
        rowTable,
        inputsM,
        inputDisabled,
        inputDisabled2,
        userSelect,
        serviceSelect,
        customerSelect,
        partnerSelect,
        entitieSelect,
        categorySelect,
        subCategorySelect,
        productSelect
    } = options;

    function loadTable() {
        $.ajax({
            type: "GET",
            url: route,
            success: function (response) {
                if ($.fn.DataTable.isDataTable(tableId)) {
                    $(tableId).DataTable().clear().destroy();
                }

                $(tableId + " tbody").empty();

                let responseData = response.data;

                if (responseData) {
                    if (!Array.isArray(responseData)) {
                        responseData = Object.values(responseData);
                    }

                    responseData.forEach(function (data) {
                        if (rowTable) {
                            const newRow = rowTable(data);
                            $(tableId + " tbody").append(newRow);
                        }
                    });

                    var table = $(tableId).DataTable({
                        paging: true,
                        language: {
                            info: "Listando _START_ até _END_ de _TOTAL_ registros",
                            sInfoFiltered: "<br> Total de _MAX_ registros cadastrados",
                            sInfoEmpty: "Listando 0 até 0 de 0 registros",
                            sZeroRecords: "Nenhum registro encontrado!",
                            paginate: {
                                next: "<i class='iconsminds-arrow-right'></i>",
                                previous: "<i class='iconsminds-arrow-left'></i>"
                            }
                        },
                        searching: true,
                        ordering: true,
                        info: true,
                        dom: 'Bfrtip',
                        buttons: [
                            { extend: 'copy', text: 'Copy', className: 'd-none' },
                            { extend: 'csv', text: 'CSV', className: 'd-none' },
                            { extend: 'excel', text: 'Excel', className: 'd-none' },
                            { extend: 'pdf', text: 'PDF', className: 'd-none' }
                        ]
                    });

                    $('#dataTablesCopy').on('click', function () {
                        table.button('.buttons-copy').trigger();
                    });
                    $('#dataTablesExcel').on('click', function () {
                        table.button('.buttons-excel').trigger();
                    });
                    $('#dataTablesCsv').on('click', function () {
                        table.button('.buttons-csv').trigger();
                    });
                    $('#dataTablesPdf').on('click', function () {
                        table.button('.buttons-pdf').trigger();
                    });

                    $('#searchDatatable').on('keyup', function () {
                        table.search(this.value).draw();
                    });
                    $('#pageCountDatatable .dropdown-item').on('click', function () {
                        var length = $(this).text();
                        table.page.len(length).draw();
                        $('#pageCountDatatable button').text(length);
                    });
                } else {
                    console.error("A propriedade 'data' não é válida ou está ausente na resposta da API:", response);
                }
            },
            error: function (xhr) {
                console.error(xhr.responseText);
            }
        });
    }

    // Open modal for creating new entry
    function storeModal() {
        if (userSelect) selectUser();
        if (serviceSelect) selectService();
        if (partnerSelect) selectPartner();
        if (categorySelect) selectCategory();
        if (subCategorySelect) selectSubCategory();
        if (productSelect) selectProduct();
        if (entitieSelect) selectEntities();
        if (customerSelect) selectCustomer();

        openModalCreate(inputId, "", createTitle, formId, modalId, inputDisabled, inputDisabled2);
    }

    // Open modal to view entry details
    function viewModal() {

        var $row = $(this).closest("tr");
        var dataId = $row.data("id");

        openModalView(dataId);
    }

    function openModalView(dataId) {
        $.ajax({
            type: "GET",
            url: route + "/" + dataId,
            success: function (response) {
                var data = response.data;

                $(formId)[0].reset();
                $("input[name=inputId]").val(data.id);

                const viewModal = inputsM(data);
                $(formId).append(viewModal);

                $(formId + " input, " + formId + " select, " + formId + " textarea").prop('disabled', true);
                $(modalId + " .modal-title").text(viewTitle);
                $(modalId + " button[type='submit']").hide();

                $(modalId).modal("show");
            },
            error: function (xhr) {
                console.error(xhr.responseText);
            }
        });
    }

    // Open modal for editing entry
    function editModal() {
        var $row = $(this).closest("tr");
        var dataId = $row.data("id");

        if (userSelect) selectUser();
        if (partnerSelect) selectPartner();
        if (categorySelect) selectCategory();
        if (customerSelect) selectCustomer();
        if (entitieSelect) selectEntities();

        openModalEdit(dataId);
    }

    function openModalEdit(dataId) {
        $.ajax({
            type: "GET",
            url: route + "/" + dataId,
            success: function (response) {
                var data = response.data;

                $(formId)[0].reset();
                $(formId + " input, " + formId + " textarea, " + formId + " select").prop('disabled', false);
                $("input[name='" + inputId + "']").val(data.id);

                displayImageFromBase64(data.image);

                const inputModal = inputsM(data);
                $(formId).append(inputModal);

                if (userSelect) selectUser(data.user.id);
                if (productSelect) selectProduct(data.product_id);
                if (subCategorySelect) selectSubCategory(data.sub_category_id);
                if (categorySelect) selectCategory(data.category_id);
                if (entitieSelect) selectEntities(data.customer_id);


                $(modalId + " .modal-title").text(editTitle);
                $(modalId + " button[type='submit']").text("Atualizar").show();

                $(modalId).modal("show");
            },
            error: function (xhr) {
                console.error(xhr.responseText);
            }
        });
    }

    function displayImageFromBase64(base64String) {
        var cameraIcon = document.getElementById('cameraIcon');
        var uploadedImage = document.getElementById('uploadedImage');

        if (base64String) {
            uploadedImage.src = "data:image/jpeg;base64," + base64String;
            cameraIcon.style.display = 'none';
            uploadedImage.style.display = 'block';
        }
    }

    // Handle form submission for create/edit
    $(document).on("submit", formId, function (e) {
        e.preventDefault();

        var formData = new FormData($(this)[0]);
        var dataId = $("input[name='" + inputId + "']").val();

        var ajaxConfig = {
            type: "POST",
            url: dataId ? route + "/" + dataId : route,
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                $(modalId).modal('hide');
                alert(dataId ? "info" : "success", response.message);
                loadTable();
                if (userSelect) selectUser();
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    alertError(xhr.responseJSON.errors);
                } else {
                    alert("error", "Ocorreu um erro na operação!");
                }
            }
        };

        if (dataId > 0) {
            ajaxConfig.headers = {'X-HTTP-Method-Override': 'PUT'};
        }

        $.ajax(ajaxConfig);
    });

    // Handle data deletion
    function deleteData() {
        var dataId = $(this).closest("tr").data("id");
        var token = $('meta[name="csrf-token"]').attr('content');
        var $this = $(this);

        $('#confirmationModal').modal('show');

        $('#confirmDelete').on('click', function () {
            $('#confirmationModal').modal('hide');

            $.ajax({
                type: "DELETE",
                url: route + "/" + dataId,
                headers: {'X-CSRF-TOKEN': token},
                success: function (response) {
                    $this.closest("tr").remove();
                    alert("alert", response.message);
                    loadTable();
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
    }

    $(document).ready(function () {
        loadTable();

        $(document).on("click", createbtn, storeModal);
        $(tableId).on("click", viewbtn, viewModal);
        $(tableId).on("click", editbtn, editModal);
        $(tableId).on("click", deletebtn, deleteData);

        $(tableId).on('click', '.approve-btn', function (e) {
            e.preventDefault();
            var id = $(this).data('id');
            var route = $(this).data('route');
            approveBudgetAjax(id, route);
        });

        function approveBudgetAjax(id, route) {
            $.ajax({
                url: '/sales/budget/status/' + id + '/aprove',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    alert('success', 'Orçamento aprovado com sucesso!');
                    loadTable();
                },
                error: function (xhr, status, error) {
                    alert('error', 'Erro ao aprovar orçamento: ' + error);
                }
            });
        }

        $(tableId).on('click', '.reject-btn', function (e) {
            e.preventDefault();
            var id = $(this).data('id');
            var route = $(this).data('route');
            rejectBudgetAjax(id, route);
        });

        function rejectBudgetAjax(id, route) {
            $('#confirmationRejectModal').modal('show');

            $('#confirmReject').on('click', function () {
                $('#confirmationRejectModal').modal('hide');
                $.ajax({
                    url: '/sales/budget/status/' + id + '/reject',
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        alert('alert', 'Orçamento rejeitado com sucesso!');
                        loadTable();
                    },
                    error: function (xhr, status, error) {
                        alert('error', 'Erro ao rejeitar orçamento: ' + error);
                    }
                });
            });
        }
    });
}
