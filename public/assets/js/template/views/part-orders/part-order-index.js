/* assets/js/template/views/part-orders/part-order-index.js */
/* global window, document */

(() => {
    const root = document.getElementById('orders-parts-fragment') || document;

    const API_BASE = root.dataset.apiBase || '/part-orders/part-order-api';
    const GROUP_PREFIX = API_BASE.replace(/\/part-order-api\/?$/, '');
    const URL = {
        list: (params = '') => `${API_BASE}${params ? `?${params}` : ''}`,
        show: (id) => `${API_BASE}/${encodeURIComponent(id)}`,
        store: () => `${API_BASE}`,
        update: (id) => `${API_BASE}/${encodeURIComponent(id)}`,
        destroy: (id) => `${API_BASE}/${encodeURIComponent(id)}`,
        duplicate: (id) => `${GROUP_PREFIX}/${encodeURIComponent(id)}/duplicate`,
        send: (id) => `${GROUP_PREFIX}/${encodeURIComponent(id)}/send`,
        resend: (id) => `${GROUP_PREFIX}/${encodeURIComponent(id)}/resend`,
    };

    const csrf = () =>
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
        window?.Laravel?.csrfToken ||
        '';

    const fmtBR = (n) =>
        'R$ ' + Number(n || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 });

    const todayISO = () => new Date().toISOString().slice(0, 10);

    // Toast
    const toastBox = document.getElementById('toast');
    const toast = (m) => {
        if (!toastBox) return alert(m);
        toastBox.firstElementChild.textContent = m;
        toastBox.classList.remove('hidden');
        window.clearTimeout(toastBox.__t);
        toastBox.__t = window.setTimeout(() => toastBox.classList.add('hidden'), 1600);
    };

    const q = (sel) => (root?.querySelector ? root.querySelector(sel) : null) || document.querySelector(sel);

    const qa = (sel) => {
        const scoped = root?.querySelectorAll ? Array.from(root.querySelectorAll(sel)) : [];
        return scoped.length ? scoped : Array.from(document.querySelectorAll(sel));
    };

    async function apiFetch(url, opts = {}) {
        const headers = {
            Accept: 'application/json',
            ...(opts.body ? { 'Content-Type': 'application/json' } : {}),
            'X-Requested-With': 'XMLHttpRequest',
            ...(csrf() ? { 'X-CSRF-TOKEN': csrf() } : {}),
            ...(opts.headers || {}),
        };

        const res = await fetch(url, { credentials: 'same-origin', ...opts, headers });

        if (res.status === 422) {
            let payload = {};
            try { payload = await res.json(); } catch {}
            const msg =
                payload.message ||
                (payload.errors
                    ? Object.values(payload.errors).flat().slice(0, 3).join(' | ')
                    : 'Dados inválidos.');
            throw new Error(msg);
        }

        if (!res.ok) {
            let msg = `Erro HTTP ${res.status}`;
            try {
                const p = await res.json();
                msg = p.message || msg;
            } catch {}
            throw new Error(msg);
        }

        return res.json();
    }

    // ===== STATE =====
    let pageData = null;
    let orders = [];
    let statusFilter = 'all';
    let searchTxt = '';
    const DRAFT_KEY = 'cliqis_last_part_order_draft';
    let lastSentId = null;

    // ===== UI refs =====
    const tbody = q('#ordersp-body');
    const empty = q('#empty-state');
    const fltBtns = qa('.flt');
    const searchInput = q('#search');

    const cardCount = q('#card-count');
    const cardValue = q('#card-value');
    const cardItems = q('#card-items');
    const cardLabel = q('#card-filter-label');

    const banner = q('#draft-banner');
    const btnDraftView = q('#btn-draft-view');
    const btnDraftSend = q('#btn-draft-send');
    const btnDraftDismiss = q('#btn-draft-dismiss');

    // Modals (fora do root)
    const modal = document.getElementById('modal-parts');
    const modalConfirm = document.getElementById('modal-confirm');
    const succModal = document.getElementById('modal-success');
    const modalView = document.getElementById('modal-view');
    const viewContent = document.getElementById('view-content');
    const badgeDraftView = document.getElementById('badge-draft-view');
    const btnEditDraft = document.getElementById('btn-edit-draft');

    const elConfirmTo      = document.getElementById('confirm-to');
    const elConfirmNoEmail = document.getElementById('confirm-no-email');

    const elConfirmSubject = document.getElementById('confirm-subject');
    const elConfirmBody    = document.getElementById('confirm-body');
    const elConfirmPdfName = document.getElementById('confirm-pdf-name');

    const btnOpenSettings  = document.getElementById('btn-open-part-order-settings');

    const openModalById = (id) => document.getElementById(id)?.classList.remove('hidden');
    const closeModalById = (id) => document.getElementById(id)?.classList.add('hidden');

    // Form refs
    const form = q('#form-parts');
    const itemsBody = q('#items-body');
    const ufSelect = q('#pp-uf');
    const cnpjInput = q('#pp-cnpj');
    const sumICMSTag = q('#sum-icms-tag');

    // ===== Supplier change confirm (Item 5) =====
    const modalSupplierChoice = document.getElementById('modal-supplier-choice');
    const supChoiceName = document.getElementById('sup-choice-name');
    const btnSupOrderOnly = document.getElementById('btn-sup-order-only');
    const btnSupSetDefault = document.getElementById('btn-sup-set-default');

    const openSupplierChoice = () => modalSupplierChoice?.classList.remove('hidden');
    const closeSupplierChoice = () => modalSupplierChoice?.classList.add('hidden');

    let supplierBaseline = { id: '', name: '' };   // fornecedor “original” ao abrir o modal do pedido
    let supplierCurrent  = { id: '', name: '', email: '' };
    let supplierPending  = null;
    let supplierPrompted = new Set();
    let suppressSupplierPrompt = false;

    function readSupplierFields() {
        const sid = document.getElementById('pp-supplier-id')?.value || '';
        const sname = document.getElementById('pp-supplier-name')?.value || '';
        const semail = document.getElementById('pp-supplier-email')?.value || '';
        return { id: sid, name: sname, email: semail };
    }

    function setSupplierFields({ id, name, email }) {
        const sid = document.getElementById('pp-supplier-id');
        const sname = document.getElementById('pp-supplier-name');
        const semail = document.getElementById('pp-supplier-email');

        if (sid) sid.value = id || '';
        if (sname) sname.value = name || '';
        if (semail) semail.value = email || '';
    }

    function captureSupplierBaseline() {
        const v = readSupplierFields();
        supplierBaseline = { id: v.id, name: v.name, email: v.email };
        supplierCurrent  = { id: v.id, name: v.name, email: v.email };
        supplierPending  = null;
        supplierPrompted = new Set();
    }

    document.addEventListener('click', (ev) => {
        if (ev.target.closest('[data-close-supplier-choice]')) {
            // cancelou: volta pro baseline
            suppressSupplierPrompt = true;
            setSupplierFields(supplierBaseline);
            suppressSupplierPrompt = false;
            closeSupplierChoice();
        }
    });

    modalSupplierChoice?.addEventListener('click', (e) => {
        if (e.target === modalSupplierChoice) {
            suppressSupplierPrompt = true;
            setSupplierFields(supplierBaseline);
            suppressSupplierPrompt = false;
            closeSupplierChoice();
        }
    });

    // ===== Recipient edit =====
    const btnEditRecipient = document.getElementById('btn-edit-recipient');

    const modalRecipientEdit  = document.getElementById('modal-recipient-edit');
    const recName  = document.getElementById('rec-name');
    const recEmail = document.getElementById('rec-email');
    const recError = document.getElementById('rec-error');
    const btnRecSave   = document.getElementById('btn-rec-save');
    const btnRecCancel = document.getElementById('btn-rec-cancel');

    const modalRecipientScope = document.getElementById('modal-recipient-scope');
    const recScopeName  = document.getElementById('rec-scope-name');
    const recScopeEmail = document.getElementById('rec-scope-email');
    const btnRecOrderOnly    = document.getElementById('btn-rec-order-only');
    const btnRecUpdateSystem = document.getElementById('btn-rec-update-system');
    const recScopeHint = document.getElementById('rec-scope-hint');

    const modalDelete = document.getElementById('modal-delete');
    const delSub = document.getElementById('del-sub');
    const delPartialWarning = document.getElementById('del-partial-warning');
    const btnDeleteConfirm = document.getElementById('btn-delete-confirm');

    let pendingDeleteId = null;

    const SUPPLIER_API = root.dataset?.supplierApi || '/entities/supplier-api';
    const supplierUpdateUrl = (id) => `${SUPPLIER_API}/${encodeURIComponent(id)}`;

    let recipientPending = null;

    const openRecipientEdit  = () => modalRecipientEdit?.classList.remove('hidden');
    const closeRecipientEdit = () => modalRecipientEdit?.classList.add('hidden');

    const openRecipientScope  = () => modalRecipientScope?.classList.remove('hidden');
    const closeRecipientScope = () => modalRecipientScope?.classList.add('hidden');

    const setRecError = (msg) => {
        if (!recError) return;
        recError.textContent = msg || '';
        recError.classList.toggle('hidden', !msg);
    };

// abre modal editar
    btnEditRecipient?.addEventListener('click', () => {
        const name  = document.getElementById('pp-supplier-name')?.value || supplierCurrent.name || '';
        const email = document.getElementById('pp-supplier-email')?.value || supplierCurrent.email || '';

        if (recName)  recName.value  = (name || '').trim();
        if (recEmail) recEmail.value = normalizeEmail(email);

        setRecError('');
        openRecipientEdit();
    });

// fechar por botões
    btnRecCancel?.addEventListener('click', closeRecipientEdit);
    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-close-recipient-edit]')) closeRecipientEdit();
        if (e.target.closest('[data-close-recipient-scope]')) closeRecipientScope();
    });
    modalRecipientEdit?.addEventListener('click', (e) => { if (e.target === modalRecipientEdit) closeRecipientEdit(); });
    modalRecipientScope?.addEventListener('click', (e) => { if (e.target === modalRecipientScope) closeRecipientScope(); });

// salvar edição -> abre escolha de escopo
    btnRecSave?.addEventListener('click', () => {
        const name  = (recName?.value || '').trim();
        const email = normalizeEmail(recEmail?.value);

        if (!name) return setRecError('Informe o nome do destinatário.');
        if (!isValidEmail(email)) return setRecError('E-mail inválido. Corrija para enviar.');

        recipientPending = { name, email };

        // prepara scope
        if (recScopeName)  recScopeName.textContent = name;
        if (recScopeEmail) recScopeEmail.textContent = email;

        const supplierId = document.getElementById('pp-supplier-id')?.value || '';
        const canUpdateSystem = !!supplierId;

        if (btnRecUpdateSystem) {
            btnRecUpdateSystem.disabled = !canUpdateSystem;
            btnRecUpdateSystem.classList.toggle('opacity-50', !canUpdateSystem);
            btnRecUpdateSystem.classList.toggle('cursor-not-allowed', !canUpdateSystem);
        }
        recScopeHint?.classList.toggle('hidden', canUpdateSystem);

        closeRecipientEdit();
        openRecipientScope();
    });

// aplica "apenas neste pedido"
    btnRecOrderOnly?.addEventListener('click', () => {
        if (!recipientPending) return;

        const supplierId = document.getElementById('pp-supplier-id')?.value || null;

        // aplica nos campos do pedido (snapshot do pedido)
        setSupplierFields({ id: supplierId, name: recipientPending.name, email: recipientPending.email });
        supplierCurrent = { id: supplierId || '', name: recipientPending.name, email: recipientPending.email };

        // re-render preview
        currentOrderForSend = buildPreviewOrderFromForm();
        renderConfirmPreview(currentOrderForSend, confirmTpl.subject, confirmTpl.body);

        closeRecipientScope();
        toast('Destinatário atualizado neste pedido');
    });

// aplica "no sistema" (update supplier-api)
    btnRecUpdateSystem?.addEventListener('click', async () => {
        if (!recipientPending) return;

        const supplierId = document.getElementById('pp-supplier-id')?.value || '';
        if (!supplierId) return;

        try {
            await apiFetch(supplierUpdateUrl(supplierId), {
                method: 'PUT',
                body: JSON.stringify({
                    name: recipientPending.name,
                    email: recipientPending.email,
                }),
            });

            // aplica também no pedido atual
            setSupplierFields({ id: supplierId, name: recipientPending.name, email: recipientPending.email });
            supplierCurrent = { id: supplierId, name: recipientPending.name, email: recipientPending.email };
            supplierBaseline = { ...supplierBaseline, id: supplierId, name: recipientPending.name, email: recipientPending.email };

            currentOrderForSend = buildPreviewOrderFromForm();
            renderConfirmPreview(currentOrderForSend, confirmTpl.subject, confirmTpl.body);

            closeRecipientScope();
            toast('Cadastro do fornecedor atualizado');
        } catch (e) {
            toast(e.message || 'Falha ao atualizar fornecedor.');
        }
    });

    function openDeleteModal(order) {
        pendingDeleteId = order?.id || null;

        const num = order?.order_number || order?.title || '—';
        if (delSub) delSub.textContent = `Pedido: ${num}`;

        const raw = String(order?.status || '').toLowerCase();
        delPartialWarning?.classList.toggle('hidden', raw !== 'partial');

        modalDelete?.classList.remove('hidden');

        // reset botão
        if (btnDeleteConfirm) {
            btnDeleteConfirm.disabled = false;
            btnDeleteConfirm.textContent = 'Excluir definitivamente';
            btnDeleteConfirm.classList.remove('opacity-60', 'cursor-not-allowed');
        }
    }

    function closeDeleteModal() {
        modalDelete?.classList.add('hidden');
        pendingDeleteId = null;
    }

    document.addEventListener('click', (ev) => {
        if (ev.target.closest('[data-close-delete]')) closeDeleteModal();
    });

    modalDelete?.addEventListener('click', (e) => {
        if (e.target === modalDelete) closeDeleteModal();
    });

    btnDeleteConfirm?.addEventListener('click', async () => {
        if (!pendingDeleteId) return;

        // estado do botão
        btnDeleteConfirm.disabled = true;
        btnDeleteConfirm.textContent = 'Excluindo...';
        btnDeleteConfirm.classList.add('opacity-60', 'cursor-not-allowed');

        try {
            await apiFetch(URL.destroy(pendingDeleteId), { method: 'DELETE' });
            toast('Pedido excluído');
            closeDeleteModal();
            await loadList();
        } catch (e) {
            toast(e.message || 'Falha ao excluir.');
            btnDeleteConfirm.disabled = false;
            btnDeleteConfirm.textContent = 'Excluir definitivamente';
            btnDeleteConfirm.classList.remove('opacity-60', 'cursor-not-allowed');
        }
    });

    // ===== Catalog (continua localStorage por enquanto) =====
    const getLS = (k, f) => {
        try { return JSON.parse(localStorage.getItem(k) || JSON.stringify(f)); } catch { return f; }
    };
    const setLS = (k, v) => localStorage.setItem(k, JSON.stringify(v));

    const partsCatalogSeed = [];

    let catalog = getLS('cliqis_parts_catalog', partsCatalogSeed);
    setLS('cliqis_parts_catalog', catalog);

    function escapeHTML(s) {
        return (s || '').replace(/[&<>"']/g, (m) => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m]));
    }
    function escapeAttr(s) { return escapeHTML(s).replace(/"/g, '&quot;'); }

    function refreshPartsDatalist() {
        const dl = q('#parts-codes');
        if (!dl) return;
        dl.innerHTML = catalog.map((p) => `<option value="${escapeAttr(p.codigo)}">`).join('');
    }
    refreshPartsDatalist();

    function findPart(code) {
        const c = (code || '').trim().toLowerCase();
        return catalog.find((p) => String(p.codigo || '').toLowerCase() === c) || null;
    }

    function ymdFromAny(v) {
        if (!v) return '';
        const s = String(v);

        // pega "YYYY-MM-DD" mesmo se vier "YYYY-MM-DDTHH:mm..."
        const m = s.match(/^(\d{4}-\d{2}-\d{2})/);
        if (m) return m[1];

        // fallback (casos estranhos)
        const d = new Date(s);
        return Number.isNaN(d.getTime()) ? '' : d.toISOString().slice(0, 10);
    }

    function formatBRDate(v) {
        const ymd = ymdFromAny(v);
        if (!ymd) return '—';
        const [y, m, d] = ymd.split('-');
        return `${d}/${m}/${y}`;
    }

    function diffDays(d1, d2) {
        try {
            const a = new Date(d1), b = new Date(d2);
            return Math.floor((new Date(b.toDateString()) - new Date(a.toDateString())) / 86400000);
        } catch { return 0; }
    }
    function parseNumber(v) {
        if (typeof v === 'number') return v;
        if (v === null || v === undefined || v === '') return 0;
        v = String(v).trim();
        const normalized = v.replace(/\s/g, '').replace(/\./g, '').replace(',', '.');
        const n = Number(normalized);
        return Number.isFinite(n) ? n : 0;
    }
    function numToStr(n) {
        if (n === null || n === undefined || Number.isNaN(Number(n))) return '';
        return String(Number(n).toLocaleString('pt-BR', { maximumFractionDigits: 2 }));
    }
    function formatCNPJ(v) {
        const digits = String(v).replace(/\D/g, '').slice(0, 14);
        const m = digits.match(/^(\d{0,2})(\d{0,3})(\d{0,3})(\d{0,4})(\d{0,2})$/);
        if (!m) return digits;
        return [m[1], m[2] && '.' + m[2], m[3] && '.' + m[3], m[4] && '/' + m[4], m[5] && '-' + m[5]]
            .join('')
            .replace(/undefined/g, '');
    }

    const normalizeEmail = (v) => String(v ?? '').trim();

    const isValidEmail = (v) => {
        const e = normalizeEmail(v).toLowerCase();
        if (!e) return false;
        if (e === 'undefined' || e === 'null') return false;
        // simples e suficiente pro front (o backend continua validando)
        return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(e);
    };

    const bindCnpjMask = (id) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input', (e) => (e.target.value = formatCNPJ(e.target.value)));
        el.addEventListener('blur', (e) => (e.target.value = formatCNPJ(e.target.value)));
    };

    bindCnpjMask('pp-cnpj');
    bindCnpjMask('ps-cnpj');

    function rateFromUF(uf) {
        if (!uf) return 0;
        const u = String(uf).toUpperCase();
        if (u === 'SP') return 18;
        if (['MG', 'PR', 'RS', 'RJ', 'SC'].includes(u)) return 12;
        return 7;
    }

    function derivedStatus(o) {
        const raw = String(o.status || '').toLowerCase();

        if (raw === 'draft') return 'rascunho';

        // ✅ deriva por quantidade (funciona mesmo se backend não trocar status)
        const rec = Number(o.received_qty_sum ?? o.qty_received ?? o.received_qty ?? 0);
        const tot = Number(o.qty_total_sum ?? o.qty_total ?? o.total_qty_sum ?? 0);

        if (tot > 0) {
            if (rec >= tot) return 'concluido';
            if (rec > 0) return 'parcial';
        }

        if (raw === 'completed') return 'concluido';
        if (raw === 'partial') return 'parcial';

        if (raw === 'pending') {
            const days = diffDays(o.order_date || o.date, todayISO());
            return days > 10 ? 'atraso' : 'pendente';
        }

        const days = diffDays(o.order_date || o.date, todayISO());
        return days > 10 ? 'atraso' : 'aberto';
    }

    function chip(st, o = null) {
        const rec = Number(o?.received_qty_sum ?? o?.qty_received ?? o?.received_qty ?? 0);
        const tot = Number(o?.qty_total_sum ?? o?.qty_total ?? o?.total_qty_sum ?? 0);

        const partialLabel = (tot > 0) ? `Parcial ${rec}/${tot}` : 'Parcial';

        const m = {
            aberto:    ['bg-blue-50 text-blue-700', 'Em aberto'],
            pendente:  ['bg-amber-50 text-amber-700', 'Pendente'],
            atraso:    ['bg-rose-50 text-rose-700', 'Em atraso'],
            parcial:   ['bg-indigo-50 text-indigo-700', partialLabel],
            concluido: ['bg-emerald-50 text-emerald-700', 'Concluído'],
            rascunho:  ['bg-rose-50 text-rose-700', 'Rascunho'],
        };

        const [cls, lb] = m[st] || ['bg-slate-100 text-slate-700', st];
        return `<span class="inline-flex rounded-full ${cls} px-2.5 py-1 text-xs font-medium">${lb}</span>`;
    }

    // ===== LIST LOAD =====
    async function loadList() {
        try {
            pageData = await apiFetch(URL.list('page=1'));
            orders = Array.isArray(pageData.data) ? pageData.data : [];
            render();
        } catch (e) {
            toast(e.message || 'Falha ao carregar pedidos.');
            orders = [];
            render();
        }
    }

    function filteredList() {
        return orders
            .map((o) => ({ ...o, _status: derivedStatus(o) }))
            .filter((o) => (statusFilter === 'all' ? true : o._status === statusFilter))
            .filter((o) => {
                if (!searchTxt) return true;
                const t = `${o.order_number || ''} ${o.billing_cnpj || ''} ${o.title || ''}`.toLowerCase();
                return t.includes(searchTxt);
            })
            .sort((a, b) => (b.created_at || '').localeCompare(a.created_at || ''));
    }

    function updateCards(list) {
        let count = 0, total = 0, itens = 0;
        list.forEach((o) => {
            count++;
            total += Number(o.grand_total || 0);
            itens += Number(o.items_count || 0);
        });

        if (cardCount) cardCount.textContent = String(count);
        if (cardValue) cardValue.textContent = fmtBR(total);
        if (cardItems) cardItems.textContent = String(itens);

        const label =
            statusFilter === 'all'
                ? 'Todos os status'
                : ({
                    aberto: 'Em aberto',
                    pendente: 'Pendente',
                    atraso: 'Em atraso',
                    parcial: 'Parcial',
                    concluido: 'Concluído',
                    rascunho: 'Rascunho',
                }[statusFilter] || '');

        if (cardLabel) cardLabel.textContent = label;
    }

    function rowHTML(o) {
        const draftTag =
            String(o.status || '').toLowerCase() === 'draft'
                ? `<span class="ml-2 inline-flex rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-semibold text-rose-700">Rascunho</span>`
                : '';

        const st = o._status;

        const isDraft = st === 'rascunho';
        const isDone  = st === 'concluido';

        const canEdit   = isDraft;
        const canResend = !isDraft && !isDone;
        const canReceive= !isDraft && !isDone;

        return `
      <tr class="hover:bg-slate-50/60">
        <td class="px-4 py-3 font-medium">${escapeHTML(o.order_number || '—')}${draftTag}</td>
        <td class="px-4 py-3">${escapeHTML(o.billing_cnpj || '—')}</td>
        <td class="px-4 py-3">${escapeHTML(o.title || '—')}</td>
        <td class="px-4 py-3">${formatBRDate(o.order_date || '')}</td>
        <td class="px-4 py-3 text-right">${fmtBR(o.grand_total || 0)}</td>
<td class="px-4 py-3">${chip(o._status, o)}</td>
        <td class="px-4 py-3">
          <div class="flex justify-end gap-2">
            ${canReceive ? `
        <button data-finalize="${o.id}"
          class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100">
          Finalizar
        </button>
      ` : ''}
                        <button data-act="view" data-id="${o.id}" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Visualizar</button>

            ${canEdit ? `<button data-act="edit" data-id="${o.id}" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100">Editar</button>` : ''}
            ${canResend ? `<button data-act="resend" data-id="${o.id}"
  class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">
  Reenviar
</button>` : ''}
            <button data-act="clone" data-id="${o.id}" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Clonar</button>
            <button data-act="del" data-id="${o.id}" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100">Excluir</button>
          </div>
        </td>
      </tr>
    `;
    }

    function render() {
        if (!tbody) return;

        const list = filteredList();
        tbody.innerHTML = list.map(rowHTML).join('');
        if (empty) empty.classList.toggle('hidden', list.length > 0);

        tbody.querySelectorAll('[data-act="view"]').forEach((b) => b.addEventListener('click', () => openView(b.dataset.id)));
        tbody.querySelectorAll('[data-act="edit"]').forEach((b) => b.addEventListener('click', () => openEdit(b.dataset.id)));
        tbody.querySelectorAll('[data-act="resend"]').forEach((b) =>
            b.addEventListener('click', () => openResendConfirm(b.dataset.id))
        );
        tbody.querySelectorAll('[data-act="clone"]').forEach((b) => b.addEventListener('click', () => cloneOrder(b.dataset.id)));
        tbody.querySelectorAll('[data-act="del"]').forEach((b) => b.addEventListener('click', () => delOrder(b.dataset.id)));

        updateCards(list);
        updateBanner();
    }

    // ===== BANNER DRAFT =====
    function updateBanner() {
        if (!banner) return;

        const drafts = orders
            .filter(o => String(o.status || '').toLowerCase() === 'draft')
            .sort((a,b) => ((b.updated_at || b.created_at || '')).localeCompare(a.updated_at || a.created_at || ''));

        banner.classList.toggle('hidden', drafts.length === 0);
        if (!drafts.length) return;

        const picker = document.getElementById('draft-picker');
        const count  = document.getElementById('draft-count');

        const lastId = sessionStorage.getItem(DRAFT_KEY);
        const cur = drafts.find(d => d.id === lastId) || drafts[0];

        if (count) count.textContent = `(${drafts.length} rascunho${drafts.length > 1 ? 's' : ''})`;

        if (picker) {
            picker.innerHTML = drafts.map(d =>
                `<option value="${d.id}">${escapeHTML(d.order_number || d.title || 'Rascunho')}</option>`
            ).join('');
            picker.value = cur.id;

            picker.onchange = () => {
                sessionStorage.setItem(DRAFT_KEY, picker.value);
                updateBanner();
            };
        }

        btnDraftView.onclick = () => openView(cur.id);
        btnDraftSend.onclick = () => openEdit(cur.id);
        btnDraftDismiss.onclick = async () => {
            try {
                await apiFetch(URL.destroy(cur.id), { method: 'DELETE' });

                if (sessionStorage.getItem(DRAFT_KEY) === cur.id) {
                    sessionStorage.removeItem(DRAFT_KEY);
                }

                toast('Rascunho excluído');
                await loadList();
            } catch (e) {
                toast(e.message || 'Falha ao excluir rascunho.');
            }
        };
    }

    // ===== CATALOG (DB) TYPEAHEAD (sem quebrar o catálogo local) =====
    const PART_API = root.dataset?.partApi || '/catalogs/part-api';

    const debounce = (fn, delay = 250) => {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), delay);
        };
    };

    const wrapForDropdown = (input) => {
        if (!input) return null;
        const parent = input.parentNode;
        if (parent?.classList?.contains('relative')) return parent;

        const wrapper = document.createElement('div');
        wrapper.className = 'relative';
        parent.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        return wrapper;
    };

    async function fetchPartsTypeahead(term = '', limit = 6, signal) {
        const url = `${PART_API}?q=${encodeURIComponent(term)}&typeahead=1&limit=${limit}`;
        const res = await fetch(url, { headers: { Accept: 'application/json' }, signal });
        if (!res.ok) return [];
        const json = await res.json();
        return Array.isArray(json) ? json : (json.data || []);
    }

    function setupPartTypeaheadForInput({ input, mode, getRowInputs, applyPart, createPart }) {
        if (!input) return;

        const wrapper = wrapForDropdown(input);
        if (!wrapper) return;

        const dd = document.createElement('div');

        // ❌ antes: tinha w-full e prendia na coluna
        // dd.className = 'absolute z-50 mt-1 w-full ... overflow-auto hidden';

        // ✅ agora: sem w-full, com overflow-x escondido
        dd.className =
            'absolute mt-1 rounded-xl border border-slate-200 bg-white shadow-lg max-h-64 overflow-y-auto overflow-x-hidden hidden';
        dd.style.zIndex = '90';
        dd.style.left = '0';
        dd.style.right = 'auto';

        // largura “auto” controlada
        dd.style.minWidth = '100%';            // fallback (vai ser sobrescrito pelo fit)
        dd.style.width = 'max-content';
        dd.style.maxWidth = 'min(520px, 90vw)';

        wrapper.appendChild(dd);

        let abortController = null;

        const close = () => {
            dd.classList.add('hidden');
            dd.innerHTML = '';
            dd.style.left = '0';
            dd.style.right = 'auto';
        };

        const fitDropdown = () => {
            // min = input, width = max-content, max = 520px/90vw
            const w = input.getBoundingClientRect().width || input.offsetWidth || 0;
            dd.style.minWidth = w ? `${Math.ceil(w)}px` : '100%';
            dd.style.width = 'max-content';
            dd.style.maxWidth = 'min(520px, 90vw)';

            // garante que não estoura a viewport
            dd.style.left = '0';
            dd.style.right = 'auto';

            // precisa estar visível pra medir
            dd.classList.remove('hidden');
            const pad = 8;
            const r = dd.getBoundingClientRect();

            if (r.right > window.innerWidth - pad) {
                dd.style.left = 'auto';
                dd.style.right = '0';
            }
            if (r.left < pad) {
                dd.style.left = '0';
                dd.style.right = 'auto';
            }
        };

        const render = (items, term) => {
            dd.innerHTML = '';

            const termNorm = (term || '').trim().toLowerCase();
            const hasExact = items.some((p) => {
                const code = String(p.code || p.codigo || '').trim().toLowerCase();
                const name = String(p.name || p.descricao || p.description || '').trim().toLowerCase();
                return (code && code === termNorm) || (name && name === termNorm);
            });

            items.forEach((p) => {
                const code = p.code || p.codigo || '';
                const name = p.name || p.descricao || p.description || '';
                const unit = p.unit_price ?? p.valor ?? null;

                const label = mode === 'code' ? (code || name) : (name || code);
                const sub = mode === 'code'
                    ? (name || '')
                    : (code || (unit != null ? `R$ ${Number(unit || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}` : ''));

                const btn = document.createElement('button');
                btn.type = 'button';

                // mantém “cara” do teu typeahead
                btn.className = 'w-full px-3 py-2 text-left text-sm hover:bg-slate-50 flex justify-between gap-2';
                btn.innerHTML = `
        <span class="truncate">${escapeHTML(label)}</span>
        ${sub ? `<span class="ml-2 flex-shrink-0 text-xs text-slate-500">${escapeHTML(sub)}</span>` : ''}
      `;

                btn.addEventListener('click', () => {
                    applyPart(p);
                    close();
                });

                dd.appendChild(btn);
            });

            if (!items.length) return close();

            fitDropdown();
        };

        const openList = async () => {
            try {
                const term = input.value.trim();

                if (abortController) abortController.abort();
                abortController = new AbortController();

                const list = term
                    ? await fetchPartsTypeahead(term, 8, abortController.signal)
                    : await fetchPartsTypeahead('', 5, abortController.signal);

                render(list, term);
            } catch (err) {
                if (err?.name === 'AbortError') return;
                console.warn(err);
            }
        };

        input.addEventListener('focus', openList);
        input.addEventListener('click', openList);
        input.addEventListener('input', debounce(openList, 200));

        document.addEventListener('click', (ev) => {
            if (!wrapper.contains(ev.target)) close();
        });

        input.addEventListener('keydown', (ev) => {
            if (ev.key === 'Escape') close();
        });

        // opcional: se redimensionar com dropdown aberto, reajusta
        window.addEventListener('resize', () => {
            if (!dd.classList.contains('hidden')) fitDropdown();
        }, { passive: true });
    }

    // ===== MODALS =====
    function openModal() { modal?.classList.remove('hidden'); }
    function closeModal(reset = false) { modal?.classList.add('hidden'); if (reset) resetForm(); }

    let currentOrderForSend = null;
    let confirmTpl = { subject: '', body: '' };

    let confirmMode = 'send';      // 'send' | 'resend'
    let confirmOrderId = null;     // usado no resend

    function buildPreviewOrderFromForm() {
        const supplierName =
            document.getElementById('pp-supplier-name')?.value?.trim() ||
            supplierCurrent.name ||
            '';

        const supplierEmail =
            document.getElementById('pp-supplier-email')?.value?.trim() ||
            supplierCurrent.email ||
            '';

        const uf = q('#pp-uf')?.value || '';
        const rate = rateFromUF(uf);
        const totals = sumTotal(formItems, rate);

        return {
            order_number: form.dataset.orderNumber || '',      // ✅ vem do openEdit
            order_date: q('#pp-date')?.value || todayISO(),
            items_count: totals.count,
            grand_total: totals.totalFinal,
            supplier: { name: supplierName, email: supplierEmail },
        };
    }

    function getVarsFromOrder(order) {
        const supplierName  = order?.supplier?.name || '';
        const supplierEmail = order?.supplier?.email || '';
        const itemsCount    = (order?.items_count ?? 0);

        return {
            partOrderNumber: order?.order_number || 'RASCUNHO',
            supplierName,
            supplierEmail,
            orderDate: formatDateBR(order?.order_date),
            itemsCount: String(itemsCount),
            total: moneyBR(order?.grand_total),
        };
    }

    function renderConfirmPreview(order, subjectTpl, bodyTpl) {
        const vars = getVarsFromOrder(order);

        const name  = (vars.supplierName || '').trim();
        const email = normalizeEmail(vars.supplierEmail);

        const hasEmail = !!email;
        const okEmail  = hasEmail && isValidEmail(email);

        // destinatário
        if (elConfirmTo) {
            elConfirmTo.textContent = okEmail
                ? `${name || 'Fornecedor'} <${email}>`
                : (name || 'Fornecedor');
        }

        // problema do e-mail (mostra claramente)
        const problem = !hasEmail
            ? 'Sem e-mail cadastrado para este fornecedor.'
            : (!okEmail ? `E-mail inválido: ${email}` : '');

        if (elConfirmNoEmail) {
            elConfirmNoEmail.textContent = problem || '';
            elConfirmNoEmail.classList.toggle('hidden', !problem);
        }

        // assunto/corpo
        const subject = applyTpl(subjectTpl, vars);
        const body    = applyTpl(bodyTpl, vars);

        if (elConfirmSubject) elConfirmSubject.textContent = subject || '—';
        if (elConfirmBody) elConfirmBody.textContent = body || '—';

        // pdf
        if (elConfirmPdfName) {
            elConfirmPdfName.textContent = `Proposta-${vars.partOrderNumber || 'pedido'}.pdf`;
        }

        // botão enviar: só habilita com e-mail válido
        const btnConfirm = document.getElementById('btn-confirm-send');
        if (btnConfirm) {
            btnConfirm.disabled = !okEmail;
            btnConfirm.classList.toggle('opacity-50', !okEmail);
            btnConfirm.classList.toggle('cursor-not-allowed', !okEmail);
        }
    }

    async function hydrateConfirmModalFromCache() {
        const settings = await PartsSettings.get();

        confirmTpl.subject = settings?.email_subject_tpl || 'Pedido de peças @{{partOrderNumber}}';
        confirmTpl.body    = settings?.email_body_tpl || 'Olá @{{supplierName}},\n\nSegue o pedido @{{partOrderNumber}}.\n\nObrigado.';

        currentOrderForSend = buildPreviewOrderFromForm();
        renderConfirmPreview(currentOrderForSend, confirmTpl.subject, confirmTpl.body);
    }

    async function openConfirm() {
        // ✅ abre modal e já renderiza preview
        modalConfirm?.classList.remove('hidden');
        await hydrateConfirmModalFromCache();
    }

    async function openSendConfirm() {
        confirmMode = 'send';
        confirmOrderId = null;

        modalConfirm?.classList.remove('hidden');
        await hydrateConfirmModalFromCache(); // já renderiza preview pelo form
    }

    async function openResendConfirm(id) {
        confirmMode = 'resend';
        confirmOrderId = id;

        const o = await apiFetch(URL.show(id));

        // monta um "order" compatível com renderConfirmPreview
        currentOrderForSend = {
            order_number: o.order_number || '',
            order_date: o.order_date || todayISO(),
            items_count: o.items_count ?? (o.items?.length ?? 0),
            grand_total: o.grand_total ?? 0,
            supplier: {
                name: o.supplier?.name || o.supplier_name || 'Fornecedor',
                email: o.supplier_email_used || o.supplier?.email || o.supplier_email || '',
            },
        };

        // usa snapshot do envio; se não tiver, cai no template atual
        const subject = (o.email_subject_used || '').trim();
        const body = (o.email_body_used || '').trim();

        if (subject && body) {
            renderConfirmPreview(currentOrderForSend, subject, body);
        } else {
            const settings = await PartsSettings.get();
            renderConfirmPreview(
                currentOrderForSend,
                settings?.email_subject_tpl || 'Pedido de peças {{partOrderNumber}}',
                settings?.email_body_tpl || 'Olá {{supplierName}},\n\nSegue o pedido {{partOrderNumber}}.\n\nObrigado.'
            );
        }

        modalConfirm?.classList.remove('hidden');
    }

    btnOpenSettings?.addEventListener('click', async () => {
        const settings = await PartsSettings.get();
        PartsSettings.applyToSettingsModal(settings);
        openModalById('modal-parts-settings');
    });

    document.getElementById('ps-email-subject')?.addEventListener('input', (e) => {
        if (!currentOrderForSend) return;
        if (modalConfirm?.classList.contains('hidden')) return;

        const subj = e.target.value || '';
        const body = document.getElementById('ps-email-body')?.value || '';
        renderConfirmPreview(currentOrderForSend, subj, body);
    });

    document.getElementById('ps-email-body')?.addEventListener('input', (e) => {
        if (!currentOrderForSend) return;
        if (modalConfirm?.classList.contains('hidden')) return;

        const subj = document.getElementById('ps-email-subject')?.value || '';
        const body = e.target.value || '';
        renderConfirmPreview(currentOrderForSend, subj, body);
    });

// quando salvar settings, re-render no confirm (se estiver aberto)
    document.addEventListener('partOrderSettingsSaved', async () => {
        if (modalConfirm?.classList.contains('hidden')) return;
        await hydrateConfirmModalFromCache();
    });

    function formatDateBR(isoOrDate) {
        if (!isoOrDate) return '';
        // pode vir Date/Carbon serializado ou string "YYYY-MM-DD"
        const s = typeof isoOrDate === 'string' ? isoOrDate : String(isoOrDate);
        const p = s.split('T')[0].split('-');
        if (p.length !== 3) return s;
        const [y, m, d] = p;
        return `${d}/${m}/${y}`;
    }

    function moneyBR(v) {
        const n = Number(v || 0);
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(n);
    }

    function applyTpl(tpl, vars) {
        return String(tpl || '').replace(/@?{{\s*([a-zA-Z0-9_]+)\s*}}/g, (_, key) => {
            const val = vars[key];
            return (val === undefined || val === null) ? '' : String(val);
        });
    }

    const sleep = (ms) => new Promise(r => setTimeout(r, ms));

    function isColorClass(c) {
        return (
            /^bg-(slate|gray|zinc|neutral|stone|red|rose|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink)-\d+$/.test(c) ||
            /^hover:bg-(slate|gray|zinc|neutral|stone|red|rose|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink)-\d+$/.test(c) ||
            /^text-(white|black|(slate|gray|zinc|neutral|stone|red|rose|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink)-\d+)$/.test(c) ||
            /^border-(slate|gray|zinc|neutral|stone|red|rose|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink)-\d+$/.test(c)
        );
    }

    function setBtnState(btn, state) {
        if (!btn) return;

        // salva base 1x
        if (!btn.dataset.baseHtml) btn.dataset.baseHtml = btn.innerHTML;
        if (!btn.dataset.baseClass) btn.dataset.baseClass = btn.className;

        // reset
        btn.innerHTML = btn.dataset.baseHtml;
        btn.className = btn.dataset.baseClass;
        btn.disabled = false;

        const stripColors = () => {
            [...btn.classList].forEach((c) => { if (isColorClass(c)) btn.classList.remove(c); });
        };

        if (state === 'loading') {
            stripColors();
            btn.disabled = true;
            btn.classList.add('bg-slate-400', 'text-white', 'cursor-wait', 'inline-flex', 'items-center', 'gap-2');

            btn.innerHTML = `
      <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity="0.25"></circle>
        <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="4" stroke-linecap="round"></path>
      </svg>
      Enviando...
    `;
        }

        if (state === 'success') {
            stripColors();
            btn.disabled = true;
            btn.classList.add('bg-emerald-600', 'text-white', 'inline-flex', 'items-center', 'gap-2');

            btn.innerHTML = `
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
        <path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      Enviado
    `;
        }

        if (state === 'error') {
            stripColors();
            btn.disabled = false;
            btn.classList.add('bg-rose-600', 'text-white', 'inline-flex', 'items-center', 'gap-2');

            btn.innerHTML = `
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
        <path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
      </svg>
      Erro ao enviar
    `;
        }
    }

    function closeConfirm() {
        modalConfirm?.classList.add('hidden');
        if (btnConfirm) {
            btnConfirm.dataset.busy = '0';
            setBtnState(btnConfirm, 'idle');
        }
    }

    function openSuccess(id) { lastSentId = id; succModal?.classList.remove('hidden'); }
    function closeSuccess() { succModal?.classList.add('hidden'); }

    function openViewModal(order) {
        const btnViewResend = document.getElementById('btn-view-resend');

        const isDraft = String(order.status || '').toLowerCase() === 'draft';

        btnViewResend?.classList.toggle('hidden', isDraft);

        if (btnViewResend) {
            btnViewResend.onclick = () => {
                closeViewModal();
                openResendConfirm(order.id);
            };
        }

        modalView?.classList.remove('hidden');
    }

    function closeViewModal() { modalView?.classList.add('hidden'); }

    document.addEventListener('click', (ev) => {
        if (ev.target.closest('[data-close-parts]')) closeModal(true);
        if (ev.target.closest('[data-close-view]')) closeViewModal();
    });

    modal?.addEventListener('click', (e) => { if (e.target === modal) closeModal(true); });
    modalConfirm?.addEventListener('click', (e) => { if (e.target === modalConfirm) closeConfirm(); });
    succModal?.addEventListener('click', (e) => { if (e.target === succModal) closeSuccess(); });
    modalView?.addEventListener('click', (e) => { if (e.target === modalView) closeViewModal(); });

    // ===== FILTERS / SEARCH =====
    fltBtns.forEach((b) =>
        b.addEventListener('click', () => {
            statusFilter = b.dataset.status;
            fltBtns.forEach((x) => {
                x.className =
                    'flt rounded-full px-3.5 py-1.5 text-sm font-medium ring-1 ring-inset ' +
                    (x === b
                        ? 'bg-blue-50 text-blue-700 ring-blue-200'
                        : 'bg-white text-slate-700 ring-slate-200 hover:bg-slate-50');
            });
            render();
        }),
    );

    searchInput?.addEventListener('input', (e) => {
        searchTxt = String(e.target.value || '').toLowerCase().trim();
        render();
    });

    // ===== HEADER ACTIONS =====
    root.addEventListener('click', (ev) => {
        const a = ev.target.closest('[data-action]');
        if (!a) return;
        const act = a.dataset.action;
        if (act === 'newParts') openNew();
    });

    if (!window.__cliqisPartsNewBtnPatch_v3) {
        window.__cliqisPartsNewBtnPatch_v3 = true;

        document.addEventListener('click', (ev) => {
            const trg = ev.target.closest('#btn-new-parts');
            if (!trg) return;

            ev.preventDefault();
            ev.stopPropagation();
            ev.stopImmediatePropagation();

            openNew();
        }, true); // ✅ CAPTURE
    }

    const UF_ALL = ["AC","AL","AM","AP","BA","CE","DF","ES","GO","MA","MG","MS","MT","PA","PB","PE","PI","PR","RJ","RN","RO","RR","RS","SC","SE","SP","TO"];
    const UF_DEFAULT = ["SP","RJ","MG","PR","SC"];

    function showDropdown(dd, html) {
        dd.innerHTML = html;
        dd.classList.remove('hidden');
    }
    function hideDropdown(dd) {
        dd.classList.add('hidden');
        dd.innerHTML = '';
    }

    function insertAtCursor(el, text) {
        const start = el.selectionStart ?? el.value.length;
        const end = el.selectionEnd ?? el.value.length;
        el.value = el.value.slice(0, start) + text + el.value.slice(end);
        const pos = start + text.length;
        el.setSelectionRange(pos, pos);
        el.focus();
    }

    async function fetchJSON(url, opts = {}) {
        const token = csrf(); // <- chama a função

        const res = await fetch(url, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(opts.body ? { 'Content-Type': 'application/json' } : {}),
                ...(token ? { 'X-CSRF-TOKEN': token } : {}),
                ...(opts.headers || {}),
            },
            ...opts,
        });

        const data = await res.json().catch(() => null);

        if (!res.ok) {
            throw new Error(data?.message || data?.error || `Erro HTTP ${res.status}`);
        }

        return data;
    }

    const PartsSettings = {
        cache: null,

        async get() {
            if (this.cache) return this.cache;
            const json = await fetchJSON('/part-orders/settings');
            this.cache = json.data;
            return this.cache;
        },

        async save(payload) {
            const json = await fetchJSON('/part-orders/settings', {
                method: 'PUT',
                body: JSON.stringify(payload),
            });
            this.cache = json.data;
            return this.cache;
        },

        async suppliers(q = '') {
            const url = `/entities/supplier/typeahead?q=${encodeURIComponent(q)}`;
            const json = await fetchJSON(url);
            return json.data || [];
        },

        applyToOrderModal(settings) {
            const sid = document.getElementById('pp-supplier-id');
            const sname = document.getElementById('pp-supplier-name');
            const semail = document.getElementById('pp-supplier-email');

            if (sid && sname) {
                sid.value = settings?.default_supplier_id || '';
                sname.value = settings?.supplier?.name || '';
            }
            if (semail) {
                semail.value = settings?.supplier?.email || '';
            }

            supplierCurrent.email = settings?.supplier?.email || '';

            const cnpj = document.getElementById('pp-cnpj');
            if (cnpj) cnpj.value = formatCNPJ(settings?.billing_cnpj || '');

            const uf = document.getElementById('pp-uf');
            if (uf && settings?.billing_uf) uf.value = settings.billing_uf;
        },

        applyToSettingsModal(settings) {
            document.getElementById('ps-supplier-id').value = settings?.default_supplier_id || '';
            document.getElementById('ps-supplier-name').value = settings?.supplier?.name || '';
            document.getElementById('ps-cnpj').value = formatCNPJ(settings?.billing_cnpj || '');
            document.getElementById('ps-uf').value = settings?.billing_uf || '';
            document.getElementById('ps-email-subject').value = settings?.email_subject_tpl || '';
            document.getElementById('ps-email-body').value = settings?.email_body_tpl || '';
        },
    };

    function bindUfTypeahead(inputId, ddId) {
        const input = document.getElementById(inputId);
        const dd = document.getElementById(ddId);
        if (!input || !dd) return;

        function render(list) {
            const html = list.map(uf => `
      <button type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-slate-50"
              data-uf="${uf}">${uf}</button>
    `).join('');
            showDropdown(dd, html || `<div class="px-3 py-2 text-sm text-slate-500">Nada encontrado</div>`);
        }

        input.addEventListener('focus', () => {
            const v = input.value.trim().toUpperCase();
            if (!v) render(UF_DEFAULT);
        });

        input.addEventListener('input', () => {
            const v = input.value.trim().toUpperCase();
            input.value = v;
            const list = v ? UF_ALL.filter(x => x.includes(v)).slice(0, 8) : UF_DEFAULT;
            render(list);
        });

        dd.addEventListener('click', (e) => {
            const btn = e.target.closest('button[data-uf]');
            if (!btn) return;
            input.value = btn.dataset.uf;
            hideDropdown(dd);
            input.dispatchEvent(new Event('change'));
        });

        document.addEventListener('click', (e) => {
            if (e.target === input || dd.contains(e.target)) return;
            hideDropdown(dd);
        });
    }

    function bindSupplierTypeahead(inputId, hiddenId, ddId) {
        const input = document.getElementById(inputId);
        const hidden = document.getElementById(hiddenId);
        const dd = document.getElementById(ddId);
        if (!input || !hidden || !dd) return;

        let timer = null;

        async function search(q) {
            const rows = await PartsSettings.suppliers(q);
            const html = rows.map(s => `
      <button type="button" class="w-full text-left px-3 py-2 hover:bg-slate-50"
              data-id="${s.id}" data-name="${(s.name||'').replaceAll('"','&quot;')}" data-email="${s.email||''}">
        <div class="text-sm font-medium text-slate-800">${s.name || '—'}</div>
        <div class="text-xs text-slate-500">${s.email || 'Sem e-mail'} • ${s.cpfCnpj || 'Sem CNPJ'}</div>
      </button>
    `).join('');

            showDropdown(dd, html || `<div class="px-3 py-2 text-sm text-slate-500">Nenhum fornecedor</div>`);
        }

        input.addEventListener('focus', () => search(''));

        input.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => search(input.value.trim()), 180);
        });

        dd.addEventListener('click', (e) => {
            const btn = e.target.closest('button[data-id]');
            if (!btn) return;

            hidden.value = btn.dataset.id;
            input.value = btn.dataset.name;

            const emailHidden = document.getElementById('pp-supplier-email');
            if (emailHidden && input.id === 'pp-supplier-name') {
                emailHidden.value = btn.dataset.email || '';
            }

            hideDropdown(dd);

            input.dispatchEvent(new Event('change'));

            input.dispatchEvent(new CustomEvent('cliqis:supplier-selected', {
                bubbles: true,
                detail: { id: hidden.value, name: input.value, email: btn.dataset.email || '' }
            }));
        });

        document.addEventListener('click', (e) => {
            if (e.target === input || dd.contains(e.target)) return;
            hideDropdown(dd);
        });
    }

    async function initPartsSettingsUI() {
        // binds typeaheads
        bindSupplierTypeahead('ps-supplier-name', 'ps-supplier-id', 'ps-supplier-dd');
        bindSupplierTypeahead('pp-supplier-name', 'pp-supplier-id', 'pp-supplier-dd');

        bindUfTypeahead('ps-uf', 'ps-uf-dd');
        bindUfTypeahead('pp-uf', 'pp-uf-dd');

        // abrir modal config
        document.getElementById('btn-parts-settings')?.addEventListener('click', async () => {
            const settings = await PartsSettings.get();
            PartsSettings.applyToSettingsModal(settings);
            openModalById('modal-parts-settings');
        });

        // fechar modal config
        document.querySelectorAll('[data-close-settings]').forEach(el => {
            el.addEventListener('click', () => closeModalById('modal-parts-settings'));
        });

        // salvar config
        document.getElementById('btn-save-settings')?.addEventListener('click', async (e) => {
            e.preventDefault();

            const payload = {
                default_supplier_id: document.getElementById('ps-supplier-id').value || null,
                billing_cnpj: document.getElementById('ps-cnpj').value || null,
                billing_uf: (document.getElementById('ps-uf').value || '').toUpperCase() || null,
                email_subject_tpl: document.getElementById('ps-email-subject').value || null,
                email_body_tpl: document.getElementById('ps-email-body').value || null,
            };

            const settings = await PartsSettings.save(payload);

            // ✅ aplica imediatamente no modal de pedidos (sem refresh)
            PartsSettings.applyToOrderModal(settings);

            document.dispatchEvent(new Event('partOrderSettingsSaved'));

            closeModalById('modal-parts-settings');
            toast('Configurações salvas');
        });

        // inserir variáveis no body (clique nos botões)
        document.querySelectorAll('.ps-var').forEach(btn => {
            btn.addEventListener('click', () => {
                const v = btn.getAttribute('data-var');
                const textarea = document.getElementById('ps-email-body');
                if (textarea && v) insertAtCursor(textarea, v);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPartsSettingsUI);
    } else {
        initPartsSettingsUI();
    }


    // ============== RECEIVE ITENS

    const receiveDialog = document.getElementById('receive-modal');

    const pendingListEl = document.getElementById('pendingList');
    const doneListEl = document.getElementById('doneList');
    const doneCountEl = document.getElementById('doneCount');
    const doneDetailsEl = document.getElementById('doneDetails');

    const btnConfirmReceive = document.getElementById('btnConfirm'); // ✅ id certo
    const btnReceiveClose = document.getElementById('btn-receive-close');

    let receiveMode = 'total'; // total | partial
    let priceMode = 'sale';    // sale | markup

    function getAllRecItems() {
        return Array.from(receiveDialog.querySelectorAll('[data-rec-item]'));
    }

    function calcRemaining(it) {
        const ordered = Number(it.quantity || 0);
        const received = Number(it.received_qty || it.receivedQty || 0);
        const rem = Math.max(ordered - received, 0);
        // seu sistema é inteiro
        return Number.isFinite(rem) ? Math.trunc(rem) : 0;
    }

    function clampInt(v, min, max) {
        let n = parseInt(v || '0', 10);
        if (!Number.isFinite(n)) n = 0;
        return Math.max(min, Math.min(max, n));
    }

    /* ====== TOGGLES ====== */
    function renderReceiveToggles() {
        const host = document.getElementById('receive-toggles');
        if (!host) return;

        host.innerHTML = `
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="inline-flex rounded-full bg-slate-100 p-1">
        <button type="button" data-mode="total"
          class="px-4 py-2 text-sm font-semibold rounded-full ${receiveMode==='total'?'bg-white shadow text-slate-900':'text-slate-600'}">
          Entrada total
        </button>
        <button type="button" data-mode="partial"
          class="px-4 py-2 text-sm font-semibold rounded-full ${receiveMode==='partial'?'bg-white shadow text-slate-900':'text-slate-600'}">
          Entrada parcial
        </button>
      </div>

      <div class="inline-flex rounded-full bg-slate-100 p-1">
        <button type="button" data-price="sale"
          class="px-4 py-2 text-sm font-semibold rounded-full ${priceMode==='sale'?'bg-white shadow text-slate-900':'text-slate-600'}">
          Preço de venda
        </button>
        <button type="button" data-price="markup"
          class="px-4 py-2 text-sm font-semibold rounded-full ${priceMode==='markup'?'bg-white shadow text-slate-900':'text-slate-600'}">
          Margem %
        </button>
      </div>
    </div>`;

        host.querySelectorAll('[data-mode]').forEach(b => {
            b.onclick = () => { receiveMode = b.dataset.mode; renderReceiveToggles(); applyReceiveModeUI(); };
        });

        host.querySelectorAll('[data-price]').forEach(b => {
            b.onclick = () => { priceMode = b.dataset.price; renderReceiveToggles(); applyPriceModeUI(); };
        });
    }

    /* ====== PRICE MASK (BRL) ====== */
    function onlyDigits(v) { return String(v || '').replace(/[^\d]/g, ''); }
    function formatBRLFromDigits(digits) {
        const d = onlyDigits(digits);
        const n = Number(d || 0) / 100;
        return n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function parseBRLToNumber(v) {
        const s = String(v || '').trim().replace(/\./g,'').replace(',','.');
        const n = Number(s);
        return Number.isFinite(n) ? n : 0;
    }

    function bindSaleMasks() {
        receiveDialog.querySelectorAll('[data-sale]').forEach(inp => {
            if (inp.dataset.maskBound === '1') return;
            inp.dataset.maskBound = '1';

            // garante tipo
            inp.type = 'text';
            inp.inputMode = 'numeric';

            // inicia formatado
            if (!inp.value || inp.value === '0') inp.value = '0,00';
            else inp.value = formatBRLFromDigits(inp.value);

            inp.addEventListener('input', (e) => {
                e.target.value = formatBRLFromDigits(e.target.value);
                e.target.setSelectionRange(e.target.value.length, e.target.value.length);
            });

            inp.addEventListener('blur', (e) => {
                e.target.value = formatBRLFromDigits(e.target.value);
            });
        });
    }

    /* ====== MODOS UI ====== */
    function applyReceiveModeUI() {
        const lis = getAllRecItems();

        lis.forEach(li => {
            const rem = parseInt(li.dataset.remaining || '0', 10) || 0;
            const qtyInput = li.querySelector('[data-qty]');
            const pill = li.querySelector('[data-qty-pill]');

            const isDone = rem <= 0;

            if (qtyInput) {
                qtyInput.max = String(rem);
                if (isDone) {
                    qtyInput.value = '0';
                    qtyInput.readOnly = true;
                    qtyInput.classList.add('hidden');
                    if (pill) { pill.textContent = '0'; pill.classList.remove('hidden'); }
                } else if (receiveMode === 'total') {
                    qtyInput.value = String(rem);
                    qtyInput.readOnly = true;
                    qtyInput.classList.add('hidden');
                    if (pill) { pill.textContent = String(rem); pill.classList.remove('hidden'); }
                } else {
                    qtyInput.readOnly = false;
                    qtyInput.classList.remove('hidden');
                    if (pill) pill.classList.add('hidden');
                    // clamp se já veio maior
                    qtyInput.value = String(clampInt(qtyInput.value, 0, rem));
                }
            }

            // split por local: no modo total joga tudo no primeiro
            const mustSplit = receiveDialog.dataset.mustSplit === '1';
            if (mustSplit) {
                const locQtyInputs = li.querySelectorAll('[data-loc-row] [data-loc-qty]');
                if (locQtyInputs.length) {
                    if (receiveMode === 'total') {
                        locQtyInputs.forEach((inp, idx) => inp.value = (idx === 0 ? String(rem) : '0'));
                    } else {
                        // parcial: se só 1 linha, acompanha qty
                        if (locQtyInputs.length === 1 && qtyInput) {
                            locQtyInputs[0].value = String(clampInt(qtyInput.value, 0, rem));
                        }
                    }
                }
            }
        });

        updateReceiveKpis();
    }

    function applyPriceModeUI() {
        const lis = getAllRecItems();
        lis.forEach(li => {
            const rem = parseInt(li.dataset.remaining || '0', 10) || 0;
            const isDone = rem <= 0;

            const saleWrap = li.querySelector('[data-sale-wrap]');
            const markupWrap = li.querySelector('[data-markup-wrap]');
            const sale = li.querySelector('[data-sale]');
            const markup = li.querySelector('[data-markup]');

            // finalizado: trava inputs
            if (sale) sale.disabled = isDone;
            if (markup) markup.disabled = isDone;

            if (priceMode === 'sale') {
                saleWrap?.classList.remove('hidden');
                markupWrap?.classList.add('hidden');
                if (markup) markup.value = '0';
            } else {
                markupWrap?.classList.remove('hidden');
                saleWrap?.classList.add('hidden');
                if (sale) sale.value = '0,00';
            }
        });

        bindSaleMasks();
    }

    /* ====== KPIs ====== */
    function computeReceiveTotalsFromUI() {
        const lis = Array.from(receiveItemsEl.querySelectorAll('[data-rec-item]'));

        let itemsPending = 0;
        let qtyRemaining = 0;
        let qtyTotalOrder = 0;
        let qtyWillReceive = 0;

        lis.forEach(li => {
            const ordered = parseInt(li.dataset.ordered || '0', 10);
            const remaining = parseInt(li.dataset.remaining || '0', 10);

            qtyTotalOrder += ordered;
            qtyRemaining += remaining;

            if (remaining > 0) itemsPending++;

            // qty do input (modo parcial) - clamp pra não passar do remaining
            const typed = parseInt(li.querySelector('[data-qty]')?.value || '0', 10);
            const will = (receiveMode === 'total')
                ? remaining
                : Math.max(0, Math.min(typed, remaining));

            qtyWillReceive += will;
        });

        return { itemsPending, qtyRemaining, qtyTotalOrder, qtyWillReceive };
    }

    function updateReceiveKpis() {
        const box = document.getElementById('receive-kpis');
        if (!box) return;

        const t = computeReceiveTotalsFromUI();

        box.innerHTML = `
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
      <div class="text-[11px] text-slate-500">Itens pendentes</div>
      <div class="text-lg font-semibold">${t.itemsPending}</div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
      <div class="text-[11px] text-slate-500">Restantes / Total</div>
      <div class="text-lg font-semibold">${t.qtyRemaining} / ${t.qtyTotalOrder}</div>
      <div class="mt-1 text-[11px] text-slate-500">Entrada agora: ${t.qtyWillReceive}</div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
      <div class="text-[11px] text-slate-500">Modo</div>
      <div class="text-lg font-semibold">${receiveMode === 'total' ? 'Total' : 'Parcial'}</div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
      <div class="text-[11px] text-slate-500">Preço</div>
      <div class="text-lg font-semibold">${priceMode === 'sale' ? 'Venda' : 'Margem %'}</div>
    </div>
  </div>`;
    }


    /* ====== RENDER ITENS (SEPARA PENDENTES x FINALIZADOS) ====== */
    function renderReceiveItems(data) {
        const mustSplit = !!data.must_split_by_location;
        const locations = data.locations || [];
        const defaultLocId = data.default_location_id || '';

        receiveItemsEl.innerHTML = (data.items || []).map(it => {
            const ordered = parseInt(it.quantity || 0, 10);
            const received = parseInt(it.received_qty || 0, 10);
            const remaining = (it.remaining != null)
                ? parseInt(it.remaining, 10)
                : Math.max(0, ordered - received);

            return `
<li class="p-4"
    data-rec-item="${it.id}"
    data-ordered="${ordered}"
    data-received="${received}"
    data-remaining="${remaining}">
  <div class="grid grid-cols-12 gap-8 items-start">

    <!-- ESQUERDA: código / nome / unit -->
    <div class="col-span-12 md:col-span-5">
      <div class="text-xs text-slate-500">${escapeHtml(it.code || '-')}</div>
      <div class="text-sm font-semibold text-slate-900">${escapeHtml(it.description || '-')}</div>

      <div class="mt-2 text-[11px] text-slate-500 mb-1">
        Custo: <span class="text-slate-700 font-bold">${fmtBR(it.unit_price || 0)}</span> / ${fmtBR(it.line_total || 0)}
      </div>

        <div class="text-[11px] text-slate-500">
          Recebido: <span class="text-slate-700 font-bold">${it.received_qty || 0}</span> / Pedido: <span class="text-slate-700 font-bold">${it.quantity || 0}</span>
        </div>
    </div>

    <!-- DIREITA: recebido/pedido + inputs -->
    <div class="col-span-12 md:col-span-7 md:justify-self-end">
      <div class="flex flex-col items-end gap-2">

        <!-- inputs abaixo, alinhados à direita -->
        <div class="grid grid-cols-12 gap-8 w-full md:w-auto">

          <!-- QTD -->
          <div class="col-span-12 sm:col-span-6 md:col-span-6 justify-self-end">
            <div class="text-xs font-medium text-slate-600 text-left mb-1">Entrada de</div>

            <div data-qty-pill class="hidden h-10 rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-900 flex items-center justify-center tabular-nums"></div>

            <input data-qty type="number" min="0" max="${remaining}"
              class="h-10 text-center rounded-xl border border-slate-300 bg-slate-50 px-3 text-sm font-semibold text-slate-900 shadow-sm outline-none focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 flex items-center justify-center tabular-nums"
              value="${remaining}">
          </div>

          <!-- VENDA -->
          <div class="col-span-12 sm:col-span-6 md:col-span-6 justify-self-end" data-sale-wrap>
            <div class="text-xs font-medium text-slate-600 text-left mb-1">Preço de venda <i class="font-light text-slate-500">unidade</i></div>
            <input data-sale type="text" inputmode="numeric"
              class="h-10 rounded-xl border border-slate-300 bg-slate-50 px-3 text-sm font-semibold text-slate-900 shadow-sm outline-none focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 flex items-center justify-center tabular-nums"
              value="0,00">
          </div>

          <!-- MARGEM -->
          <div class="col-span-12 md:col-span-6 justify-self-end hidden" data-markup-wrap>
            <div class="text-xs font-medium text-slate-600 text-left mb-1">Margem lucro (%) <i class="font-light text-slate-500">unidade</i></div>
            <input data-markup type="number" min="0" step="0.01"
              class="h-10 rounded-xl border border-slate-300 bg-slate-50 px-3 text-sm font-semibold text-slate-900 shadow-sm outline-none focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 flex items-center justify-center tabular-nums"
              value="0">
          </div>

        </div>
      </div>
    </div>

    <!-- + Local -->
    <div class="col-span-12 ${mustSplit ? '' : 'hidden'} flex justify-end">
      <button type="button" data-add-loc
        class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
        + Local
      </button>
    </div>

  </div>

  <div class="${mustSplit ? 'mt-3' : 'hidden'}" data-locs>
    ${renderLocRow(locations, defaultLocId, remaining)}
  </div>
</li>`;


            return { remaining, html: liHtml };
        });

        // split
        const pending = items.filter(x => x.remaining > 0).map(x => x.html).join('');
        const done = items.filter(x => x.remaining <= 0).map(x => x.html).join('');

        pendingListEl.innerHTML = pending || `<li class="p-4 text-sm text-slate-500">Nenhum item pendente.</li>`;
        doneListEl.innerHTML = done || `<li class="p-4 text-sm text-slate-500">Nenhum item finalizado.</li>`;

        // rebind
        bindSaleMasks();
    }

    /* ====== LOCAIS ====== */
    function renderLocRow(locations, defaultLocId, qty) {
        const opts = locations.map(l => `<option value="${l.id}" ${l.id === defaultLocId ? 'selected' : ''}>${escapeHtml(l.name)}</option>`).join('');
        return `
    <div class="grid grid-cols-12 gap-3 items-end mb-2" data-loc-row>
      <div class="col-span-8">
        <div class="text-[11px] text-slate-500">Local</div>
        <select data-loc-id class="h-9 w-full rounded-xl border border-slate-200 px-3 text-sm">
          ${opts}
        </select>
      </div>
      <div class="col-span-3">
        <div class="text-[11px] text-slate-500">Qtd</div>
        <input data-loc-qty type="number" min="0" class="h-9 w-full rounded-xl border border-slate-200 px-3 text-sm" value="${qty}">
      </div>
      <div class="col-span-1 text-right">
        <button type="button" data-del-loc class="rounded-lg px-2 py-1 text-[11px] font-semibold text-rose-700 hover:bg-rose-50">✕</button>
      </div>
    </div>
  `;
    }

    /* ====== EVENTOS ====== */
    btnReceiveClose?.addEventListener('click', () => receiveDialog.close());

// input geral (KPIs + clamp + sync loc)
    receiveDialog.addEventListener('input', (e) => {
        if (!e.target.matches('[data-qty],[data-loc-qty],[data-sale],[data-markup]')) return;

        const li = e.target.closest('[data-rec-item]');
        if (li && e.target.matches('[data-qty]')) {
            const rem = parseInt(li.dataset.remaining || '0', 10) || 0;
            e.target.value = String(clampInt(e.target.value, 0, rem));

            // se mustSplit e só 1 local, acompanha qty
            const mustSplit = receiveDialog.dataset.mustSplit === '1';
            if (mustSplit) {
                const rows = li.querySelectorAll('[data-loc-row]');
                if (rows.length === 1) {
                    const locQty = rows[0].querySelector('[data-loc-qty]');
                    if (locQty) locQty.value = e.target.value;
                }
            }
        }

        updateReceiveKpis();
    });

    document.addEventListener('click', (e) => {
        const add = e.target.closest('[data-add-loc]');
        if (add) {
            const li = add.closest('[data-rec-item]');
            const locs = li.querySelector('[data-locs]');
            const mustSplit = receiveDialog.dataset.mustSplit === '1';
            if (!mustSplit) return;

            const dataLocations = window.__receiveLocations || [];
            const defaultLocId = window.__receiveDefaultLocId || '';
            locs.insertAdjacentHTML('beforeend', renderLocRow(dataLocations, defaultLocId, 0));
            return;
        }

        const del = e.target.closest('[data-del-loc]');
        if (del) del.closest('[data-loc-row]')?.remove();
    });

    /* ====== OPEN MODAL ====== */
    const urlReceiveData = (id) => `${id}/receive-data`;
    const urlReceive = (id) => `${id}/receive`;

    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-finalize]');
        if (!btn) return;
        await openReceiveModal(btn.getAttribute('data-finalize'));
    });

    async function openReceiveModal(orderId) {
        const res = await fetch(urlReceiveData(orderId), { headers: { Accept: 'application/json' }});
        const data = await res.json().catch(() => ({}));

        if (!res.ok) {
            toast(data?.message || 'Falha ao carregar dados do recebimento.');
            return;
        }

        window.__receiveLocations = data.locations || [];
        window.__receiveDefaultLocId = data.default_location_id || '';

        receiveDialog.dataset.orderId = orderId;
        receiveDialog.dataset.mustSplit = (data.must_split_by_location ? '1' : '0');

        receiveMode = 'total';
        priceMode = 'sale';

        renderReceiveItems(data);
        renderReceiveToggles();

        applyReceiveModeUI();
        applyPriceModeUI();
        updateReceiveKpis();

        receiveDialog.showModal();
    }

    /* ====== CONFIRMAR ====== */
    btnConfirmReceive?.addEventListener('click', async () => {
        const orderId = receiveDialog.dataset.orderId;
        const mustSplit = receiveDialog.dataset.mustSplit === '1';

        const lis = getAllRecItems();

        // monta somente itens com qty > 0 (limpa payload)
        const items = lis.map(li => {
            const partOrderItemId = li.getAttribute('data-rec-item');
            const rem = parseInt(li.dataset.remaining || '0', 10) || 0;

            const qty = (receiveMode === 'total')
                ? rem
                : clampInt(li.querySelector('[data-qty]')?.value, 0, rem);

            let sale_price = 0;
            let markup_percent = 0;

            if (priceMode === 'sale') {
                sale_price = parseBRLToNumber(li.querySelector('[data-sale]')?.value || '0');
            } else {
                markup_percent = parseFloat(li.querySelector('[data-markup]')?.value || '0') || 0;
            }

            let locations = [];
            if (mustSplit) {
                locations = Array.from(li.querySelectorAll('[data-loc-row]')).map(r => ({
                    location_id: r.querySelector('[data-loc-id]')?.value,
                    qty: clampInt(r.querySelector('[data-loc-qty]')?.value, 0, 999999),
                })).filter(x => (x.qty || 0) > 0);
            }

            return { part_order_item_id: partOrderItemId, qty, sale_price, markup_percent, locations, __rem: rem };
        }).filter(x => (x.qty || 0) > 0); // ✅ só o que vai entrar

        if (receiveMode === 'partial' && items.length === 0) {
            return toast('Informe ao menos 1 item com qty > 0.');
        }

        if (mustSplit) {
            for (const it of items) {
                const sum = (it.locations || []).reduce((a, b) => a + (b.qty || 0), 0);
                if (sum !== it.qty) return toast('Distribuição por locais não fecha em um item.');
            }
        }

        const res = await fetch(urlReceive(orderId), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                ...(csrf() ? { 'X-CSRF-TOKEN': csrf() } : {}),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ mode: receiveMode, items }),
        });

        const out = await res.json().catch(() => ({}));

        if (!res.ok) {
            toast(out?.message || 'Falha ao confirmar entrada.');
            return;
        }

        toast('Entrada confirmada.');
        receiveDialog.close();
        await loadList();
    });

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, (m) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
    }

    document.addEventListener('cliqis:supplier-selected', async (ev) => {
        // só interessa no pedido
        if (ev.target?.id !== 'pp-supplier-name') return;

        // se modal do pedido não está aberto, ignora
        if (modal?.classList.contains('hidden')) return;

        if (suppressSupplierPrompt) return;

        const d = ev.detail || {};
        const newId = d.id || '';
        const newName = d.name || '';

        // atualiza current
        supplierCurrent = { id: newId, name: newName, email: d.email || '' };

        // se não mudou, sai
        if ((supplierBaseline.id || '') === (newId || '')) return;

        // evita ficar perguntando pro mesmo ID nessa sessão
        if (supplierPrompted.has(newId)) return;

        supplierPending = { ...supplierCurrent };

        if (supChoiceName) supChoiceName.textContent = newName || '—';
        openSupplierChoice();
    });

    btnSupOrderOnly?.addEventListener('click', () => {
        if (supplierPending?.id) supplierPrompted.add(supplierPending.id);
        closeSupplierChoice();
    });

    btnSupSetDefault?.addEventListener('click', async () => {
        if (!supplierPending?.id) return;

        try {
            // garante settings carregado e faz merge (não perde campos)
            const cur = await PartsSettings.get();
            const payload = {
                default_supplier_id: supplierPending.id,
                billing_cnpj: cur?.billing_cnpj || null,
                billing_uf: cur?.billing_uf || null,
                email_subject_tpl: cur?.email_subject_tpl || null,
                email_body_tpl: cur?.email_body_tpl || null,
            };

            const saved = await PartsSettings.save(payload);

            // baseline vira o novo (não pergunta mais)
            supplierBaseline = { id: supplierPending.id, name: supplierPending.name || '' };
            supplierPrompted.add(supplierPending.id);

            toast('Fornecedor padrão atualizado');
            closeSupplierChoice();

            // opcional: mantém settings modal coerente se abrir depois
            // PartsSettings.applyToSettingsModal(saved);
        } catch (e) {
            toast(e.message || 'Falha ao salvar fornecedor padrão.');
        }
    });

    // ===== FORM =====
    let formItems = [];

    cnpjInput?.addEventListener('input', (e) => (e.target.value = formatCNPJ(e.target.value)));
    ufSelect?.addEventListener('change', updateSummary);

    form?.addEventListener('keydown', (e) => {
        if (
            e.key === 'Enter' &&
            (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT' || e.target.tagName === 'TEXTAREA')
        ) {
            e.preventDefault();
            focusNext(e.target);
        }
    });

    function blankItem() {
        return { id: null, part_id: null, code: '', description: '', ncm: '', unit_price: 0, ipi_rate: 0, quantity: 1, discount_rate: 0, position: 0 };
    }

    function isFilled(it) {
        const hasText = (it.code && it.code.trim()) || (it.description && it.description.trim());
        const hasValue = Number(it.unit_price || 0) > 0;
        return Boolean(hasText || hasValue);
    }

    function cleanItems(items) {
        return items
            .filter(isFilled)
            .map((it, idx) => ({ ...it, quantity: Number(it.quantity || 1) || 1, position: Number(it.position ?? idx) || idx }));
    }

    function computeLine(it) {
        const base = Number(it.unit_price || 0) * Number(it.quantity || 0);
        const valIPI = base * (Number(it.ipi_rate || 0) / 100);
        const valComIPI = base + valIPI;
        const valDesc = valComIPI * (Number(it.discount_rate || 0) / 100);
        const valComDesc = valComIPI - valDesc;
        return { base, valIPI, valComIPI, valDesc, valComDesc };
    }

    function sumTotal(items, icmsRate = 0) {
        let sub = 0, ipi = 0, desc = 0, total = 0, count = 0;
        items.filter(isFilled).forEach((it) => {
            const c = computeLine(it);
            sub += Number(it.unit_price || 0) * Number(it.quantity || 0);
            ipi += c.valIPI;
            desc += c.valDesc;
            total += c.valComDesc;
            count++;
        });
        const icms = sub * (Number(icmsRate || 0) / 100);
        const final = total + icms;
        return { subtotal: sub, ipiTotal: ipi, descTotal: desc, icms, totalFinal: final, count };
    }

    function rowItemHTML(it, i) {
        return `
      <div data-row="${i}" class="parts-grid grid gap-3 py-2 text-xs">
        <input value="${escapeAttr(it.code)}" data-f="code" list="parts-codes" class="h-9 w-full rounded-lg border border-slate-300 px-2">
        <input value="${escapeAttr(it.description)}" data-f="description" class="h-9 w-full rounded-lg border border-slate-300 px-2">
        <input value="${escapeAttr(it.ncm)}" data-f="ncm" class="h-9 w-full rounded-lg border border-slate-300 px-2">
        <input value="${numToStr(it.unit_price)}" data-f="unit_price" inputmode="decimal" class="h-9 w-full rounded-lg border border-slate-300 px-2 text-right">
        <input value="${numToStr(it.ipi_rate)}" data-f="ipi_rate" inputmode="decimal" class="h-9 w-full rounded-lg border border-slate-300 px-2 text-right">
        <input value="${numToStr(it.quantity)}" data-f="quantity" inputmode="decimal" class="h-9 w-full rounded-lg border border-slate-300 px-2 text-right">
        <input data-f="valIpi" disabled class="h-9 w-full rounded-lg border border-slate-200 bg-slate-50 px-2 text-right">
        <input value="${numToStr(it.discount_rate)}" data-f="discount_rate" inputmode="decimal" class="h-9 w-full rounded-lg border border-slate-300 px-2 text-right">
        <input data-f="valDesc" disabled class="h-9 w-full rounded-lg border border-slate-200 bg-slate-50 px-2 text-right">
        <div class="flex items-center justify-end">
          <button type="button" data-remove class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-[11px] font-medium text-rose-700 hover:bg-rose-100">Remover</button>
        </div>
      </div>
    `;
    }

    function renderItems() {
        if (!itemsBody) return;

        if (!formItems.length) formItems = Array.from({ length: 5 }, (_, idx) => ({ ...blankItem(), position: idx }));

        itemsBody.innerHTML = formItems.map((it, idx) => rowItemHTML(it, idx)).join('');

        itemsBody.querySelectorAll('[data-row]').forEach((row) => {
            const i = Number(row.dataset.row);

            const bind = (f, parse = false) => (e) => {
                const v = e.target.value;

                // ✅ se o usuário mexeu manualmente em código/descrição, invalida o vínculo
                if ((f === 'code' || f === 'description') && !row.__fromTypeahead) {
                    formItems[i].part_id = null;
                }
                row.__fromTypeahead = false;

                formItems[i][f] = parse ? parseNumber(v) : v;

                if (f === 'code') {
                    const p = findPart(v); // continua funcionando (catálogo local)
                    if (p) {
                        formItems[i].description = p.descricao || '';
                        formItems[i].ncm = p.ncm || '';
                        formItems[i].unit_price = Number(p.valor || 0);
                        formItems[i].ipi_rate = Number(p.ipi || 0);

                        row.querySelector('[data-f="description"]').value = p.descricao || '';
                        row.querySelector('[data-f="ncm"]').value = p.ncm || '';
                        row.querySelector('[data-f="unit_price"]').value = numToStr(p.valor || 0);
                        row.querySelector('[data-f="ipi_rate"]').value = numToStr(p.ipi || 0);
                    }
                }

                updateRowComputed(i, row);
                updateSummary();
            };

            row.querySelector('[data-f="code"]').addEventListener('input', bind('code'));
            row.querySelector('[data-f="description"]').addEventListener('input', bind('description'));
            row.querySelector('[data-f="ncm"]').addEventListener('input', bind('ncm'));
            row.querySelector('[data-f="unit_price"]').addEventListener('input', bind('unit_price', true));
            row.querySelector('[data-f="ipi_rate"]').addEventListener('input', bind('ipi_rate', true));
            row.querySelector('[data-f="quantity"]').addEventListener('input', bind('quantity', true));
            row.querySelector('[data-f="discount_rate"]').addEventListener('input', bind('discount_rate', true));

            row.querySelector('[data-remove]').addEventListener('click', () => {
                formItems.splice(i, 1);
                renderItems();
                updateSummary();
            });

            row.querySelectorAll('input').forEach((inp) => {
                inp.addEventListener('keydown', (ev) => {
                    if (ev.key === 'Enter') { ev.preventDefault(); focusNext(inp); }
                });
            });

            // ===== TYPEAHEAD (DB) PARA PEÇAS: código e descrição =====
            const codeEl = row.querySelector('[data-f="code"]');
            const descEl = row.querySelector('[data-f="description"]');
            const ncmEl  = row.querySelector('[data-f="ncm"]');
            const unitEl = row.querySelector('[data-f="unit_price"]');
            const ipiEl  = row.querySelector('[data-f="ipi_rate"]');

            const applyPartFromApi = (p) => {
                const id = p.id || null;
                const code = p.code || p.codigo || '';
                const name = p.name || p.descricao || p.description || '';
                const ncm  = p.ncm_code || p.ncm || '';
                const unit = p.unit_price ?? p.valor ?? null;

                row.__fromTypeahead = true;

                formItems[i].part_id = id;
                if (code) formItems[i].code = code;
                if (name) formItems[i].description = name;
                if (ncm) formItems[i].ncm = ncm;
                if (unit != null) formItems[i].unit_price = Number(unit || 0);

                if (codeEl && code) codeEl.value = code;
                if (descEl && name) descEl.value = name;
                if (ncmEl && ncm) ncmEl.value = ncm;
                if (unitEl && unit != null) unitEl.value = numToStr(unit);

                updateRowComputed(i, row);
                updateSummary();
            };

            let modeLastFocused = 'name';
            codeEl?.addEventListener('focus', () => (modeLastFocused = 'code'));
            descEl?.addEventListener('focus', () => (modeLastFocused = 'name'));

            setupPartTypeaheadForInput({
                input: descEl,
                mode: 'name',
                getRowInputs: () => ({ codeEl, descEl, ncmEl, unitEl, ipiEl }),
                applyPart: applyPartFromApi
            });

            setupPartTypeaheadForInput({
                input: codeEl,
                mode: 'code',
                getRowInputs: () => ({ codeEl, descEl, ncmEl, unitEl, ipiEl }),
                applyPart: applyPartFromApi
            });

            updateRowComputed(i, row);
        });

        updateSummary();
    }

    function updateRowComputed(i, row) {
        const c = computeLine(formItems[i]);
        row.querySelector('[data-f="valIpi"]').value = numToStr(c.valComIPI);
        row.querySelector('[data-f="valDesc"]').value = numToStr(c.valComDesc);
    }

    function focusNext(el) {
        const inputs = Array.from(itemsBody.querySelectorAll('input:not([disabled])'));
        const i = inputs.indexOf(el);
        if (i > -1 && inputs[i + 1]) inputs[i + 1].focus();
    }

    function updateSummary() {
        const uf = q('#pp-uf')?.value || '';
        const rate = rateFromUF(uf);
        const s = sumTotal(formItems, rate);

        q('#sum-items').textContent = String(s.count);
        q('#sum-sub').textContent = fmtBR(s.subtotal);
        q('#sum-ipi').textContent = fmtBR(s.ipiTotal);
        q('#sum-disc').textContent = '- ' + fmtBR(s.descTotal);
        q('#sum-icms').textContent = fmtBR(s.icms);
        if (sumICMSTag) sumICMSTag.textContent = uf ? `(${uf} — ${rate.toLocaleString('pt-BR')}%)` : '(—)';
        q('#sum-total').textContent = fmtBR(s.totalFinal);
    }

    function validateHeader() {
        const title = (q('#pp-title').value || '').trim();
        const cnpj = (q('#pp-cnpj').value || '').trim();
        const uf = q('#pp-uf').value;

        if (!title) { toast('Informe o nome do pedido.'); return false; }
        if (!cnpj) { toast('Informe o CNPJ de faturamento.'); return false; }
        if (!uf) { toast('Selecione a UF de faturamento.'); return false; }
        if (cleanItems(formItems).length === 0) { toast('Adicione ao menos 1 item preenchido.'); return false; }
        return true;
    }

    function resetForm() {
        form?.reset();
        form.dataset.editId = '';
        form.dataset.orderNumber = ''; // ✅ ADD
        q('#pp-date').value = todayISO();
        formItems = [];
        itemsBody.innerHTML = '';
        updateSummary();
    }

    let openingNew = false;

    async function openNew() {
        if (openingNew) return;
        openingNew = true;

        try {
            resetForm();

            try {
                const settings = await PartsSettings.get();
                PartsSettings.applyToOrderModal(settings);
            } catch (e) {
                console.warn('Falha ao carregar settings', e);
            }

            captureSupplierBaseline();

            q('#parts-modal-title').textContent = 'Novo pedido de peças';
            formItems = Array.from({ length: 5 }, (_, idx) => ({ ...blankItem(), position: idx }));
            renderItems();
            openModal();
        } finally {
            setTimeout(() => { openingNew = false; }, 0);
        }
    }

    async function openEdit(id) {
        try {
            const o = await apiFetch(URL.show(id));

            supplierCurrent.email = o?.supplier?.email || supplierCurrent.email || '';

            if (String(o.status || '').toLowerCase() !== 'draft') {
                toast('Só rascunho pode editar.');
                return;
            }

            resetForm();
            q('#parts-modal-title').textContent = `Editar ${o.order_number || ''}`;
            form.dataset.editId = o.id;
            form.dataset.orderNumber = o.order_number || '';

            q('#pp-title').value = o.title || '';
            q('#pp-cnpj').value = o.billing_cnpj || '';
            q('#pp-date').value = o.order_date || todayISO();
            q('#pp-uf').value = o.billing_uf || '';

            // ✅ 1) Preenche fornecedor ANTES do baseline
            try {
                const settings = await PartsSettings.get();

                suppressSupplierPrompt = true;

                // aplica defaults do sistema (se pedido não tiver fornecedor salvo)
                PartsSettings.applyToOrderModal(settings);

                // se o pedido tiver fornecedor, sobrescreve
                const orderSupplierId =
                    o.supplier_id || o.supplier?.id || o.default_supplier_id || '';
                const orderSupplierName =
                    o.supplier_name || o.supplier?.name || '';

                const orderSupplierEmail = o.supplier?.email || o.supplier_email || '';

                if (orderSupplierId || orderSupplierName || orderSupplierEmail) {
                    setSupplierFields({ id: orderSupplierId, name: orderSupplierName, email: orderSupplierEmail });
                }

                suppressSupplierPrompt = false;
            } catch {
                suppressSupplierPrompt = false;
            }

            // ✅ 2) Agora sim captura baseline correto
            captureSupplierBaseline();

            formItems = (o.items || []).map((it, idx) => ({
                id: it.id || null,
                part_id: it.part_id || null,
                code: it.code || '',
                description: it.description || '',
                ncm: it.ncm || '',
                unit_price: Number(it.unit_price || 0),
                ipi_rate: Number(it.ipi_rate || 0),
                quantity: Number(it.quantity || 1),
                discount_rate: Number(it.discount_rate || 0),
                position: Number(it.position ?? idx),
            }));

            if (!formItems.length) {
                formItems = Array.from({ length: 5 }, (_, idx) => ({ ...blankItem(), position: idx }));
            }

            renderItems();
            openModal();
        } catch (e) {
            toast(e.message || 'Falha ao abrir rascunho.');
        }
    }

    async function openView(id) {
        try {
            const o = await apiFetch(URL.show(id));
            const st = derivedStatus(o);

            btnEditDraft?.classList.toggle('hidden', String(o.status || '').toLowerCase() !== 'draft');
            badgeDraftView?.classList.toggle('hidden', String(o.status || '').toLowerCase() !== 'draft');

            if (btnEditDraft) btnEditDraft.onclick = () => { closeViewModal(); openEdit(o.id); };

            viewContent.innerHTML = buildProposalHTML(o, st);
            openViewModal(o);

        } catch (e) {
            toast(e.message || 'Falha ao carregar pedido.');
        }
    }

    function buildProposalHTML(o, st) {
        const items = Array.isArray(o.items) ? o.items : [];

        const supplierName  = o.supplier?.name  || o.supplier_name  || '';
        const supplierEmail = o.supplier?.email || o.supplier_email || '';

        const supplierLine = supplierName ? ` • Fornecedor: ${escapeHTML(supplierName)}` : '';
        const supplierEmailLine = supplierEmail
            ? `<div class="text-xs text-slate-500">E-mail fornecedor: ${escapeHTML(supplierEmail)}</div>`
            : '';

        const rows = items.map((it, idx) => {
            const unit = Number(it.unit_price || 0);
            const qty = Number(it.quantity || 0);
            const ipi = Number(it.ipi_rate || 0);
            const disc = Number(it.discount_rate || 0);

            const base = unit * qty;
            const valIPI = base * (ipi / 100);
            const valComIPI = base + valIPI;
            const valDesc = valComIPI * (disc / 100);
            const valComDesc = valComIPI - valDesc;

            return `
        <tr class="border-b border-slate-200">
          <td class="px-3 py-2">${idx + 1}</td>
          <td class="px-3 py-2">${escapeHTML(it.code || '')}</td>
          <td class="px-3 py-2">${escapeHTML(it.description || '')}</td>
          <td class="px-3 py-2">${escapeHTML(it.ncm || '')}</td>
          <td class="px-3 py-2 text-right">${fmtBR(unit)}</td>
          <td class="px-3 py-2 text-right">${ipi.toLocaleString('pt-BR')}%</td>
          <td class="px-3 py-2 text-right">${qty.toLocaleString('pt-BR')}</td>
          <td class="px-3 py-2 text-right">${fmtBR(valComIPI)}</td>
          <td class="px-3 py-2 text-right">${disc.toLocaleString('pt-BR')}%</td>
          <td class="px-3 py-2 text-right">${fmtBR(valComDesc)}</td>
        </tr>
      `;
        }).join('');

        const rate = Number(o.icms_rate ?? rateFromUF(o.billing_uf));
        const totals = (o.grand_total !== null && o.grand_total !== undefined)
            ? {
                subtotal: Number(o.subtotal || 0),
                ipiTotal: Number(o.ipi_total || 0),
                descTotal: Number(o.discount_total || 0),
                icms: Number(o.icms_total || 0),
                totalFinal: Number(o.grand_total || 0),
            }
            : sumTotal(items.map(it => ({
                code: it.code,
                description: it.description,
                unit_price: it.unit_price,
                ipi_rate: it.ipi_rate,
                quantity: it.quantity,
                discount_rate: it.discount_rate,
            })), rate);

        const tagUF = o.billing_uf ? `(${o.billing_uf} — ${rate.toLocaleString('pt-BR')}%)` : '(—)';

        return `
      <div class="mb-6 flex items-start justify-between">
        <div>
          <div class="text-2xl font-semibold">Proposta • ${escapeHTML(o.order_number || '—')}</div>
<div class="text-sm text-slate-600">
  Data: ${formatBRDate(o.order_date)} • CNPJ: ${escapeHTML(o.billing_cnpj || '—')}${supplierLine}
</div>
${supplierEmailLine}
                  <div class="mt-2">${chip(st)}</div>
        </div>
        <div class="text-right">
          <div class="text-sm text-slate-600">Cliqis</div>
          <div class="text-xs text-slate-500">Proposta gerada automaticamente</div>
        </div>
      </div>

      <div class="overflow-x-auto rounded-xl border border-slate-200">
        <table class="w-full text-sm">
          <thead class="bg-slate-50 text-slate-600">
            <tr>
              <th class="px-3 py-2 text-left">#</th>
              <th class="px-3 py-2 text-left">Código</th>
              <th class="px-3 py-2 text-left">Descrição</th>
              <th class="px-3 py-2 text-left">NCM</th>
              <th class="px-3 py-2 text-right">Valor item</th>
              <th class="px-3 py-2 text-right">IPI %</th>
              <th class="px-3 py-2 text-right">Qtd</th>
              <th class="px-3 py-2 text-right">Valor c/ IPI</th>
              <th class="px-3 py-2 text-right">Desc. %</th>
              <th class="px-3 py-2 text-right">Valor final</th>
            </tr>
          </thead>
          <tbody>${rows}</tbody>
        </table>
      </div>

      <div class="mt-6 grid gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <div class="text-sm font-semibold">Observações</div>
          <p class="mt-1 text-sm text-slate-600">Prazo de entrega e condições serão definidos após confirmação do pedido.</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <div class="flex items-center justify-between"><span>Subtotal</span><span class="font-medium">${fmtBR(totals.subtotal)}</span></div>
          <div class="mt-1 flex items-center justify-between"><span>IPI</span><span class="font-medium">${fmtBR(totals.ipiTotal)}</span></div>
          <div class="mt-1 flex items-center justify-between"><span>ICMS <span class="text-slate-500">${tagUF}</span></span><span class="font-medium">${fmtBR(totals.icms)}</span></div>
          <div class="mt-1 flex items-center justify-between"><span>Descontos</span><span class="font-medium text-emerald-700">- ${fmtBR(totals.descTotal)}</span></div>
          <div class="mt-2 border-t pt-2 text-lg font-semibold flex items-center justify-between"><span>Total</span><span>${fmtBR(totals.totalFinal)}</span></div>
        </div>
      </div>
    `;
    }

    document.getElementById('btn-print')?.addEventListener('click', () => window.print());

    function buildPayload(status = 'draft') {
        const uf = q('#pp-uf').value || '';
        const itemsClean = cleanItems(formItems);

        const supplierId    = document.getElementById('pp-supplier-id')?.value || null;
        const supplierName  = (document.getElementById('pp-supplier-name')?.value || '').trim() || null;
        const supplierEmail = normalizeEmail(document.getElementById('pp-supplier-email')?.value) || null;

        return {
            title: (q('#pp-title').value || '').trim(),
            billing_cnpj: (q('#pp-cnpj').value || '').trim(),
            billing_uf: uf,
            order_date: q('#pp-date').value || todayISO(),
            status,
            icms_rate: rateFromUF(uf),

            supplier_id: supplierId,
            supplier_name: supplierName,
            supplier_email: supplierEmail,

            items: itemsClean.map((it, idx) => ({
                id: it.id || null,
                part_id: it.part_id || null,
                code: (it.code || '').trim() || null,
                description: (it.description || '').trim() || null,
                ncm: (it.ncm || '').trim() || null,
                unit_price: Number(it.unit_price || 0),
                ipi_rate: Number(it.ipi_rate || 0),
                quantity: Number(it.quantity || 1),
                discount_rate: Number(it.discount_rate || 0),
                position: Number(it.position ?? idx),
            })),
        };
    }

    async function saveDraft() {
        if (!validateHeader()) return null;

        const id = form.dataset.editId || null;
        const payload = buildPayload('draft');

        try {
            const res = id
                ? await apiFetch(URL.update(id), { method: 'PUT', body: JSON.stringify(payload) })
                : await apiFetch(URL.store(), { method: 'POST', body: JSON.stringify(payload) });

            const saved = res.data || res;
            sessionStorage.setItem(DRAFT_KEY, saved.id);
            toast(id ? 'Rascunho atualizado' : 'Rascunho salvo');

            await loadList();
            closeModal(true);
            return saved.id;
        } catch (e) {
            toast(e.message || 'Falha ao salvar rascunho.');
            return null;
        }
    }

    async function delOrder(id) {
        try {
            let o = orders.find(x => x.id === id);

            if (!o) o = await apiFetch(URL.show(id));

            openDeleteModal(o);
        } catch (e) {
            toast(e.message || 'Falha ao preparar exclusão.');
        }
    }

    document.getElementById('btn-receive-close')?.addEventListener('click', () => receiveDialog?.close());

    receiveDialog?.addEventListener('click', (e) => {
        if (e.target === receiveDialog) receiveDialog.close();
    });

    async function cloneOrder(id) {
        try {
            const res = await apiFetch(URL.duplicate(id), { method: 'POST' });
            const newId = res.id || res.data?.id;
            toast('Pedido clonado');
            await loadList();
            if (newId) openView(newId);
        } catch (e) {
            toast(e.message || 'Falha ao clonar.');
        }
    }

    function sendOrderFlow() {
        if (!validateHeader()) return;

        openSendConfirm();

        const email = normalizeEmail(document.getElementById('pp-supplier-email')?.value || supplierCurrent.email);
        if (!isValidEmail(email)) {
            toast('Corrija o e-mail do destinatário para enviar.');
        }
    }

    // Modal Confirm
    const btnConfirm = document.getElementById('btn-confirm-send');
    const btnReturn = document.getElementById('btn-return-edit');
    const btnConfirmX = document.getElementById('btn-confirm-x');

    btnReturn?.addEventListener('click', closeConfirm);
    btnConfirmX?.addEventListener('click', closeConfirm);

    btnConfirm?.addEventListener('click', async () => {
        if (btnConfirm.dataset.busy === '1') return;
        btnConfirm.dataset.busy = '1';

        const minDelay = sleep(3000);
        setBtnState(btnConfirm, 'loading');

        try {
            let id = null;

            if (confirmMode === 'send') {
                // mantém teu fluxo atual (salva draft antes)
                id = form.dataset.editId || null;
                const payload = buildPayload('draft');

                const res = id
                    ? await apiFetch(URL.update(id), { method: 'PUT', body: JSON.stringify(payload) })
                    : await apiFetch(URL.store(), { method: 'POST', body: JSON.stringify(payload) });

                const saved = res.data || res;
                id = saved.id;

                await apiFetch(URL.send(id), { method: 'POST' });

                sessionStorage.removeItem(DRAFT_KEY);
                closeModal(true); // fecha modal de edição (send)
                toast('Pedido enviado');
            } else {
                // resend direto
                id = confirmOrderId;
                await apiFetch(URL.resend(id), { method: 'POST' });
                toast('Pedido reenviado');
            }

            await minDelay;
            setBtnState(btnConfirm, 'success');
            await sleep(650);

            await loadList();
            closeConfirm();
            openSuccess(id);
        } catch (e) {
            await minDelay;

            setBtnState(btnConfirm, 'error');
            toast(e.message || 'Falha no envio.');

            setTimeout(() => {
                setBtnState(btnConfirm, 'idle');
                btnConfirm.dataset.busy = '0';
            }, 1200);

            return;
        }

        btnConfirm.dataset.busy = '0';
    });

    function initStockEntryModal(modalEl) {
        if (!modalEl || modalEl.dataset.inited === "1") return;
        modalEl.dataset.inited = "1";

        const getItems = () => Array.from(modalEl.querySelectorAll("[data-stock-item]"));

        const remainingOf = (itemEl) => {
            const ordered  = parseInt(itemEl.dataset.ordered || "0", 10);
            const received = parseInt(itemEl.dataset.received || "0", 10);
            return Math.max(ordered - received, 0);
        };

        const setDisabledLook = (input, disabled) => {
            input.readOnly = !!disabled;
            input.classList.toggle("bg-slate-50", !!disabled);
            input.classList.toggle("text-slate-500", !!disabled);
            input.classList.toggle("cursor-not-allowed", !!disabled);
        };

        const togglePriceVisibility = (itemEl, qty) => {
            const wrap = itemEl.querySelector("[data-price-wrap]");
            if (!wrap) return;
            // some o preço quando qty = 0 (ou vazio) pra ficar clean
            wrap.classList.toggle("hidden", !qty || Number(qty) <= 0);
        };

        const applyMode = (mode) => {
            const items = getItems();

            items.forEach((itemEl) => {
                const remain = remainingOf(itemEl);

                const qtyInput  = itemEl.querySelector("[data-qty-input]");
                const qtyHelp   = itemEl.querySelector("[data-qty-help]");
                const priceInput = itemEl.querySelector("[data-price-input]");

                // Se não tem nada pra receber: oculta o item inteiro (ou troque pra "opacity-50" se preferir mostrar)
                if (remain === 0) {
                    itemEl.classList.add("hidden");
                    return;
                } else {
                    itemEl.classList.remove("hidden");
                }

                if (!qtyInput) return;

                qtyInput.max = String(remain);
                if (qtyHelp) qtyHelp.textContent = `Máx: ${remain}`;

                if (mode === "total") {
                    qtyInput.value = String(remain);
                    setDisabledLook(qtyInput, true);
                    if (priceInput) priceInput.disabled = false;
                    togglePriceVisibility(itemEl, remain);
                } else {
                    // parcial
                    qtyInput.value = ""; // você define
                    setDisabledLook(qtyInput, false);
                    togglePriceVisibility(itemEl, 0);
                }
            });
        };

        // muda modo
        modalEl.addEventListener("change", (e) => {
            if (e.target && e.target.name === "entry_mode") {
                applyMode(e.target.value);
            }
        });

        // clamp + mostrar/ocultar preço conforme qty
        modalEl.addEventListener("input", (e) => {
            const t = e.target;
            if (!t || !t.matches("[data-qty-input]")) return;

            const itemEl = t.closest("[data-stock-item]");
            if (!itemEl) return;

            const remain = remainingOf(itemEl);
            let v = t.value === "" ? "" : parseInt(t.value, 10);

            if (v !== "") {
                if (Number.isNaN(v)) v = 0;
                if (v < 0) v = 0;
                if (v > remain) v = remain;
                t.value = String(v);
            }

            togglePriceVisibility(itemEl, v === "" ? 0 : v);
        });

        // init
        const checked = modalEl.querySelector('input[name="entry_mode"]:checked');
        applyMode(checked ? checked.value : "total");
    }

// uso:
    document.addEventListener("DOMContentLoaded", () => {
        const modal = document.querySelector("[data-stock-entry-modal]");
        if (modal) initStockEntryModal(modal);
    });

    // Sucesso modal
    document.getElementById('btn-success-close')?.addEventListener('click', closeSuccess);
    document.getElementById('btn-success-x')?.addEventListener('click', closeSuccess);
    document.getElementById('btn-success-view')?.addEventListener('click', () => {
        closeSuccess();
        if (lastSentId) openView(lastSentId);
    });

    // Botões principais
    q('#btn-save-draft')?.addEventListener('click', (e) => { e.preventDefault(); saveDraft(); });
    q('#btn-send')?.addEventListener('click', (e) => { e.preventDefault(); sendOrderFlow(); });

    // Itens
    q('#btn-add-item')?.addEventListener('click', () => {
        formItems.push({ ...blankItem(), position: formItems.length });
        renderItems();
    });

    // ===== INIT =====
    q('#pp-date') && (q('#pp-date').value = todayISO());
    loadList();
})();
