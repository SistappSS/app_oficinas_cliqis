const cardsWrap   = document.getElementById("cards");
const searchInput = document.getElementById("search");
const chipsWrap   = document.getElementById("chips");
const emptyState  = document.getElementById("empty-state");

const partsModal      = document.getElementById("parts-modal");
const partsTitle      = document.getElementById("parts-modal-title");
const partsList       = document.getElementById("parts-modal-list");
const partsEmpty      = document.getElementById("parts-modal-empty");
const partsClose      = document.getElementById("parts-modal-close");
const partsCloseX     = document.getElementById("parts-modal-x");

const catalogModal    = document.getElementById("catalog-modal");
const catalogTitle    = document.getElementById("catalog-modal-title");
const catalogIframe   = document.getElementById("catalog-iframe");
const catalogEmpty    = document.getElementById("catalog-empty");
const catalogClose    = document.getElementById("catalog-modal-close");
const catalogCloseX   = document.getElementById("catalog-modal-x");

const editModal   = document.getElementById("edit-modal");
const editForm    = document.getElementById("edit-form");
const editModalTitle = document.getElementById("edit-modal-title");

const editName    = document.getElementById("edit-name");
const editCode    = document.getElementById("edit-code");
const editModel   = document.getElementById("edit-model");
const editSerial  = document.getElementById("edit-serial");
const editNotes   = document.getElementById("edit-notes");
const editPartsList = document.getElementById("edit-parts-list");

const editPhoto   = document.getElementById("edit-photo");
const editPhotoPreview     = document.getElementById("edit-photo-preview");
const editPhotoPlaceholder = document.getElementById("edit-photo-placeholder");

const btnAddEquipment = document.getElementById("btn-add-equipment");
const btnCloseModal   = document.getElementById("close-modal");
const btnCancelModal  = document.getElementById("cancel-modal");
const btnGoToPieces   = document.getElementById("go-to-pieces");

const partsPickerSelectPage = document.getElementById("parts-picker-select-page");

let equipments = [];
let editingId  = null;

let currentImagePayload = null;

const btnManageParts = document.getElementById("btn-manage-parts");

const partsPickerModal   = document.getElementById("parts-picker-modal");
const partsPickerTitle   = document.getElementById("parts-picker-title");
const partsPickerSearch  = document.getElementById("parts-picker-search");
const partsPickerOnlySel = document.getElementById("parts-picker-only-selected");
const partsPickerList    = document.getElementById("parts-picker-list");
const partsPickerCount   = document.getElementById("parts-picker-count");
const partsPickerSelected= document.getElementById("parts-picker-selected");
const partsPickerLoading = document.getElementById("parts-picker-loading");
const partsPickerClose   = document.getElementById("parts-picker-close");
const partsPickerCloseX  = document.getElementById("parts-picker-x");
const partsPickerSave    = document.getElementById("parts-picker-save");

let partsPickerSelectedWrap = document.getElementById("parts-picker-selected-wrap");
let partsPickerResultsWrap  = document.getElementById("parts-picker-results-wrap");
let partsPickerSelectedSep  = document.getElementById("parts-picker-selected-sep");
let partsPickerOnlyEmpty    = document.getElementById("parts-picker-only-empty");

let selectedPartMap = new Map(); // id -> objeto da peça

let selectedPartIds = new Set(); // ids marcados
let partsNextUrl = null;
let partsLoading = false;
let partsLastQuery = "";
let currentEditingEq = null;

const partsPickerUnselectBatch = document.getElementById("parts-picker-unselect-batch");

let lastPageParts = [];
let selectedPageStack = [];
let lockedPartIds = new Set();
let bulkOpsLock = false;

let partsTotal = 0;
let partsFrom = 0;
let partsTo = 0;

const catalogUpload    = document.getElementById("catalog-upload");
const catalogUploadBtn = document.getElementById("catalog-upload-btn");
const catalogSaveBtn   = document.getElementById("catalog-save-btn");
const catalogFileName  = document.getElementById("catalog-file-name");

let currentCatalogEqId = null;
let currentCatalogPayload = null;
let currentCatalogObjectUrl = null;

const catalogEqImage      = document.getElementById("catalog-eq-image");
const catalogEqImageEmpty = document.getElementById("catalog-eq-image-empty");
const catalogEqName       = document.getElementById("catalog-eq-name");
const catalogEqDesc       = document.getElementById("catalog-eq-desc");
const catalogEqCode       = document.getElementById("catalog-eq-code");
const catalogEqSerial     = document.getElementById("catalog-eq-serial");
const catalogEqParts      = document.getElementById("catalog-eq-parts");
const catalogEqPartsEmpty = document.getElementById("catalog-eq-parts-empty");
const catalogDetails = document.getElementById("catalog-details");

const catalogOpenEdit  = document.getElementById("catalog-open-edit");
const catalogOpenParts = document.getElementById("catalog-open-parts");

const csrf = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute("content") || null;

async function api(url, options = {}) {
    const finalOptions = {
        credentials: "same-origin",
        headers: {},
        ...options,
    };

    if (!(finalOptions.body instanceof FormData)) {
        finalOptions.headers["Content-Type"] = "application/json";
    }

    if (csrf) {
        finalOptions.headers["X-CSRF-TOKEN"] = csrf;
    }

    const res = await fetch(url, finalOptions);

    if (!res.ok) {
        let msg = "Erro ao comunicar com o servidor.";
        try {
            const text = await res.text();
            msg = text || msg;
        } catch (_) {}
        throw new Error(msg);
    }

    try {
        return await res.json();
    } catch {
        return null;
    }
}

async function loadEquipments() {
    try {
        const res = await fetch("/catalogs/equipment-api");
        const json = await res.json();
        equipments = json.data || json || [];
        renderCards();
    } catch (e) {
        console.error(e);
        equipments = [];
        renderCards();
    }
}

function renderChipsInfo(filteredCount) {
    chipsWrap.innerHTML = "";
    const total = equipments.length;
    if (!total) return;

    const chipTotal = document.createElement("span");
    chipTotal.className =
        "inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1.5 font-medium text-slate-700";
    chipTotal.innerHTML = `<span>${filteredCount}/${total} equipamentos listados</span>`;
    chipsWrap.appendChild(chipTotal);
}

function buildImageUrl(eq) {
    const info = eq.extra_info || eq.extraInfo || null;
    if (!info || !info.image_path) return "";

    const imgMeta = info.image_path;
    if (typeof imgMeta === "string") {
        return imgMeta; // caso especial: se você quiser gravar uma URL simples
    }

    if (typeof imgMeta === "object" && imgMeta.data) {
        const mime = imgMeta.mime || "image/png";
        return `data:${mime};base64,${imgMeta.data}`;
    }

    return "";
}

function renderCards() {
    const q = (searchInput.value || "").trim().toLowerCase();
    cardsWrap.innerHTML = "";

    const filtered = equipments.filter((eq) => {
        const hay = [
            eq.name,
            eq.description,
            eq.code,
            eq.serial_number,
        ]
            .filter(Boolean)
            .join(" ")
            .toLowerCase();

        return hay.includes(q);
    });

    renderChipsInfo(filtered.length);

    if (!filtered.length) {
        emptyState.classList.remove("hidden");
        return;
    } else {
        emptyState.classList.add("hidden");
    }

    filtered.forEach((eq) => {
        const card = document.createElement("article");
        card.className =
            "group relative flex flex-col rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-blue-500 hover:shadow-md cursor-pointer";

        const imageUrl = buildImageUrl(eq);

        const partNames = (eq.parts || []).map((p) => p.name).filter(Boolean);
        const piecesCount = partNames.length;
        const partsCount = Array.isArray(eq.parts)
            ? eq.parts.length
            : (eq.parts_count ?? 0);
        const partsPreview = partNames.slice(0, 3).join(", ");
        const moreCount = Math.max(0, partNames.length - 3);
        const partsLabel = partNames.length
            ? partsPreview + (moreCount > 0 ? ` +${moreCount}` : "")
            : "";

        const hasCatalog = (() => {
            const extra = eq.extra_info || eq.extraInfo || null;
            if (!extra) return false;
            return Boolean(extra.catalog_pdf) || Boolean(extra.iframe_url);
        })();

        card.innerHTML = `
          <div class="aspect-[4/3] w-full overflow-hidden rounded-xl bg-slate-100 mb-3">
            ${
            imageUrl
                ? `<img src="${imageUrl}" alt="${eq.name || ""}"
                 class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" />`
                : `<div class="h-full w-full flex items-center justify-center text-[11px] text-slate-400">Sem imagem</div>`
        }
          </div>

          <div class="flex-1">
            <h3 class="font-semibold text-slate-900 line-clamp-2">${eq.name || "-"}</h3>
            <p class="mt-1 text-sm text-slate-600 line-clamp-2">${eq.description || ""}</p>
            ${
            partsLabel
                ? `<p class="mt-2 text-[11px] text-slate-500 line-clamp-2">
                         <span class="font-medium">Peças:</span> ${partsLabel}
                       </p>`
                : ""
        }
          </div>

          <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500">
    <span>${eq.code ? "Cód. " + eq.code : ""}</span>
    <button type="button"
  class="js-open-parts inline-flex items-center gap-1 rounded-full bg-slate-50 px-2 py-1 hover:bg-slate-100">
  <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M4 7h16M4 12h10M4 17h6" stroke-linecap="round" stroke-linejoin="round"/>
  </svg>

  <span class="inline-flex items-center gap-1">
    ${partsCount} peça(s)
    ${
            hasCatalog
                ? `<i class="fa-solid fa-paperclip fs-3"></i>`
                : ``
        }
  </span>
</button>
  </div>

          <div class="mt-4 flex items-center justify-end gap-2 text-xs">
            <button type="button"
              class="btn-view inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 font-medium text-slate-700 hover:bg-slate-50">
              Ver catálogo
            </button>
            <button type="button"
              class="btn-edit inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 font-medium text-slate-700 hover:bg-slate-50">
              Editar
            </button>
            <button type="button"
              class="btn-delete inline-flex items-center gap-1 rounded-lg border border-red-100 bg-red-50 px-3 py-1.5 font-medium text-red-700 hover:border-red-200 hover:bg-red-100">
              Excluir
            </button>
          </div>
        `;

        const viewBtn   = card.querySelector(".btn-view");
        const editBtn   = card.querySelector(".btn-edit");
        const deleteBtn = card.querySelector(".btn-delete");
        const partsBtn = card.querySelector(".js-open-parts");
        partsBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            openPartsModal(eq);
        });

        viewBtn.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation(); // garante
            openCatalogModal(eq);
        });

        card.addEventListener("click", (e) => {
            if (
                e.target.closest(".btn-edit") ||
                e.target.closest(".btn-delete") ||
                e.target.closest(".btn-view") ||
                e.target.closest(".js-open-parts")
            ) return;

            openCatalogModal(eq, { mode: "details" });
        });

        editBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            openEditModal(eq);
        });

        deleteBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            deleteEquipment(eq.id);
        });

        cardsWrap.appendChild(card);
    });
}

function resetForm() {
    editForm.reset();
    editPhotoPreview.src = "";
    editPhotoPreview.classList.add("hidden");
    editPhotoPlaceholder.classList.remove("hidden");
    currentImagePayload = null;
}

function openCreateModal() {
    lockScroll(true);

    editingId = null;
    resetForm();
    editModalTitle.textContent = "Novo equipamento";
    editModal.classList.remove("hidden");
    editModal.classList.add("flex");
}

function openEditModal(eq) {
    lockScroll(true);

    editingId = eq.id;
    resetForm();

    editModalTitle.textContent = "Editar equipamento";

    editName.value  = eq.name || "";
    editCode.value  = eq.code || "";
    editModel.value = eq.description || "";
    editSerial.value = eq.serial_number || "";
    editNotes.value  = eq.notes || "";

    const imageUrl = buildImageUrl(eq);
    if (imageUrl) {
        editPhotoPreview.src = imageUrl;
        editPhotoPreview.classList.remove("hidden");
        editPhotoPlaceholder.classList.add("hidden");
    }

    currentImagePayload = null;

    currentEditingEq = eq;

    selectedPartIds = new Set((eq.parts || []).map(p => p.id));
    selectedPartMap = new Map((eq.parts || []).map(p => [p.id, p]));
    lockedPartIds = new Set((eq.parts || []).map(p => p.id));

    renderEditParts(eq);

    editModal.classList.remove("hidden");
    editModal.classList.add("flex");
}

function renderEditParts(eq) {
    editPartsList.innerHTML = "";

    const parts = Array.isArray(eq.parts) ? eq.parts : [];

    if (!parts.length) {
        const span = document.createElement("span");
        span.className = "text-[11px] text-slate-500";
        span.textContent = "Nenhuma peça vinculada a este equipamento.";
        editPartsList.appendChild(span);
        return;
    }

    parts.forEach((p) => {
        const chip = document.createElement("span");
        chip.className = "inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] text-slate-700";
        chip.innerHTML = `
      <span>${p.code ? p.code + " • " : ""}${p.name}</span>
    `;
        editPartsList.appendChild(chip);
    });
}

function closeEditModal() {
    lockScroll(false);

    editingId = null;
    resetForm();
    editModal.classList.add("hidden");
    editModal.classList.remove("flex");
}

async function deleteEquipment(id) {
    if (!confirm("Deseja realmente excluir este equipamento?")) return;

    try {
        await api(`/catalogs/equipment-api/${id}`, {
            method: "DELETE",
        });
        await loadEquipments();
    } catch (e) {
        console.error(e);
        alert("Erro ao excluir equipamento.");
    }
}

function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => {
            const result = String(reader.result || "");
            const [meta, b64] = result.split(",");
            const mimeMatch = meta.match(/data:(.*);base64/);
            const mime = mimeMatch ? mimeMatch[1] : "image/png";

            resolve({
                mime,
                data: b64,
                name: file.name,
                size: file.size,
            });
        };
        reader.onerror = (err) => reject(err);
        reader.readAsDataURL(file);
    });
}

// preview da imagem + prepara payload base64
if (editPhoto) {
    editPhoto.addEventListener("change", async () => {
        const file = editPhoto.files?.[0];
        if (!file) {
            editPhotoPreview.src = "";
            editPhotoPreview.classList.add("hidden");
            editPhotoPlaceholder.classList.remove("hidden");
            currentImagePayload = null;
            return;
        }

        try {
            const meta = await fileToBase64(file);
            currentImagePayload = meta;
            const src = `data:${meta.mime};base64,${meta.data}`;
            editPhotoPreview.src = src;
            editPhotoPreview.classList.remove("hidden");
            editPhotoPlaceholder.classList.add("hidden");
        } catch (e) {
            console.error(e);
            alert("Erro ao processar imagem.");
        }
    });
}

function ensurePartsPickerDom() {
    if (!partsPickerList) return;

    const invalid =
        !partsPickerSelectedWrap ||
        !partsPickerResultsWrap ||
        !partsPickerList.contains(partsPickerSelectedWrap) ||
        !partsPickerList.contains(partsPickerResultsWrap);

    if (invalid) {
        partsPickerList.innerHTML = `
      <div id="parts-picker-selected-wrap" class="space-y-2"></div>
      <div id="parts-picker-selected-sep" class="my-3 hidden border-t border-slate-100"></div>
      <div id="parts-picker-results-wrap" class="space-y-2"></div>
      <p id="parts-picker-only-empty" class="mt-2 hidden text-sm text-slate-500">
        Nenhuma peça selecionada.
      </p>
    `;

        partsPickerSelectedWrap = document.getElementById("parts-picker-selected-wrap");
        partsPickerResultsWrap  = document.getElementById("parts-picker-results-wrap");
        partsPickerSelectedSep  = document.getElementById("parts-picker-selected-sep");
        partsPickerOnlyEmpty    = document.getElementById("parts-picker-only-empty");
    }
}

function openPartsPickerModal() {
    lockScroll(true);

    if (!editingId) {
        alert("Salve o equipamento primeiro para vincular peças.");
        return;
    }

    ensurePartsPickerDom();

    partsPickerTitle.textContent = currentEditingEq?.name || "";
    partsPickerSearch.value = "";
    partsPickerOnlySel.checked = false;
    partsNextUrl = null;

    partsPickerSelectedWrap.innerHTML = "";
    partsPickerResultsWrap.innerHTML = "";

    partsTotal = 0;
    partsFrom = 0;
    partsTo = 0;
    updatePartsPickerChips(0);

    partsPickerModal.classList.remove("hidden");
    partsPickerModal.classList.add("flex");

    renderSelectedSection();
    syncPickerMode();
    refreshBulkButtons();

    selectedPageStack = [];
    refreshBulkButtons();

    loadPartsFirstPage().catch(console.error);
}

function closePartsPickerModal() {
    lockScroll(false);

    partsPickerModal.classList.add("hidden");
    partsPickerModal.classList.remove("flex");
}

function updatePartsPickerChips(lastLoadedCount) {
    const totalTxt = partsTotal ? ` de ${partsTotal}` : "";

    partsPickerCount.textContent = `${lastLoadedCount} itens carregados${totalTxt}`;
    partsPickerSelected.textContent = `${selectedPartIds.size} selecionada(s)`;
}

function debounce(fn, wait = 250) {
    let t = null;
    return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), wait);
    };
}

async function fetchPartsPage({ query, url }) {
    const endpoint =
        url || `/catalogs/part-api?q=${encodeURIComponent(query || "")}&per_page=30`; // <-- CONFERE A ROTA

    partsLoading = true;
    partsPickerLoading.classList.remove("invisible");

    try {
        const res = await fetch(endpoint, { credentials: "same-origin" });

        if (!res.ok) {
            const txt = await res.text();
            throw new Error(`Parts API erro (${res.status}): ${txt.slice(0, 200)}`);
        }

        const json = await res.json();

        partsNextUrl = json.next_page_url || null;
        partsTotal = json.total ?? 0;
        partsFrom = json.from ?? 0;
        partsTo = json.to ?? 0;

        return json.data || [];
    } finally {
        partsLoading = false;
        partsPickerLoading.classList.add("invisible");
    }
}

function renderPartsRows(rows, append = true) {
    if (!append) partsPickerResultsWrap.innerHTML = "";

    // modo atual
    syncPickerMode();

    // se está “só selecionadas”, não renderiza resultados
    if (partsPickerOnlySel.checked) {
        updatePartsPickerChips(partsPickerResultsWrap.querySelectorAll("label").length);
        return;
    }

    rows.forEach((p) => {
        // se já está selecionada, ela fica só em cima
        if (selectedPartIds.has(p.id)) return;

        partsPickerResultsWrap.appendChild(buildPartRow(p, false));
    });

    refreshBulkButtons();

    updatePartsPickerChips(partsPickerResultsWrap.querySelectorAll("label").length);
}

async function loadPartsFirstPage() {
    partsLastQuery = (partsPickerSearch.value || "").trim();
    const rows = await fetchPartsPage({ query: partsLastQuery });

    lastPageParts = rows; // <- batch atual
    renderPartsRows(rows, false);

    refreshBulkButtons();
}

async function loadPartsNextPage() {
    if (!partsNextUrl || partsLoading) return;

    const rows = await fetchPartsPage({ query: partsLastQuery, url: partsNextUrl });

    lastPageParts = rows; // <- batch atual vira o último carregado
    renderPartsRows(rows, true);

    refreshBulkButtons();
}

partsPickerList?.addEventListener("scroll", () => {
    if (bulkOpsLock) return; // <- ESSENCIAL

    const nearBottom =
        partsPickerList.scrollTop + partsPickerList.clientHeight >= partsPickerList.scrollHeight - 120;

    if (nearBottom) loadPartsNextPage();
});


partsPickerSearch?.addEventListener("input", debounce(() => {
    partsNextUrl = null;
    loadPartsFirstPage();
}, 250));

partsPickerOnlySel?.addEventListener("change", () => {
    renderSelectedSection();
    syncPickerMode();

    if (partsPickerOnlySel.checked) {
        partsPickerResultsWrap.innerHTML = "";
    } else {
        partsNextUrl = null;
        loadPartsFirstPage();
    }

    refreshBulkButtons();
});

partsPickerSave?.addEventListener("click", async () => {
    if (!editingId) return;

    try {
        await api(`/catalogs/equipment-api/${editingId}`, {
            method: "PATCH",
            body: JSON.stringify({ part_ids: Array.from(selectedPartIds) }),
        });

        await loadEquipments();

        const updated = equipments.find(e => e.id === editingId);
        if (updated) {
            currentEditingEq = updated;
            renderEditParts(updated); // atualiza chips no modal de editar
        }

        closePartsPickerModal();
    } catch (e) {
        console.error(e);
        alert("Erro ao salvar peças do equipamento.");
    }
});

btnManageParts?.addEventListener("click", openPartsPickerModal);

partsPickerClose?.addEventListener("click", closePartsPickerModal);
partsPickerCloseX?.addEventListener("click", closePartsPickerModal);
partsPickerModal?.addEventListener("click", (e) => {
    if (e.target === partsPickerModal) closePartsPickerModal();
});

function syncPickerMode() {
    const only = partsPickerOnlySel.checked;

    if (only) {
        partsPickerResultsWrap.classList.add("hidden");
        partsPickerSelectedSep.classList.add("hidden");
        partsPickerOnlyEmpty.classList.toggle("hidden", selectedPartIds.size > 0);
    } else {
        partsPickerResultsWrap.classList.remove("hidden");
        partsPickerOnlyEmpty.classList.add("hidden");
        partsPickerSelectedSep.classList.toggle("hidden", selectedPartIds.size === 0);
    }
}

function buildPartRow(p, checked) {
    const isLocked = lockedPartIds.has(p.id);
    const isChecked = checked; // aqui quem decide é o "checked" que você passa

    const row = document.createElement("label");
    row.className =
        "flex items-center gap-3 rounded-xl border px-3 py-2 cursor-pointer " +
        (isChecked
            ? "border-blue-200 bg-blue-50 hover:bg-blue-100"
            : "border-slate-200 bg-white hover:bg-slate-50");

    row.innerHTML = `
    <input type="checkbox"
      class="parts-picker-cb rounded border-slate-300"
      ${isChecked ? "checked" : ""}
      data-id="${p.id}">
    <div class="min-w-0 flex-1">
      <p class="text-xs font-medium text-slate-900 truncate">${p.name || "-"}</p>
      <p class="text-[11px] text-slate-500 truncate">
        ${p.code ? "Cód. " + p.code : ""} ${p.ncm_code ? " • NCM " + p.ncm_code : ""}
        ${isLocked ? " • Vinculada" : ""}
      </p>
    </div>
    <div class="text-[11px] font-medium text-slate-600">
      ${p.unit_price != null ? Number(p.unit_price).toFixed(2) : ""}
    </div>
  `;

    row.__partPayload = p;

    const cb = row.querySelector(".parts-picker-cb");
    cb.addEventListener("change", () => {
        const id = cb.getAttribute("data-id");
        if (!id) return;

        if (cb.checked) {
            selectedPartIds.add(id);
            selectedPartMap.set(id, p);

            // se estava “desvinculada manualmente”, não volta a ser locked
            // (locked só é o estado inicial vindo do banco)
        } else {
            selectedPartIds.delete(id);
            selectedPartMap.delete(id);

            // IMPORTANTE: se ela era do banco, agora o usuário manualmente desvinculou
            lockedPartIds.delete(id);
        }

        renderSelectedSection();

        // evita duplicar (em cima + embaixo)
        if (cb.checked && !partsPickerOnlySel.checked) row.remove();

        updatePartsPickerChips(partsPickerResultsWrap.querySelectorAll("label").length);
        syncPickerMode();
        refreshBulkButtons();
    });

    return row;
}

function renderSelectedSection() {
    partsPickerSelectedWrap.innerHTML = "";

    const selected = Array.from(selectedPartMap.values())
        .sort((a,b) => String(a.name||"").localeCompare(String(b.name||"")));

    selected.forEach((p) => {
        partsPickerSelectedWrap.appendChild(buildPartRow(p, true));
    });

    partsPickerSelectedSep.classList.toggle("hidden", selected.length === 0);
    partsPickerOnlyEmpty.classList.toggle("hidden", selected.length > 0);

    refreshBulkButtons();
}

function openPartsModal(eq) {
    partsTitle.textContent = eq.name || "";
    partsList.innerHTML = "";

    const parts = Array.isArray(eq.parts) ? eq.parts : [];

    if (!parts.length) {
        partsEmpty.classList.remove("hidden");
    } else {
        partsEmpty.classList.add("hidden");

        parts.forEach((p) => {
            const li = document.createElement("li");
            li.className = "flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2";
            li.innerHTML = `
        <div class="min-w-0">
          <p class="text-xs font-medium text-slate-800 truncate">
            ${p.name || "-"}
          </p>
          <p class="text-[11px] text-slate-500 truncate">
            ${p.code ? "Cód. " + p.code : ""} ${p.ncm_code ? "• NCM " + p.ncm_code : ""}
          </p>
        </div>
        <span class="text-[11px] font-medium text-slate-600">
          ${p.unit_price != null ? Number(p.unit_price).toFixed(2) : ""}
        </span>
      `;
            partsList.appendChild(li);
        });
    }

    partsModal.classList.remove("hidden");
    partsModal.classList.add("flex");
}

function closePartsModal() {
    lockScroll(false);

    partsModal.classList.add("hidden");
    partsModal.classList.remove("flex");
}

partsClose.addEventListener("click", closePartsModal);
partsCloseX.addEventListener("click", closePartsModal);
partsModal.addEventListener("click", (e) => {
    if (e.target === partsModal) closePartsModal();
});

// ---- Catálogo (iframe) ----
function normalizePdfValue(val) {
    if (!val) return null;

    if (typeof val === "string") {
        const s = val.trim();

        // se veio como JSON string, converte
        if ((s.startsWith("{") && s.endsWith("}")) || (s.startsWith("[") && s.endsWith("]"))) {
            try { return JSON.parse(s); } catch (_) { return s; }
        }

        // pode ser URL normal ou data:
        return s;
    }

    if (typeof val === "object") return val;

    return null;
}

function pdfPayloadToBlobUrl(payload) {
    const mime = payload?.mime || "application/pdf";
    const b64  = payload?.data || "";
    if (!b64) return "";

    const bin = atob(b64);
    const bytes = new Uint8Array(bin.length);
    for (let i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);

    const blob = new Blob([bytes], { type: mime });
    return URL.createObjectURL(blob);
}

function openCatalogModal(eq, opts = {}) {
    const mode = opts.mode || "catalog"; // default: só catálogo

    catalogTitle.textContent = eq.name || "";
    currentCatalogEqId = eq.id;
    currentCatalogPayload = null;

    setCatalogMode(mode);

    // se for details, preenche painel de cima
    if (mode === "details") {
        fillCatalogSide(eq);
    }

    if (catalogSaveBtn) catalogSaveBtn.disabled = true;
    if (catalogFileName) catalogFileName.textContent = "";
    if (catalogUpload) catalogUpload.value = "";

    if (currentCatalogObjectUrl) {
        URL.revokeObjectURL(currentCatalogObjectUrl);
        currentCatalogObjectUrl = null;
    }

    const pdfUrl = buildPdfUrl(eq);
    const extra = eq.extra_info || eq.extraInfo || null;
    const iframeUrl = extra?.iframe_url || "";
    const urlToShow = pdfUrl || iframeUrl || "";

    if (urlToShow) {
        catalogIframe.src = urlToShow;
        catalogIframe.classList.remove("hidden");
        catalogEmpty.classList.add("hidden");
    } else {
        catalogIframe.src = "";
        catalogIframe.classList.add("hidden");
        catalogEmpty.classList.remove("hidden");
        catalogEmpty.classList.add("flex");
    }

    catalogModal.classList.remove("hidden");
    catalogModal.classList.add("flex");
}

function fillCatalogSide(eq) {
    if (!catalogDetails || catalogDetails.classList.contains("hidden")) return;

    // nome/desc
    if (catalogEqName)  catalogEqName.textContent = eq?.name || "-";
    if (catalogEqDesc)  catalogEqDesc.textContent = eq?.description || "";

    // code/serial
    if (catalogEqCode)  catalogEqCode.textContent = eq?.code || "-";
    if (catalogEqSerial) catalogEqSerial.textContent = eq?.serial_number || "-";

    // imagem
    const imageUrl = buildImageUrl(eq);

    if (catalogEqImage && catalogEqImageEmpty) {
        if (imageUrl) {
            catalogEqImage.src = imageUrl;
            catalogEqImage.classList.remove("hidden");
            catalogEqImageEmpty.classList.add("hidden");
        } else {
            catalogEqImage.src = "";
            catalogEqImage.classList.add("hidden");
            catalogEqImageEmpty.classList.remove("hidden");
        }
    }

    // peças
    if (catalogEqParts) catalogEqParts.innerHTML = "";
    const parts = Array.isArray(eq?.parts) ? eq.parts : [];

    if (!parts.length) {
        catalogEqPartsEmpty?.classList.remove("hidden");
    } else {
        catalogEqPartsEmpty?.classList.add("hidden");

        parts.slice(0, 12).forEach(p => {
            const chip = document.createElement("span");
            chip.className = "inline-flex items-center rounded-full bg-white px-2.5 py-1 text-[11px] text-slate-700 border border-slate-200";
            chip.textContent = `${p.code ? p.code + " • " : ""}${p.name || "-"}`;
            catalogEqParts.appendChild(chip);
        });

        if (parts.length > 12) {
            const more = document.createElement("span");
            more.className = "inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-medium text-blue-700";
            more.textContent = `+${parts.length - 12}`;
            catalogEqParts.appendChild(more);
        }
    }

    // botões esquerda
    if (catalogOpenEdit) {
        catalogOpenEdit.onclick = () => openEditModal(eq);
    }
    if (catalogOpenParts) {
        catalogOpenParts.onclick = () => openPartsModal(eq);
    }
}

function setCatalogMode(mode) {
    // mode: "catalog" | "details"
    if (!catalogDetails) return;

    if (mode === "details") {
        catalogDetails.classList.remove("hidden");
    } else {
        catalogDetails.classList.add("hidden");
    }
}

function buildPdfUrl(eq) {
    const extra = eq.extra_info || eq.extraInfo || null;
    if (!extra) return "";

    const raw = normalizePdfValue(extra.catalog_pdf);
    if (!raw) return "";

    // string: pode ser URL normal ou data:application/pdf;base64,...
    if (typeof raw === "string") return raw;

    // object: { mime, data, name, size } (base64)
    if (typeof raw === "object") {
        if (raw.url) return String(raw.url);

        if (raw.data) {
            // preferir Blob URL (evita iframe travar com data URL gigante)
            const blobUrl = pdfPayloadToBlobUrl(raw);
            if (blobUrl) {
                // guarda pra revogar no closeCatalogModal()
                currentCatalogObjectUrl = blobUrl;
                return blobUrl;
            }

            const mime = raw.mime || "application/pdf";
            return `data:${mime};base64,${raw.data}`;
        }
    }

    return "";
}

if (catalogUploadBtn && catalogUpload) {
    catalogUploadBtn.addEventListener("click", () => catalogUpload.click());
}

if (catalogUpload) {
    catalogUpload.addEventListener("change", async () => {
        const file = catalogUpload.files?.[0];
        if (!file) return;

        if (file.type !== "application/pdf") {
            alert("Envie um PDF.");
            catalogUpload.value = "";
            return;
        }

        // preview rápido (objectURL)
        if (currentCatalogObjectUrl) URL.revokeObjectURL(currentCatalogObjectUrl);
        currentCatalogObjectUrl = URL.createObjectURL(file);

        catalogIframe.src = currentCatalogObjectUrl;
        catalogIframe.classList.remove("hidden");
        catalogEmpty.classList.add("hidden");

        if (catalogFileName) catalogFileName.textContent = file.name;

        // payload base64 (reusa sua fileToBase64)
        const meta = await fileToBase64(file);
        currentCatalogPayload = meta;

        if (catalogSaveBtn) catalogSaveBtn.disabled = false;
    });
}

if (catalogSaveBtn) {
    catalogSaveBtn.addEventListener("click", async () => {
        if (!currentCatalogEqId || !currentCatalogPayload) return;

        try {
            await api(`/catalogs/equipment-api/${currentCatalogEqId}`, {
                method: "PATCH",
                body: JSON.stringify({ extra_catalog_pdf: currentCatalogPayload }),
            });

            await loadEquipments();

            const updated = equipments.find(e => e.id === currentCatalogEqId);
            if (updated) openCatalogModal(updated);
            else closeCatalogModal();
        } catch (e) {
            console.error(e);
            alert("Erro ao salvar catálogo.");
        }
    });
}

function closeCatalogModal() {
    catalogIframe.src = "";

    if (currentCatalogObjectUrl) {
        URL.revokeObjectURL(currentCatalogObjectUrl);
        currentCatalogObjectUrl = null;
    }

    currentCatalogEqId = null;
    currentCatalogPayload = null;
    if (catalogUpload) catalogUpload.value = "";
    if (catalogFileName) catalogFileName.textContent = "";
    if (catalogSaveBtn) catalogSaveBtn.disabled = true;

    catalogModal.classList.add("hidden");
    catalogModal.classList.remove("flex");
}


catalogClose.addEventListener("click", closeCatalogModal);
catalogCloseX.addEventListener("click", closeCatalogModal);
catalogModal.addEventListener("click", (e) => {
    if (e.target === catalogModal) closeCatalogModal();
});

// submit (create/update)
editForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    const payload = {
        code: (editCode.value || "").trim() || null,
        name: (editName.value || "").trim(),
        description: (editModel.value || "").trim() || null,
        serial_number: (editSerial.value || "").trim() || null,
        notes: (editNotes.value || "").trim() || null,
    };

    if (!payload.name) {
        alert("Informe o nome do equipamento.");
        return;
    }

    // só envia extra_image_path se usuário escolheu arquivo
    if (currentImagePayload) {
        payload.extra_image_path = currentImagePayload;
    }

    try {
        if (!editingId) {
            await api("/catalogs/equipment-api", {
                method: "POST",
                body: JSON.stringify(payload),
            });
        } else {
            await api(`/catalogs/equipment-api/${editingId}`, {
                method: "PUT",
                body: JSON.stringify(payload),
            });
        }

        await loadEquipments();
        closeEditModal();
    } catch (e) {
        console.error(e);
        alert("Erro ao salvar equipamento.");
    }
});

function refreshBulkButtons() {
    // botão selecionar: sempre ativo (exceto modo "só selecionadas")
    if (partsPickerSelectPage) {
        const disabled = partsPickerOnlySel.checked || lastPageParts.length === 0;
        partsPickerSelectPage.disabled = disabled;
        partsPickerSelectPage.classList.toggle("opacity-50", disabled);
        partsPickerSelectPage.classList.toggle("cursor-not-allowed", disabled);
    }

    // botão desmarcar lote: ativo só se tiver lote na pilha
    if (partsPickerUnselectBatch) {
        const disabled = selectedPageStack.length === 0;
        partsPickerUnselectBatch.disabled = disabled;
        partsPickerUnselectBatch.classList.toggle("opacity-50", disabled);
        partsPickerUnselectBatch.classList.toggle("cursor-not-allowed", disabled);
    }
}

function selectCurrentBatch() {
    if (partsPickerOnlySel.checked) return;
    if (!lastPageParts.length) return;

    bulkOpsLock = true;

    // ids elegíveis (não travadas e não selecionadas ainda)
    const toSelect = lastPageParts.filter(p =>
        p?.id && !selectedPartIds.has(p.id)
    );

    if (!toSelect.length) {
        bulkOpsLock = false;
        return;
    }

    // seleciona e salva o lote na pilha
    const batchIds = [];
    toSelect.forEach(p => {
        selectedPartIds.add(p.id);
        selectedPartMap.set(p.id, p);
        batchIds.push(p.id);

        // remove da lista de baixo se estiver renderizado
        const row = partsPickerResultsWrap.querySelector(`.parts-picker-cb[data-id="${CSS.escape(p.id)}"]`)?.closest("label");
        if (row) row.remove();
    });

    selectedPageStack.push(batchIds);

    renderSelectedSection();
    syncPickerMode();
    updatePartsPickerChips(partsPickerResultsWrap.querySelectorAll("label").length);
    refreshBulkButtons();

    // solta trava depois de estabilizar DOM (evita scroll carregar next)
    requestAnimationFrame(() => requestAnimationFrame(() => { bulkOpsLock = false; }));
}

function unselectLastBatch() {
    if (!selectedPageStack.length) return;

    bulkOpsLock = true;

    const batchIds = selectedPageStack.pop() || [];

    batchIds.forEach(id => {
        if (!id) return;

        if (lockedPartIds.has(id)) return; // <- só aqui bloqueia

        selectedPartIds.delete(id);
        selectedPartMap.delete(id);
    });

    renderSelectedSection();
    syncPickerMode();
    updatePartsPickerChips(partsPickerResultsWrap.querySelectorAll("label").length);
    refreshBulkButtons();

    requestAnimationFrame(() => requestAnimationFrame(() => { bulkOpsLock = false; }));
}

// eventos de UI
partsPickerSelectPage?.addEventListener("click", selectCurrentBatch);
partsPickerUnselectBatch?.addEventListener("click", unselectLastBatch);
btnAddEquipment?.addEventListener("click", openCreateModal);
btnCloseModal?.addEventListener("click", closeEditModal);
btnCancelModal?.addEventListener("click", closeEditModal);
editModal?.addEventListener("click", (e) => {
    if (e.target === editModal) closeEditModal();
});

btnGoToPieces?.addEventListener("click", () => {
    // rota do cadastro de peças
    window.location.href = "/catalogs/part";
});

// busca local
searchInput?.addEventListener("input", () => {
    renderCards();
});

document.addEventListener("keydown", (e) => {
    if (e.key !== "Escape") return;

    if (!partsPickerModal.classList.contains("hidden")) return closePartsPickerModal();
    if (!catalogModal.classList.contains("hidden")) return closeCatalogModal();
    if (!partsModal.classList.contains("hidden")) return closePartsModal();
    if (!editModal.classList.contains("hidden")) return closeEditModal();
});

function lockScroll(lock) {
    document.documentElement.classList.toggle("overflow-hidden", lock);
    document.body.classList.toggle("overflow-hidden", lock);
}

// load inicial
loadEquipments();
