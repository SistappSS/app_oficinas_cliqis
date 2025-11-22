export function viewBtn() {
    return '<a class="dropdown-item viewbtn" style="color: #717171 !important;"><i class="iconsminds-monitor text-primary"></i> Visualizar registro</a>';
}

export function editBtn() {
    return '<a class="dropdown-item editbtn" style="color: #717171 !important;"><i class="iconsminds-pen-2 fs-5 text-primary"></i> Editar registro </a>';
}

export function deleteBtn() {
    return '<a class="dropdown-item deletebtn" style="color: #717171 !important;"><i class="iconsminds-mail-remove-x text-primary"></i> Excluir registro</a>';
}

export function buyModule(id) {
    return '<a href="/modules/buy-module/'+ id +'" class="dropdown-item" target="_blank" style="color: #717171 !important;"><i class="iconsminds-basket-coins text-primary"></i>Adquirir módulo</a>';
}

export function viewBudget(phone, id) {
    return '<a href="/sales/budget/'+phone+'/'+id+'/show" class="dropdown-item viewbtn" target="_blank" style="color: #717171 !important;"><i class="iconsminds-monitor text-primary"></i> Visualizar orçamento</a>';
}

export function viewContract(id) {
    return '<a href="/sales/contract/'+id+'/show" class="dropdown-item viewbtn" target="_blank" style="color: #717171 !important;"><i class="iconsminds-monitor text-primary"></i> Visualizar contrato</a>';
}

export function divider() {
    return '<div class="dropdown-divider"></div>';
}

export function aproveBudget(id) {
    return `<a href="#" class="dropdown-item approve-btn" data-id="${id}" data-route="${route}" style="color: #717171 !important;">
                <i class="iconsminds-add-bag text-primary"></i> Aprovar orçamento
            </a>`;
}

export function rejectBudget(id) {
    return `<a href="#" class="dropdown-item reject-btn" data-id="${id}" data-route="${route}" style="color: #717171 !important;">
                <i class="iconsminds-remove-bag text-primary"></i> Rejeitar orçamento
            </a>`;
}
