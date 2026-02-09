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
  <div class="inline-flex gap-2">
    <button data-move="${escapeHtml(it.id)}"
      class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
      Movimentar
    </button>

    <button data-log="${escapeHtml(it.id)}"
      class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
      Ver log
    </button>
  </div>
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

        loadKpis().catch(console.error);
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

    // ===== MODAL LOG POR ITEM =====
    const logModal = document.getElementById("stklog-modal");
    const logTbody = document.getElementById("stklog-tbody");
    const logEmpty = document.getElementById("stklog-empty");
    const logTitle = document.getElementById("stklog-title");
    const logSubtitle = document.getElementById("stklog-subtitle");
    const logQ = document.getElementById("stklog-q");
    const logType = document.getElementById("stklog-type");
    const logPrev = document.getElementById("stklog-prev");
    const logNext = document.getElementById("stklog-next");
    const logPageInfo = document.getElementById("stklog-pageinfo");

    let logState = { stock_part_id: "", page: 1, q: "", type: "", last: null };

    const openLogModal = () => {
        logModal.classList.remove("hidden");
        document.documentElement.classList.add("overflow-hidden");
    };
    const closeLogModal = () => {
        logModal.classList.add("hidden");
        document.documentElement.classList.remove("overflow-hidden");
    };

    if (logModal) {
        logModal.querySelectorAll("[data-stklog-close]").forEach(b => b.addEventListener("click", closeLogModal));
    }

    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && !logModal.classList.contains("hidden")) closeLogModal();
    });

    const labelType2 = (t) => {
        if (t === "in") return "Entrada";
        if (t === "out") return "Saída";
        if (t === "adjust") return "Ajuste";
        if (t === "transfer") return "Transferência";
        return t || "-";
    };

    const fmtBRL2 = (n) => Number(n || 0).toLocaleString("pt-BR", { style: "currency", currency: "BRL" });

    const esc2 = (s) => String(s ?? "")
        .replaceAll("&","&amp;").replaceAll("<","&lt;").replaceAll(">","&gt;")
        .replaceAll('"',"&quot;").replaceAll("'","&#039;");

    const buildLogParams = () => {
        const p = new URLSearchParams();
        p.set("page", String(logState.page));
        p.set("stock_part_id", logState.stock_part_id);
        if (logState.q) p.set("q", logState.q);
        if (logState.type) p.set("type", logState.type);
        return p.toString();
    };

    const setLogLoading = () => {
        logTbody.innerHTML = `<tr><td class="px-6 py-6 text-slate-500" colspan="6">Carregando...</td></tr>`;
        logEmpty.classList.add("hidden");
    };

    async function loadLog() {
        setLogLoading();
        const res = await fetch(`/stock/movements-api?${buildLogParams()}`, { headers: { Accept: "application/json" } });

        if (!res.ok) {
            logTbody.innerHTML = `<tr><td class="px-6 py-6 text-red-600" colspan="6">Falha ao carregar.</td></tr>`;
            return;
        }

        const data = await res.json().catch(() => ({}));
        const pag = data.items || {};
        logState.last = pag;

        const rows = pag.data || [];
        if (rows.length === 0) {
            logTbody.innerHTML = "";
            logEmpty.classList.remove("hidden");
        } else {
            logEmpty.classList.add("hidden");
            logTbody.innerHTML = rows.map(m => `
      <tr>
        <td class="px-6 py-4 text-slate-700">${esc2(m.created_at || "-")}</td>
        <td class="px-3 py-4 text-slate-900">${esc2(labelType2(m.type))}</td>
        <td class="px-3 py-4 text-slate-700">${esc2(m.reason_label || "-")}</td>
        <td class="px-3 py-4 text-right text-slate-900">${Number(m.total_qty || 0)}</td>
        <td class="px-3 py-4 text-right text-slate-900">${fmtBRL2(m.total_cost || 0)}</td>
        <td class="px-6 py-4 text-right">
          <button
            type="button"
            class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50"
            data-stklog-open-mv
            data-mv-id="${esc2(m.id)}"
            title="Ver detalhe"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </td>
      </tr>
    `).join("");
        }

        const from = pag.from ?? 0;
        const to = pag.to ?? 0;
        const total = pag.total ?? 0;
        const cur = pag.current_page ?? 1;
        const last = pag.last_page ?? 1;

        logPageInfo.textContent = `${from}-${to} de ${total} • pág ${cur}/${last}`;
        logPrev.disabled = cur <= 1;
        logNext.disabled = cur >= last;
    }

// abrir pelo botão "Ver log" do estoque
    elTbody.addEventListener("click", (e) => {
        const btn = e.target.closest("[data-log]");
        if (!btn) return;

        logState.stock_part_id = btn.getAttribute("data-log");
        logState.page = 1;
        logState.q = "";
        logState.type = "";
        logQ.value = "";
        logType.value = "";

        logTitle.textContent = "Movimentações da peça";
        logSubtitle.textContent = `stock_part_id: ${logState.stock_part_id}`;

        openLogModal();
        loadLog();
    });

    let logT = null;
    const debouncedLog = (fn, ms = 350) => {
        window.clearTimeout(logT);
        logT = window.setTimeout(fn, ms);
    };

    logQ.addEventListener("input", () => {
        logState.q = logQ.value.trim();
        logState.page = 1;
        debouncedLog(loadLog);
    });

    logType.addEventListener("change", () => {
        logState.type = logType.value;
        logState.page = 1;
        loadLog();
    });

    logPrev.addEventListener("click", () => {
        if (logState.page > 1) { logState.page -= 1; loadLog(); }
    });

    logNext.addEventListener("click", () => {
        const last = logState.last?.last_page || 1;
        if (logState.page < last) { logState.page += 1; loadLog(); }
    });


    // ====== MODAL MOVIMENTAR IN/OUT ======
    const mvModal = document.getElementById("stock-move-modal");

    const smTitle = document.getElementById("sm-title");
    const smSubtitle = document.getElementById("sm-subtitle");

    const smCurQty = document.getElementById("sm-cur-qty");
    const smCurAvg = document.getElementById("sm-cur-avg");
    const smCurMin = document.getElementById("sm-cur-min");
    const smGlobalAvg = document.getElementById("sm-global-avg");

    const smType = document.getElementById("sm-type");
    const smLoc = document.getElementById("sm-location");
    const smQty = document.getElementById("sm-qty");
    const smUnitCost = document.getElementById("sm-unit-cost");
    const smSalePrice = document.getElementById("sm-sale-price");
    const smMarkup = document.getElementById("sm-markup");
    const smReason = document.getElementById("sm-reason");
    const smNotes = document.getElementById("sm-notes");

    const smCostHint = document.getElementById("sm-cost-hint");
    const smNotesHint = document.getElementById("sm-notes-hint");
    const smErr = document.getElementById("sm-error");
    const smSubmit = document.getElementById("sm-submit");

    const smToast = document.getElementById("sm-toast");
    const smToastTitle = document.getElementById("sm-toast-title");
    const smToastMsg = document.getElementById("sm-toast-msg");

    const kTotalSkus = $("kpi-total-skus");
    const kSkusNote  = $("kpi-skus-note");
    const kTotalQty  = $("kpi-total-qty");
    const kTotalCost = $("kpi-total-cost");
    const kTotalSale = $("kpi-total-sale");
    const kSaleNote  = $("kpi-sale-note");
    const kIn7  = $("kpi-in-7");
    const kIn30 = $("kpi-in-30");
    const kOut7 = $("kpi-out-7");
    const kOut30= $("kpi-out-30");

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");

    const brl = (n) => Number(n || 0).toLocaleString("pt-BR", { style: "currency", currency: "BRL" });

    let moveState = {
        stock_part_id: null,
        snapshot: null,
        canOverrideOut: false,
    };

    const elReason = document.getElementById('sm-reason');

    async function openMoveModal() {
        mvModal.classList.remove("hidden");
        document.documentElement.classList.add("overflow-hidden");

        await refreshReasonsForMoveModal();
    }

    function closeMoveModal() {
        mvModal.classList.add("hidden");
        document.documentElement.classList.remove("overflow-hidden");
    }
    mvModal.querySelectorAll("[data-mv-close]").forEach((b) => b.addEventListener("click", closeMoveModal));
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && !mvModal.classList.contains("hidden")) closeMoveModal();
    });

    function toast(title, msg) {
        smToastTitle.textContent = title;
        smToastMsg.textContent = msg || "";
        smToast.classList.remove("hidden");
        window.setTimeout(() => smToast.classList.add("hidden"), 2500);
    }

    function fillReasonSelect(reasons, defaultCode) {
        const opts = (reasons || []).map((r) => {
            const tag = r.is_system ? " (Sistema)" : "";
            return `<option value="${escapeHtml(r.code)}">${escapeHtml(r.label)}${tag}</option>`;
        });

        // fallback se vier vazio
        if (opts.length === 0) {
            opts.push(`<option value="manual_${defaultCode}">Manual (${defaultCode})</option>`);
        }

        smReason.innerHTML = opts.join("");
        // tenta setar manual_in/manual_out se existir
        const prefer = defaultCode === "in" ? "manual_in" : "manual_out";
        const hasPrefer = Array.from(smReason.options).some(o => o.value === prefer);
        smReason.value = hasPrefer ? prefer : smReason.options[0].value;
    }

    function fillLocationSelect(locs, preferredId) {
        const opts = (locs || []).map((l) => {
            const tag = l.is_default ? " (Padrão)" : "";
            return `<option value="${escapeHtml(l.id)}">${escapeHtml(l.name)}${tag}</option>`;
        });
        smLoc.innerHTML = opts.join("");

        if (preferredId && opts.length) {
            const ok = Array.from(smLoc.options).some(o => o.value === preferredId);
            if (ok) smLoc.value = preferredId;
        } else if (opts.length) {
            // default: primeiro (já vem com default no topo pelo backend)
            smLoc.value = smLoc.options[0].value;
        }
    }

    function currentLocData() {
        const id = smLoc.value;
        const loc = (moveState.snapshot?.locations || []).find(l => String(l.id) === String(id));
        return loc || { qty_on_hand: 0, avg_cost: 0, min_qty: 0 };
    }

    function applyHeaderAndCards() {
        const p = moveState.snapshot?.part;
        if (!p) return;

        smTitle.textContent = `${p.code || "-"} • ${p.description || p.name || "-"}`;
        smSubtitle.textContent = p.ncm ? `NCM: ${p.ncm}` : "";

        const loc = currentLocData();
        smCurQty.textContent = String(loc.qty_on_hand || 0);
        smCurAvg.textContent = brl(loc.avg_cost || 0);
        smCurMin.textContent = String(loc.min_qty || 0);
        smGlobalAvg.textContent = brl(p.avg_cost_global || 0);
    }

    function applyDefaultsForType() {
        const p = moveState.snapshot?.part;
        if (!p) return;

        const loc = currentLocData();

        // defaults de venda/margem
        smSalePrice.value = String(Number(p.default_sale_price || 0) || "");
        smMarkup.value = String(Number(p.default_markup_percent || 0) || "");

        if (smType.value === "out") {
            // SAÍDA: abre com avg_cost do local (editável)
            smUnitCost.value = String(Number(loc.avg_cost || 0).toFixed(4));
            smCostHint.textContent = "Saída: custo puxado do custo médio do local (editável).";
            smNotesHint.textContent = moveState.canOverrideOut
                ? "Se alterar o custo na saída, coloque uma observação (o backend exige)."
                : "Você não tem permissão para alterar custo na saída (se tentar, será ignorado).";
        } else {
            // ENTRADA: abre com last_cost (se tiver) senão avg_global
            const base = Number(p.last_cost || 0) > 0 ? Number(p.last_cost || 0) : Number(p.avg_cost_global || 0);
            smUnitCost.value = String(Number(base || 0).toFixed(4));
            smCostHint.textContent = "Entrada: custo sugerido pelo último custo (ou custo médio global).";
            smNotesHint.textContent = "";
        }
    }

    async function fetchSnapshot(stockPartId) {
        const res = await fetch(`/stock/stock-api/${encodeURIComponent(stockPartId)}`, {
            headers: { Accept: "application/json" }
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.message || "Falha ao carregar item.");
        return data;
    }


    let reasonsCache = null;

    async function loadReasonsPicklist() {
        if (reasonsCache) return reasonsCache;

        const res = await fetch("/stock/settings/reasons-picklist", {
            headers: { Accept: "application/json" },
        });

        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.message || "Falha ao carregar motivos.");

        reasonsCache = Array.isArray(data.items) ? data.items : [];
        return reasonsCache;
    }

    function fillReasonsSelect(selectEl, reasons, preferredCode) {
        const opts = [`<option value="">—</option>`];

        for (const r of reasons) {
            const tag = Number(r.is_system || 0) === 1 ? " (Sistema)" : "";
            opts.push(
                `<option value="${escapeHtml(r.code)}">${escapeHtml(r.label)}${tag}</option>`
            );
        }

        selectEl.innerHTML = opts.join("");

        if (preferredCode) {
            const has = Array.from(selectEl.options).some(o => o.value === preferredCode);
            if (has) selectEl.value = preferredCode;
        }
    }

    async function refreshReasonsForMoveModal() {
        if (!smReason) return;

        try {
            const reasons = await loadReasonsPicklist();
            const preferred = smType.value === "in" ? "manual_in" : "manual_out";
            fillReasonsSelect(smReason, reasons, preferred);
        } catch (err) {
            console.warn(err);
            smReason.innerHTML = `<option value="">—</option>`;
        }
    }

    function resetModalUI() {
        smErr.classList.add("hidden");
        smErr.textContent = "";

        smTitle.textContent = "Carregando…";
        smSubtitle.textContent = "";
        smCurQty.textContent = "0";
        smCurAvg.textContent = brl(0);
        smCurMin.textContent = "0";
        smGlobalAvg.textContent = brl(0);

        smType.value = "out";
        smQty.value = "";
        smUnitCost.value = "";
        smSalePrice.value = "";
        smMarkup.value = "";
        smNotes.value = "";
        smCostHint.textContent = "";
        smNotesHint.textContent = "";
    }

    async function openFor(stockPartId) {
        moveState.stock_part_id = stockPartId;

        resetModalUI();
        await openMoveModal();

        const snap = await fetchSnapshot(stockPartId);
        moveState.snapshot = snap;
        moveState.canOverrideOut = !!snap.permissions?.override_cost_out;

        // location preferido: se estiver filtrando por location na tela, usa ele
        const preferredLoc = state.location_id || (snap.locations?.find(l => l.is_default)?.id ?? "");
        fillLocationSelect(snap.locations || [], preferredLoc);

        // motivo default baseado no tipo
        fillReasonSelect(snap.reasons || [], smType.value);

        applyHeaderAndCards();
        applyDefaultsForType();
    }

    function validatePayload(payload) {
        if (!payload.stock_part_id) return "Item inválido.";
        if (!payload.location_id) return "Selecione um local.";
        if (!payload.qty || payload.qty <= 0) return "Quantidade inválida.";

        if (payload.type === "in") {
            if (payload.unit_cost === null || payload.unit_cost === "" || Number(payload.unit_cost) < 0) return "Custo inválido.";
        }
        return null;
    }

    async function submitMovement() {
        smErr.classList.add("hidden");
        smErr.textContent = "";

        const type = smType.value; // in/out
        const payload = {
            type,
            stock_part_id: moveState.stock_part_id,
            location_id: smLoc.value,
            qty: Number(smQty.value || 0),
            unit_cost: smUnitCost.value === "" ? null : Number(smUnitCost.value),
            sale_price: smSalePrice.value === "" ? null : Number(smSalePrice.value),
            markup_percent: smMarkup.value === "" ? null : Number(smMarkup.value),
            reason_code: smReason.value || null,
            notes: (smNotes.value || "").trim() || null,
        };

        const err = validatePayload(payload);
        if (err) {
            smErr.textContent = err;
            smErr.classList.remove("hidden");
            return;
        }

        const url = type === "in"
            ? "/stock/movements-api/manual-in"
            : "/stock/movements-api/manual-out";

        smSubmit.disabled = true;

        const res = await fetch(url, {
            method: "POST",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/json",
                ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
            },
            body: JSON.stringify(payload),
        });

        const data = await res.json().catch(() => ({}));
        smSubmit.disabled = false;

        if (!res.ok) {
            const msg = data.message || "Falha ao salvar.";
            smErr.textContent = msg;
            smErr.classList.remove("hidden");
            return;
        }

        closeMoveModal();
        toast("Movimentação registrada", `ID: ${(data.movement_id || "").slice(0, 8)}`);

        load();
    }

    smType.addEventListener("change", () => {
        refreshReasonsForMoveModal().catch(console.error);
        applyDefaultsForType();
        applyHeaderAndCards();
    });


    smLoc.addEventListener("change", () => {
        applyHeaderAndCards();
        if (smType.value === "out") {
            applyDefaultsForType();
        }
    });

    smSubmit.addEventListener("click", () => submitMovement().catch(console.error));

    elTbody.addEventListener("click", (e) => {
        const btn = e.target.closest("[data-move]");
        if (!btn) return;
        const id = btn.getAttribute("data-move");
        openFor(id).catch((err) => {
            console.error(err);
            smErr.textContent = err?.message || "Falha ao abrir modal.";
            smErr.classList.remove("hidden");
            openMoveModal();
        });
    });

    // === KPIS
    const fmtInt = (n) => Number(n || 0).toLocaleString("pt-BR");
    const fmtBRL = (n) => Number(n || 0).toLocaleString("pt-BR", { style: "currency", currency: "BRL" });

    const buildFilterParams = () => {
        const p = new URLSearchParams();
        if (state.q) p.set("q", state.q);
        if (state.location_id) p.set("location_id", state.location_id);
        p.set("active", String(state.active));
        return p.toString();
    };

    function setKpiLoading() {
        if (kTotalSkus) kTotalSkus.textContent = "—";
        if (kSkusNote)  kSkusNote.textContent  = "Carregando...";
        if (kTotalQty)  kTotalQty.textContent  = "—";
        if (kTotalCost) kTotalCost.textContent = "—";
        if (kTotalSale) kTotalSale.textContent = "—";
        if (kIn7)  kIn7.textContent  = "—";
        if (kIn30) kIn30.textContent = "—";
        if (kOut7) kOut7.textContent = "—";
        if (kOut30)kOut30.textContent= "—";
    }

    async function loadKpis() {
        setKpiLoading();

        const res = await fetch(`/stock/kpis-api?${buildFilterParams()}`, {
            headers: { Accept: "application/json" }
        });

        if (!res.ok) return;

        const data = await res.json().catch(() => ({}));
        const k = data.kpis || {};

        if (kTotalSkus) kTotalSkus.textContent = fmtInt(k.total_skus || 0);
        if (kSkusNote)  kSkusNote.textContent  = `Em estoque: ${fmtInt(k.skus_in_stock || 0)} • com venda: ${fmtInt(k.sale_skus_in_stock || 0)}`;

        if (kTotalQty)  kTotalQty.textContent  = fmtInt(k.total_qty || 0);
        if (kTotalCost) kTotalCost.textContent = fmtBRL(k.total_cost_value || 0);
        if (kTotalSale) kTotalSale.textContent = fmtBRL(k.total_sale_value || 0);

        if (kIn7)  kIn7.textContent   = `${fmtInt(k.in_7?.total_qty || 0)} • ${fmtBRL(k.in_7?.total_cost || 0)}`;
        if (kIn30) kIn30.textContent  = `${fmtInt(k.in_30?.total_qty || 0)} • ${fmtBRL(k.in_30?.total_cost || 0)}`;
        if (kOut7) kOut7.textContent  = `${fmtInt(k.out_7?.total_qty || 0)} • ${fmtBRL(k.out_7?.total_cost || 0)}`;
        if (kOut30)kOut30.textContent = `${fmtInt(k.out_30?.total_qty || 0)} • ${fmtBRL(k.out_30?.total_cost || 0)}`;
    }

    async function reloadAll() {
        await Promise.all([load(), loadKpis()]);
    }

// inicial
    reloadAll();

// nos eventos de filtro (onde hoje você chama load())
    elQ.addEventListener("input", () => {
        state.q = elQ.value.trim();
        state.page = 1;
        debounced(reloadAll);
    });

    elLoc.addEventListener("change", () => {
        state.location_id = elLoc.value;
        state.page = 1;
        reloadAll();
    });

    elActive.addEventListener("change", () => {
        state.active = elActive.checked ? 1 : 0;
        state.page = 1;
        reloadAll();
    });

// paginação: mantém só load()
    elPrev.addEventListener("click", () => {
        if (state.page > 1) { state.page -= 1; load(); }
    });

    elNext.addEventListener("click", () => {
        const last = state.last?.last_page || 1;
        if (state.page < last) { state.page += 1; load(); }
    });

    reloadAll();
})();
