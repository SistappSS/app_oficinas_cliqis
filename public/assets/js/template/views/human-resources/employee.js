import { ModelCrud } from "../../partials/modelCrud.js";

const escapeHtml = (str = "") => {
    return String(str).replace(/[&<>"']/g, (c) => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;",
    }[c] || c));
};

function initDepartmentAutocomplete() {
    const input  = document.querySelector("#department_search");
    const hidden = document.querySelector("#department_id");
    const box    = document.querySelector("#department_results");

    if (!input || !hidden || !box) return;

    let typingTimer = null;

    async function searchDepartments(term) {
        const url = new URL("/human-resources/department-api", window.location.origin);
        if (term) url.searchParams.set("q", term);

        const res = await fetch(url, {
            headers: { "Accept": "application/json" }
        });

        if (!res.ok) {
            console.error("Erro ao buscar departamentos", res.status);
            return [];
        }

        const json = await res.json();
        return json.data || json || [];
    }

    function closeBox() {
        box.classList.add("hidden");
        box.innerHTML = "";
    }

    function openBox(html) {
        box.innerHTML = html;
        box.classList.remove("hidden");
    }

    async function handleSearch(term) {
        hidden.value = "";

        if (!term) {
            closeBox();
            return;
        }

        const list = await searchDepartments(term);

        const normalize = (str = "") =>
            str
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .trim()
                .toLowerCase();

        const normalizedTerm = normalize(term);

        const existsExact = list.some(d => normalize(d.name || "") === normalizedTerm);

        if (!list.length) {
            openBox(`
            <button type="button"
                    data-create="${escapeHtml(term)}"
                    class="block w-full px-3 py-2 text-left text-sm text-blue-700 hover:bg-blue-50">
                + Criar novo departamento "${escapeHtml(term)}"
            </button>
        `);
            return;
        }

        const itemsHtml = list.map(d => `
        <button type="button"
                data-id="${escapeHtml(d.id)}"
                data-name="${escapeHtml(d.name || "(sem nome)")}"
                class="flex w-full items-center justify-between px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
            <span>${escapeHtml(d.name || "(sem nome)")}</span>
        </button>
    `).join("");

        let createHtml = "";
        if (!existsExact) {
            createHtml = `
            <button type="button"
                    data-create="${escapeHtml(term)}"
                    class="block w-full border-t border-slate-200 px-3 py-2 text-left text-xs text-slate-500 hover:bg-slate-50">
                + Criar novo departamento "${escapeHtml(term)}"
            </button>
        `;
        }

        openBox(itemsHtml + createHtml);
    }

    input.addEventListener("input", () => {
        const term = input.value.trim();
        clearTimeout(typingTimer);
        typingTimer = setTimeout(() => handleSearch(term), 250);
    });

    input.addEventListener("focus", () => {
        if (input.value.trim()) {
            handleSearch(input.value.trim());
        }
    });

    box.addEventListener("click", async (e) => {
        const btn = e.target.closest("button");
        if (!btn) return;

        const id   = btn.dataset.id;
        const name = btn.dataset.name;
        const createName = btn.dataset.create;

        // selecionar existente
        if (id && name) {
            hidden.value = id;
            input.value  = name;
            closeBox();
            return;
        }

        // criar novo
        if (createName) {
            try {
                const fd = new FormData();
                fd.append("name", createName);

                const res = await fetch("/human-resources/department-api", {
                    method: "POST",
                    headers: {
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "",
                    },
                    body: fd,
                });

                if (!res.ok) {
                    console.error("Erro ao criar departamento", res.status, await res.text());
                    alert("Erro ao criar departamento.");
                    return;
                }

                const json = await res.json();
                const d = json.data || json;

                hidden.value = d.id;
                input.value  = d.name || createName;
                closeBox();
            } catch (err) {
                console.error(err);
                alert("Erro ao criar departamento.");
            }
        }
    });

    // fechar ao clicar fora
    document.addEventListener("click", (e) => {
        if (!box.contains(e.target) && e.target !== input) {
            closeBox();
        }
    });
}

document.addEventListener("DOMContentLoaded", async () => {
    initDepartmentAutocomplete();

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
        searchKeys: ["full_name", "email", "phone", "document_number", "position"],
        normalize: (json) => json.data || [],

        renderRow: (r) => `
<tr class="hover:bg-slate-50">
    <td class="px-3 py-3 text-left">${r.full_name || "-"}</td>
    <td class="px-3 py-3 text-left">${r.email || "-"}</td>
    <td class="px-3 py-3 text-left">${r.phone || "-"}</td>
    <td class="px-3 py-3 text-left">${r.position || "-"}</td>
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

            // departamento
            const depHidden = document.querySelector("#department_id");
            const depSearch = document.querySelector("#department_search");
            if (depHidden) depHidden.value = e.department_id || "";
            if (depSearch) {
                depSearch.value =
                    (e.department && e.department.name) ||
                    e.department_name ||
                    "";
            }

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
