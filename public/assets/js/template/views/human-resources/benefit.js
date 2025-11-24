import { ModelCrud } from "../../partials/modelCrud.js";

document.addEventListener("DOMContentLoaded", () => {
    new ModelCrud({
        name: "beneficios",
        label: "benefício",
        tbody: "#tbody",
        modal: "#benefit-modal",
        form: "#benefit-form",
        btnAdd: "#btn-add",
        search: "#search",
        modalTitle: "#m-title",
        btnClose: "#m-close",
        btnCancel: "#m-cancel",
        btnDelete: "#btn-delete",
        btnSubmit: "#m-submit",
        routes: {
            index: "/human-resources/benefit-api",
            store: "/human-resources/benefit-api",
            update: "/human-resources/benefit-api",
            delete: "/human-resources/benefit-api",
        },
        searchKeys: ["name", "description"],
        normalize: (json) => json.data || [],

        renderRow: (r) => `
<tr class="hover:bg-slate-50">
    <td class="px-3 py-3 text-left">${r.name || "-"}</td>
    <td class="px-3 py-3 text-left">${r.description || "-"}</td>

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

        fillForm: (b) => {
            document.querySelector("#benefit_id").value = b.id || "";
            document.querySelector("#name").value = b.name || "";
            document.querySelector("#description").value = b.description || "";
        },

        getPayload: () => ({
            name: document.querySelector("#name").value,
            description: document.querySelector("#description").value,
        }),

        getId: () => document.querySelector("#benefit_id").value,
    });
});
