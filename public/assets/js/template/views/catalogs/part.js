import { ModelCrud } from "../../partials/modelCrud.js";
import { initImportExportModal } from "../../../common/import_export.js";


async function loadSuppliers() {
    const sel = document.querySelector("#supplier_id");
    if (!sel) return;

    try {
        const res = await fetch("/entities/supplier-api");
        if (!res.ok) throw new Error("Erro ao buscar fornecedores");

        const json = await res.json();
        const list = json.data || json || [];

        sel.innerHTML = '<option value="">Selecione...</option>' +
            list
                .map(s => `<option value="${s.id}">${s.name || s.company_name || "(sem nome)"}</option>`)
                .join("");
    } catch (e) {
        console.error(e);
        sel.innerHTML = '<option value="">Erro ao carregar fornecedores</option>';
    }
}

document.addEventListener("DOMContentLoaded", async () => {
    await loadSuppliers();

    initImportExportModal();

    document.querySelector("#btn-export")?.addEventListener("click", () => {
        window.IO_MODAL?.openExport();
    });

    document.querySelector("#btn-import")?.addEventListener("click", () => {
        window.IO_MODAL?.openImport();
    });

    new ModelCrud({
        name: "pecas",
        label: "peça",
        tbody: "#tbody",
        modal: "#part-modal",
        form: "#part-form",
        btnAdd: "#btn-add",
        search: "#search",
        modalTitle: "#m-title",
        btnClose: "#m-close",
        btnCancel: "#m-cancel",
        btnDelete: "#btn-delete",
        btnSubmit: "#m-submit",
        routes: {
            index: "/catalogs/part-api",
            store: "/catalogs/part-api",
            update: "/catalogs/part-api",
            delete: "/catalogs/part-api",
        },
        // ajusta se expuser supplier_name na API
        searchKeys: ["code", "name", "ncm_code", "description"],
        normalize: (json) => json.data || [],

        renderRow: (r) => `
<tr class="hover:bg-slate-50">
    <td class="px-3 py-3 text-left">
        ${r.code || "-"}
    </td>
    <td class="px-3 py-3 text-left">
        ${r.name || "-"}
    </td>
    <td class="px-3 py-3 text-left">
        ${r.supplier?.name || r.supplier_name || "-"}
    </td>
    <td class="px-3 py-3 text-left">
        ${r.ncm_code || "-"}
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
                              data-link-equipments
                              data-id="${r.id}">
                          Vincular equipamentos
                      </button>
                    </li>
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

        fillForm: (p) => {
            document.querySelector("#part_id").value = p.id || "";
            document.querySelector("#code").value = p.code || "";
            document.querySelector("#name").value = p.name || "";
            document.querySelector("#description").value = p.description || "";
            document.querySelector("#ncm_code").value = p.ncm_code || "";
            document.querySelector("#unit_price").value = p.unit_price ?? "";

            const supplierSelect = document.querySelector("#supplier_id");
            if (supplierSelect) supplierSelect.value = p.supplier_id || "";

            document.querySelector("#is_active").checked = !!p.is_active;
        },

        getPayload: () => ({
            code: document.querySelector("#code").value,
            name: document.querySelector("#name").value,
            description: document.querySelector("#description").value,
            ncm_code: document.querySelector("#ncm_code").value,
            unit_price: document.querySelector("#unit_price").value || 0,
            supplier_id: document.querySelector("#supplier_id").value || null,
            is_active: document.querySelector("#is_active").checked,
        }),

        getId: () => document.querySelector("#part_id").value,
    });

    document.addEventListener("click", (e) => {
        const btn = e.target.closest("[data-link-equipments]");
        if (!btn) return;

        e.preventDefault();
        const partId = btn.dataset.id;
        openPartEquipmentsModal(partId);
    });

    const peModal        = document.querySelector("#part-equipments-modal");
    const peForm         = document.querySelector("#part-equipments-form");
    const peClose        = document.querySelector("#part-equipments-close");
    const peCancel       = document.querySelector("#part-equipments-cancel");
    const peTitleName    = document.querySelector("#part-equipments-part-name");
    const peHiddenId     = document.querySelector("#part-equipments-id");

    const searchInputEq  = document.querySelector("#equipment-search");
    const optionsBox     = document.querySelector("#equipment-options");
    const selectedBox    = document.querySelector("#equipment-selected");

    let currentPartId = null;
    let selectedEquipments = new Map();

    const csrf = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content") || null;

    function openModal() {
        peModal.classList.remove("hidden");
        peModal.classList.add("flex");
    }

    function closeModal() {
        currentPartId = null;
        selectedEquipments = new Map();
        peHiddenId.value = "";
        searchInputEq.value = "";
        optionsBox.innerHTML = "";
        selectedBox.innerHTML = "";
        peModal.classList.add("hidden");
        peModal.classList.remove("flex");
    }

    async function openPartEquipmentsModal(partId) {
        currentPartId = partId;
        peHiddenId.value = partId;

        try {
            const res = await fetch(`/catalogs/part-api/${partId}`);
            const json = await res.json();
            const part = json.data || json;

            peTitleName.textContent = part.name ? `Peça: ${part.name}` : "";

            selectedEquipments = new Map();
            (part.equipments || []).forEach((eq) => {
                selectedEquipments.set(eq.id, {
                    id: eq.id,
                    name: eq.name,
                    code: eq.code || null,
                });
            });

            renderSelectedChips();
            await loadEquipmentOptions("");
            openModal();
        } catch (e) {
            console.error(e);
            alert("Erro ao carregar equipamentos da peça.");
        }
    }

    async function loadEquipmentOptions(term = "") {
        const url = new URL("/catalogs/equipment-api", window.location.origin);
        if (term) url.searchParams.set("q", term);
        url.searchParams.set("per_page", "8");

        try {
            const res = await fetch(url);
            const json = await res.json();
            const list = json.data || json || [];
            renderEquipmentOptions(list);
        } catch (e) {
            console.error(e);
            optionsBox.innerHTML =
                '<p class="px-2 py-1.5 text-xs text-rose-600">Erro ao carregar equipamentos.</p>';
        }
    }

    function renderEquipmentOptions(list) {
        optionsBox.innerHTML = "";

        if (!list.length) {
            optionsBox.innerHTML =
                '<p class="px-2 py-1.5 text-xs text-slate-500">Nenhum equipamento encontrado.</p>';
            return;
        }

        list.forEach((eq) => {
            const isSelected = selectedEquipments.has(eq.id);

            const btn = document.createElement("button");
            btn.type = "button";
            btn.dataset.id = eq.id;
            btn.className =
                "w-full flex items-center justify-between rounded-lg px-2 py-1.5 text-xs transition " +
                (isSelected
                    ? "bg-blue-50 text-blue-700 border border-blue-200"
                    : "bg-white text-slate-700 hover:bg-slate-100 border border-transparent");

            const labelCode = eq.code
                ? `<span class="ml-1 text-[11px] text-slate-400">(${eq.code})</span>`
                : "";

            btn.innerHTML = `
            <span class="flex-1 text-left truncate">
                ${eq.name || "-"} ${labelCode}
            </span>
            <span class="ml-3 text-[11px] ${
                isSelected ? "text-blue-700" : "text-slate-500"
            }">
                ${isSelected ? "Selecionado" : "Selecionar"}
            </span>
        `;

            btn.addEventListener("click", () => {
                if (selectedEquipments.has(eq.id)) {
                    selectedEquipments.delete(eq.id);
                } else {
                    selectedEquipments.set(eq.id, {
                        id: eq.id,
                        name: eq.name,
                        code: eq.code || null,
                    });
                }
                renderSelectedChips();
                renderEquipmentOptions(list); // atualiza estados
            });

            optionsBox.appendChild(btn);
        });
    }

    function renderSelectedChips() {
        selectedBox.innerHTML = "";

        if (!selectedEquipments.size) {
            selectedBox.innerHTML =
                '<p class="text-xs text-slate-400">Nenhum equipamento vinculado.</p>';
            return;
        }

        [...selectedEquipments.values()].forEach((eq) => {
            const badge = document.createElement("span");
            badge.className =
                "inline-flex items-center gap-1 rounded-full bg-blue-50 px-3 py-1 text-xs text-blue-700";

            const labelCode = eq.code
                ? `<span class="text-[11px] text-blue-500/80">(${eq.code})</span>`
                : "";

            badge.innerHTML = `
            <span class="truncate">
                ${eq.name || "-"} ${labelCode}
            </span>
        `;

            const btnX = document.createElement("button");
            btnX.type = "button";
            btnX.className =
                "ml-1 inline-flex h-4 w-4 items-center justify-center rounded-full bg-blue-100 text-[10px] hover:bg-blue-200";
            btnX.textContent = "×";
            btnX.addEventListener("click", () => {
                selectedEquipments.delete(eq.id);
                renderSelectedChips();
            });

            badge.appendChild(btnX);
            selectedBox.appendChild(badge);
        });
    }

    function debounce(fn, delay = 300) {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), delay);
        };
    }

    searchInputEq?.addEventListener(
        "input",
        debounce(() => {
            loadEquipmentOptions(searchInputEq.value.trim());
        }, 300)
    );

    peClose?.addEventListener("click", closeModal);
    peCancel?.addEventListener("click", closeModal);
    peModal?.addEventListener("click", (e) => {
        if (e.target === peModal) closeModal();
    });

    peForm?.addEventListener("submit", async (e) => {
        e.preventDefault();
        if (!currentPartId) return;

        const equipment_ids = [...selectedEquipments.keys()];

        try {
            await fetch(`/catalogs/part-api/${currentPartId}`, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
                },
                body: JSON.stringify({ equipment_ids }),
            });

            closeModal();

            console.log('equipment_ids');

            // tenta recarregar via ModelCrud, se ele expuser o método
            if (window.partCrud && typeof window.partCrud.reload === "function") {
                //window.partCrud.reload();
            } else {
                //window.location.reload();
            }
        } catch (err) {
            console.error(err);
            alert("Erro ao salvar vínculos de equipamentos.");
        }
    });
});
