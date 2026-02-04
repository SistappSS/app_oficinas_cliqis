// assets/js/template/views/service-orders/service-order-billing.js

document.addEventListener("DOMContentLoaded", () => {
    const q  = (sel) => document.querySelector(sel);
    const qs = (sel) => Array.from(document.querySelectorAll(sel));

    const tbody        = q("#tbody");
    const searchInput  = q("#search");
    const kpiApproved  = q("#kpi-approved");
    const kpiCount     = q("#kpi-count");

    // modal NF
    const nfModal         = q("#modal-nf");
    const nfClose         = q("#nf-close");
    const nfCancel        = q("#nf-cancel");
    const nfForm          = q("#billing-form");
    const nfOsIdInput     = q("#nf-os-id");
    const nfOsLabel       = q("#nf-os-label");
    const nfPaymentDate   = q("#nf-payment-date");
    const nfPaymentMethod = q("#nf-payment-method");
    const nfInstallments  = q("#nf-installments");
    const nfAmount        = q("#nf-amount");
    const nfAlert = q("#nf-alert");

    const nfSubmit = q("#nf-submit");
    const nfSubmitLabel = q("#nf-submit-label");
    const nfSpinner = q("#nf-submit-spinner");
    const nfOk = q("#nf-submit-ok");
    const nfX = q("#nf-submit-x");

    const state = {
        search: "",
        items: [],
    };

    const fmtBR = (n) =>
        "R$ " +
        Number(n || 0).toLocaleString("pt-BR", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });

    const debounce = (fn, delay = 300) => {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), delay);
        };
    };

    const useDown = q("#use_down_payment");
    const downWrap = q("#down-payment-wrap");
    const noDownWrap = q("#no-down-payment-wrap");

    const downPct = nfForm.querySelector('[name="down_payment_percent"]');
    const remainInst = nfForm.querySelector('[name="remaining_installments"]');
    const installments = q("#nf-installments");

    useDown?.addEventListener("change", syncDownPaymentUI);

    // === LOAD ===
    async function loadApprovedOrders() {
        if (!tbody) return;

        const baseUrl = "/service-orders/service-order-api";
        const url     = new URL(baseUrl, window.location.origin);

        if (state.search.trim()) {
            url.searchParams.set("q", state.search.trim());
        }

        // só OS aprovadas
        url.searchParams.set("status", "approved");

        try {
            const resp = await fetch(url.toString(), {
                headers: { Accept: "application/json" },
            });

            if (!resp.ok) {
                console.error("Erro ao carregar OS aprovadas:", await resp.text());
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="px-6 py-6 text-center text-sm text-rose-500">
                            Erro ao carregar ordens de serviço aprovadas.
                        </td>
                    </tr>`;
                return;
            }

            const json  = await resp.json();
            const items = json.data || json;

            state.items = Array.isArray(items) ? items : [];

            if (!state.items.length) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="px-6 py-6 text-center text-sm text-slate-500">
                            Nenhuma OS aprovada encontrada.
                        </td>
                    </tr>`;
                kpiApproved.textContent = "R$ 0,00";
                kpiCount.textContent    = "0";
                return;
            }

            // KPIs
            const totalApproved = state.items.reduce(
                (sum, os) => sum + Number(os.grand_total || 0),
                0
            );
            kpiApproved.textContent = fmtBR(totalApproved);
            kpiCount.textContent    = String(state.items.length);

            // tabela
            tbody.innerHTML = state.items.map(renderRow).join("");

            bindRowButtons();
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
        const status = os.status || "draft";

        const badgeMap = {
            draft:     "bg-slate-100 text-slate-700",
            pending:   "bg-amber-50 text-amber-700",
            approved:  "bg-emerald-50 text-emerald-700",
            rejected:  "bg-rose-50 text-rose-700",
            completed: "bg-sky-50 text-sky-700",
        };

        const badgeClass = badgeMap[status] || "bg-slate-100 text-slate-700";

        const customerName =
            (os.secondary_customer && os.secondary_customer.name) ||
            os.client_name ||
            "-";

        const total     = Number(os.grand_total || 0);
        const orderDate = os.order_date || "-";
        const ticket    = os.ticket_number || "-";

        return `
<tr class="hover:bg-slate-50">
    <td class="px-6 py-3">
        <div class="flex items-center gap-3">
            <span class="grid h-8 w-8 place-items-center rounded-full bg-blue-100 text-blue-700 font-semibold">
                ${customerName ? customerName.slice(0, 1) : "?"}
            </span>
            <p class="font-medium truncate max-w-[220px]">${customerName}</p>
        </div>
    </td>
    <td class="px-3 py-3">
        <div class="font-medium">${os.order_number || "-"}</div>
    </td>
    <td class="px-3 py-3 whitespace-nowrap">
        ${orderDate}
    </td>
    <td class="px-3 py-3">
        ${ticket}
    </td>
    <td class="px-3 py-3 text-right whitespace-nowrap">
        ${fmtBR(total)}
    </td>
    <td class="px-3 py-3 text-center">
        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ${badgeClass}">
            ${status}
        </span>
    </td>
    <td class="px-6 py-3 text-right">
        <div class="flex justify-end">
            <button type="button" title="Gerar NF / Cobrança"
                    data-nf="${os.id}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M6 4h9l3 3v13H6z"/>
                    <path d="M10 9h4"/>
                    <path d="M10 13h4"/>
                </svg>
            </button>

            <button type="button" title="Notificar por e-mail (futuro)"
                    data-email="${os.id}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M4 4h16v16H4z"/>
                    <path d="m4 7 8 5 8-5"/>
                </svg>
            </button>

            <button type="button" title="Área do cliente (futuro)"
                    data-customer-area="${os.secondary_customer_id || ""}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <circle cx="12" cy="8" r="3"/>
                    <path d="M6 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/>
                </svg>
            </button>
        </div>
    </td>
</tr>`;
    }

    function bindRowButtons() {
        if (!tbody) return;

        tbody.querySelectorAll("[data-nf]").forEach((btn) => {
            btn.addEventListener("click", () => {
                const id = btn.getAttribute("data-nf");
                const os = state.items.find((o) => String(o.id) === String(id));
                if (!os) return;
                openNfModal(os);
            });
        });

        // os outros dois ficam pra depois (e-mail / área cliente)
    }

    // === MODAL NF ===
    function openNfModal(os) {
        if (!nfModal) return;

        nfAlertHide();
        setBtnState("idle");

        if (nfPaymentDate && !nfPaymentDate._flatpickr) {
            flatpickr(nfPaymentDate, {
                altInput: true,
                altFormat: 'd/m/Y',
                dateFormat: 'Y-m-d',
                allowInput: true,
            });
        }

        nfOsIdInput.value = os.id || "";
        const customerName =
            (os.secondary_customer && os.secondary_customer.name) ||
            os.client_name ||
            "-";

        nfOsLabel.textContent = `OS ${os.order_number || "-"} — ${customerName}`;
        nfAmount.value        = (Number(os.grand_total || 0) || 0).toFixed(2);

        if (!nfPaymentDate.value) {
            nfPaymentDate.value = os.order_date || new Date().toISOString().slice(0, 10);
        }

        nfPaymentMethod.value = "";
        nfInstallments.value  = 1;

        useDown.checked = false;
        syncDownPaymentUI();

        nfModal.classList.remove("hidden");
    }

    function closeNfModal() {
        if (!nfModal) return;
        nfModal.classList.add("hidden");
    }

    function nfAlertHide() {
        if (!nfAlert) return;
        nfAlert.classList.add("hidden");
        nfAlert.textContent = "";
    }

    function nfAlertShow(msg) {
        if (!nfAlert) return;
        nfAlert.textContent = msg || "Erro ao gerar NF.";
        nfAlert.classList.remove("hidden");
    }

    function setBtnState(state) {
        // state: idle | loading | ok | error
        if (!nfSubmit) return;

        nfSubmit.disabled = (state === "loading");
        nfSpinner?.classList.toggle("hidden", state !== "loading");
        nfOk?.classList.toggle("hidden", state !== "ok");
        nfX?.classList.toggle("hidden", state !== "error");

        // reset classes
        nfSubmit.classList.remove("bg-blue-700","hover:bg-blue-800","bg-emerald-600","hover:bg-emerald-700","bg-rose-600","hover:bg-rose-700");

        if (state === "idle" || state === "loading") {
            nfSubmit.classList.add("bg-blue-700","hover:bg-blue-800");
            if (nfSubmitLabel) nfSubmitLabel.textContent = state === "loading" ? "Gerando..." : "Gerar NF (simulado)";
        }

        if (state === "ok") {
            nfSubmit.classList.add("bg-emerald-600","hover:bg-emerald-700");
            if (nfSubmitLabel) nfSubmitLabel.textContent = "Gerado";
        }

        if (state === "error") {
            nfSubmit.classList.add("bg-rose-600","hover:bg-rose-700");
            if (nfSubmitLabel) nfSubmitLabel.textContent = "Erro";
        }
    }

    const wrapDown = q("#down-payment-wrap");
    const wrapNo   = q("#no-down-payment-wrap");

    function syncDownPaymentUI() {
        const on = !!useDown?.checked;

        downWrap.classList.toggle("hidden", !on);
        noDownWrap.classList.toggle("hidden", on);

        // required (UX)
        downPct.required = on;
        remainInst.required = on;
        installments.required = !on;

        // disabled (garante que não envia hidden)
        downPct.disabled = !on;
        remainInst.disabled = !on;
        installments.disabled = on;

        // limpa campos do bloco escondido
        if (on) {
            installments.value = installments.value || 1;
        } else {
            downPct.value = "";
            remainInst.value = "";
        }
    }

    useDown?.addEventListener("change", syncDownPaymentUI);

    syncDownPaymentUI();

    if (nfClose)  nfClose.addEventListener("click", closeNfModal);
    if (nfCancel) nfCancel.addEventListener("click", closeNfModal);

    if (nfForm) {
        nfForm.addEventListener("submit", async (e) => {
            e.preventDefault();

            nfAlertHide();
            setBtnState("loading");

            const osId = nfOsIdInput.value;
            if (!osId) {
                setBtnState("error");
                nfAlertShow("OS inválida.");
                return;
            }

            try {
                const fd = new FormData(nfForm);
                const useDownChecked = document.querySelector("#use_down_payment")?.checked;
                fd.set("use_down_payment", useDownChecked ? "1" : "0");

                const res = await fetch(`/service-orders/${osId}/billing/generate`, {
                    method: "POST",
                    headers: {
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || ""
                    },
                    body: fd
                });

                const j = await res.json().catch(() => null);

                if (!res.ok || !j?.ok) {
                    setBtnState("error");
                    nfAlertShow(j?.message || "Erro ao gerar NF.");
                    return;
                }

                setBtnState("ok");

                setTimeout(() => {
                    closeNfModal();
                    loadApprovedOrders();
                    setBtnState("idle"); // reset pro próximo uso
                }, 2000);

            } catch (err) {
                console.error(err);
                setBtnState("error");
                nfAlertShow("Falha de rede ao gerar NF.");
            }
        });
    }

    // busca
    if (searchInput) {
        searchInput.addEventListener(
            "input",
            debounce(() => {
                state.search = searchInput.value || "";
                loadApprovedOrders();
            }, 300)
        );
    }

    // init
    loadApprovedOrders();
});
