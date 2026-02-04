/* assets/js/template/views/part-orders/part-order-index.js */
/* global window, document */

(() => {
    const root = document.getElementById('orders-parts-fragment') || document;

    // ===== ENDPOINTS (se mudar prefix, muda só aqui) =====
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

    const btnCloseX        = document.getElementById('btn-confirm-x');
    const btnReturnEdit    = document.getElementById('btn-return-edit');

// IDs do teu modal de configurações (AJUSTE se forem outros)
    const inpSubjectTpl = document.getElementById('settings-email-subject-tpl');
    const inpBodyTpl    = document.getElementById('settings-email-body-tpl');

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

    // ===== Catalog (continua localStorage por enquanto) =====
    const getLS = (k, f) => {
        try { return JSON.parse(localStorage.getItem(k) || JSON.stringify(f)); } catch { return f; }
    };
    const setLS = (k, v) => localStorage.setItem(k, JSON.stringify(v));

    const partsCatalogSeed = [
        { codigo: '6206088', descricao: 'Mecanismo Fujitsu c/proteção', ncm: '8443.99.41', valor: 670.65, ipi: 6.5 },
    ];
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
        if (raw === 'pending') return 'pendente';
        if (raw === 'completed') return 'concluido';

        let st = 'aberto';
        const days = diffDays(o.order_date || o.date, todayISO());
        if (days > 10 && st !== 'concluido') st = 'atraso';
        return st;
    }

    function chip(st) {
        const m = {
            aberto: ['bg-blue-50 text-blue-700', 'Em aberto'],
            pendente: ['bg-amber-50 text-amber-700', 'Pendente'],
            atraso: ['bg-rose-50 text-rose-700', 'Em atraso'],
            concluido: ['bg-emerald-50 text-emerald-700', 'Concluído'],
            rascunho: ['bg-rose-50 text-rose-700', 'Rascunho'],
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
                : ({ aberto: 'Em aberto', pendente: 'Pendente', atraso: 'Em atraso', concluido: 'Concluído' }[statusFilter] || '');
        if (cardLabel) cardLabel.textContent = label;
    }

    function rowHTML(o) {
        const draftTag =
            String(o.status || '').toLowerCase() === 'draft'
                ? `<span class="ml-2 inline-flex rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-semibold text-rose-700">Rascunho</span>`
                : '';

        const isDraft = String(o.status || '').toLowerCase() === 'draft';
        const canEdit = isDraft;
        const canResend = !isDraft;

        return `
      <tr class="hover:bg-slate-50/60">
        <td class="px-4 py-3 font-medium">${escapeHTML(o.order_number || '—')}${draftTag}</td>
        <td class="px-4 py-3">${escapeHTML(o.billing_cnpj || '—')}</td>
        <td class="px-4 py-3">${escapeHTML(o.title || '—')}</td>
        <td class="px-4 py-3">${formatBRDate(o.order_date || '')}</td>
        <td class="px-4 py-3 text-right">${fmtBR(o.grand_total || 0)}</td>
        <td class="px-4 py-3">${chip(o._status)}</td>
        <td class="px-4 py-3">
          <div class="flex justify-end gap-2">
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

    function setupPartTypeaheadForInput({
                                            input,
                                            mode, // "code" | "name"
                                            getRowInputs, // () => { codeEl, descEl, ncmEl, unitEl, ipiEl }
                                            applyPart,    // (part) => void
                                            createPart,   // (term) => Promise<void>
                                        }) {
        if (!input) return;

        const wrapper = wrapForDropdown(input);
        if (!wrapper) return;

        const dd = document.createElement('div');
        dd.className =
            'absolute z-50 mt-1 w-full rounded-xl border border-slate-200 bg-white shadow-lg max-h-64 overflow-auto hidden';
        wrapper.appendChild(dd);

        let abortController = null;

        const norm = (s) =>
            (s || '')
                .toString()
                .trim()
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '');

        const close = () => {
            dd.classList.add('hidden');
            dd.innerHTML = '';
        };

        const render = (items, term) => {
            dd.innerHTML = '';

            const termNorm = norm(term);
            const hasExact = items.some((p) => {
                const code = norm(p.code || p.codigo || '');
                const name = norm(p.name || p.descricao || p.description || '');
                return (code && code === termNorm) || (name && name === termNorm);
            });

            // lista resultados
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

            // CTA criar (igual modal de funcionários)
            const showCreate = termNorm && !hasExact;
            if (showCreate) {
                const cta = document.createElement('button');
                cta.type = 'button';
                cta.className =
                    'w-full px-3 py-2 text-left text-sm hover:bg-blue-50 flex items-center gap-2 text-blue-700 border-t border-slate-100';
                cta.innerHTML = `+ Criar nova peça "${escapeHTML(term)}"`;
                cta.addEventListener('click', async () => {
                    await createPart(term);
                    close();
                });
                dd.appendChild(cta);
            }

            // se não tem nada e nem create, fecha
            if (!items.length && !showCreate) close();
            else dd.classList.remove('hidden');
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
                if (err?.name === 'AbortError') return; // ✅ ignora
                console.warn(err);
            }
        };

        input.addEventListener('focus', openList);
        input.addEventListener('click', openList);
        input.addEventListener('input', debounce(openList, 200));

        document.addEventListener('click', (ev) => {
            if (!wrapper.contains(ev.target)) close();
        });

        // se fechar com ESC
        input.addEventListener('keydown', (ev) => {
            if (ev.key === 'Escape') close();
        });
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

    function openSettingsModal() {
        document.getElementById('modal-part-order-settings')?.classList.remove('hidden');
    }

    btnOpenSettings?.addEventListener('click', openSettingsModal);

    function refreshPreviewFromSettingsInputs() {
        if (!currentOrderForSend) return;
        const subj = inpSubjectTpl?.value ?? '';
        const body = inpBodyTpl?.value ?? '';
        renderConfirmPreview(currentOrderForSend, subj, body);
    }
    inpSubjectTpl?.addEventListener('input', refreshPreviewFromSettingsInputs);
    inpBodyTpl?.addEventListener('input', refreshPreviewFromSettingsInputs);

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

    function openViewModal() {
        const btnViewResend = document.getElementById('btn-view-resend');

        btnViewResend?.classList.toggle('hidden', String(o.status || '').toLowerCase() === 'draft');

        if (btnViewResend) {
            btnViewResend.onclick = () => {
                closeViewModal();
                openResendConfirm(o.id);
            };
        }

        modalView?.classList.remove('hidden');
    }

    function closeViewModal() { modalView?.classList.add('hidden'); }

    document.addEventListener('click', (ev) => {
        if (ev.target.closest('[data-close-parts]')) closeModal(true);
        if (ev.target.closest('[data-close-regpart]')) closeReg();
        if (ev.target.closest('[data-close-import]')) closeImport();
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
        if (act === 'regPart') openReg();
        if (act === 'importParts') openImport();
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

    // function openModal(id) { document.getElementById(id)?.classList.remove('hidden'); }
    // function closeModal(id) { document.getElementById(id)?.classList.add('hidden'); }

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

            const createPartInDb = async (term) => {
                const codeTyped = (codeEl?.value || '').trim();
                const descTyped = (descEl?.value || '').trim();
                const ncmTyped  = (ncmEl?.value || '').trim();
                const unitTyped = parseNumber(unitEl?.value || 0);

                // define nome/código dependendo de onde o cara tá digitando
                const code = codeTyped || (modeLastFocused === 'code' ? term.trim() : null);
                const name = descTyped || (modeLastFocused === 'name' ? term.trim() : '') || (code || 'Peça');

                if (!name) return;

                const payload = {
                    code: code || null,
                    name,
                    description: descTyped || null,
                    unit_price: unitTyped || 0,
                    ncm_code: ncmTyped || null,
                    supplier_id: null,
                    is_active: true,
                };

                const res = await apiFetch(PART_API, { method: 'POST', body: JSON.stringify(payload) });
                const created = res.data || res;

                toast('Peça criada');
                applyPartFromApi(created);
            };

// guarda onde o usuário tá interagindo (pra criar com o “term” certo)
            let modeLastFocused = 'name';
            codeEl?.addEventListener('focus', () => (modeLastFocused = 'code'));
            descEl?.addEventListener('focus', () => (modeLastFocused = 'name'));

            setupPartTypeaheadForInput({
                input: descEl,
                mode: 'name',
                getRowInputs: () => ({ codeEl, descEl, ncmEl, unitEl, ipiEl }),
                applyPart: applyPartFromApi,
                createPart: createPartInDb,
            });

            setupPartTypeaheadForInput({
                input: codeEl,
                mode: 'code',
                getRowInputs: () => ({ codeEl, descEl, ncmEl, unitEl, ipiEl }),
                applyPart: applyPartFromApi,
                createPart: createPartInDb,
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
            openViewModal();
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
        if (!confirm('Excluir este pedido?')) return;
        try {
            await apiFetch(URL.destroy(id), { method: 'DELETE' });
            toast('Pedido excluído');
            await loadList();
        } catch (e) {
            toast(e.message || 'Falha ao excluir.');
        }
    }

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
    q('#btn-open-reg')?.addEventListener('click', openReg);

    // ===== REG PART (local) =====
    function openReg() { document.getElementById('modal-reg-part')?.classList.remove('hidden'); }
    function closeReg() { document.getElementById('modal-reg-part')?.classList.add('hidden'); }

    document.getElementById('form-reg-part')?.addEventListener('submit', (e) => {
        e.preventDefault();
        const part = {
            codigo: (q('#rp-code').value || '').trim(),
            descricao: (q('#rp-desc').value || '').trim(),
            ncm: (q('#rp-ncm').value || '').trim(),
            valor: parseNumber(q('#rp-price').value),
            ipi: parseNumber(q('#rp-ipi').value),
        };
        if (!part.codigo) { toast('Informe o código.'); return; }

        const ix = catalog.findIndex((p) => String(p.codigo).toLowerCase() === String(part.codigo).toLowerCase());
        if (ix > -1) catalog[ix] = part; else catalog.push(part);

        setLS('cliqis_parts_catalog', catalog);
        refreshPartsDatalist();
        toast('Peça salva no catálogo (local)');
        closeReg();
        e.target.reset();
    });

    // ===== IMPORT CSV (local) =====
    function openImport() { document.getElementById('modal-import-parts')?.classList.remove('hidden'); }

    function closeImport() {
        const m = document.getElementById('modal-import-parts');
        if (!m) return;
        m.classList.add('hidden');
        resetImportState();
    }

    document.getElementById('btn-dl-template')?.addEventListener('click', () => {
        const bom = '\uFEFF';
        const csv = [
            'codigo;descricao;ncm;valor;ipi',
            '6206088;Mecanismo Fujitsu c/protecao;8443.99.41;670,65;6,5',
            'AB123;Correia transportadora;4010.12.90;120,00;10',
        ].join('\r\n');
        const a = document.createElement('a');
        a.href = URL.createObjectURL(new Blob([bom + csv], { type: 'text/csv;charset=utf-8;' }));
        a.download = 'modelo-pecas.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(a.href);
    }, { passive: true });

    const csvState = { parsed: [], counts: { valid: 0, new: 0, upd: 0, errors: [] } };

    document.getElementById('csv-file')?.addEventListener('change', async (e) => {
        const f = e.target.files?.[0];
        if (!f) return;

        const text = await f.text();
        const rows = parseCSV(text);
        const r = normalizeParts(rows);

        csvState.parsed = r.items;

        let n = 0, u = 0;
        r.items.forEach((p) => {
            const ix = catalog.findIndex((x) => String(x.codigo).toLowerCase() === String(p.codigo).toLowerCase());
            if (ix > -1) u++; else n++;
        });

        csvState.counts = { valid: r.items.length, new: n, upd: u, errors: r.errors };

        q('#csv-summary').classList.remove('hidden');
        q('#sum-valid').textContent = r.items.length;
        q('#sum-new').textContent = n;
        q('#sum-upd').textContent = u;
        q('#sum-errors').textContent = r.errors.length ? ('Erros: ' + r.errors.join(' | ')) : '';
        q('#btn-import-confirm').disabled = r.items.length === 0;
    });

    document.getElementById('btn-import-confirm')?.addEventListener('click', () => {
        if (!csvState.parsed.length) return;

        csvState.parsed.forEach((p) => {
            const ix = catalog.findIndex((x) => String(x.codigo).toLowerCase() === String(p.codigo).toLowerCase());
            if (ix > -1) catalog[ix] = p; else catalog.push(p);
        });

        setLS('cliqis_parts_catalog', catalog);
        refreshPartsDatalist();

        toast(`Importação local: ${csvState.counts.valid} válidos (${csvState.counts.new} novos, ${csvState.counts.upd} atualizados)`);
        closeImport();
    });

    function resetImportState() {
        const file = document.getElementById('csv-file');
        const summary = document.getElementById('csv-summary');
        const btn = document.getElementById('btn-import-confirm');
        const errs = document.getElementById('sum-errors');

        if (file) file.value = '';
        if (summary) summary.classList.add('hidden');
        if (btn) btn.disabled = true;
        if (errs) errs.textContent = '';
    }

    function parseCSV(text) {
        const hasSemicolon = (text.match(/;/g) || []).length;
        const hasComma = (text.match(/,/g) || []).length;
        const delim = hasSemicolon > hasComma ? ';' : ',';
        const lines = text.replace(/\r\n/g, '\n').replace(/\r/g, '\n').split('\n').filter((l) => l.trim().length);
        return lines.map((line) => splitCSVLine(line, delim));
    }

    function splitCSVLine(line, delim) {
        const out = [];
        let cur = '';
        let inQ = false;
        for (let i = 0; i < line.length; i++) {
            const ch = line[i];
            if (ch === '"') {
                if (inQ && line[i + 1] === '"') { cur += '"'; i++; }
                else inQ = !inQ;
            } else if (ch === delim && !inQ) { out.push(cur); cur = ''; }
            else cur += ch;
        }
        out.push(cur);
        return out.map((v) => v.trim());
    }

    function normalizeParts(rows) {
        if (!rows.length) return { items: [], errors: ['CSV vazio'] };

        const header = rows[0].map((h) => h.toLowerCase().normalize('NFD').replace(/\p{Diacritic}/gu, '').trim());

        const idx = {
            codigo: findHeaderIndex(header, ['codigo', 'cod', 'código', 'code', 'item_code']),
            descricao: findHeaderIndex(header, ['descricao', 'descrição', 'desc', 'description', 'nome']),
            ncm: findHeaderIndex(header, ['ncm']),
            valor: findHeaderIndex(header, ['valor', 'preco', 'preço', 'valor_item', 'unit_price', 'valorunit']),
            ipi: findHeaderIndex(header, ['ipi', 'ipi%', 'aliquota_ipi', 'aliquota', 'aliquota-ipi']),
        };

        const miss = Object.entries(idx).filter(([, v]) => v === -1).map(([k]) => k);
        if (miss.includes('codigo') || miss.includes('valor')) {
            return { items: [], errors: [`Colunas obrigatórias ausentes: ${miss.join(', ')}`] };
        }

        const items = [];
        const errors = [];

        for (let r = 1; r < rows.length; r++) {
            const row = rows[r];
            if (!row || row.every((c) => !String(c || '').trim())) continue;

            const codigo = (row[idx.codigo] ?? '').trim();
            if (!codigo) { errors.push(`L${r + 1}: código vazio`); continue; }

            const descricao = (idx.descricao > -1 ? row[idx.descricao] : '').trim();
            const ncm = (idx.ncm > -1 ? row[idx.ncm] : '').trim();
            const valor = parseNumber(idx.valor > -1 ? row[idx.valor] : '0');
            const ipi = parseNumber(idx.ipi > -1 ? row[idx.ipi] : '0');

            items.push({ codigo, descricao, ncm, valor, ipi });
        }

        return { items, errors };
    }

    function findHeaderIndex(header, keys) {
        for (const key of keys) {
            const i = header.findIndex((h) => h === key || h.replace(/\s+/g, '') === key.replace(/\s+/g, ''));
            if (i > -1) return i;
        }
        for (const key of keys) {
            const i = header.findIndex((h) => h.includes(key));
            if (i > -1) return i;
        }
        return -1;
    }

    // ===== INIT =====
    q('#pp-date') && (q('#pp-date').value = todayISO());
    loadList();
})();
