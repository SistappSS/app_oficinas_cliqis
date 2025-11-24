import { ModelCrud } from "../../partials/modelCrud.js";

async function loadSelectOptions() {
    await Promise.all([
        loadEmployees(),
        loadBenefits(),
    ]);
}

async function loadEmployees() {
    const sel = document.querySelector("#employee_id");
    if (!sel) return;

    try {
        const res = await fetch("/human-resources/employee-api");
        if (!res.ok) throw new Error("Erro ao buscar funcionários");

        const json = await res.json();
        const list = json.data || json || [];

        sel.innerHTML = '<option value="">Selecione...</option>' +
            list
                .map(e => `<option value="${e.id}">${e.full_name || e.name || "(sem nome)"}</option>`)
                .join("");
    } catch (e) {
        console.error(e);
        sel.innerHTML = '<option value="">Erro ao carregar funcionários</option>';
    }
}

async function loadBenefits() {
    const sel = document.querySelector("#benefit_id");
    if (!sel) return;

    try {
        const res = await fetch("/human-resources/benefit-api");
        if (!res.ok) throw new Error("Erro ao buscar benefícios");

        const json = await res.json();
        const list = json.data || json || [];

        sel.innerHTML = '<option value="">Selecione...</option>' +
            list
                .map(b => `<option value="${b.id}">${b.name || "(sem nome)"}</option>`)
                .join("");
    } catch (e) {
        console.error(e);
        sel.innerHTML = '<option value="">Erro ao carregar benefícios</option>';
    }
}

document.addEventListener("DOMContentLoaded", async () => {
    // carrega selects (funcionário e benefício) logo no início
    await loadSelectOptions();

    new ModelCrud({
        name: "beneficios-funcionarios",
        label: "benefício de funcionário",
        tbody: "#tbody",
        modal: "#employee-benefit-modal",
        form: "#employee-benefit-form",
        btnAdd: "#btn-add",
        search: "#search",
        modalTitle: "#m-title",
        btnClose: "#m-close",
        btnCancel: "#m-cancel",
        btnDelete: "#btn-delete",
        btnSubmit: "#m-submit",
        routes: {
            index: "/human-resources/employee-benefit-api",
            store: "/human-resources/employee-benefit-api",
            update: "/human-resources/employee-benefit-api",
            delete: "/human-resources/employee-benefit-api",
        },
        searchKeys: ["notes"],
        normalize: (json) => json.data || [],

        renderRow: (r) => `
<tr class="hover:bg-slate-50">
    <td class="px-3 py-3 text-left">
        ${r.employee?.full_name || r.employee_name || "-"}
    </td>
    <td class="px-3 py-3 text-left">
        ${r.benefit?.name || r.benefit_name || "-"}
    </td>
    <td class="px-3 py-3 text-right">
        ${r.value != null ? Number(r.value).toFixed(2) : "0.00"}
    </td>
    <td class="px-3 py-3 text-left">
        ${r.notes || "-"}
    </td>

    <td class="px-6 py-3 text-center whitespace-nowrap">
        <div class="relative inline-block text-left">
            <button type="button"
                    class="rounded-lg p-2 hover:bg-slate-100 focus:outline-none"
                    data-menu-trigger
                    data-id="${r.id}"
                    aria-haspopup="true"
                    aria-expanded="false">⋮</button>

            <div id="menu-${r.id}"
                 class="hidden absolute right-0 top-full z-50 mt-2 w-48 rounded-2xl overflow-hidden border border-slate-200 bg-white shadow-xl ring-1 ring-black/5"
                 data-menu
                 data-for="${r.id}">
                <ul class="py-1 text-sm text-slate-700">
                    <li>
                        <button type="button"
                                class="block w-full px-3 py-2 text-left bg-transparent rounded-none hover:bg-slate-50 focus:outline-none"
                                data-view
                                data-id="${r.id}">
                            Visualizar registro
                        </button>
                    </li>
                    <li>
                        <button type="button"
                                class="block w-full px-3 py-2 text-left bg-transparent rounded-none hover:bg-slate-50 focus:outline-none"
                                data-edit
                                data-id="${r.id}">
                            Editar registro
                        </button>
                    </li>
                    <li class="border-t border-slate-200 mt-1 pt-1">
                        <button type="button"
                                class="block w-full px-3 py-2 text-left bg-transparent rounded-none text-rose-600 hover:bg-rose-50 focus:outline-none"
                                data-del
                                data-id="${r.id}">
                            Excluir registro
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </td>
</tr>`,

        fillForm: (eb) => {
            document.querySelector("#employee_benefit_id").value = eb.id || "";

            const employeeSelect = document.querySelector("#employee_id");
            const benefitSelect  = document.querySelector("#benefit_id");

            if (employeeSelect) employeeSelect.value = eb.employee_id || "";
            if (benefitSelect)  benefitSelect.value  = eb.benefit_id || "";

            document.querySelector("#value").value = eb.value ?? "";
            document.querySelector("#notes").value = eb.notes || "";
        },

        getPayload: () => ({
            employee_id: document.querySelector("#employee_id").value || null,
            benefit_id: document.querySelector("#benefit_id").value || null,
            value: document.querySelector("#value").value || null,
            notes: document.querySelector("#notes").value || null,
        }),

        getId: () => document.querySelector("#employee_benefit_id").value,
    });
});
