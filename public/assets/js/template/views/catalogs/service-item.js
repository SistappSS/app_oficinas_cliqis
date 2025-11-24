import { ModelCrud } from "../../partials/modelCrud.js";

async function loadServiceTypes() {
    const sel = document.querySelector("#service_type_id");
    if (!sel) return;

    try {
        const res = await fetch("/catalogs/service-type-api");
        if (!res.ok) throw new Error("Erro ao buscar tipos de serviço");

        const json = await res.json();
        const list = json.data || json || [];

        sel.innerHTML = '<option value="">Selecione...</option>' +
            list
                .map(t => `<option value="${t.id}">${t.name || "(sem nome)"}</option>`)
                .join("");
    } catch (e) {
        console.error(e);
        sel.innerHTML = '<option value="">Erro ao carregar tipos de serviço</option>';
    }
}

document.addEventListener("DOMContentLoaded", async () => {
    // carrega tipos de serviço antes do CRUD
    await loadServiceTypes();

    new ModelCrud({
        name: "servicos",
        label: "serviço",
        tbody: "#tbody",
        modal: "#service-item-modal",
        form: "#service-item-form",
        btnAdd: "#btn-add",
        search: "#search",
        modalTitle: "#m-title",
        btnClose: "#m-close",
        btnCancel: "#m-cancel",
        btnDelete: "#btn-delete",
        btnSubmit: "#m-submit",
        routes: {
            index: "/catalogs/service-item-api",
            store: "/catalogs/service-item-api",
            update: "/catalogs/service-item-api",
            delete: "/catalogs/service-item-api",
        },
        searchKeys: ["name", "description"],
        normalize: (json) => json.data || [],

        renderRow: (r) => `
<tr class="hover:bg-slate-50">
    <td class="px-3 py-3 text-left">
        ${r.name || "-"}
    </td>

    <td class="px-3 py-3 text-left">
        ${r.service_type?.name || r.serviceType?.name || r.service_type_name || "-"}
    </td>

    <td class="px-3 py-3 text-left max-w-md truncate" title="${(r.description || "").replace(/"/g, "&quot;")}">
        ${r.description || "-"}
    </td>

    <td class="px-3 py-3 text-right">
        ${r.unit_price != null ? Number(r.unit_price).toFixed(2) : "0.00"}
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

        fillForm: (s) => {
            document.querySelector("#service_item_id").value = s.id || "";
            document.querySelector("#name").value = s.name || "";
            document.querySelector("#description").value = s.description || "";
            document.querySelector("#unit_price").value = s.unit_price ?? "";

            const typeSelect = document.querySelector("#service_type_id");
            if (typeSelect) typeSelect.value = s.service_type_id || "";

            document.querySelector("#is_active").checked = !!s.is_active;
        },

        getPayload: () => ({
            name: document.querySelector("#name").value,
            service_type_id: document.querySelector("#service_type_id").value || null,
            description: document.querySelector("#description").value,
            unit_price: document.querySelector("#unit_price").value || 0,
            is_active: document.querySelector("#is_active").checked,
        }),

        getId: () => document.querySelector("#service_item_id").value,
    });
});
