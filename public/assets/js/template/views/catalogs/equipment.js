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

let equipments = [];
let editingId  = null;
// imagem nova que será salva (base64 JSON). Se null = não mexer.
let currentImagePayload = null;

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
      <span>${partsCount} peça(s)</span>
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

        const openDetails = () => {
            // rota de detalhe (quando existir)
            window.location.href = `/catalogs/equipment/${encodeURIComponent(
                eq.id
            )}`;
        };

        card.addEventListener("click", (e) => {
            if (
                e.target.closest(".btn-edit") ||
                e.target.closest(".btn-delete") ||
                e.target.closest(".btn-view")
            )
                return;
            openDetails();
        });

        const viewBtn   = card.querySelector(".btn-view");
        const editBtn   = card.querySelector(".btn-edit");
        const deleteBtn = card.querySelector(".btn-delete");
        const partsBtn = card.querySelector(".js-open-parts");
        partsBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            openPartsModal(eq);
        });

        viewBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            openCatalogModal(eq);
        });

        viewBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            openDetails();
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
    editingId = null;
    resetForm();
    editModalTitle.textContent = "Novo equipamento";
    editModal.classList.remove("hidden");
    editModal.classList.add("flex");
}

function openEditModal(eq) {
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

    // currentImagePayload fica null => não altera imagem se usuário não trocar
    currentImagePayload = null;

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
    partsModal.classList.add("hidden");
    partsModal.classList.remove("flex");
}

partsClose.addEventListener("click", closePartsModal);
partsCloseX.addEventListener("click", closePartsModal);
partsModal.addEventListener("click", (e) => {
    if (e.target === partsModal) closePartsModal();
});

// ---- Catálogo (iframe) ----

function openCatalogModal(eq) {
    catalogTitle.textContent = eq.name || "";

    // extra_info vem da relação Equipment::extraInfo()
    const extra = eq.extra_info || eq.extraInfo || null;
    const url   = extra && extra.iframe_url ? extra.iframe_url : "";

    if (url) {
        catalogIframe.src = url;
        catalogIframe.classList.remove("hidden");
        catalogEmpty.classList.add("hidden");
    } else {
        catalogIframe.src = "";
        catalogIframe.classList.add("hidden");
        catalogEmpty.classList.remove("hidden");
    }

    catalogModal.classList.remove("hidden");
    catalogModal.classList.add("flex");
}

function closeCatalogModal() {
    catalogIframe.src = "";
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

// eventos de UI
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

// load inicial
loadEquipments();
