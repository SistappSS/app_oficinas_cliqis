export function selectUser(partnerId = null) {
    var partnerIds = [];

    $.ajax({
        type: "GET",
        url: "/entities/partner-api",
        success: function (response) {
            if (Array.isArray(response.data)) {
                response.data.forEach(function (item) {
                    if (item.partner && item.partner.id) {
                        partnerIds.push(item.partner.id);
                    }
                });
            } else {
                console.error("A propriedade 'data' não é um array na resposta da API:", response);
            }

            fetchPartners(partnerId, partnerIds);
        },
        error: function (xhr) {
            console.error(xhr.responseText);
        },
        async: false
    });
}

function fetchPartners(partnerId, partnerIds) {
    $.ajax({
        type: "GET",
        url: "/entities/user-api",
        success: function (response) {
            var select = $("#user_id");

            select.empty();

            if (Array.isArray(response.data)) {
                select.append('<optgroup label="Usuários cadastrados"></optgroup>');
                response.data.forEach(function (user) {
                    if (!partnerIds.includes(user.id)) {
                        var option = $('<option></option>').attr('value', user.id).text(user.name);
                        select.append(option);
                    }
                });

                if (partnerId) {
                    select.val(partnerId).trigger('change');
                }

                select.select2({
                    width: '100%',
                    theme: 'bootstrap'
                });
            } else {
                console.error("A propriedade 'data' não é um array na resposta da API:", response);
            }
        },
        error: function (xhr) {
            console.error(xhr.responseText);
        }
    });
}

export function selectService() {
    var selectedItems = [];
    var discount = 0;
    var response;

    $.ajax({
        type: "GET",
        url: "/service-api",
        success: function (responseData) {
            response = responseData;
            var select = $("#service_id");

            select.empty();

            if (Array.isArray(response.data)) {
                response.data.forEach(function (item) {
                    var option = $('<option></option>').attr('value', item.id).text(item.name);
                    select.append(option);
                });

                new MultiSelectTag('service_id', {
                    rounded: true,
                    placeholder: 'Selecionar serviço',
                    tagColor: {
                        textColor: '#327b2c',
                        borderColor: '#92e681',
                        bgColor: '#eaffe6',
                    },
                    onChange: function (values) {
                        selectedItems = values;
                        updatePrice();
                    }
                });
            } else {
                console.error("A propriedade 'data' não é um array na resposta da API:", response);
            }
        },
        error: function (xhr) {
            console.error(xhr.responseText);
        }
    });

    function updatePrice() {
        if (!response || !Array.isArray(response.data)) {
            console.error("Resposta inválida da API:", response);
            return;
        }

        var subtotal = 0;
        selectedItems.forEach(function (selectedItem) {
            var itemId = selectedItem.value;

            if (typeof itemId !== 'number' && typeof itemId !== 'string') {
                console.error("ItemId inválido:", itemId);
                return;
            }

            var foundItem = response.data.find(item => item.id == itemId);
            if (foundItem) {
                subtotal += parseFloat(foundItem.price);
            } else {
                console.error("Item com id " + itemId + " não encontrado na resposta da API.");
            }
        });

        var discountValue = parseFloat($("#discount_price").val()) || 0;
        var total = subtotal - (subtotal * discountValue / 100);

        $("#subtotal_price").val(subtotal.toFixed(2));
        $("#total_price").val(total.toFixed(2));
    }

    $("#discount_price").on('input', updatePrice);
}

export function selectPartner() {
    $.ajax({
        type: "GET",
        url: "/entities/partner-api",
        success: function (response) {
            var select = $("#partner_id");

            select.empty();

            if (Array.isArray(response.data)) {
                select.append('<option selected disabled>Seleciona representante</option>');
                response.data.forEach(function (data) {
                    var option = $('<option></option>').attr('value', data.id).text(data.user.name);
                    select.append(option);
                });
            } else {
                console.error("A propriedade 'data' não é um array na resposta da API:", response);
            }
        },
        error: function (xhr) {
            console.error(xhr.responseText);
        }
    });
}

export function selectCategory(selectedId = null) {
    $.ajax({
        type: "GET",
        url: "/inventories/products/category-api",
        success: function (response) {
            var select = $("#category_id");

            select.empty();

            if (Array.isArray(response.data)) {
                select.append('<option value="">Selecione uma categoria</option>');

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

                if (selectedId) {
                    choices.setChoiceByValue(selectedId);
                }

            } else {
                console.error("A propriedade 'data' não é um array na resposta da API:", response);
            }
        },
        error: function (xhr) {
            console.error(xhr.responseText);
        }
    });
}

export function selectProduct(selectedId = null) {
    $.ajax({
        type: "GET",
        url: "/inventories/products/product-api",
        success: function (response) {
            var select = $("#product_id");

            select.empty();

            if (Array.isArray(response.data)) {
                response.data.forEach(function (data) {
                    var option = $('<option></option>').attr('value', data.id).text(data.name);
                    select.append(option);
                });

                if (selectedId) {
                    select.val(selectedId).trigger('change');
                }

                select.select2({
                    width: '100%',
                    theme: 'bootstrap'
                });
            } else {
                console.error("A propriedade 'data' não é um array na resposta da API:", response);
            }
        },
        error: function (xhr) {
            console.error(xhr.responseText);
        }
    });
}

export function selectSubCategory(selectedId = null) {
    $.ajax({
        type: "GET",
        url: "/inventories/products/sub-category-api",
        success: function (response) {
            var select = $("#sub_category_id");

            select.empty();

            if (Array.isArray(response.data)) {
                response.data.forEach(function (data) {
                    var option = $('<option></option>').attr('value', data.id).text(data.name);
                    select.append(option);
                });

                if (selectedId) {
                    select.val(selectedId).trigger('change');
                }

                select.select2({
                    width: '100%',
                    theme: 'bootstrap'
                });
            } else {
                console.error("A propriedade 'data' não é um array na resposta da API:", response);
            }
        },
        error: function (xhr) {
            console.error(xhr.responseText);
        }
    });
}

export function selectCustomer() {
    $.ajax({
        type: "GET",
        url: "/entities/customer-api",
        success: function (response) {
            var select = $("#customer_id");

            select.empty();

            if (Array.isArray(response.data)) {
                response.data.forEach(function (data) {
                    var option = $('<option></option>').attr('value', data.id).text(data.name);
                    select.append(option);
                });
            } else {
                console.error("A propriedade 'data' não é um array na resposta da API:", response);
            }
        },
        error: function (xhr) {
            console.error(xhr.responseText);
        }
    });
}

export function selectEntities() {
    let select = $("#customer_id");
    select.empty();

    function fetchData(url, groupLabel) {
        return $.ajax({
            type: "GET",
            url: url,
            success: function (response) {
                if (Array.isArray(response.data)) {
                    // Cria um grupo de opções para organizar os itens
                    let optgroup = $('<optgroup></optgroup>').attr('label', groupLabel);

                    response.data.forEach(function (data) {
                        let option = $('<option></option>').attr('value', data.id).text(data.name);
                        optgroup.append(option);
                    });

                    select.append(optgroup);
                } else {
                    console.error(`A propriedade 'data' não é um array na resposta da API (${url}):`, response);
                }
            },
            error: function (xhr) {
                console.error(`Erro ao buscar dados de ${url}:`, xhr.responseText);
            }
        });
    }

    $.when(
        fetchData("/entities/customer-api", "Clientes"),
        fetchData("/entities/user-api", "Usuários")
    ).done(function () {
        console.log("Todos os dados carregados com sucesso!");
    });
}

export function selectBranch() {
    $.ajax({
        type: "GET",
        url: "/branch-api",
        success: function (response) {
            var select = $("#branch_id");

            select.empty();

            if (Array.isArray(response.data)) {
                select.append('<option selected disabled>Selecionar filial</option>');
                response.data.forEach(function (data) {
                    var option = $('<option></option>').attr('value', data.id).text(data.name);
                    select.append(option);
                });
            } else {
                console.error("A propriedade 'data' não é um array na resposta da API:", response);
            }
        },
        error: function (xhr) {
            console.error(xhr.responseText);
        }
    });
}
