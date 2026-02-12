/* global window, document, fetch */

(() => {
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, m => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[m]));

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");

    const elQ = $('loc-q');
    const elDefault = $('loc-default');
    const elTbody = $('loc-tbody');
    const elEmpty = $('loc-empty');
    const elPrev = $('loc-prev');
    const elNext = $('loc-next');
    const elPage = $('loc-pageinfo');
    const btnNew = $('loc-new');

    const modal = $('loc-modal');
    const mTitle = $('loc-modal-title');
    const mSub = $('loc-modal-sub');
    const mName = $('loc-name');
    const mDefault = $('loc-isdefault');
    const mErr = $('loc-err');
    const mSave = $('loc-save');

    let state = {page: 1, q: '', def: 'all', last: null};
    let editing = null; // {id}

    const setErr = (msg) => {
        if (!msg) {
            mErr.classList.add('hidden');
            mErr.textContent = '';
            return;
        }
        mErr.textContent = msg;
        mErr.classList.remove('hidden');
    };

    const openModal = () => {
        modal.classList.remove('hidden');
        document.documentElement.classList.add('overflow-hidden');
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        document.documentElement.classList.remove('overflow-hidden');
    };
    modal.querySelectorAll('[data-loc-close]').forEach(b => b.addEventListener('click', closeModal));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
    });

    const buildParams = () => {
        const p = new URLSearchParams();
        p.set('page', String(state.page));
        if (state.q) p.set('q', state.q);
        p.set('default', state.def);
        return p.toString();
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
                const isDef = Number(r.is_default || 0) === 1;

                return `
          <tr>
            <td class="px-6 py-4 font-medium text-slate-900">${esc(r.name)}</td>
            <td class="px-3 py-4">
              ${isDef
                    ? `<span class="inline-flex items-center rounded-lg px-2 py-1 text-xs bg-emerald-50 text-emerald-700">Padrão</span>`
                    : `<span class="inline-flex items-center rounded-lg px-2 py-1 text-xs bg-slate-100 text-slate-700">—</span>`
                }
            </td>
            <td class="px-6 py-4 text-right">
              <div class="inline-flex gap-2">
                <button
                  data-edit="${esc(r.id)}"
                  data-name="${esc(r.name)}"
                  data-default="${isDef ? '1' : '0'}"
                  class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
                >Editar</button>

                <button
                  data-make-default="${esc(r.id)}"
                  data-name="${esc(r.name)}"
                  class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                  ${isDef ? 'disabled' : ''}
                >Tornar padrão</button>

                <button
                  data-del="${esc(r.id)}"
                  data-default="${isDef ? '1' : '0'}"
                  class="rounded-xl border border-rose-200 bg-white px-3 py-2 text-sm text-rose-700 hover:bg-rose-50 disabled:opacity-50"
                  ${isDef ? 'disabled' : ''}
                >Excluir</button>
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
        elTbody.innerHTML = `<tr><td class="px-6 py-6 text-slate-500" colspan="3">Carregando...</td></tr>`;
        elEmpty.classList.add('hidden');

        const res = await fetch(`/stock/settings/location-api?${buildParams()}`, {
            headers: {Accept: 'application/json'}
        });

        if (!res.ok) {
            elTbody.innerHTML = `<tr><td class="px-6 py-6 text-red-600" colspan="3">Falha ao carregar.</td></tr>`;
            return;
        }

        const data = await res.json().catch(() => ({}));
        render(data.items || {});
    };

    // debounce
    let t = null;
    const debounced = (fn, ms = 350) => {
        window.clearTimeout(t);
        t = window.setTimeout(fn, ms);
    };

    elQ.addEventListener('input', () => {
        state.q = elQ.value.trim();
        state.page = 1;
        debounced(load);
    });
    elDefault.addEventListener('change', () => {
        state.def = elDefault.value;
        state.page = 1;
        load();
    });

    elPrev.addEventListener('click', () => {
        if (state.page > 1) {
            state.page -= 1;
            load();
        }
    });
    elNext.addEventListener('click', () => {
        const last = state.last?.last_page || 1;
        if (state.page < last) {
            state.page += 1;
            load();
        }
    });

    btnNew.addEventListener('click', () => {
        editing = null;
        mSub.textContent = 'Local';
        mTitle.textContent = 'Novo local';
        mName.value = '';
        mDefault.checked = false;
        setErr('');
        openModal();
    });

    mSave.addEventListener('click', async () => {
        setErr('');

        const payload = {
            name: (mName.value || '').trim(),
            is_default: mDefault.checked ? 1 : 0,
        };

        if (!payload.name) return setErr('Informe o nome.');

        const url = editing
            ? `/stock/settings/location-api/${encodeURIComponent(editing.id)}`
            : `/stock/settings/location-api`;

        const method = editing ? 'PUT' : 'POST';

        const res = await fetch(url, {
            method,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                ...(csrf ? {'X-CSRF-TOKEN': csrf} : {}),
            },
            body: JSON.stringify(payload),
        });

        const data = await res.json().catch(() => ({}));
        if (!res.ok) return setErr(data.message || 'Falha ao salvar.');

        closeModal();
        load();
    });

    document.addEventListener('click', async (e) => {
        const btnEdit = e.target.closest('[data-edit]');
        if (btnEdit) {
            editing = {id: btnEdit.dataset.edit};

            mSub.textContent = 'Local';
            mTitle.textContent = 'Editar local';
            mName.value = btnEdit.dataset.name || '';
            mDefault.checked = (btnEdit.dataset.default || '0') === '1';

            setErr('');
            openModal();
            return;
        }

        const btnMakeDefault = e.target.closest('[data-make-default]');
        if (btnMakeDefault) {
            const id = btnMakeDefault.dataset.makeDefault;
            const name = btnMakeDefault.dataset.name || '';

            const res = await fetch(`/stock/settings/location-api/${encodeURIComponent(id)}`, {
                method: 'PUT',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    ...(csrf ? {'X-CSRF-TOKEN': csrf} : {}),
                },
                body: JSON.stringify({name, is_default: 1}),
            });

            const data = await res.json().catch(() => ({}));
            if (!res.ok) return alert(data.message || 'Falha ao definir padrão.');
            load();
            return;
        }
    });

    const brl = (n) => Number(n || 0).toLocaleString("pt-BR", { style: "currency", currency: "BRL" });

    const delModal = document.getElementById('locdel-modal');
    if (!delModal) return; // se não existe, não inicializa delete

    const delEmpty = document.getElementById('locdel-empty');

    const elName = document.getElementById('locdel-name');
    const elSkus = document.getElementById('locdel-skus');
    const elQty  = document.getElementById('locdel-qty');
    const elCost = document.getElementById('locdel-cost');
    const elTb   = document.getElementById('locdel-tbody');
    const elAlert = document.getElementById('locdel-alert');
    const btnOk  = document.getElementById('locdel-confirm');

    let currentId = null;
    let blockers = [];

    const openDel = () => {
        delModal.classList.remove('hidden');
        document.documentElement.classList.add('overflow-hidden');
    };

    const closeDel = () => {
        delModal.classList.add('hidden');
        document.documentElement.classList.remove('overflow-hidden');
    };

    delModal.querySelectorAll('[data-locdel-close]').forEach(b => b.addEventListener('click', closeDel));

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !delModal.classList.contains('hidden')) closeDel();
    });

    function setAlert(msg) {
        if (!msg) {
            elAlert.classList.add('hidden');
            elAlert.textContent = '';
            return;
        }
        elAlert.textContent = msg;
        elAlert.classList.remove('hidden');
    }

    function renderItems(items) {
        const arr = Array.isArray(items) ? items : [];

        if (!arr.length) {
            elTb.innerHTML = '';
            delEmpty?.classList.remove('hidden');
            return;
        }

        delEmpty?.classList.add('hidden');

        elTb.innerHTML = arr.map(it => `
    <tr>
      <td class="px-6 py-4 font-medium text-slate-900">${esc(it.code)}</td>
      <td class="px-3 py-4 text-slate-700">${esc(it.description || '-')}</td>
      <td class="px-3 py-4 text-right text-slate-900">${Number(it.qty || 0)}</td>
      <td class="px-6 py-4 text-right text-slate-900">${
            Number(it.avg_cost || 0).toLocaleString("pt-BR", { minimumFractionDigits: 4, maximumFractionDigits: 4 })
        }</td>
    </tr>
  `).join('');
    }

    function applyBlockers(loc) {
        const isDefault = Number(loc?.is_default || 0) === 1;
        const hasStock  = blockers.includes('has_stock');

        if (isDefault) {
            setAlert('Este é o local padrão. Troque o padrão antes de excluir.');
            btnOk.disabled = true;
            return;
        }

        if (hasStock) {
            setAlert('Este local possui itens com saldo. Zere/transfera os itens antes de excluir.');
            btnOk.disabled = true;
            return;
        }

        setAlert('');
        btnOk.disabled = false;
    }

    async function loadCheck(id) {
        currentId = id;
        blockers = [];
        btnOk.disabled = true;

        elTb.innerHTML = `<tr><td class="px-6 py-6 text-slate-500" colspan="4">Carregando...</td></tr>`;
        delEmpty?.classList.add('hidden');
        setAlert('');

        // 🔥 AJUSTE AQUI: bate com sua rota atual
        const res = await fetch(`/stock/location-api/${encodeURIComponent(id)}/delete-check`, {
            headers: { Accept: 'application/json' }
        });

        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            setAlert(data.message || 'Falha ao carregar validação.');
            return;
        }

        const loc = data.location || {};
        blockers = Array.isArray(data.blockers) ? data.blockers : [];

        elName.textContent = loc.name || '-';
        elSkus.textContent = String(data.stats?.skus_with_qty ?? 0);
        elQty.textContent  = String(data.stats?.total_qty ?? 0);
        elCost.textContent = brl(data.stats?.total_cost_est ?? 0);

        renderItems(data.items || []);
        applyBlockers(loc);
    }

// Clique no botão excluir (na tabela de locais)
// 🔥 AJUSTE AQUI: seu botão tem data-del e não data-loc-del
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-del]');
        if (!btn) return;

        const id = btn.dataset.del;
        if (!id) return;

        openDel();
        loadCheck(id).catch(() => setAlert('Falha ao carregar validação.'));
    });

// Confirmar exclusão
    btnOk.addEventListener('click', async () => {
        if (!currentId) return;
        if (btnOk.disabled) return;

        btnOk.disabled = true;

        const res = await fetch(`/stock/settings/location-api/${encodeURIComponent(currentId)}`, {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
            }
        });

        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            setAlert(data.message || 'Não foi possível excluir.');
            await loadCheck(currentId);
            return;
        }

        closeDel();
        load(); // 👈 melhor que reload, você já tem load() pronto
    });

    load();
})();
