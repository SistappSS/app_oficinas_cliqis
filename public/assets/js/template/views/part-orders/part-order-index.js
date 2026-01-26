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

    // Form refs
    const form = q('#form-parts');
    const itemsBody = q('#items-body');
    const ufSelect = q('#pp-uf');
    const cnpjInput = q('#pp-cnpj');
    const sumICMSTag = q('#sum-icms-tag');

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

    function formatBRDate(iso) {
        try { const [y, m, d] = String(iso || '').split('-'); return `${d}/${m}/${y}`; } catch { return iso; }
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

        const canEdit = String(o.status || '').toLowerCase() === 'draft';

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
        tbody.querySelectorAll('[data-act="clone"]').forEach((b) => b.addEventListener('click', () => cloneOrder(b.dataset.id)));
        tbody.querySelectorAll('[data-act="del"]').forEach((b) => b.addEventListener('click', () => delOrder(b.dataset.id)));

        updateCards(list);
        updateBanner();
    }

    // ===== BANNER DRAFT =====
    function updateBanner() {
        if (!banner) return;

        const lastId = sessionStorage.getItem(DRAFT_KEY);
        const draft = lastId && orders.find((o) => o.id === lastId && String(o.status || '').toLowerCase() === 'draft');

        banner.classList.toggle('hidden', !draft);
        if (!draft) return;

        btnDraftView.onclick = () => openView(draft.id);
        btnDraftSend.onclick = () => openEdit(draft.id);
        btnDraftDismiss.onclick = async () => {
            try {
                await apiFetch(URL.destroy(draft.id), { method: 'DELETE' });
                sessionStorage.removeItem(DRAFT_KEY);
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
            const term = input.value.trim();

            if (abortController) abortController.abort();
            abortController = new AbortController();

            // no foco: top 5
            const list = term ? await fetchPartsTypeahead(term, 8, abortController.signal)
                : await fetchPartsTypeahead('', 5, abortController.signal);

            render(list, term);
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
    function openConfirm() { modalConfirm?.classList.remove('hidden'); }
    function closeConfirm() { modalConfirm?.classList.add('hidden'); }
    function openSuccess(id) { lastSentId = id; succModal?.classList.remove('hidden'); }
    function closeSuccess() { succModal?.classList.add('hidden'); }
    function openViewModal() { modalView?.classList.remove('hidden'); }
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

    if (!window.__cliqisPartsNewBtnPatch_v2) {
        window.__cliqisPartsNewBtnPatch_v2 = true;
        document.addEventListener('click', (ev) => {
            const trg = ev.target.closest('#btn-new-parts');
            if (trg) { ev.preventDefault(); openNew(); }
        });
    }

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
        q('#pp-date').value = todayISO();
        formItems = [];
        itemsBody.innerHTML = '';
        updateSummary();
    }

    function openNew() {
        resetForm();
        q('#parts-modal-title').textContent = 'Novo pedido de peças';
        formItems = Array.from({ length: 5 }, (_, idx) => ({ ...blankItem(), position: idx }));
        renderItems();
        openModal();
    }

    async function openEdit(id) {
        try {
            const o = await apiFetch(URL.show(id));
            if (String(o.status || '').toLowerCase() !== 'draft') {
                toast('Só rascunho pode editar.');
                return;
            }

            resetForm();
            q('#parts-modal-title').textContent = `Editar ${o.order_number || ''}`;
            form.dataset.editId = o.id;

            q('#pp-title').value = o.title || '';
            q('#pp-cnpj').value = o.billing_cnpj || '';
            q('#pp-date').value = o.order_date || todayISO();
            q('#pp-uf').value = o.billing_uf || '';

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

            if (!formItems.length) formItems = Array.from({ length: 5 }, (_, idx) => ({ ...blankItem(), position: idx }));

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
          <div class="text-sm text-slate-600">Data: ${formatBRDate(o.order_date)} • CNPJ: ${escapeHTML(o.billing_cnpj || '—')}</div>
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

        return {
            title: (q('#pp-title').value || '').trim(),
            billing_cnpj: (q('#pp-cnpj').value || '').trim(),
            billing_uf: uf,
            order_date: q('#pp-date').value || todayISO(),
            status,
            icms_rate: rateFromUF(uf),
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
        openConfirm();
    }

    // Modal Confirm
    const btnConfirm = document.getElementById('btn-confirm-send');
    const btnReturn = document.getElementById('btn-return-edit');
    const btnConfirmX = document.getElementById('btn-confirm-x');

    btnReturn?.addEventListener('click', closeConfirm);
    btnConfirmX?.addEventListener('click', closeConfirm);

    btnConfirm?.addEventListener('click', async () => {
        try {
            let id = form.dataset.editId || null;
            const payload = buildPayload('draft');

            const res = id
                ? await apiFetch(URL.update(id), { method: 'PUT', body: JSON.stringify(payload) })
                : await apiFetch(URL.store(), { method: 'POST', body: JSON.stringify(payload) });

            const saved = res.data || res;
            id = saved.id;

            await apiFetch(URL.send(id), { method: 'POST' });

            sessionStorage.removeItem(DRAFT_KEY);

            await loadList();
            closeConfirm();
            closeModal(true);
            openSuccess(id);
            toast('Pedido enviado');
        } catch (e) {
            toast(e.message || 'Falha ao enviar pedido.');
            closeConfirm();
        }
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
