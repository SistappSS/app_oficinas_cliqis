import { ModelCrud } from "../../partials/modelCrud.js";

document.addEventListener("DOMContentLoaded", () => {
    new ModelCrud({
        name: "fornecedores",
        label: "fornecedor",
        tbody: "#tbody",
        modal: "#supplier-modal",
        form: "#supplier-form",
        btnAdd: "#btn-add",
        search: "#search",
        modalTitle: "#m-title",
        btnClose: "#m-close",
        btnCancel: "#m-cancel",
        btnDelete: "#btn-delete",
        btnSubmit: "#m-submit",
        routes: {
            index: "/entities/supplier-api",
            store: "/entities/supplier-api",
            update: "/entities/supplier-api",
            delete: "/entities/supplier-api",
        },
        searchKeys: ["name", "email", "cityName"],
        normalize: (json) => json.data || [],
        renderRow: (r) => `
<tr class="hover:bg-slate-50">
  <td class="px-3 py-3">${r.name}</td>
  <td class="px-3 py-3">${r.cpfCnpj || "-"}</td>
  <td class="px-3 py-3">${r.company_email || "-"}</td>
                <td class="px-3 py-3"><span class="inline-flex items-center rounded-md bg-${
            r.is_active ? "blue" : "purple"
        }-400/10 px-2 py-1 text-xs font-medium text-${
            r.is_active ? "blue" : "purple"
        }-400 inset-ring inset-ring-${
            r.is_active ? "blue" : "purple"
        }-400/30">${r.is_active ? "Ativo" : "Inativo"}</span></td>

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

        </tr>`,
        fillForm: (c) => {
            document.querySelector("#supplier_id").value = c.id;
            document.querySelector("#name").value = c.name;
            document.querySelector("#cpfCnpj").value = c.cpfCnpj;
            document.querySelector("#mobilePhone").value = c.mobilePhone;
            document.querySelector("#email").value = c.company;
            document.querySelector("#postalCode").value = c.postalCode;
            document.querySelector("#address").value = c.address;
            document.querySelector("#addressNumber").value = c.addressNumber;
            document.querySelector("#province").value = c.province;
            document.querySelector("#complement").value = c.complement;
            document.querySelector("#cityName").value = c.cityName;
            document.querySelector("#state").value = c.state;
            document.querySelector("#is_active").checked = !!c.is_active;
        },
        getPayload: () => ({
            name: document.querySelector("#name").value,
            cpfCnpj: document.querySelector("#cpfCnpj").value,
            mobilePhone: document.querySelector("#mobilePhone").value,
            email: document.querySelector("#email").value,
            postalCode: document.querySelector("#postalCode").value,
            address: document.querySelector("#address").value,
            addressNumber: document.querySelector("#addressNumber").value,
            province: document.querySelector("#province").value,
            complement: document.querySelector("#complement").value,
            cityName: document.querySelector("#cityName").value,
            state: document.querySelector("#state").value,

            is_active: document.querySelector("#is_active").checked,
        }),

        getId: () => document.querySelector("#supplier_id").value,
    });
});
