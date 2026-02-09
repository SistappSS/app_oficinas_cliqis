/* global window, document, fetch */

(() => {
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));

    const elQ = $('r-q');
    const elActive = $('r-active');
    const elSystem = $('r-system');

    const elTbody = $('r-tbody');
    const elEmpty = $('r-empty');
    const elPrev = $('r-prev');
    const elNext = $('r-next');
    const elPage = $('r-pageinfo');

    const btnNew = $('r-new');

    const modal = $('r-modal');
    const mTitle = $('r-modal-title');
    const mSub = $('r-modal-sub');
    const mCode = $('r-code');
    const mLabel = $('r-label');
    const mActive = $('r-active2');
    const mErr = $('r-err');
    const mSave = $('r-save');

    let state = { page: 1, q: '', active: '1', system: 'all', last: null };
    let editing = null; // {id,is_system}

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");

    const buildParams = () => {
        const p = new URLSearchParams();
        p.set('page', String(state.page));
        if (state.q) p.set('q', state.q);
        p.set('active', state.active);
        p.set('system', state.system);
        return p.toString();
    };

    const openModal = () => {
        modal.classList.remove('hidden');
        document.documentElement.classList.add('overflow-hidden');
    };
    const closeModal = () => {
        modal.classList.add('hidden');
        document.documentElement.classList.remove('overflow-hidden');
    };
    modal.querySelectorAll('[data-r-close]').forEach(b => b.addEventListener('click', closeModal));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal(); });

    const setErr = (msg) => {
        if (!msg) { mErr.classList.add('hidden'); mErr.textContent = ''; return; }
        mErr.textContent = msg;
        mErr.classList.remove('hidden');
    };

    const render = (pag) => {
        const rows = pag.data || [];
        state.last = pag;

        if (!rows.length) {
            elTbody.innerHTML = '';
            elEmpty.classList.remove('hidden');
        } else {
            elEmpty.classList.add('hidden');
            elTbody.innerHTML = rows.map(r => {
                const isSystem = Number(r.is_system || 0) === 1;
                const isActive = Number(r.is_active || 0) === 1;

                return `
          <tr>
            <td class="px-6 py-4 font-medium text-slate-900">${esc(r.code)}</td>
            <td class="px-3 py-4 text-slate-700">${esc(r.label)}</td>
            <td class="px-3 py-4">
              <span class="inline-flex items-center rounded-lg px-2 py-1 text-xs ${isSystem ? 'bg-slate-100 text-slate-700' : 'bg-blue-50 text-blue-700'}">
                ${isSystem ? 'Sistema' : 'Tenant'}
              </span>
            </td>
            <td class="px-3 py-4">
              <span class="inline-flex items-center rounded-lg px-2 py-1 text-xs ${isActive ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'}">
                ${isActive ? 'Ativo' : 'Inativo'}
              </span>
            </td>
            <td class="px-6 py-4 text-right">
              <div class="inline-flex gap-2">
                <button data-edit="${esc(r.id)}" data-system="${isSystem ? '1' : '0'}" data-code="${esc(r.code)}" data-label="${esc(r.label)}" data-active="${isActive ? '1' : '0'}"
                  class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Editar</button>

                <button data-toggle="${esc(r.id)}" data-active="${isActive ? '1' : '0'}"
                  class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                  ${isActive ? 'Desativar' : 'Ativar'}
                </button>

                ${isSystem ? '' : `
                  <button data-del="${esc(r.id)}"
                    class="rounded-xl border border-rose-200 bg-white px-3 py-2 text-sm text-rose-700 hover:bg-rose-50">Excluir</button>
                `}
              </div>
            </td>
          </tr>
        `;
            }).join('');
        }

        const from = pag.from ?? 0;
        const to = pag.to ?? 0;
        const total = pag.total ?? 0;
        const cur = pag.current_page ?? 1;
        const last = pag.last_page ?? 1;

        elPage.textContent = `${from}-${to} de ${total} • pág ${cur}/${last}`;
        elPrev.disabled = cur <= 1;
        elNext.disabled = cur >= last;
    };

    const load = async () => {
        elTbody.innerHTML = `<tr><td class="px-6 py-6 text-slate-500" colspan="5">Carregando...</td></tr>`;
        elEmpty.classList.add('hidden');

        const res = await fetch(`/stock/settings/reason-api?${buildParams()}`, { headers: { "Accept": "application/json",               "Content-Type": "application/json",
                ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}), } });
        if (!res.ok) {
            elTbody.innerHTML = `<tr><td class="px-6 py-6 text-red-600" colspan="5">Falha ao carregar.</td></tr>`;
            return;
        }
        const data = await res.json().catch(() => ({}));
        render(data.items || {});
    };

    // debounce
    let t = null;
    const debounced = (fn, ms = 350) => { window.clearTimeout(t); t = window.setTimeout(fn, ms); };

    elQ.addEventListener('input', () => { state.q = elQ.value.trim(); state.page = 1; debounced(load); });
    elActive.addEventListener('change', () => { state.active = elActive.value; state.page = 1; load(); });
    elSystem.addEventListener('change', () => { state.system = elSystem.value; state.page = 1; load(); });

    elPrev.addEventListener('click', () => { if (state.page > 1) { state.page -= 1; load(); } });
    elNext.addEventListener('click', () => {
        const last = state.last?.last_page || 1;
        if (state.page < last) { state.page += 1; load(); }
    });

    btnNew.addEventListener('click', () => {
        editing = null;

        mSub.textContent = 'Motivo';
        mTitle.textContent = 'Novo motivo';
        mLabel.value = '';
        mCode.value = '';
        mActive.checked = true;

        mCode.readOnly = true;
        mCode.disabled = true;

        setErr('');
        openModal();
    });

    mSave.addEventListener('click', async () => {
        setErr('');

        let payload = {
            code: mCode.value.trim(),
            label: mLabel.value.trim(),
            is_active: mActive.checked ? 1 : 0,
        };

        if (!payload.label) return setErr('Informe o label.');

        if (!editing && !payload.code) {
            payload.code = slugToCode(payload.label);
            mCode.value = payload.code; // opcional: reflete no input
        }

        if (!editing && !payload.code) return setErr('Informe o código.');
        if (!editing && !/^[a-z0-9_]+$/.test(payload.code)) {
            return setErr('Código inválido. Use a-z 0-9 _.');
        }

        const url = editing
            ? `/stock/settings/reason-api/${encodeURIComponent(editing.id)}`
            : `/stock/settings/reason-api`;

        const method = editing ? 'PUT' : 'POST';

        if (editing && editing.is_system) {
            delete payload.code;
        }

        const res = await fetch(url, {
            method,
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/json",
                ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
            },
            body: JSON.stringify(payload),
        });

        const data = await res.json().catch(() => ({}));
        if (!res.ok) return setErr(data.message || 'Falha ao salvar.');

        closeModal();
        load();
    });

    function slugToCode(label) {
        return String(label || "")
            .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9]+/g, "_")
            .replace(/^_+|_+$/g, "")
            .replace(/_+/g, "_")
            .slice(0, 80);
    }

    function syncCodeFromLabel() {
        // só auto-gerar quando for NOVO
        if (editing) return;
        if (!mLabel || !mCode) return;
        mCode.value = slugToCode(mLabel.value);
    }

    mCode.readOnly = true;

    mLabel?.addEventListener("input", syncCodeFromLabel);

    document.addEventListener('click', async (e) => {
        const btnEdit = e.target.closest('[data-edit]');
        if (btnEdit) {
            editing = { id: btnEdit.dataset.edit, is_system: btnEdit.dataset.system === '1' };

            mSub.textContent = editing.is_system ? 'Motivo (Sistema)' : 'Motivo (Tenant)';
            mTitle.textContent = 'Editar motivo';

            mCode.value = btnEdit.dataset.code || '';
            mLabel.value = btnEdit.dataset.label || '';
            mActive.checked = (btnEdit.dataset.active || '1') === '1';

            mCode.readOnly = editing.is_system;
            mCode.disabled = false;

            setErr('');
            openModal();
            return;
        }

        const btnToggle = e.target.closest('[data-toggle]');
        if (btnToggle) {
            const id = btnToggle.dataset.toggle;
            const curActive = (btnToggle.dataset.active || '1') === '1';
            const payload = { label: '__keep__', is_active: curActive ? 0 : 1 };

            // pega label atual da linha (pra não precisar endpoint extra)
            const row = btnToggle.closest('tr');
            const labelCell = row?.children?.[1];
            payload.label = labelCell ? labelCell.textContent.trim() : '__keep__';

            const res = await fetch(`/stock/settings/reason-api/${encodeURIComponent(id)}`, {
                method: 'PUT',
                headers: {
                    "Accept": "application/json",
                    "Content-Type": "application/json",
                    ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
                },
                body: JSON.stringify(payload),
            });

            if (!res.ok) return;
            load();
            return;
        }

        const btnDel = e.target.closest('[data-del]');
        if (btnDel) {
            const id = btnDel.dataset.del;
            if (!window.confirm('Excluir este motivo?')) return;

            const res = await fetch(`/stock/settings/reason-api/${encodeURIComponent(id)}`, {
                method: 'DELETE',
                headers: {
                    "Accept": "application/json",
                    "Content-Type": "application/json",
                    ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
                },
            });

            if (!res.ok) return;
            load();
        }
    });

    load();
})();
