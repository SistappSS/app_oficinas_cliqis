/* global window, document, fetch */

(() => {
    const $ = (id) => document.getElementById(id);

    const elQ = $("stock-q");
    const elLoc = $("stock-location");
    const elActive = $("stock-active");
    const elTbody = $("tbody");
    const elEmpty = $("stock-empty");
    const elCount = $("stock-count");
    const elPrev = $("stock-prev");
    const elNext = $("stock-next");
    const elPageInfo = $("stock-pageinfo");

    let state = {
        page: 1,
        q: "",
        location_id: "",
        active: 1,
        last: null,
    };

    const fmtBR = (n) => {
        const x = Number(n || 0);
        return x.toLocaleString("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    const fmtBR4 = (n) => {
        const x = Number(n || 0);
        return x.toLocaleString("pt-BR", { minimumFractionDigits: 4, maximumFractionDigits: 4 });
    };

    const escapeHtml = (s) => String(s ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");

    const buildParams = () => {
        const p = new URLSearchParams();
        p.set("page", String(state.page));
        if (state.q) p.set("q", state.q);
        if (state.location_id) p.set("location_id", state.location_id);
        p.set("active", String(state.active));
        return p.toString();
    };

    const setLoading = (on) => {
        if (on) {
            elTbody.innerHTML = `
        <tr>
          <td class="px-6 py-6 text-slate-500" colspan="8">Carregando...</td>
        </tr>`;
            elEmpty.classList.add("hidden");
        }
    };

    const renderRows = (items, useLocation) => {
        if (!items || items.length === 0) {
            elTbody.innerHTML = "";
            elEmpty.classList.remove("hidden");
            return;
        }

        elEmpty.classList.add("hidden");

        elTbody.innerHTML = items.map((it) => {
            const qty = useLocation ? (it.qty_location ?? 0) : (it.qty_on_hand_global ?? 0);
            const avg = useLocation ? (it.avg_cost_location ?? 0) : (it.avg_cost_global ?? 0);

            const code = escapeHtml(it.code);
            const desc = escapeHtml(it.description || it.name || "-");

            const sale = Number(it.default_sale_price || 0);
            const mk = Number(it.default_markup_percent || 0);

            return `
        <tr>
          <td class="px-6 py-4 font-medium text-slate-900">${code}</td>
          <td class="px-3 py-4 text-slate-700">${desc}</td>
          <td class="px-3 py-4 text-right text-slate-900">${Number(qty || 0)}</td>
          <td class="px-3 py-4 text-right text-slate-900">${fmtBR4(avg)}</td>
          <td class="px-3 py-4 text-right text-slate-900">${fmtBR(it.last_cost || 0)}</td>
          <td class="px-3 py-4 text-right text-slate-900">${fmtBR(sale)}</td>
          <td class="px-3 py-4 text-right text-slate-900">${mk > 0 ? mk.toFixed(2) + "%" : "0%"}</td>
          <td class="px-6 py-4 text-right">
            <button data-move="${escapeHtml(it.id)}"
              class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
              Movimentar
            </button>
          </td>
        </tr>
      `;
        }).join("");
    };

    const renderPagination = (p) => {
        elCount.textContent = String(p.total || 0);

        const from = p.from ?? 0;
        const to = p.to ?? 0;
        const lastPage = p.last_page ?? 1;
        const cur = p.current_page ?? 1;

        elPageInfo.textContent = `${from}-${to} de ${p.total || 0} • pág ${cur}/${lastPage}`;

        elPrev.disabled = cur <= 1;
        elNext.disabled = cur >= lastPage;
    };

    const fillLocations = (locations) => {
        const cur = elLoc.value;
        const opts = [
            `<option value="">Global (todos locais)</option>`,
            ...(locations || []).map((l) => {
                const tag = l.is_default ? " (Padrão)" : "";
                return `<option value="${escapeHtml(l.id)}">${escapeHtml(l.name)}${tag}</option>`;
            })
        ];
        elLoc.innerHTML = opts.join("");
        if (cur) elLoc.value = cur;
    };

    const load = async () => {
        setLoading(true);

        const res = await fetch(`/stock/stock-api?${buildParams()}`, {
            headers: { "Accept": "application/json" }
        });

        if (!res.ok) {
            elTbody.innerHTML = `<tr><td class="px-6 py-6 text-red-600" colspan="8">Falha ao carregar.</td></tr>`;
            return;
        }

        const data = await res.json();
        fillLocations(data.locations || []);

        const pag = data.items || {};
        state.last = pag;

        renderRows(pag.data || [], !!state.location_id);
        renderPagination(pag);
    };

    // Debounce simples
    let t = null;
    const debounced = (fn, ms = 350) => {
        window.clearTimeout(t);
        t = window.setTimeout(fn, ms);
    };

    // Events
    elQ.addEventListener("input", () => {
        state.q = elQ.value.trim();
        state.page = 1;
        debounced(load);
    });

    elLoc.addEventListener("change", () => {
        state.location_id = elLoc.value;
        state.page = 1;
        load();
    });

    elActive.addEventListener("change", () => {
        state.active = elActive.checked ? 1 : 0;
        state.page = 1;
        load();
    });

    elPrev.addEventListener("click", () => {
        if (state.page > 1) {
            state.page -= 1;
            load();
        }
    });

    elNext.addEventListener("click", () => {
        const last = state.last?.last_page || 1;
        if (state.page < last) {
            state.page += 1;
            load();
        }
    });

    // Placeholder do botão “Movimentar”
    elTbody.addEventListener("click", (e) => {
        const btn = e.target.closest("[data-move]");
        if (!btn) return;
        const id = btn.getAttribute("data-move");
        window.location.href = `/stock/movements?stock_part_id=${encodeURIComponent(id)}`;
    });

    load();
})();
