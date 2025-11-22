import {ModelCrud} from "../../partials/modelCrud.js";

document.addEventListener("DOMContentLoaded", () => {
    new ModelCrud({
        name: "funcionalidades",
        label: "funcionalidade",
        tbody: "#tbody",
        modal: "#feature-modal",
        form: "#feature-form",
        btnAdd: "#btn-add",
        search: "#search",
        modalTitle: "#m-title",
        btnClose: "#m-close",
        btnCancel: "#m-cancel",
        btnDelete: "#btn-delete",
        btnSubmit: "#m-submit",
        routes: {
            index: "/modules/feature-api",
            store: "/modules/feature-api",
            update: "/modules/feature-api",
            delete: "/modules/feature-api"
        },
        searchKeys: ["name", "email", "cityName"],
        normalize: json => json.data || [],
        renderRow: r => `
            <tr class="hover:bg-slate-50">
                <td class="px-3 py-3">${r.name}</td>
                <td class="px-3 py-3">${r.email || "-"}</td>
                <td class="px-3 py-3">${r.cityName || "-"}</td>
                <td class="px-3 py-3 text-right">
                    <button data-edit data-id="${r.id}">✏️</button>
                    <button data-del data-id="${r.id}">🗑️</button>
                </td>
            </tr>`,
        fillForm: c => {
            document.querySelector("#customer_id").value = c.id;
            document.querySelector("#name").value = c.name;
            document.querySelector("#cpfCnpj").value = c.cpfCnpj;
            document.querySelector("#mobilePhone").value = c.mobilePhone;
            document.querySelector("#postalCode").value = c.postalCode;
            document.querySelector("#address").value = c.address;
            document.querySelector("#addressNumber").value = c.addressNumber;
            document.querySelector("#province").value = c.province;
            document.querySelector("#complement").value = c.complement;
            document.querySelector("#cityName").value = c.cityName;
            document.querySelector("#state").value = c.state;
        },
        getPayload: () => ({
            name: document.querySelector("#name").value,
            cpfCnpj: document.querySelector("#cpfCnpj").value,
            mobilePhone: document.querySelector("#mobilePhone").value,
            postalCode: document.querySelector("#postalCode").value,
            address: document.querySelector("#address").value,
            addressNumber: document.querySelector("#addressNumber").value,
            province: document.querySelector("#province").value,
            complement: document.querySelector("#complement").value,
            cityName: document.querySelector("#cityName").value,
            state: document.querySelector("#state").value,


            is_active: document.querySelector("#is_active").checked
        }),
        getId: () => document.querySelector("#customer_id").value
    });
});
