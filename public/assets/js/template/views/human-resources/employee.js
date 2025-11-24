import { ModelCrud } from "../../partials/modelCrud.js";

document.addEventListener("DOMContentLoaded", () => {
    new ModelCrud({
        name: "funcionarios",
        label: "funcionário",
        tbody: "#tbody",
        modal: "#employee-modal",
        form: "#employee-form",
        btnAdd: "#btn-add",
        search: "#search",
        modalTitle: "#m-title",
        btnClose: "#m-close",
        btnCancel: "#m-cancel",
        btnDelete: "#btn-delete",
        btnSubmit: "#m-submit",
        routes: {
            index: "/human-resources/employee-api",
            store: "/human-resources/employee-api",
            update: "/human-resources/employee-api",
            delete: "/human-resources/employee-api",
        },
        // campos usados na busca
        searchKeys: ["full_name", "email", "phone", "document_number", "position"],
        normalize: (json) => json.data || [],

        renderRow: (r) => `
<tr class="hover:bg-slate-50">
    <td class="px-3 py-3 text-left">${r.full_name || "-"}</td>
    <td class="px-3 py-3 text-left">${r.document_number || "-"}</td>
    <td class="px-3 py-3 text-left">${r.email || "-"}</td>
    <td class="px-3 py-3 text-left">${r.phone || "-"}</td>
    <td class="px-3 py-3 text-left">${r.position || "-"}</td>
    <td class="px-3 py-3 text-right">
        ${r.hourly_rate != null ? Number(r.hourly_rate).toFixed(2) : "0.00"}
    </td>
    <td class="px-3 py-3 text-center">
        <span class="inline-flex items-center rounded-md bg-${
            r.is_technician ? "emerald" : "slate"
        }-400/10 px-2 py-1 text-xs font-medium text-${
            r.is_technician ? "emerald" : "slate"
        }-600 inset-ring inset-ring-${
            r.is_technician ? "emerald" : "slate"
        }-400/30">
            ${r.is_technician ? "Sim" : "Não"}
        </span>
    </td>
    <td class="px-3 py-3 text-right">
        <span class="inline-flex items-center rounded-md bg-${
            r.is_active ? "blue" : "rose"
        }-400/10 px-2 py-1 text-xs font-medium text-${
            r.is_active ? "blue" : "rose"
        }-600 inset-ring inset-ring-${
            r.is_active ? "blue" : "rose"
        }-400/30">
            ${r.is_active ? "Ativo" : "Inativo"}
        </span>
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

        fillForm: (e) => {
            document.querySelector("#employee_id").value = e.id || "";
            document.querySelector("#user_id").value = e.user_id || "";
            document.querySelector("#full_name").value = e.full_name || "";
            document.querySelector("#email").value = e.email || "";
            document.querySelector("#phone").value = e.phone || "";
            document.querySelector("#document_number").value = e.document_number || "";
            document.querySelector("#position").value = e.position || "";
            document.querySelector("#hourly_rate").value = e.hourly_rate ?? "";

            // department_id pode vir como e.department_id
            const dept = document.querySelector("#department_id");
            if (dept) dept.value = e.department_id || "";

            document.querySelector("#is_active").checked = !!e.is_active;
            document.querySelector("#is_technician").checked = !!e.is_technician;
        },

        getPayload: () => ({
            user_id: document.querySelector("#user_id").value || null,
            department_id: document.querySelector("#department_id").value || null,
            full_name: document.querySelector("#full_name").value,
            email: document.querySelector("#email").value,
            phone: document.querySelector("#phone").value,
            document_number: document.querySelector("#document_number").value,
            position: document.querySelector("#position").value,
            hourly_rate: document.querySelector("#hourly_rate").value || 0,
            is_active: document.querySelector("#is_active").checked,
            is_technician: document.querySelector("#is_technician").checked,
        }),

        getId: () => document.querySelector("#employee_id").value,
    });
});
