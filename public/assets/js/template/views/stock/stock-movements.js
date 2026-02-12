/* global window, document, fetch */

(() => {
    const $ = (id) => document.getElementById(id);

    // ====== BASE / URLS ======
    const host = document.getElementById("stock-movements-fragment");
    const API_BASE = host?.dataset?.apiBase || "/stock/movements-api";

    const URL = {
        list: (params = "") => `${API_BASE}${params ? `?${params}` : ""}`,
        show: (id) => `${API_BASE}/${encodeURIComponent(id)}`,
    };

    // ====== LIST UI ======
    const elQ = $("mov-q");
    const elTypeFilter = $("mov-type");
    const elTbody = $("mov-tbody");
    const elEmpty = $("mov-empty");
    const elPrev = $("mov-prev");
    const elNext = $("mov-next");
    const elPageInfo = $("mov-pageinfo");

    const paramsUrl = new URLSearchParams(window.location.search);
    const preStockPartId = paramsUrl.get("stock_part_id") || "";

    let state = {
        page: 1,
        q: "",
        type: "",
        period: "",
        date_from: "",
        date_to: "",
        last: null,
    };

    const esc = (s) =>
        String(s ?? "").replace(/[&<>"']/g, (m) => ({
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#39;",
        }[m]));

    const fmtBRL = (n) =>
        Number(n || 0).toLocaleString("pt-BR", { style: "currency", currency: "BRL" });

    const labelType = (t) => {
        if (t === "in") return "Entrada";
        if (t === "out") return "Saída";
        if (t === "adjust") return "Ajuste";
        if (t === "transfer") return "Transferência";
        return t || "-";
    };

    const labelSource = (s) => {
        const map = {
            part_order: "Pedido",
            sale: "Venda",
            manual: "Manual",
        };
        return map[s] || s || "-";
    };

    const setLoading = () => {
        elTbody.innerHTML = `<tr><td class="px-6 py-6 text-slate-500" colspan="8">Carregando...</td></tr>`;
        elEmpty.classList.add("hidden");
    };

    const buildParams = () => {
        const p = new URLSearchParams();
        p.set("page", String(state.page));
        if (state.q) p.set("q", state.q);
        if (state.type) p.set("type", state.type);
        if (preStockPartId) p.set("stock_part_id", preStockPartId);

        if (state.period) p.set("period", state.period);
        if (state.period === "custom") {
            if (state.date_from) p.set("date_from", state.date_from);
            if (state.date_to) p.set("date_to", state.date_to);
        }

        return p.toString();
    };

    const render = (pag) => {
        const rows = pag.data || [];

        if (rows.length === 0) {
            elTbody.innerHTML = "";
            elEmpty.classList.remove("hidden");
        } else {
            elEmpty.classList.add("hidden");

            elTbody.innerHTML = rows.map((m) => {
                const dt = esc(m.created_at || "-");
                const tp = esc(labelType(m.type));
                const reason = esc(m.reason_label || "-");

                const srcType = labelSource(m.source_type);
                const src = esc(srcType ? `${srcType}${m.source_id ? " • " + m.source_id : ""}` : "-");

                const user = esc((m.user_name && m.user_name.trim()) ? m.user_name : (m.user_id || "-"));

                const totalQty = Number(m.total_qty || 0);
                const totalCost = fmtBRL(m.total_cost || 0);

                return `
        <tr>
          <td class="px-6 py-4 text-slate-700">${dt}</td>
          <td class="px-3 py-4 text-slate-900">${tp}</td>
          <td class="px-3 py-4 text-slate-700">${reason}</td>
          <td class="px-3 py-4 text-slate-700">${src}</td>
          <td class="px-3 py-4 text-slate-700">${user}</td>
          <td class="px-3 py-4 text-right text-slate-900">${totalQty}</td>
          <td class="px-3 py-4 text-right text-slate-900">${totalCost}</td>
          <td class="px-6 py-4 text-right">
            <button
              type="button"
              class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50"
              data-view-movement
              data-mv-id="${esc(m.id)}"
              title="Ver movimentação"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </td>
        </tr>
      `;
            }).join("");
        }

        const from = pag.from ?? 0;
        const to = pag.to ?? 0;
        const total = pag.total ?? 0;
        const cur = pag.current_page ?? 1;
        const last = pag.last_page ?? 1;

        state.last = pag;
        elPageInfo.textContent = `${from}-${to} de ${total} • pág ${cur}/${last}`;
        elPrev.disabled = cur <= 1;
        elNext.disabled = cur >= last;
    };

    const load = async () => {
        setLoading();
        const res = await fetch(URL.list(buildParams()), { headers: { Accept: "application/json" } });

        if (!res.ok) {
            elTbody.innerHTML = `<tr><td class="px-6 py-6 text-red-600" colspan="8">Falha ao carregar.</td></tr>`;
            return;
        }

        const data = await res.json().catch(() => ({}));
        render(data.items || {});
    };

    // ====== FILTERS / PAGINATION ======
    let t = null;

    const elPeriod = $("mov-period");
    const elPeriodWrap = $("mov-period-wrap");
    const elFrom = $("mov-from");
    const elTo = $("mov-to");

    const debounced = (fn, ms = 350) => {
        window.clearTimeout(t);
        t = window.setTimeout(fn, ms);
    };

    elQ.addEventListener("input", () => {
        state.q = elQ.value.trim();
        state.page = 1;
        debounced(safeLoad);
    });

    elTypeFilter.addEventListener("change", () => {
        state.type = elTypeFilter.value;
        state.page = 1;
        safeLoad();
    });

    elPrev.addEventListener("click", () => {
        if (state.page > 1) {
            state.page -= 1;
            safeLoad();
        }
    });

    elNext.addEventListener("click", () => {
        const last = state.last?.last_page || 1;
        if (state.page < last) {
            state.page += 1;
            safeLoad();
        }
    });

    elPeriod.addEventListener("change", () => {
        state.period = elPeriod.value || "";
        state.page = 1;

        if (state.period === "custom") {
            elPeriodWrap.classList.remove("hidden");
            state.date_from = elFrom.value || "";
            state.date_to = elTo.value || "";
        } else {
            elPeriodWrap.classList.add("hidden");
            state.date_from = "";
            state.date_to = "";
            elFrom.value = "";
            elTo.value = "";
            setPeriodError(false);
        }

        safeLoad();
    });

    const onCustomDate = () => {
        if (state.period !== "custom") return;
        state.date_from = elFrom.value || "";
        state.date_to = elTo.value || "";
        state.page = 1;
        safeLoad();
    };

    elFrom.addEventListener("change", onCustomDate);
    elTo.addEventListener("change", onCustomDate);

    const elPeriodError = $("mov-period-error");

    function setPeriodError(on) {
        if (!elPeriodError) return;
        elPeriodError.classList.toggle("hidden", !on);
    }

// retorna true se pode carregar
    function canLoad() {
        if (state.period !== "custom") {
            setPeriodError(false);
            return true;
        }

        const from = state.date_from || "";
        const to = state.date_to || "";

        // se um dos dois estiver vazio, deixa carregar (vai filtrar só >= from ou <= to)
        if (!from || !to) {
            setPeriodError(false);
            return true;
        }

        // comparação lexicográfica funciona pra YYYY-MM-DD
        const ok = from <= to;
        setPeriodError(!ok);
        return ok;
    }

    function safeLoad() {
        if (!canLoad()) return;
        load();
    }

    // ====== MODAL VIEW MOVIMENTAÇÃO ======
    const modal = $("mv-modal");
    const mvTbody = $("mv-tbody");

    const elTitle = $("mv-title");
    const elSubtitle = $("mv-subtitle");
    const elMvType = $("mv-type");
    const elReason = $("mv-reason");
    const elTotalQty = $("mv-total-qty");
    const elTotalCost = $("mv-total-cost");
    const elNotes = $("mv-notes");

    const openModal = () => {
        modal.classList.remove("hidden");
        document.documentElement.classList.add("overflow-hidden");
    };
    const closeModal = () => {
        modal.classList.add("hidden");
        document.documentElement.classList.remove("overflow-hidden");
    };

    modal.querySelectorAll("[data-mv-close]").forEach((btn) => btn.addEventListener("click", closeModal));
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && !modal.classList.contains("hidden")) closeModal();
    });

    async function openMovement(id) {
        elTitle.textContent = "Carregando…";
        elSubtitle.textContent = "";
        elMvType.textContent = "-";
        elReason.textContent = "-";
        elTotalQty.textContent = "0";
        elTotalCost.textContent = fmtBRL(0);
        elNotes.textContent = "";
        mvTbody.innerHTML = `<tr><td class="px-6 py-4 text-slate-500" colspan="6">Carregando…</td></tr>`;
        openModal();

        const res = await fetch(URL.show(id), { headers: { Accept: "application/json" } });
        const data = await res.json().catch(() => ({}));

        if (!res.ok) {
            mvTbody.innerHTML = `<tr><td class="px-6 py-4 text-red-600" colspan="6">${esc(data.message || "Falha ao carregar.")}</td></tr>`;
            elTitle.textContent = "Erro";
            return;
        }

        const mv = data.movement || {};
        const typeMap = { in: "Entrada", out: "Saída", adjust: "Ajuste", transfer: "Transferência" };

        elTitle.textContent = `#${(mv.id || "").slice(0, 8) || "-"}`;
        elSubtitle.textContent = `${mv.created_at || ""}${mv.user?.name ? " • " + mv.user.name : ""}`;
        elMvType.textContent = typeMap[mv.type] || mv.type || "-";
        elReason.textContent = mv.reason?.label || "-";
        elTotalQty.textContent = String(data.totals?.qty ?? 0);
        elTotalCost.textContent = fmtBRL(data.totals?.cost ?? 0);
        elNotes.textContent = mv.notes ? `Obs: ${mv.notes}` : "";

        const items = Array.isArray(data.items) ? data.items : [];
        if (items.length === 0) {
            mvTbody.innerHTML = `<tr><td class="px-6 py-4 text-slate-500" colspan="6">Sem itens.</td></tr>`;
            return;
        }

        mvTbody.innerHTML = items.map((it) => `
      <tr>
        <td class="px-6 py-4">${esc(it.location?.name || "-")}</td>
        <td class="px-3 py-4 font-medium text-slate-900">${esc(it.code || "-")}</td>
        <td class="px-3 py-4">${esc(it.description || "")}</td>
        <td class="px-3 py-4 text-right">${Number(it.qty || 0)}</td>
        <td class="px-3 py-4 text-right">${fmtBRL(it.unit_cost || 0)}</td>
        <td class="px-6 py-4 text-right">${fmtBRL(it.total_cost || 0)}</td>
      </tr>
    `).join("");
    }

    // clique no olho (delegado no tbody pra evitar listener global demais)
    elTbody.addEventListener("click", (e) => {
        const btn = e.target.closest("[data-view-movement]");
        if (!btn) return;
        const id = btn.dataset.mvId;
        if (!id) return;

        openMovement(id).catch((err) => {
            console.error(err);
            elTitle.textContent = "Erro";
            mvTbody.innerHTML = `<tr><td class="px-6 py-4 text-red-600" colspan="6">Falha ao carregar.</td></tr>`;
            openModal();
        });
    });

    safeLoad();
})();
