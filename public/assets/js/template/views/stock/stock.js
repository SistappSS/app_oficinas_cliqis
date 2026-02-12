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
    <button data-view="${escapeHtml(it.id)}"
      class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
      Visualizar
    </button>

    <button data-adjust="${escapeHtml(it.id)}"
      class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
      Ajustar
    </button>

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
    };

    // Debounce simples
    let t = null;
    const debounced = (fn, ms = 350) => {
        window.clearTimeout(t);
        t = window.setTimeout(fn, ms);
    };

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
    function closeLogModal() {
        logModal.classList.add("hidden");
        logState.return_to_view = false;
        syncBodyLock();
    }

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
        <td class="px-3 py-4 text-slate-700">${esc2(m.changes_summary || "-")}</td>
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


    // ===== MODAL AJUSTAR =====
    const adjModal = document.getElementById("stock-adjust-modal");
    const saTitle = document.getElementById("sa-title");
    const saSubtitle = document.getElementById("sa-subtitle");
    const saCurQty = document.getElementById("sa-cur-qty");
    const saCurAvg = document.getElementById("sa-cur-avg");
    const saCurMin = document.getElementById("sa-cur-min");
    const saGlobal = document.getElementById("sa-global");

    const saLoc = document.getElementById("sa-location");
    const saLocHint = document.getElementById("sa-loc-hint");
    const saQty = document.getElementById("sa-qty");
    const saAvg = document.getElementById("sa-avg-cost");
    const saLast = document.getElementById("sa-last-cost");
    const saSale = document.getElementById("sa-sale-price");
    const saMk = document.getElementById("sa-markup");
    const saMkHint = document.getElementById("sa-markup-hint");
    const saNotes = document.getElementById("sa-notes");
    const saErr = document.getElementById("sa-error");
    const saSubmit = document.getElementById("sa-submit");

    let adjState = { stock_part_id: null, snap: null, lockedLoc: false, lastEdited: "mk" };

    const openAdjModal = () => {
        adjModal.classList.remove("hidden");
        document.documentElement.classList.add("overflow-hidden");
    };
    const closeAdjModal = () => {
        adjModal.classList.add("hidden");
        document.documentElement.classList.remove("overflow-hidden");
    };

    if (adjModal) {
        adjModal.querySelectorAll("[data-sa-close]").forEach(b => b.addEventListener("click", closeAdjModal));
    }
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && adjModal && !adjModal.classList.contains("hidden")) closeAdjModal();
    });

    function setAdjErr(msg) {
        if (!saErr) return;
        if (!msg) { saErr.classList.add("hidden"); saErr.textContent = ""; return; }
        saErr.textContent = msg;
        saErr.classList.remove("hidden");
    }

    function findLocFromSnap(locId) {
        const locs = adjState.snap?.locations || [];
        return locs.find(l => String(l.id) === String(locId)) || { qty_on_hand: 0, avg_cost: 0, min_qty: 0 };
    }

    function fillAdjLocations(locs, preferredId) {
        const opts = (locs || []).map((l) => {
            const tag = l.is_default ? " (Padrão)" : "";
            return `<option value="${escapeHtml(l.id)}">${escapeHtml(l.name)}${tag}</option>`;
        });
        saLoc.innerHTML = opts.join("");

        if (preferredId && opts.length) {
            const ok = Array.from(saLoc.options).some(o => o.value === preferredId);
            if (ok) saLoc.value = preferredId;
        } else if (opts.length) {
            saLoc.value = saLoc.options[0].value;
        }
    }

    const toNum = (v) => {
        if (v === "" || v === null || v === undefined) return null;
        const n = Number(v);
        return Number.isFinite(n) ? n : null;
    };

    function baseCostForMarkup() {
        const last = toNum(saLast?.value) || 0;
        if (last > 0) return last;

        const loc = findLocFromSnap(saLoc.value);
        const avg = Number(loc.avg_cost || 0);
        return avg > 0 ? avg : 0;
    }

    function syncMkFromSale() {
        const base = baseCostForMarkup();
        const sale = toNum(saSale.value) || 0;

        if (base <= 0) {
            saMkHint.textContent = "Base de custo = 0 (não dá pra calcular markup automático).";
            return;
        }

        const mk = ((sale / base) - 1) * 100;
        saMk.value = Number.isFinite(mk) ? mk.toFixed(2) : "0.00";
        saMkHint.textContent = `Base custo: ${fmtBR4(base)} (last_cost > 0 senão avg_cost do local).`;
    }

    function syncSaleFromMk() {
        const base = baseCostForMarkup();
        const mk = toNum(saMk.value) || 0;

        if (base <= 0) {
            saMkHint.textContent = "Base de custo = 0 (não dá pra calcular venda automático).";
            return;
        }

        const sale = base * (1 + (mk / 100));
        saSale.value = Number.isFinite(sale) ? sale.toFixed(2) : "0.00";
        saMkHint.textContent = `Base custo: ${fmtBR4(base)} (last_cost > 0 senão avg_cost do local).`;
    }

    function applyAdjHeaderCards() {
        const p = adjState.snap?.part;
        if (!p) return;

        saTitle.textContent = `${p.code || "-"} • ${p.description || p.name || "-"}`;
        saSubtitle.textContent = p.ncm ? `NCM: ${p.ncm}` : "";

        const loc = findLocFromSnap(saLoc.value);

        saCurQty.textContent = String(loc.qty_on_hand || 0);
        saCurAvg.textContent = brl(loc.avg_cost || 0);
        saCurMin.textContent = String(loc.min_qty || 0);

        saGlobal.textContent = `${Number(p.qty_on_hand_global || 0)} • ${brl(p.avg_cost_global || 0)}`;
    }

    function fillAdjForm() {
        const p = adjState.snap?.part;
        if (!p) return;

        const loc = findLocFromSnap(saLoc.value);

        saQty.value = String(Number(loc.qty_on_hand || 0));
        saAvg.value = String(Number(loc.avg_cost || 0).toFixed(4));

        saLast.value = String(Number(p.last_cost || 0).toFixed(4));
        saSale.value = String(Number(p.default_sale_price || 0).toFixed(2));
        saMk.value = String(Number(p.default_markup_percent || 0).toFixed(2));

        saNotes.value = "";
        saMkHint.textContent = "";
    }

    async function openAdjustFor(stockPartId) {
        adjState.stock_part_id = stockPartId;
        setAdjErr("");

        saTitle.textContent = "Carregando…";
        saSubtitle.textContent = "";
        saLocHint.textContent = "";

        openAdjModal();

        const snap = await fetchSnapshot(stockPartId);
        adjState.snap = snap;

        const preferredLoc = state.location_id || (snap.locations?.find(l => l.is_default)?.id ?? "");
        fillAdjLocations(snap.locations || [], preferredLoc);

        // se tela está filtrada por local, trava para não ajustar errado
        adjState.lockedLoc = !!state.location_id;
        saLoc.disabled = adjState.lockedLoc;
        saLocHint.textContent = adjState.lockedLoc
            ? "Local travado (seguindo o filtro atual da tela)."
            : "Você pode escolher o local a ajustar.";

        applyAdjHeaderCards();
        fillAdjForm();

        // ao abrir, deixa coerente markup/venda (sem mexer no valor do user)
        syncMkFromSale();
    }

    function buildAdjustPayload() {
        const p = adjState.snap?.part;
        if (!p) return { err: "Item inválido." };

        const locId = saLoc.value;
        if (!locId) return { err: "Selecione um local." };

        const loc = findLocFromSnap(locId);

        const nextQty = toNum(saQty.value);
        const nextAvg = toNum(saAvg.value);
        const nextLast = toNum(saLast.value);
        const nextSale = toNum(saSale.value);
        const nextMk = toNum(saMk.value);

        if (nextQty === null || nextQty < 0) return { err: "Quantidade inválida." };
        if (nextAvg === null || nextAvg < 0) return { err: "Custo médio inválido." };
        if (nextLast === null || nextLast < 0) return { err: "Último custo inválido." };
        if (nextSale === null || nextSale < 0) return { err: "Venda inválida." };
        if (nextMk === null || nextMk < 0) return { err: "Markup inválido." };

        const payload = { location_id: locId };

        // só manda o que mudou
        if (Number(nextQty) !== Number(loc.qty_on_hand || 0)) payload.qty_on_hand = Number(nextQty);
        if (Number(nextAvg) !== Number(loc.avg_cost || 0)) payload.avg_cost = Number(nextAvg);

        if (Number(nextLast) !== Number(p.last_cost || 0)) payload.last_cost = Number(nextLast);
        if (Number(nextSale) !== Number(p.default_sale_price || 0)) payload.default_sale_price = Number(nextSale);
        if (Number(nextMk) !== Number(p.default_markup_percent || 0)) payload.default_markup_percent = Number(nextMk);

        const notes = (saNotes.value || "").trim();
        if (notes) payload.notes = notes;

        const changedKeys = Object.keys(payload).filter(k => k !== "location_id" && k !== "notes");
        if (changedKeys.length === 0 && !payload.notes) {
            return { err: "Nada para ajustar." };
        }

        return { payload };
    }

    async function submitAdjust() {
        setAdjErr("");

        const built = buildAdjustPayload();
        if (built.err) return setAdjErr(built.err);

        saSubmit.disabled = true;

        const res = await fetch(`/stock/stock-api/${encodeURIComponent(adjState.stock_part_id)}/adjust`, {
            method: "POST",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/json",
                ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
            },
            body: JSON.stringify(built.payload),
        });

        const data = await res.json().catch(() => ({}));
        saSubmit.disabled = false;

        if (!res.ok) {
            setAdjErr(data.message || "Falha ao salvar ajuste.");
            return;
        }

        closeAdjModal();
        toast("Ajuste aplicado", `Mov: ${(data.movement_id || "").slice(0, 8)}`);
        reloadAll();
    }

    saSubmit?.addEventListener("click", () => submitAdjust().catch(console.error));

    saLoc?.addEventListener("change", () => {
        applyAdjHeaderCards();
        fillAdjForm();
        syncMkFromSale();
    });

    saSale?.addEventListener("input", () => { adjState.lastEdited = "sale"; syncMkFromSale(); });
    saMk?.addEventListener("input", () => { adjState.lastEdited = "mk"; syncSaleFromMk(); });
    saLast?.addEventListener("input", () => {
        // se mudar a base de custo, mantém coerência
        if (adjState.lastEdited === "sale") syncMkFromSale();
        else syncSaleFromMk();
    });

// clique no botão Ajustar
    elTbody.addEventListener("click", (e) => {
        const btn = e.target.closest("[data-adjust]");
        if (!btn) return;
        const id = btn.getAttribute("data-adjust");
        openAdjustFor(id).catch((err) => {
            console.error(err);
            setAdjErr(err?.message || "Falha ao abrir ajuste.");
            openAdjModal();
        });
    });

// ===== MODAL VISUALIZAR =====
    const viewModal = document.getElementById("stock-view-modal");
    const svTitle = document.getElementById("sv-title");
    const svSubtitle = document.getElementById("sv-subtitle");
    const svQtyGlobal = document.getElementById("sv-qty-global");
    const svAvgGlobal = document.getElementById("sv-avg-global");
    const svTotalCost = document.getElementById("sv-total-cost");
    const svSaleMk = document.getElementById("sv-sale-mk");

    const svLocsTbody = document.getElementById("sv-locs-tbody");
    const svLogsTbody = document.getElementById("sv-logs-tbody");
    const svLogsEmpty = document.getElementById("sv-logs-empty");
    const svOpenFullLog = document.getElementById("sv-open-full-log");

    const svTotalSale = document.getElementById("sv-total-sale");
    const svLastCost  = document.getElementById("sv-last-cost");
    const svLocsFoot  = document.getElementById("sv-locs-footnote");

    const svPricesTbody = document.getElementById("sv-prices-tbody");
    const svPricesEmpty = document.getElementById("sv-prices-empty");
    const svTransferTbody = document.getElementById("sv-transfer-tbody");
    const svTransferEmpty = document.getElementById("sv-transfer-empty");
    const svOpenFullAdjust = document.getElementById("sv-open-full-adjust");
    const svOpenFullTransfer = document.getElementById("sv-open-full-transfer");

    let viewState = { stock_part_id: null, snap: null };

    const openViewModal = () => {
        viewModal.classList.remove("hidden");
        document.documentElement.classList.add("overflow-hidden");
    };
    const closeViewModal = () => {
        viewModal.classList.add("hidden");
        document.documentElement.classList.remove("overflow-hidden");
    };

    function setSvTab(tab) {
        viewModal.querySelectorAll("[data-sv-pane]").forEach(p => {
            p.classList.toggle("hidden", p.getAttribute("data-sv-pane") !== tab);
        });

        viewModal.querySelectorAll("[data-sv-tab]").forEach((btn) => {
            const on = btn.getAttribute("data-sv-tab") === tab;

            btn.classList.remove(
                "bg-white","text-blue-700","border-blue-200","hover:bg-blue-50",
                "bg-blue-900","text-white","border-blue-900"
            );

            if (on) {
                btn.classList.add("bg-blue-900","text-white","border-blue-900","hover:bg-blue-800");
            } else {
                btn.classList.add("bg-white","text-blue-700","border-blue-200","hover:text-white","hover:bg-blue-700");
            }
        });

        if (!viewState.stock_part_id) return;

        if (tab === "logs") {
            loadViewLogsPreview({ stockPartId: viewState.stock_part_id, type: "", tbody: svLogsTbody, emptyEl: svLogsEmpty })
                .catch(console.error);
        }

        if (tab === "prices") {
            loadViewLogsPreview({ stockPartId: viewState.stock_part_id, type: "adjust", tbody: svPricesTbody, emptyEl: svPricesEmpty })
                .catch(console.error);
        }

        if (tab === "transfer") {
            loadViewLogsPreview({ stockPartId: viewState.stock_part_id, type: "transfer", tbody: svTransferTbody, emptyEl: svTransferEmpty })
                .catch(console.error);
        }
    }

    if (viewModal) {
        viewModal.querySelectorAll("[data-sv-close]").forEach(b => b.addEventListener("click", closeViewModal));
        viewModal.querySelectorAll("[data-sv-tab]").forEach(b => b.addEventListener("click", () => {
            setSvTab(b.getAttribute("data-sv-tab"));
        }));
        // default tab (na criação)
        setSvTab("summary");
    }

    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && viewModal && !viewModal.classList.contains("hidden")) closeViewModal();
    });

    async function loadViewLogsPreview({ stockPartId, type = "", tbody, emptyEl }) {
        if (!tbody || !emptyEl) return;

        tbody.innerHTML = `<tr><td class="px-6 py-6 text-slate-500" colspan="5">Carregando…</td></tr>`;
        emptyEl.classList.add("hidden");

        const p = new URLSearchParams();
        p.set("stock_part_id", stockPartId);
        p.set("page", "1");
        if (type) p.set("type", type);

        const res = await fetch(`/stock/movements-api?${p.toString()}`, {
            headers: { Accept: "application/json" }
        });

        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            tbody.innerHTML = `<tr><td class="px-6 py-6 text-red-600" colspan="5">Falha ao carregar.</td></tr>`;
            return;
        }

        const rows = (data.items?.data || []).slice(0, 8);
        if (!rows.length) {
            tbody.innerHTML = "";
            emptyEl.classList.remove("hidden");
            return;
        }

        tbody.innerHTML = rows.map(m => `
  <tr>
    <td class="px-6 py-4 text-slate-700">${esc2(m.created_at || "-")}</td>
    <td class="px-3 py-4 text-slate-900">${esc2(labelType2(m.type))}</td>
    <td class="px-3 py-4 text-slate-700">${esc2(m.reason_label || "-")}</td>
    <td class="px-3 py-4 text-slate-700">${esc2(m.changes_summary || "-")}</td>
    <td class="px-3 py-4 text-right text-slate-900">${Number(m.total_qty || 0)}</td>
    <td class="px-6 py-4 text-right text-slate-900">${fmtBRL2(m.total_cost || 0)}</td>
  </tr>
`).join("");
    }

    function fillViewFromSnap() {
        const p = viewState.snap?.part;
        if (!p) return;

        svTitle.textContent = `${p.code || "-"} • ${p.description || p.name || "-"}`;
        svSubtitle.textContent = p.ncm ? `NCM: ${p.ncm}` : "";

        const qtyG = Number(p.qty_on_hand_global || 0);
        const avgG = Number(p.avg_cost_global || 0);
        const totalCost = qtyG * avgG;

        const sale = Number(p.default_sale_price || 0);
        const totalSale = qtyG * sale;

        svQtyGlobal.textContent = qtyG.toLocaleString("pt-BR");
        svAvgGlobal.textContent = brl(avgG);
        svTotalCost.textContent = brl(totalCost);

        svSaleMk.textContent = `${brl(sale)} • ${Number(p.default_markup_percent || 0).toFixed(2)}%`;

        if (svTotalSale) svTotalSale.textContent = brl(totalSale);
        if (svLastCost)  svLastCost.textContent  = brl(Number(p.last_cost || 0));

        const locs = (viewState.snap?.locations || []);
        const sumQty = locs.reduce((a, l) => a + Number(l.qty_on_hand || 0), 0);
        const sumCostVal = locs.reduce((a, l) => a + (Number(l.qty_on_hand || 0) * Number(l.avg_cost || 0)), 0);
        const avgWeighted = sumQty > 0 ? (sumCostVal / sumQty) : 0;

        // tabela por locais
        svLocsTbody.innerHTML = locs.map(l => {
            const q = Number(l.qty_on_hand || 0);
            const a = Number(l.avg_cost || 0);
            const costVal = q * a;
            const saleVal = sale > 0 ? q * sale : 0;

            return `
      <tr>
        <td class="px-6 py-4 text-slate-700">
          ${escapeHtml(l.name)}${l.is_default ? ' <span class="text-xs text-slate-500">(Padrão)</span>' : ''}
        </td>
        <td class="px-3 py-4 text-right text-slate-900">${q.toLocaleString("pt-BR")}</td>
        <td class="px-3 py-4 text-right text-slate-900">${fmtBR4(a)}</td>
        <td class="px-3 py-4 text-right text-slate-900">${brl(costVal)}</td>
        <td class="px-3 py-4 text-right text-slate-900">${Number(l.min_qty || 0).toLocaleString("pt-BR")}</td>
        <td class="px-6 py-4 text-right text-slate-900">${brl(saleVal)}</td>
      </tr>
    `;
        }).join("");

        // footnote: conferência
        if (svLocsFoot) {
            const diffQty = sumQty !== qtyG;
            const diffAvg = Math.abs(avgWeighted - avgG) > 0.0001;

            svLocsFoot.textContent =
                `Soma locais: qtd ${sumQty.toLocaleString("pt-BR")} • custo total ${brl(sumCostVal)} • custo médio ponderado ${fmtBR4(avgWeighted)}`
                + (diffQty || diffAvg ? " • ⚠ difere do global" : " • OK");
        }
    }

    async function openViewFor(stockPartId) {
        viewState.stock_part_id = stockPartId;

        svTitle.textContent = "Carregando…";
        svSubtitle.textContent = "";
        svLocsTbody.innerHTML = "";
        svLogsTbody.innerHTML = "";
        svLogsEmpty.classList.add("hidden");

        if (svPricesTbody) svPricesTbody.innerHTML = "";
        if (svPricesEmpty) svPricesEmpty.classList.add("hidden");
        if (svTransferTbody) svTransferTbody.innerHTML = "";
        if (svTransferEmpty) svTransferEmpty.classList.add("hidden");

        openViewModal();
        setSvTab("summary"); // sempre abre no resumo

        const snap = await fetchSnapshot(stockPartId);
        viewState.snap = snap;

        fillViewFromSnap();

        // não carrega logs aqui; carrega quando clicar na aba (lazy)
        svOpenFullLog.onclick = () => openFullLog(stockPartId, "");
        if (svOpenFullAdjust) svOpenFullAdjust.onclick = () => openFullLog(stockPartId, "adjust");
        if (svOpenFullTransfer) svOpenFullTransfer.onclick = () => openFullLog(stockPartId, "transfer");
    }

    function openFullLog(stockPartId, type) {
        logState.stock_part_id = stockPartId;
        logState.page = 1;
        logState.q = "";
        logState.type = type || "";

        logQ.value = "";
        logType.value = type || "";
        logTitle.textContent = "Movimentações da peça";
        logSubtitle.textContent = `stock_part_id: ${logState.stock_part_id}`;

        openLogModal();
        loadLog();
    }

    elTbody.addEventListener("click", (e) => {
        const btn = e.target.closest("[data-view]");
        if (!btn) return;
        const id = btn.getAttribute("data-view");
        openViewFor(id).catch(console.error);
    });

    function syncBodyLock() {
        const anyOpen =
            !mvModal.classList.contains("hidden") ||
            !adjModal.classList.contains("hidden") ||
            !viewModal.classList.contains("hidden") ||
            !logModal.classList.contains("hidden");

        document.documentElement.classList.toggle("overflow-hidden", anyOpen);
    }




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

    reloadAll();

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

    elPrev.addEventListener("click", () => {
        if (state.page > 1) { state.page -= 1; load(); }
    });

    elNext.addEventListener("click", () => {
        const last = state.last?.last_page || 1;
        if (state.page < last) { state.page += 1; load(); }
    });
})();
