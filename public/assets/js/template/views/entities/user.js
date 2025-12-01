import { ModelCrud } from "../../partials/modelCrud.js";

let META = {
    roles: [],
    permissions_grouped: {},
};

async function loadMeta() {
    try {
        const res = await fetch("/entities/user/permissions");
        if (!res.ok) return;
        META = await res.json();
    } catch (e) {
        console.error("Erro ao carregar meta de usuários", e);
    }
}

function renderRoles(currentRoles) {
    const container = document.querySelector("#roles_edit_list");
    if (!container) return;

    const selected = new Set((currentRoles || []).map((r) => r.name));

    if (!META.roles || !META.roles.length) {
        container.innerHTML =
            '<span class="text-xs text-slate-400">Nenhuma role disponível para este tenant.</span>';
        return;
    }

    container.innerHTML = META.roles
        .map((role) => {
            const checked = selected.has(role.name) ? "checked" : "";
            return `
<label class="inline-flex items-center gap-2 text-xs bg-slate-50 border border-slate-200 rounded-full px-3 py-1">
    <input type="checkbox" class="role-checkbox" value="${role.name}" ${checked}>
    <span class="text-slate-700">${role.short}</span>
</label>`;
        })
        .join("");
}

function renderPermissions(currentPermissions) {
    const container = document.querySelector("#permissions_edit_list");
    if (!container) return;

    const selected = new Set((currentPermissions || []).map((p) => p.name));
    const groups = META.permissions_grouped || {};
    const groupNames = Object.keys(groups);

    if (!groupNames.length) {
        container.innerHTML =
            '<span class="text-xs text-slate-400">Nenhuma permissão disponível para este tenant.</span>';
        return;
    }

    container.innerHTML = groupNames
        .map((group) => {
            const perms = groups[group] || [];
            const permsHtml = perms
                .map((p) => {
                    const checked = selected.has(p.name) ? "checked" : "";
                    return `
<label class="inline-flex items-center gap-2 text-xs">
    <input type="checkbox" class="perm-checkbox" value="${p.name}" ${checked}>
    <span class="text-slate-700">${p.label}</span>
</label>`;
                })
                .join("<br>");

            const label = group.charAt(0).toUpperCase() + group.slice(1);

            return `
<div class="border border-slate-200 rounded-xl p-2">
    <div class="text-xs font-semibold text-slate-700 mb-1">${label}</div>
    <div class="space-y-1">${permsHtml}</div>
</div>`;
        })
        .join("");
}

document.addEventListener("DOMContentLoaded", async () => {
    await loadMeta();

    new ModelCrud({
        name: "usuarios",
        label: "usuário",
        tbody: "#tbody",
        modal: "#user-modal",
        form: "#user-form",
        // sem criação manual por aqui
        // btnAdd: "#btn-add",
        search: "#search",
        modalTitle: "#m-title",
        btnClose: "#m-close",
        btnCancel: "#m-cancel",
        btnDelete: "#btn-delete",
        btnSubmit: "#m-submit",
        routes: {
            index: "/entities/user-api",
            update: "/entities/user-api",
            delete: "/entities/user-api",
        },
        searchKeys: ["name", "email"],
        normalize: (json) => json.data || [],

        renderRow: (u) => {
            const roles = (u.roles || []).map((r) => r.name);

            const niceRoles = roles.map((name) => {
                const m = name.match(/^sist_\d+_(.+)$/);
                const raw = m ? m[1] : name;
                return raw.replace(/_/g, " ");
            });

            const typeLabel =
                u.type === "owner"
                    ? "Cliente principal"
                    : u.type === "employee"
                        ? "Funcionário"
                        : "Outro";

            const rolesHtml =
                niceRoles.length > 0
                    ? niceRoles
                        .map(
                            (r) =>
                                `<span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700">${r}</span>`
                        )
                        .join(" ")
                    : '<span class="text-xs text-slate-400">Sem roles</span>';

            const createdAt = u.created_at
                ? new Date(u.created_at).toLocaleString("pt-BR")
                : "-";

            return `
<tr class="hover:bg-slate-50">
    <td class="px-6 py-3 text-left text-sm font-medium text-slate-900">${u.name || "-"}</td>
    <td class="px-3 py-3 text-left text-sm text-slate-700">${u.email || "-"}</td>
    <td class="px-3 py-3 text-center text-xs">
        <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700">
            ${typeLabel}
        </span>
    </td>
    <td class="px-3 py-3 text-left">
        <div class="flex flex-wrap gap-1">
            ${rolesHtml}
        </div>
    </td>
    <td class="px-3 py-3 text-right text-xs text-slate-500">
        ${createdAt}
    </td>
    <td class="px-6 py-3 text-center whitespace-nowrap">
        <div class="relative inline-block text-left">
            <button type="button"
                    class="rounded-lg p-2 hover:bg-slate-100 focus:outline-none"
                    data-menu-trigger
                    data-id="${u.id}"
                    aria-haspopup="true"
                    aria-expanded="false">⋮</button>

            <div id="menu-${u.id}"
                 class="hidden absolute right-0 top-full z-50 mt-2 w-48 rounded-2xl overflow-hidden border border-slate-200 bg-white shadow-xl ring-1 ring-black/5"
                 data-menu
                 data-for="${u.id}">
                <ul class="py-1 text-sm text-slate-700">
                    <li>
                        <button type="button"
                                class="block w-full px-3 py-2 text-left bg-transparent rounded-none hover:bg-slate-50 focus:outline-none"
                                data-view
                                data-id="${u.id}">
                            Visualizar
                        </button>
                    </li>
                    <li>
                        <button type="button"
                                class="block w-full px-3 py-2 text-left bg-transparent rounded-none hover:bg-slate-50 focus:outline-none"
                                data-edit
                                data-id="${u.id}">
                            Editar
                        </button>
                    </li>
                    <li class="border-t border-slate-200 mt-1 pt-1">
                        <button type="button"
                                class="block w-full px-3 py-2 text-left bg-transparent rounded-none text-rose-600 hover:bg-rose-50 focus:outline-none"
                                data-del
                                data-id="${u.id}">
                            Excluir
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </td>
</tr>`;
        },

        fillForm: (u) => {
            document.querySelector("#user_id").value = u.id || "";
            document.querySelector("#name").value = u.name || "";
            document.querySelector("#email").value = u.email || "";

            const typeInput = document.querySelector("#user_type");
            if (typeInput) {
                typeInput.value =
                    u.type === "owner"
                        ? "Cliente principal"
                        : u.type === "employee"
                            ? "Funcionário"
                            : "Outro";
            }

            const createdAtInput = document.querySelector("#created_at");
            if (createdAtInput) {
                createdAtInput.value = u.created_at
                    ? new Date(u.created_at).toLocaleString("pt-BR")
                    : "";
            }

            const pwd = document.querySelector("#password");
            const pwdConf = document.querySelector("#password_confirmation");

            if (pwd) pwd.value = "";
            if (pwdConf) pwdConf.value = "";

            renderRoles(u.roles || []);
            renderPermissions(u.permissions || []);
        },

        getPayload: () => {
            const roles = Array.from(
                document.querySelectorAll(".role-checkbox:checked")
            ).map((el) => el.value);

            const permissions = Array.from(
                document.querySelectorAll(".perm-checkbox:checked")
            ).map((el) => el.value);

            const password = document.querySelector("#password")?.value || "";
            const password_confirmation =
                document.querySelector("#password_confirmation")?.value || "";

            const payload = {
                name: document.querySelector("#name").value,
                email: document.querySelector("#email").value,
                roles,
                permissions,
            };

            if (password) {
                payload.password = password;
                payload.password_confirmation = password_confirmation;
            }

            return payload;
        },

        getId: () => document.querySelector("#user_id").value,
    });
});
