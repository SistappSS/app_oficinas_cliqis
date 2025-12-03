// assets/js/template/views/entities/permission_user.js

document.addEventListener("DOMContentLoaded", () => {
    const csrf =
        document.querySelector('meta[name="csrf-token"]')?.content || "";

    const q = (sel) => document.querySelector(sel);

    const ROUTES = {
        rolesIndex: "/permissions-user/roles-api",
        rolesStore: "/permissions-user/roles-api",
        role: (id) => `/permissions-user/roles-api/${id}`,
        permissionsIndex: "/permissions-user/permissions-api",
        rolePermissions: (id) =>
            `/permissions-user/roles-api/${id}/permissions`,
        syncRolePermissions: (id) =>
            `/permissions-user/roles-api/${id}/permissions`,
    };

    const state = {
        roles: [],
        permissions: [],
        selectedRoleId: null,
        selectedPermissionIds: new Set(),
    };

    // ===== helpers =====
    const rolesListEl = q("#roles-list");
    const rolesEmptyEl = q("#roles-empty");
    const rolesSearchInput = q("#roles-search");

    const roleDetailEmpty = q("#role-detail-empty");
    const roleDetail = q("#role-detail");
    const roleNameInput = q("#role-name-input");
    const btnSaveRoleName = q("#btn-save-role-name");
    const btnDeleteRole = q("#btn-delete-role");

    const permissionsContainer = q("#permissions-container");
    const btnSaveRolePermissions = q("#btn-save-role-permissions");

    const btnNewRole = q("#btn-new-role");
    const roleModal = q("#role-modal");
    const roleModalName = q("#role-modal-name");
    const roleModalSave = q("#role-modal-save");

    function debounce(fn, delay = 300) {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), delay);
        };
    }

    async function fetchJson(url, options = {}) {
        const opts = {
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
            ...options,
        };

        if (
            ["POST", "PUT", "PATCH", "DELETE"].includes(
                (opts.method || "GET").toUpperCase()
            )
        ) {
            opts.headers["Content-Type"] =
                opts.headers["Content-Type"] || "application/json";
            opts.headers["X-CSRF-TOKEN"] = csrf;
        }

        const resp = await fetch(url, opts);
        if (!resp.ok) {
            let msg = "";
            try {
                const j = await resp.json();
                msg = j.message || JSON.stringify(j);
            } catch (e) {
                msg = await resp.text();
            }
            console.error("Erro em", url, msg);
            throw new Error(msg || "Erro de requisição");
        }
        return await resp.json();
    }

    // ===== modal novo perfil =====
    function openRoleModal() {
        if (!roleModal) return;
        roleModalName.value = "";
        roleModal.classList.remove("hidden");
        roleModal.classList.add("flex");
        roleModalName?.focus();
    }

    function closeRoleModal() {
        if (!roleModal) return;
        roleModal.classList.add("hidden");
        roleModal.classList.remove("flex");
    }

    if (btnNewRole && roleModal) {
        btnNewRole.addEventListener("click", (e) => {
            e.preventDefault();
            openRoleModal();
        });

        document
            .querySelectorAll("[data-role-modal-close]")
            .forEach((btn) => {
                btn.addEventListener("click", (e) => {
                    e.preventDefault();
                    closeRoleModal();
                });
            });

        if (roleModalSave) {
            roleModalSave.addEventListener("click", async () => {
                const name = (roleModalName.value || "").trim();
                if (!name) {
                    alert("Informe um nome para o perfil.");
                    roleModalName.focus();
                    return;
                }

                try {
                    const json = await fetchJson(ROUTES.rolesStore, {
                        method: "POST",
                        body: JSON.stringify({ name }),
                    });

                    state.roles.push(json);
                    state.filteredRoles = [];
                    renderRolesList();
                    closeRoleModal();
                } catch (e) {
                    alert(
                        "Erro ao criar perfil: " +
                        (e.message || "verifique o console")
                    );
                }
            });
        }
    }

    // ===== roles =====
    async function loadRoles() {
        try {
            const json = await fetchJson(ROUTES.rolesIndex);
            state.roles = Array.isArray(json) ? json : [];
            state.filteredRoles = [];
            renderRolesList();
        } catch (e) {
            console.error(e);
            rolesListEl.innerHTML =
                '<p class="text-[11px] text-rose-500 px-2 py-1">Erro ao carregar perfis.</p>';
        }
    }

    function applyRoleFilter(term) {
        term = term.trim().toLowerCase();
        if (!term) {
            state.filteredRoles = [];
            renderRolesList();
            return;
        }

        state.filteredRoles = state.roles.filter((r) =>
            (r.display_name || "").toLowerCase().includes(term)
        );
        renderRolesList();
    }

    function renderRolesList() {
        if (!rolesListEl) return;
        const list =
            state.filteredRoles.length > 0
                ? state.filteredRoles
                : state.roles;

        if (!list.length) {
            rolesEmptyEl?.classList.remove("hidden");
            rolesListEl.innerHTML = "";
            return;
        }

        rolesEmptyEl?.classList.add("hidden");

        const activeId = state.activeRole?.id;

        rolesListEl.innerHTML = list
            .map((role) => {
                const isActive = String(activeId) === String(role.id);
                return `
<button type="button"
        data-role-id="${role.id}"
        class="w-full rounded-xl border px-3 py-2 text-left text-xs transition ${
                    isActive
                        ? "border-blue-200 bg-blue-50 text-blue-900"
                        : "border-slate-200 bg-slate-50 hover:bg-slate-100"
                }">
    <div class="flex items-center justify-between gap-2">
        <span class="font-medium truncate">${role.display_name}</span>
        <span class="inline-flex items-center rounded-full bg-white/80 px-2 py-0.5 text-[10px] text-slate-500 border border-slate-200">
            ${role.permissions_count} perm.
        </span>
    </div>
</button>`;
            })
            .join("");

        rolesListEl
            .querySelectorAll("[data-role-id]")
            .forEach((btn) => {
                btn.addEventListener("click", () => {
                    const id = btn.getAttribute("data-role-id");
                    selectRole(id);
                });
            });
    }

    async function selectRole(roleId) {
        const role = state.roles.find(
            (r) => String(r.id) === String(roleId)
        );
        if (!role) return;

        state.activeRole = role;
        roleDetailEmpty?.classList.add("hidden");
        roleDetail?.classList.remove("hidden");

        if (roleNameInput) {
            roleNameInput.value = role.display_name || "";
        }

        // carrega ids de permissões do perfil (UUID string)
        try {
            const json = await fetchJson(
                ROUTES.rolePermissions(role.id)
            );
            const ids = Array.isArray(json) ? json : [];
            state.rolePermissions = new Set(
                ids.map((v) => String(v))
            );
        } catch (e) {
            console.error(e);
            state.rolePermissions = new Set();
        }

        // garantir que as permissões globais estejam carregadas
        if (!state.loadedPermissions) {
            await loadPermissions();
        }
        renderPermissions();
        renderRolesList();
    }

    if (rolesSearchInput) {
        rolesSearchInput.addEventListener(
            "input",
            debounce(() => {
                applyRoleFilter(rolesSearchInput.value || "");
            }, 200)
        );
    }

    if (btnSaveRoleName) {
        btnSaveRoleName.addEventListener("click", async () => {
            if (!state.activeRole) return;
            const name = (roleNameInput.value || "").trim();
            if (!name) {
                alert("Informe um nome para o perfil.");
                return;
            }

            try {
                const json = await fetchJson(
                    ROUTES.role(state.activeRole.id),
                    {
                        method: "PUT",
                        body: JSON.stringify({ name }),
                    }
                );

                const idx = state.roles.findIndex(
                    (r) => String(r.id) === String(json.id)
                );
                if (idx >= 0) {
                    state.roles[idx] = json;
                }
                state.activeRole = json;
                renderRolesList();
            } catch (e) {
                alert(
                    "Erro ao salvar nome do perfil: " +
                    (e.message || "veja o console")
                );
            }
        });
    }

    if (btnDeleteRole) {
        btnDeleteRole.addEventListener("click", async () => {
            if (!state.activeRole) return;
            if (
                !confirm(
                    "Deseja realmente excluir este perfil? Esta ação não pode ser desfeita."
                )
            )
                return;

            try {
                await fetchJson(ROUTES.role(state.activeRole.id), {
                    method: "DELETE",
                });

                state.roles = state.roles.filter(
                    (r) =>
                        String(r.id) !==
                        String(state.activeRole.id)
                );
                state.activeRole = null;
                state.rolePermissions = new Set();
                roleDetail?.classList.add("hidden");
                roleDetailEmpty?.classList.remove("hidden");
                renderRolesList();
            } catch (e) {
                alert(
                    "Erro ao excluir perfil: " +
                    (e.message || "veja o console")
                );
            }
        });
    }

    // ===== permissions =====
    async function loadPermissions() {
        try {
            const json = await fetchJson(ROUTES.permissionsIndex);
            state.permissions = Array.isArray(json) ? json : [];
            state.loadedPermissions = true;
        } catch (e) {
            console.error(e);
            permissionsContainer.innerHTML =
                '<p class="text-[11px] text-rose-500">Erro ao carregar permissões.</p>';
        }
    }

    function updateToggleVisual(toggleEl, checked) {
        const knob = toggleEl.querySelector("span");
        if (!knob) return;

        // base fixa do botão
        toggleEl.className =
            "permission-toggle relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer items-center rounded-full border transition-all " +
            (checked ? "bg-emerald-500 border-emerald-500" : "bg-slate-100 border-slate-200");

        // base fixa do knob
        knob.className =
            "pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow transform transition-transform " +
            (checked ? "translate-x-4" : "translate-x-0");
    }

    function renderPermissions() {
        const container = document.querySelector("#permissions-container");
        if (!container) return;

        // garante SET de strings
        if (!(state.selectedPermissionIds instanceof Set)) {
            const base = Array.isArray(state.selectedPermissionIds)
                ? state.selectedPermissionIds
                : [];
            state.selectedPermissionIds = new Set(base.map(String));
        }
        const selectedIds = state.selectedPermissionIds;

        // monta recursos a partir do segundo nome (ação = primeira palavra, recurso = resto)
        const resources = {};
        (state.permissions || []).forEach((p) => {
            // evita permission interna se ainda vier do back por algum motivo
            if ((p.base_name || "").toLowerCase().includes("employee_customer_cliqis")) {
                return;
            }

            const label = p.display_name || p.base_name || "";
            const parts = label.split(/\s+/).filter(Boolean);

            let action = (parts[0] || "").toLowerCase();      // cadastrar / editar / excluir / visualizar
            let resourceLabel = parts.slice(1).join(" ").trim(); // Benefícios, Clientes, etc

            if (!resourceLabel) {
                resourceLabel = "Geral";
            }

            const groupKey = resourceLabel.toUpperCase();

            if (!resources[groupKey]) {
                resources[groupKey] = [];
            }

            resources[groupKey].push({
                id: String(p.id),
                fullLabel: label,
                baseName: p.base_name || "",
                action,
            });
        });

        const order = { cadastrar: 1, editar: 2, excluir: 3, visualizar: 4 };

        const sectionsHtml = Object.entries(resources)
            .sort((a, b) => a[0].localeCompare(b[0], "pt-BR"))
            .map(([resourceLabel, perms]) => {
                const lines = perms
                    .slice()
                    .sort((a, b) => (order[a.action] || 99) - (order[b.action] || 99))
                    .map((perm) => {
                        const checked = selectedIds.has(perm.id);

                        const toggleClasses =
                            "permission-toggle relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer items-center rounded-full border transition-all " +
                            (checked
                                ? "bg-emerald-500 border-emerald-500"
                                : "bg-slate-100 border-slate-200");

                        const knobClasses =
                            "pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow transform transition-transform " +
                            (checked ? "translate-x-4" : "translate-x-0");

                        return `
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 bg-white/80 px-3 py-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-slate-900 truncate">
                                    ${perm.fullLabel}
                                </p>
                                <p class="mt-0.5 text-[11px] text-slate-400 truncate">
                                    ${perm.baseName}
                                </p>
                            </div>
                            <button type="button"
                                    data-permission-id="${perm.id}"
                                    data-checked="${checked ? "1" : "0"}"
                                    class="${toggleClasses}">
                                <span class="${knobClasses}"></span>
                            </button>
                        </div>
                    `;
                    })
                    .join("");

                return `
                <section class="rounded-2xl border border-slate-100 bg-slate-50/80 p-3">
                    <header class="mb-2 text-[11px] font-semibold tracking-wide text-slate-500 uppercase">
                        ${resourceLabel}
                    </header>
                    <div class="space-y-2">
                        ${lines}
                    </div>
                </section>
            `;
            })
            .join("");

        container.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            ${sectionsHtml}
        </div>
    `;

        // liga toggles
        container.querySelectorAll(".permission-toggle").forEach((btn) => {
            btn.addEventListener("click", () => {
                const id = btn.dataset.permissionId;
                if (!id) {
                    console.warn("permission-id vazio no toggle", btn.dataset);
                    return;
                }

                const isChecked = selectedIds.has(id);
                const next = !isChecked;

                if (next) selectedIds.add(id);
                else selectedIds.delete(id);

                btn.dataset.checked = next ? "1" : "0";
                updateToggleVisual(btn, next);
            });
        });
    }

    if (permissionsContainer) {
        permissionsContainer.addEventListener("click", (e) => {
            const btn = e.target.closest(
                "[data-permission-toggle]"
            );
            if (!btn) return;

            const id = btn.getAttribute("data-permission-id");
            if (!id) {
                console.warn("permission-id vazio no toggle");
                return;
            }

            const current = btn.getAttribute("data-checked") === "1";
            const next = !current;
            btn.setAttribute("data-checked", next ? "1" : "0");

            if (next) {
                state.rolePermissions.add(String(id));
            } else {
                state.rolePermissions.delete(String(id));
            }

            updateToggleVisual(btn);
        });
    }

    if (btnSaveRolePermissions) {
        btnSaveRolePermissions.addEventListener("click", async () => {
            if (!state.activeRole) {
                alert("Selecione um perfil antes de salvar.");
                return;
            }

            const permissionIds = Array.from(state.selectedPermissionIds).map(String);

            try {
                await fetchJson(
                    ROUTES.syncRolePermissions(state.activeRole.id),
                    {
                        method: "POST",
                        body: JSON.stringify({
                            permission_ids: permissionIds,
                        }),
                    }
                );

                window.location.reload();
            } catch (e) {
                alert(
                    "Erro ao salvar permissões: " +
                    (e.message || "veja o console")
                );
            }
        });
    }

    // init
    loadRoles();
});
