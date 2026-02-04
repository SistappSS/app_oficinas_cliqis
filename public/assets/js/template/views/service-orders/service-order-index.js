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

function normalizeStatus(os) {
    const s = String(os?.status || "draft").toLowerCase();

    // garante compatibilidade caso algum endpoint ainda mande "invoiced"
    if (["nf_emitida", "nf-emitida", "invoiced"].includes(s)) return "nf_emitida";

    // fallback por campos/flags se existirem
    if (os?.nf_emitida === true || os?.nf_emitted === true || !!os?.invoice_id) return "nf_emitida";

    return s;
}

function getStatusLabel(os, normalizedStatus) {
    if (normalizedStatus === "nf_emitida") return "NF EMITIDA";

    const raw = (os?.status_label || "").trim();
    if (raw) return raw;

    const map = {
        draft: "Rascunho",
        pending: "Pendente",
        approved: "Aprovada",
        rejected: "Rejeitada",
        completed: "Concluída",
    };

    return map[normalizedStatus] || normalizedStatus;
}

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
        return `${date}`;
    }

    const status = normalizeStatus(os);
    const label  = getStatusLabel(os, status);

    const badgeMap = {
        draft: "bg-slate-100 text-slate-700",
        pending: "bg-amber-50 text-amber-700",
        approved: "bg-emerald-50 text-emerald-700",
        rejected: "bg-rose-50 text-rose-700",
        completed: "bg-slate-100 text-slate-700",
        nf_emitida: "bg-blue-50 text-blue-700",
    };

    const badgeClass = badgeMap[status] || "bg-slate-100 text-slate-700";

    const customerName =
        (os.secondary_customer && os.secondary_customer.name) ||
        os.client_name ||
        "-";

    const total = Number(os.grand_total || 0).toFixed(2);
    const orderDate = formatDatePtBR(os.order_date || os.created_at);

    const menuHtml = buildMenuByStatus(os);

    return `
  <tr class="hover:bg-slate-50" data-status="${status}">
    <td class="px-6 py-3 text-left whitespace-nowrap">${os.order_number || "-"}</td>
    <td class="px-3 py-3 text-left">${customerName}</td>
    <td class="px-3 py-3 text-center whitespace-nowrap">R$ ${total}</td>
    <td class="px-3 py-3 text-center">
      <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${badgeClass}">
        ${label}
      </span>
    </td>
    <td class="px-3 py-3 text-center whitespace-nowrap">${orderDate}</td>

    <td class="px-6 py-3 text-center whitespace-nowrap">
      <div class="relative inline-block text-left">
        <button type="button"
                class="rounded-lg p-2 hover:bg-slate-100 focus:outline-none"
                data-menu-trigger
                data-id="${os.id}"
                aria-haspopup="true"
                aria-expanded="false">⋮</button>

        <div id="menu-${os.id}"
             class="hidden absolute right-0 top-full z-50 mt-2 w-56 rounded-2xl overflow-hidden border border-slate-200 bg-white shadow-xl ring-1 ring-black/5"
             data-menu
             data-for="${os.id}">
          <ul class="py-1 text-sm text-slate-700">
            ${menuHtml}
          </ul>
        </div>
      </div>
    </td>
  </tr>`;
}

function liBtn({ label, attr, danger = false }) {
    const base = "block w-full px-3 py-2 text-left bg-transparent rounded-none hover:bg-slate-50 focus:outline-none";
    const cls = danger ? `${base} text-rose-600 hover:bg-rose-50` : base;
    return `<li><button type="button" class="${cls}" ${attr}>${label}</button></li>`;
}

function buildMenuByStatus(os) {
    const status = os.status || "draft";

    const idAttr = (extra = "") => `data-id="${os.id}" data-status="${status}" ${extra}`.trim();

    const isInvoiced =
        status === "nf_emitida" ||
        status === "invoiced" || // compat
        os.nf_emitida === true ||
        os.nf_emitted === true ||
        !!os.invoice_id;

    const S = {
        draft: [
            liBtn({ label: "Duplicar OS", attr: `data-dup ${idAttr()}` }),
            liBtn({ label: "Editar OS",   attr: `data-edit ${idAttr()}` }),
            `<li class="border-t border-slate-200 mt-1 pt-1"></li>`,
            liBtn({ label: "Excluir OS",  attr: `data-del ${idAttr()}`, danger: true }),
        ],
        pending: [
            liBtn({ label: "Visualizar OS", attr: `data-view ${idAttr()}` }),
            liBtn({ label: "Baixar PDF",    attr: `data-download ${idAttr()}` }),
            liBtn({
                label: "Assinatura Digital",
                attr: `data-sign ${idAttr(`data-sign-mode="sign" data-email="${os.secondary_customer?.email || ""}"`)}`
            }),
            liBtn({ label: "Duplicar OS",   attr: `data-dup ${idAttr()}` }),
            liBtn({ label: "Editar OS",     attr: `data-edit ${idAttr()}` }),
            liBtn({ label: "Recusado",      attr: `data-reject ${idAttr()}` }),
            `<li class="border-t border-slate-200 mt-1 pt-1"></li>`,
            liBtn({ label: "Excluir OS",    attr: `data-del ${idAttr()}`, danger: true }),
        ],
        approved: [
            liBtn({ label: "Visualizar OS", attr: `data-view ${idAttr()}` }),
            liBtn({ label: "Baixar PDF",    attr: `data-download ${idAttr()}` }),
            liBtn({ label: "Duplicar OS",   attr: `data-dup ${idAttr()}` }),
            liBtn({ label: "Editar OS",     attr: `data-edit ${idAttr()}` }),
            `<li class="border-t border-slate-200 mt-1 pt-1"></li>`,
            liBtn({ label: "Excluir OS",    attr: `data-del ${idAttr()}`, danger: true }),
        ],
        rejected: [
            liBtn({ label: "Visualizar OS", attr: `data-view ${idAttr()}` }),
            liBtn({ label: "Baixar PDF",    attr: `data-download ${idAttr()}` }),
            liBtn({
                label: "Nova aprovação",
                attr: `data-sign ${idAttr(`data-sign-mode="reapprove" data-email="${os.secondary_customer?.email || ""}"`)}`
            }),
            liBtn({ label: "Duplicar OS",   attr: `data-dup ${idAttr()}` }),
            liBtn({ label: "Editar OS",     attr: `data-edit ${idAttr()}` }),
            `<li class="border-t border-slate-200 mt-1 pt-1"></li>`,
            liBtn({ label: "Excluir OS",    attr: `data-del ${idAttr()}`, danger: true }),
        ],
        completed: [
            liBtn({ label: "Visualizar OS", attr: `data-view ${idAttr()}` }),
            liBtn({ label: "Baixar PDF",    attr: `data-download ${idAttr()}` }),
            liBtn({ label: "Duplicar OS",   attr: `data-dup ${idAttr()}` }),
            liBtn({ label: "Enviar cópia e-mail", attr: `data-email ${idAttr(`data-to="${os.secondary_customer?.email || ""}" data-number="${os.order_number || ""}"`)}` }),
            `<li class="border-t border-slate-200 mt-1 pt-1"></li>`,
            liBtn({ label: "Excluir OS",    attr: `data-del ${idAttr()}`, danger: true }),
        ],
        nf_emitida: [
            liBtn({ label: "Visualizar OS", attr: `data-view ${idAttr()}` }),
            liBtn({ label: "Baixar PDF",    attr: `data-download ${idAttr()}` }),
            liBtn({ label: "Duplicar OS",   attr: `data-dup ${idAttr()}` }),
            liBtn({ label: "Enviar cópia e-mail", attr: `data-email ${idAttr(`data-to="${os.secondary_customer?.email || ""}" data-number="${os.order_number || ""}"`)}` }),
            `<li class="border-t border-slate-200 mt-1 pt-1"></li>`,
            liBtn({ label: "Excluir OS",    attr: `data-del ${idAttr()}`, danger: true }),
        ],
    };

    const key = isInvoiced ? "nf_emitida" : status;
    return (S[key] || S.draft).join("");
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
    const actionSign = e.target.closest("[data-sign]");
    const actionReject = e.target.closest("[data-reject]");

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
        const st = actionEdit.dataset.status;

        if (st === "approved") {
            openApprovedEditDialog(id);
            return;
        }

        if (id) window.location.href = `/service-orders/service-order/${id}/edit`;
        return;
    }

    if (actionSign) {
        e.preventDefault();
        closeAllMenus();

        const id = actionSign.dataset.id;
        if (!id) return;

        const mode = actionSign.dataset.signMode || "sign";
        const email = actionSign.dataset.email || "";

        openSignatureDialog({ id, mode, email });
        return;
    }

    if (actionReject) {
        e.preventDefault();
        closeAllMenus();

        const id = actionReject.dataset.id;
        if (!id) return;

        const tr = actionReject.closest("tr");
        const osNumber = tr?.querySelector("td")?.textContent?.trim() || "";
        const client = tr?.querySelectorAll("td")[1]?.textContent?.trim() || "";
        const meta = osNumber && client ? `OS ${osNumber} • ${client}` : "";

        openRejectDialog(id, meta);
        return;
    }

    if (actionDel) {
        e.preventDefault();
        closeAllMenus();

        const id = actionDel.dataset.id;
        const st = actionDel.dataset.status;
        const tr = actionDel.closest("tr");
        const osNumber = tr?.querySelector("td")?.textContent?.trim() || "";
        const client = tr?.querySelectorAll("td")[1]?.textContent?.trim() || "";

        let warn = "";
        if (st === "approved") warn = "Ao excluir, remove também cobranças/contas a receber.";
        if (st === "completed" || st === "invoiced") warn = "Ao excluir, sai de cobranças/contas a receber e precisa cancelar a NF (se emitida).";

        const meta = osNumber && client ? `OS ${osNumber} • ${client}` : "";
        openDeleteDialog(id, warn ? `${meta}\n${warn}` : meta);
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

    window.location.href = `/service-orders/service-order/create/${newId}?mode=duplicate&from=${encodeURIComponent(id)}`;
}

// ===== Modal assinatura (index) =====
const dlgSig = document.querySelector("#signature-actions");
const sigTitle = document.querySelector("#sig-title");
const sigSubtitle = document.querySelector("#sig-subtitle");
const sigBtnTablet = document.querySelector("#sig-tablet");
const sigBtnEmail = document.querySelector("#sig-email");
const sigBtnCancel = document.querySelector("#sig-cancel");
const sigBtnClose = document.querySelector("#sig-close");
const sigFeedback = document.querySelector("#sig-feedback");

function openSignatureDialog({ id, mode = "sign", email = "" }) {
    if (!dlgSig) return;

    // guarda no dataset (fonte da verdade)
    dlgSig.dataset.targetId = id || "";
    dlgSig.dataset.mode = mode || "sign";
    dlgSig.dataset.email = (email || "").trim();

    if (sigTitle) sigTitle.textContent = (mode === "reapprove") ? "Nova aprovação" : "Assinatura Digital";
    if (sigSubtitle) sigSubtitle.textContent = "Escolha como deseja coletar a assinatura.";
    if (sigFeedback) { sigFeedback.classList.add("hidden"); sigFeedback.textContent = ""; }

    dlgSig.showModal();
}

function closeSignatureDialog() {
    if (!dlgSig) return;
    dlgSig.close();
    dlgSig.dataset.targetId = "";
    dlgSig.dataset.mode = "sign";
    dlgSig.dataset.email = "";
}

sigBtnCancel?.addEventListener("click", closeSignatureDialog);
sigBtnClose?.addEventListener("click", closeSignatureDialog);

function sigGetTargetId() {
    const id = dlgSig?.dataset?.targetId || "";
    return id && id !== "null" && id !== "undefined" ? id : "";
}

function showSigMsg(text) {
    if (!sigFeedback) return;
    sigFeedback.textContent = text;
    sigFeedback.classList.remove("hidden");
}

sigBtnTablet?.addEventListener("click", () => {
    const id = sigGetTargetId();
    if (!id) {
        showSigMsg("ID da OS não encontrado. Feche e abra o menu novamente.");
        return;
    }
    closeSignatureDialog();
    window.location.href = `/service-orders/service-order/${id}/edit?open_signature=1`;
});

sigBtnEmail?.addEventListener("click", async () => {
    const id = sigGetTargetId();
    if (!id) {
        showSigMsg("ID da OS não encontrado. Feche e abra o menu novamente.");
        return;
    }

    const defaultEmail = (dlgSig?.dataset?.email || "").trim();
    const email = defaultEmail || prompt("E-mail para enviar o link:", "") || "";
    const cleanEmail = email.trim();

    sigBtnEmail.disabled = true;

    try {
        const resp = await fetch(`/service-orders/${id}/signature-link/send`, {
            method: "POST",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": getCsrfToken(),
            },
            body: JSON.stringify({ email: cleanEmail || null }),
        });

        const j = await resp.json().catch(() => ({}));
        const ok = (j?.ok ?? j?.success) === true;

        if (!resp.ok || !ok) throw new Error("Falha ao enviar");

        showSigMsg("Link de assinatura enviado por e-mail.");
    } catch (e) {
        console.error(e);
        showSigMsg("Falha ao enviar o link. Tente novamente.");
    } finally {
        sigBtnEmail.disabled = false;
    }
});

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

// ===== Modal editar aprovada =====
const dlgApproved = document.querySelector("#confirm-approved-edit");
const btnApprovedYes = document.querySelector("#approved-edit-yes");
const btnApprovedNo  = document.querySelector("#approved-edit-no");
let approvedEditTargetId = null;

function openApprovedEditDialog(id) {
    approvedEditTargetId = id;
    dlgApproved?.showModal();
}
function closeApprovedEditDialog() {
    approvedEditTargetId = null;
    dlgApproved?.close();
}
btnApprovedNo?.addEventListener("click", closeApprovedEditDialog);
dlgApproved?.addEventListener("close", () => (approvedEditTargetId = null));
btnApprovedYes?.addEventListener("click", () => {
    const id = approvedEditTargetId;
    closeApprovedEditDialog();
    if (id) window.location.href = `/service-orders/service-order/${id}/edit`;
});

// ===== Modal recusar =====
const dlgReject = document.querySelector("#confirm-reject");
const btnRejectYes = document.querySelector("#reject-yes");
const btnRejectNo  = document.querySelector("#reject-no");
const rejectMeta   = document.querySelector("#reject-meta");
let rejectTargetId = null;

function openRejectDialog(id, metaText = "") {
    rejectTargetId = id;
    if (rejectMeta) rejectMeta.textContent = metaText || "";
    dlgReject?.showModal();
}
function closeRejectDialog() {
    rejectTargetId = null;
    dlgReject?.close();
}
btnRejectNo?.addEventListener("click", closeRejectDialog);
dlgReject?.addEventListener("close", () => (rejectTargetId = null));

btnRejectYes?.addEventListener("click", async () => {
    if (!rejectTargetId) return;
    btnRejectYes.disabled = true;

    try {
        await setOsStatus(rejectTargetId, "rejected");
        closeRejectDialog();
        await loadOrders({ status: getCurrentStatusFilter() });
    } catch (e) {
        console.error(e);
        alert("Não foi possível recusar esta OS.");
    } finally {
        btnRejectYes.disabled = false;
    }
});

function getCurrentStatusFilter() {
    const active = document.querySelector("[data-status-filter].active-status");
    return active ? active.getAttribute("data-status-filter") || "" : "";
}

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute("content") : "";
}

async function setOsStatus(id, status) {
    const resp = await fetch(`/service-orders/${id}/status`, {
        method: "POST",
        headers: {
            "Accept": "application/json",
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest",
            "X-CSRF-TOKEN": getCsrfToken(),
        },
        body: JSON.stringify({ status }),
    });

    const json = await resp.json().catch(() => ({}));

    if (!resp.ok || !(json?.ok ?? true)) {
        console.error("setOsStatus error:", json);
        throw new Error("Falha ao alterar status");
    }

    return json;
}

