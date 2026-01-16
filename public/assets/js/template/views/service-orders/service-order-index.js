// assets/js/template/views/service-orders/service-order-index.js

document.addEventListener("DOMContentLoaded", () => {
    const searchInput = document.querySelector("#search");
    const statusButtons = document.querySelectorAll("[data-status-filter]");

    const state = {
        status: "", // "", "draft", "pending", "approved", "completed", "rejected"
    };

    // load inicial
    loadOrders(state);

    // busca com debounce
    if (searchInput) {
        searchInput.addEventListener(
            "input",
            debounce(() => loadOrders(state), 300)
        );
    }

    // filtros de status (chips)
    statusButtons.forEach((btn) => {
        btn.addEventListener("click", () => {
            statusButtons.forEach((b) => b.classList.remove("active-status"));
            btn.classList.add("active-status");
            state.status = btn.getAttribute("data-status-filter") || "";
            loadOrders(state);
        });
    });

    // se o botão "Nova OS" for <button>, redireciona
    const btnAdd = document.querySelector("#btn-add");
    if (btnAdd && btnAdd.tagName === "BUTTON") {
        btnAdd.addEventListener("click", () => {
            window.location.href = "/service-orders/create";
        });
    }
});

function debounce(fn, delay = 300) {
    let t;
    return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), delay);
    };
}

async function loadOrders(state, pageUrl) {
    const tbody = document.querySelector("#tbody");
    const searchInput = document.querySelector("#search");

    if (!tbody) return;

    const baseUrl = pageUrl || "/service-orders/service-order-api";
    const url = new URL(baseUrl, window.location.origin);

    const term = (searchInput?.value || "").trim();
    if (term) url.searchParams.set("q", term);
    if (state.status) url.searchParams.set("status", state.status);

    try {
        const resp = await fetch(url.toString(), {
            headers: {Accept: "application/json"},
        });

        if (!resp.ok) {
            console.error("Erro ao carregar OS:", await resp.text());
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-6 py-6 text-center text-sm text-rose-500">
                        Erro ao carregar ordens de serviço.
                    </td>
                </tr>`;
            return;
        }

        const json = await resp.json();
        const items = json.data || json;

        if (!Array.isArray(items) || !items.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-6 py-6 text-center text-sm text-slate-500">
                        Nenhuma OS encontrada.
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = items.map(renderRow).join("");
    } catch (e) {
        console.error(e);
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="px-6 py-6 text-center text-sm text-rose-500">
                    Erro inesperado ao carregar ordens de serviço.
                </td>
            </tr>`;
    }
}

function renderRow(os) {
    function formatDatePtBR(value) {
        if (!value) return "-";
        const d = new Date(value);
        if (isNaN(d.getTime())) return value;

        const date = d.toLocaleDateString("pt-BR");
        const time = d.toLocaleTimeString("pt-BR", { hour: "2-digit", minute: "2-digit" });
        return `${date} ás ${time}`;
    }

    const status = os.status || "draft";
    const label  = os.status_label || status;

    const badgeMap = {
        draft: "bg-slate-100 text-slate-700",
        pending: "bg-amber-50 text-amber-700",
        approved: "bg-emerald-50 text-emerald-700",
        rejected: "bg-rose-50 text-rose-700",
        completed: "bg-sky-50 text-sky-700",
    };

    const badgeClass = badgeMap[status] || "bg-slate-100 text-slate-700";

    const customerName =
        (os.secondary_customer && os.secondary_customer.name) ||
        os.client_name ||
        "-";

    const total = Number(os.grand_total || 0).toFixed(2);
    const orderDate = formatDatePtBR(os.order_date || os.created_at);

    return `
        <tr class="hover:bg-slate-50">
    <td class="px-6 py-3 text-left whitespace-nowrap">
        ${os.order_number || "-"}
    </td>
    <td class="px-3 py-3 text-left">
        ${customerName}
    </td>
    <td class="px-3 py-3 text-center whitespace-nowrap">
        R$ ${total}
    </td>
    <td class="px-3 py-3 text-center">
        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${badgeClass}">
            ${label}
        </span>
    </td>

    <td class="px-3 py-3 text-center whitespace-nowrap">
        ${orderDate}
    </td>
    <td class="px-6 py-3 text-center whitespace-nowrap">
        <div class="relative inline-block text-left">
            <button type="button"
                    class="rounded-lg p-2 hover:bg-slate-100 focus:outline-none"
                    data-menu-trigger
                    data-id="${os.id}"
                    aria-haspopup="true"
                    aria-expanded="false">⋮</button>

            <div id="menu-${os.id}"
                 class="hidden absolute right-0 top-full z-50 mt-2 w-48 rounded-2xl overflow-hidden border border-slate-200 bg-white shadow-xl ring-1 ring-black/5"
                 data-menu
                 data-for="${os.id}">
                <ul class="py-1 text-sm text-slate-700">
                    <li>
                        <button type="button"
                                class="block w-full px-3 py-2 text-left bg-transparent rounded-none hover:bg-slate-50 focus:outline-none"
                                data-view
                                data-id="${os.id}">
                            Visualizar OS
                        </button>
                    </li>
                    <li>
                      <button type="button" class="block w-full px-3 py-2 text-left bg-transparent rounded-none hover:bg-slate-50 focus:outline-none" data-download data-id="${os.id}">Baixar PDF</button>
                    </li>
                    <li>
                      <button type="button" class="block w-full px-3 py-2 text-left bg-transparent rounded-none hover:bg-slate-50 focus:outline-none" data-email data-to="${os.secondary_customer.email}" data-number="${os.order_number}" data-id="${os.id}">Enviar por e-mail</button>
                    </li>
                    <li>
                      <button type="button" class="block w-full px-3 py-2 text-left bg-transparent rounded-none hover:bg-slate-50 focus:outline-none" data-dup data-id="${os.id}">Duplicar OS</button>
                    </li>
                    <li>
                        <button type="button"
                                class="block w-full px-3 py-2 text-left bg-transparent rounded-none hover:bg-slate-50 focus:outline-none"
                                data-edit
                                data-id="${os.id}">
                            Editar OS
                        </button>
                    </li>
                    <li class="border-t border-slate-200 mt-1 pt-1">
                        <button type="button"
                                class="block w-full px-3 py-2 text-left bg-transparent rounded-none text-rose-600 hover:bg-rose-50 focus:outline-none"
                                data-del
                                data-id="${os.id}">
                            Excluir OS
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </td>
</tr>`;
}


function closeAllMenus() {
    document.querySelectorAll("[data-menu]").forEach((m) => m.classList.add("hidden"));
}

document.addEventListener("click", async (e) => {
    const trigger = e.target.closest("[data-menu-trigger]");
    const actionView = e.target.closest("[data-view]");
    const actionEdit = e.target.closest("[data-edit]");
    const actionDel = e.target.closest("[data-del]");
    const menuEl = e.target.closest("[data-menu]");
    const actionPdf = e.target.closest("[data-pdf]");
    const actionDownload = e.target.closest("[data-download]");
    const actionEmail = e.target.closest("[data-email]");
    const actionDup = e.target.closest("[data-dup]");

    // clicou fora de qualquer menu/trigger -> fecha
    if (!trigger && !menuEl) closeAllMenus();

    // abrir/fechar menu
    if (trigger) {
        e.preventDefault();

        const id = trigger.dataset.id;
        const menu = document.querySelector(`#menu-${id}`);
        if (!menu) return;

        const isHidden = menu.classList.contains("hidden");
        closeAllMenus();
        if (isHidden) menu.classList.remove("hidden");
        return;
    }

    // ações
    if (actionView) {
        e.preventDefault();
        closeAllMenus();
        const id = actionView.dataset.id;
        if (id) window.open(`/service-orders/${id}/pdf`, "_blank");
        return;
    }

    if (actionEdit) {
        e.preventDefault();
        closeAllMenus();
        const id = actionEdit.dataset.id;
        if (id) window.location.href = `/service-orders/service-order/${id}/edit`;
        return;
    }

    if (actionDel) {
        e.preventDefault();
        closeAllMenus();

        const id = actionDel.dataset.id;
        const tr = actionDel.closest("tr");
        const osNumber = tr?.querySelector("td")?.textContent?.trim() || "";
        const client = tr?.querySelectorAll("td")[1]?.textContent?.trim() || "";

        if (id) openDeleteDialog(id, osNumber && client ? `OS ${osNumber} • ${client}` : "");
        return;
    }

    if (actionPdf) {
        e.preventDefault(); closeAllMenus();
        const id = actionPdf.dataset.id;
        window.open(`/service-orders/${id}/pdf`, "_blank");
        return;
    }

    if (actionDownload) {
        e.preventDefault(); closeAllMenus();
        const id = actionDownload.dataset.id;
        window.open(`/service-orders/${id}/pdf/download`, "_blank");
        return;
    }

    if (actionEmail) {
        e.preventDefault();
        closeAllMenus();

        const id = actionEmail.dataset.id;
        if (!id) return;

        // defaults
        openEmailDialog({
            id,
            to: actionEmail.dataset.to || "", // opcional
            subject: `Ordem de Serviço #${actionEmail.dataset.number || ""}`.trim(),
            message: `Olá! Segue em anexo a Ordem de Serviço para conferência.\n\nQualquer dúvida, me chame.`,
        });

        return;
    }

    if (actionDup) {
        e.preventDefault();
        closeAllMenus();
        const id = actionDup.dataset.id;
        duplicateOs(id);
        return;
    }
});

async function duplicateOs(id) {
    if (!id) return;

    const resp = await fetch(`/service-orders/${id}/duplicate`, {
        method: "POST",
        headers: {
            "Accept": "application/json",
            "X-Requested-With": "XMLHttpRequest",
            "X-CSRF-TOKEN": getCsrfToken(),
        },
    });

    const json = await resp.json().catch(() => null);

    if (!resp.ok) {
        console.error("duplicate error:", json || (await resp.text()));
        return;
    }

    const newId = json?.id;
    if (!newId) {
        console.error("duplicate: id não veio no JSON", json);
        return;
    }

    // aqui sim vai pra tela de editar (create com id)
    window.location.href = `/service-orders/service-order/create/${newId}`;
}

// ===== Modal delete (dialog) =====
const dlgDelete = document.querySelector("#confirm-delete");
const btnDelYes = document.querySelector("#confirm-delete-yes");
const btnDelNo  = document.querySelector("#confirm-delete-no");

let deleteTargetId = null;
let deleting = false;

const btnDelX = document.querySelector("#confirm-delete-no-x");
btnDelX?.addEventListener("click", () => closeDeleteDialog());

function openDeleteDialog(id, metaText = "") {
    if (!dlgDelete) return;
    deleteTargetId = id;

    const meta = document.querySelector("#confirm-delete-meta");
    if (meta) {
        if (metaText) { meta.textContent = metaText; meta.classList.remove("hidden"); }
        else { meta.textContent = ""; meta.classList.add("hidden"); }
    }

    dlgDelete.showModal();
}

function closeDeleteDialog() {
    if (!dlgDelete) return;
    deleteTargetId = null;
    dlgDelete.close();
}

btnDelNo?.addEventListener("click", () => closeDeleteDialog());

// se apertar ESC, garante reset
dlgDelete?.addEventListener("close", () => {
    deleteTargetId = null;
    deleting = false;
    if (btnDelYes) {
        btnDelYes.disabled = false;
        btnDelYes.innerHTML = "Excluir";
    }
});

// confirma exclusão
btnDelYes?.addEventListener("click", async () => {
    if (!deleteTargetId || deleting) return;
    deleting = true;

    const original = btnDelYes.innerHTML;
    btnDelYes.disabled = true;
    btnDelYes.innerHTML = `
    <span class="inline-flex items-center gap-2">
      <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
      </svg>
      Excluindo...
    </span>
  `;

    try {
        const resp = await fetch(`/service-orders/service-order-api/${deleteTargetId}`, {
            method: "DELETE",
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": getCsrfToken(),
            },
        });

        if (!resp.ok) {
            console.error("Erro ao excluir OS:", await resp.text());
            alert("Não foi possível excluir esta OS.");
            return;
        }

        await new Promise((r) => setTimeout(r, 2500));

        closeDeleteDialog();
        await loadOrders({ status: getCurrentStatusFilter() });
    } catch (err) {
        console.error(err);
        alert("Erro inesperado ao excluir OS.");
    } finally {
        btnDelYes.disabled = false;
        btnDelYes.innerHTML = original;
        deleting = false;
    }
});

// ===== Modal email =====
const dlgMail = document.querySelector("#send-email");
const btnMailYes = document.querySelector("#send-email-yes");
const btnMailNo  = document.querySelector("#send-email-no");

const inMailTo = document.querySelector("#mail_to");
const inMailSubject = document.querySelector("#mail_subject");
const inMailMessage = document.querySelector("#mail_message");

let mailTargetId = null;
let mailSending = false;

function openEmailDialog({ id, to, subject, message }) {
    mailTargetId = id;

    if (inMailTo) inMailTo.value = to || "";
    if (inMailSubject) inMailSubject.value = subject || "";
    if (inMailMessage) inMailMessage.value = message || "";

    dlgMail?.showModal();
}

function closeEmailDialog() {
    mailTargetId = null;
    dlgMail?.close();
}

btnMailNo?.addEventListener("click", () => closeEmailDialog());

dlgMail?.addEventListener("close", () => {
    mailTargetId = null;
    mailSending = false;
    if (btnMailYes) {
        btnMailYes.disabled = false;
        btnMailYes.innerHTML = "Enviar";
    }
});

btnMailYes?.addEventListener("click", async () => {
    if (!mailTargetId || mailSending) return;
    mailSending = true;

    const original = btnMailYes.innerHTML;
    btnMailYes.disabled = true;
    btnMailYes.innerHTML = "Enviando...";

    const payload = {
        to: (inMailTo?.value || "").trim() || null,
        subject: (inMailSubject?.value || "").trim() || null,
        message: (inMailMessage?.value || "").trim() || null,
    };

    try {
        const resp = await fetch(`/service-orders/${mailTargetId}/email`, {
            method: "POST",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": getCsrfToken(),
            },
            body: JSON.stringify(payload),
        });

        if (!resp.ok) {
            const txt = await resp.text();
            showEmailFeedback("error", "Falha ao enviar", "Verifique o e-mail e tente novamente.");
            console.error(txt);
            return;
        }

        showEmailFeedback("success", "Enviado!", "E-mail enviado com sucesso.");
    } catch (err) {
        console.error(err);
        alert("Erro inesperado ao enviar e-mail.");
    } finally {
        btnMailYes.disabled = false;
        btnMailYes.innerHTML = original;
        mailSending = false;
    }
});

function showEmailFeedback(type, title, msg) {
    const box = document.querySelector("#email-feedback");
    const t = document.querySelector("#email-feedback-title");
    const m = document.querySelector("#email-feedback-msg");
    if (!box || !t || !m) return;

    // type: "success" | "error"
    if (type === "success") {
        box.style.display = "block";
        box.style.background = "#ecfdf5";
        box.style.border = "1px solid #a7f3d0";
        box.style.color = "#065f46";
    } else {
        box.style.display = "block";
        box.style.background = "#fff1f2";
        box.style.border = "1px solid #fecdd3";
        box.style.color = "#9f1239";
    }

    t.textContent = title;
    m.textContent = msg || "";
}

function clearEmailFeedback() {
    const box = document.querySelector("#email-feedback");
    if (box) box.style.display = "none";
}

function getCurrentStatusFilter() {
    const active = document.querySelector("[data-status-filter].active-status");
    return active ? active.getAttribute("data-status-filter") || "" : "";
}

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute("content") : "";
}
