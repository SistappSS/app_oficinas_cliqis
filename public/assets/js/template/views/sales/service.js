import {ModelCrud} from "../../partials/modelCrud.js";

document.addEventListener("DOMContentLoaded", () => {
    new ModelCrud({
        name: "serviços",
        label: "serviço",
        tbody: "#tbody",
        modal: "#modalService",
        form: "#formService",
        btnAdd: "#btn-add",
        search: "#search",
        modalTitle: "#m-title",
        btnClose: "#m-close",
        btnCancel: "#m-cancel",
        btnDelete: "#btn-delete",
        btnSubmit: "#m-submit",
        routes: {
            index: "/sales/service-api",
            store: "/sales/service-api",
            update: "/sales/service-api",
            delete: "/sales/service-api"
        },
        searchKeys: ["name", "price", "type"],
        normalize: json => json.data || [],

        renderRow: r => {
            const typeLabel = {
                payment_unique: 'Pagamento único',
                monthly:        'Mensal',
                yearly:         'Anual'
            }[r.type] || r.type || '-';

            return `
                <tr class="text-center hover:bg-slate-50">
                    <td class="px-3 py-3 text-left">${r.name}</td>
                    <td class="px-3 py-3">${r.brlPrice || "-"}</td>
                    <td class="px-3 py-3">${typeLabel}</td>

                    <td class="px-6 py-3 text-center whitespace-nowrap">
                        <div class="relative inline-block text-left">
                            <button type="button" class="rounded-lg p-2 hover:bg-slate-100 focus:outline-none" data-menu-trigger data-id="${r.id}" aria-haspopup="true" aria-expanded="false" >⋮</button>

                            <div id="menu-${r.id}" class="hidden absolute right-0 top-full z-50 mt-2 w-48 rounded-2xl overflow-hidden border border-slate-200 bg-white shadow-xl ring-1 ring-black/5"
                                data-menu data-for="${r.id}">
                                <ul class="py-1 text-sm text-slate-700">
                                    <li>
                                        <button type="button" class="block w-full px-3 py-2 text-left bg-transparent rounded-none hover:bg-slate-50 focus:outline-none" data-view data-id="${r.id}">Visualizar registro</button>
                                    </li>
                                    <li>
                                        <button type="button" class="block w-full px-3 py-2 text-left bg-transparent rounded-none hover:bg-slate-50 focus:outline-none" data-edit data-id="${r.id}">Editar registro</button>
                                    </li>
                                    <li class="border-t border-slate-200 mt-1 pt-1">
                                        <button type="button" class="block w-full px-3 py-2 text-left bg-transparent rounded-none text-rose-600 hover:bg-rose-50 focus:outline-none" data-del data-id="${r.id}">Excluir registro</button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </td>
                </tr>`;
        },

        fillForm: c => {
            document.querySelector("#service_id").value      = c.id ?? "";
            document.querySelector("#name").value            = c.name ?? "";
            document.querySelector("#price").value           = c.price ?? c.brlPrice ?? "";
            document.querySelector("#description").value     = c.description ?? "";

            // mapa pra garantir compatibilidade com o que vier do backend
            const mapType = {
                "Pagamento único": "payment_unique",
                "Mensal":          "monthly",
                "Anual":           "yearly",
                "payment_unique":  "payment_unique",
                "monthly":         "monthly",
                "yearly":          "yearly"
            };

            const currentType = mapType[c.type] || null;

            document.querySelectorAll('input[name="type"]').forEach(radio => {
                radio.checked = (radio.value === currentType);
            });
        },


        getPayload: () => {
            // agora sim: lê o radio marcado
            const selectedType = document.querySelector('input[name="type"]:checked')?.value || null;

            return {
                name: document.querySelector("#name").value,
                price: document.querySelector("#price").value,
                description: document.querySelector("#description").value,
                type: selectedType
            };
        },

        getId: () => document.querySelector("#service_id").value
    });
});
