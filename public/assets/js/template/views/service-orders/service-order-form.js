// assets/js/template/views/service-orders/service-order-form.js

document.addEventListener("DOMContentLoaded", () => {
    const csrf =
        document.querySelector('meta[name="csrf-token"]')?.content || "";

    const q = (sel) => document.querySelector(sel);

    const ROUTES = {
        serviceOrder: "/service-orders/service-order-api",
        customerSearch: "/entities/customer-api",
        customer: "/entities/customer-api",
        employee: "/human-resources/employee-api",
        serviceItem: "/catalogs/service-item-api",
        part: "/catalogs/part-api",
        equipment: "/catalogs/equipment-api",
    };

    // ========== HELPERS ==========
    const toNumber = (v) => {
        if (v === null || v === undefined) return 0;
        let s = String(v).trim();
        if (!s) return 0;

        // pt-BR -> 1.234,56
        if (s.includes(",") && !s.includes(".")) {
            s = s.replace(/\./g, "").replace(",", ".");
        }

        const n = Number(s);
        return isNaN(n) ? 0 : n;
    };

    const formatCurrency = (n) =>
        (n || 0).toLocaleString("pt-BR", {
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

    const postJson = async (url, body) => {
        const res = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrf,
                Accept: "application/json",
            },
            body: JSON.stringify(body),
        });
        if (!res.ok) {
            console.error("Erro POST", url, await res.text());
            throw new Error("Falha POST " + url);
        }
        return await res.json();
    };

    const state = {
        saving: false,
    };

    // ========== CAMPOS PRINCIPAIS ==========
    const orderIdInput = q("#service_order_id");
    const orderNumberDisplay = q("#order_number_display");
    const orderDateInput = q("#order_date");
    const requesterNameInput = q("#requester_name");

    const technicianNameInput = q("#service_responsible");
    let technicianIdInput = q("#technician_id");
    if (!technicianIdInput && technicianNameInput) {
        technicianIdInput = document.createElement("input");
        technicianIdInput.type = "hidden";
        technicianIdInput.id = "technician_id";
        technicianNameInput.parentNode.appendChild(technicianIdInput);
    }

    const clientNameInput = q("#os_client_name");
    const clientDocInput = q("#cpfCnpj");
    const clientEmailInput = q("#os_client_email");
    const clientPhoneInput = q("#mobilePhone");
    const clientAddressInput = q("#address");
    const clientAddressNumberInput = q("#addressNumber");
    const clientComplementInput = q("#complement");
    const clientCityInput = q("#cityName");
    const clientStateInput = q("#state");
    const clientProvinceInput = q("#province");
    const clientZipInput = q("#postalCode");
    const ticketNumberInput = q("#ticket_number");

    const clientResults = q("#os_client_results");
    let customerIdInput = q("#secondary_customer_id");
    if (!customerIdInput && clientNameInput) {
        customerIdInput = document.createElement("input");
        customerIdInput.type = "hidden";
        customerIdInput.id = "secondary_customer_id";
        clientNameInput.parentNode.appendChild(customerIdInput);
    }

    // listas dinâmicas
    const equipmentListEl = q("#equipment-list");
    const serviceListEl = q("#service-list");
    const partListEl = q("#part-list");
    const laborListEl = q("#labor-list");

    // totais / blocos
    const servicesSubtotalDisplay = q("#services-subtotal-display");
    const partsSubtotalDisplay = q("#parts-subtotal-display");
    const laborTotalAmountDisplay = q("#labor-total-amount-display");

    const boxServicesValue = q("#box-services-value");
    const boxPartsValue = q("#box-parts-value");
    // pode ser que o id do box de mão de obra ainda esteja duplicado como box-parts-value
    let boxLaborValue = q("#box-labor-value");
    if (!boxLaborValue) {
        // fallback tosco: pega o terceiro card de totais
        const cards = document.querySelectorAll(
            "#so-totals-block .rounded-2xl.bg-slate-50\\/80 span.font-semibold"
        );
        if (cards[2]) boxLaborValue = cards[2];
    }

    const discountInput = q("#discount");
    const additionInput = q("#addition");
    const grandTotalDisplay = q("#grand_total_display");

    const footerServicesValue = q("#footer-services-value");
    const footerPartsValue = q("#footer-parts-value");
    const footerLaborValue = q("#footer-labor-value");
    const footerGrandValue = q("#footer-grand-value");

    const laborHourValueInput = q("#labor_hour_value");
    const paymentConditionSel = q("#payment_condition");
    const paymentNotesInput = q("#payment_notes");

    const btnAddEquipment = q("#btn-add-equipment");
    const btnAddService = q("#btn-add-service");
    const btnAddPart = q("#btn-add-part");
    const btnAddLabor = q("#btn-add-labor");
    const btnSave = q("#btn-save-os");
    const btnFinish = q("#btn-finish-os");

    // modais
    const saveModal = q("#os-save-modal");
    const finalizeModal = q("#os-finalize-modal");
    const signModal = q("#os-signature-modal");

    // ========== TYPEAHEAD GENÉRICO ==========
    const wrapForDropdown = (input) => {
        if (!input) return null;
        const parent = input.parentNode;
        if (parent.classList.contains("relative")) return parent;

        const wrapper = document.createElement("div");
        wrapper.className = "relative";
        parent.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        return wrapper;
    };

    function setupTypeahead({
                                input,
                                hiddenIdInput,
                                searchUrl,
                                mapItem,
                                onSelect,
                                extraQuery = "",
                                minChars = 2,
                            }) {
        if (!input) return;

        const wrapper = wrapForDropdown(input);
        if (!wrapper) return;

        const dropdown = document.createElement("div");
        dropdown.className =
            "absolute z-30 mt-1 w-full rounded-xl border border-slate-200 bg-white shadow-lg max-h-60 overflow-auto hidden";
        wrapper.appendChild(dropdown);

        let abortController = null;

        input.addEventListener("input", async () => {
            const term = input.value.trim();
            if (hiddenIdInput) hiddenIdInput.value = "";

            if (term.length < minChars) {
                dropdown.classList.add("hidden");
                dropdown.innerHTML = "";
                return;
            }

            if (abortController) abortController.abort();
            abortController = new AbortController();

            try {
                const res = await fetch(
                    `${searchUrl}?q=${encodeURIComponent(
                        term
                    )}${extraQuery}`,
                    {
                        signal: abortController.signal,
                        headers: { Accept: "application/json" },
                    }
                );
                if (!res.ok) throw new Error("erro buscar");

                const json = await res.json();
                const data = Array.isArray(json) ? json : json.data || [];
                const items = data.map(mapItem).filter(Boolean);

                dropdown.innerHTML = "";
                if (!items.length) {
                    dropdown.classList.add("hidden");
                    return;
                }

                for (const item of items) {
                    const btn = document.createElement("button");
                    btn.type = "button";
                    btn.className =
                        "w-full px-3 py-2 text-left text-sm hover:bg-slate-50 flex justify-between gap-2";
                    btn.innerHTML = `
                        <span class="truncate">${item.label}</span>
                        ${
                        item.sublabel
                            ? `<span class="ml-2 flex-shrink-0 text-xs text-slate-500">${item.sublabel}</span>`
                            : ""
                    }
                    `;
                    btn.addEventListener("click", () => {
                        if (hiddenIdInput) hiddenIdInput.value = item.id || "";
                        input.value = item.label || "";
                        dropdown.classList.add("hidden");
                        dropdown.innerHTML = "";
                        onSelect && onSelect(item);
                    });
                    dropdown.appendChild(btn);
                }

                dropdown.classList.remove("hidden");
            } catch (e) {
                if (e.name !== "AbortError") console.error(e);
            }
        });

        document.addEventListener("click", (ev) => {
            if (!wrapper.contains(ev.target)) dropdown.classList.add("hidden");
        });
    }

    // ========== CLIENTE (search + preencher) ==========
    async function searchCustomers(term) {
        const url = new URL(ROUTES.customerSearch, window.location.origin);
        url.searchParams.set("q", term);

        const resp = await fetch(url.toString(), {
            headers: { Accept: "application/json" },
        });

        if (!resp.ok) {
            console.error("Erro ao buscar clientes:", await resp.text());
            return [];
        }

        const json = await resp.json();
        return json.data || [];
    }

    let lastCustomerResults = [];

    function applyCustomerToForm(c) {
        if (!c) return;

        if (clientNameInput) clientNameInput.value = c.name || "";
        if (clientDocInput) clientDocInput.value = c.cpfCnpj || "";
        if (clientEmailInput) clientEmailInput.value = c.email || "";
        if (clientPhoneInput) clientPhoneInput.value = c.mobilePhone || "";

        if (clientAddressInput) clientAddressInput.value = c.address || "";
        if (clientAddressNumberInput) clientAddressNumberInput.value = c.addressNumber || "";
        if (clientProvinceInput) clientProvinceInput.value = c.province || "";
        if (clientComplementInput) clientComplementInput.value = c.complement || "";

        if (clientCityInput) clientCityInput.value = c.cityName || "";
        if (clientStateInput) clientStateInput.value = c.state || "";
        if (clientZipInput) clientZipInput.value = c.postalCode || "";
    }

    function renderCustomerResults(items) {
        if (!clientResults) return;

        if (!items.length) {
            clientResults.classList.add("hidden");
            clientResults.innerHTML = "";
            return;
        }

        lastCustomerResults = items;
        clientResults.innerHTML = items
            .map(
                (c, idx) => `
            <button type="button"
                    class="block w-full px-3 py-2 text-left hover:bg-slate-50"
                    data-index="${idx}">
                <div class="text-xs font-medium text-slate-900">${c.name}</div>
                <div class="text-[11px] text-slate-500">
                    ${(c.cpfCnpj || "")} ${(c.cityName || "")}/${c.state || ""}
                </div>
            </button>
        `
            )
            .join("");

        clientResults.classList.remove("hidden");

        clientResults
            .querySelectorAll("button[data-index]")
            .forEach((btn) => {
                btn.addEventListener("click", () => {
                    const idx = parseInt(btn.dataset.index, 10);
                    const c = lastCustomerResults[idx];
                    applyCustomerToForm(c);
                    clientResults.classList.add("hidden");
                    clientResults.innerHTML = "";
                    // não seto secondary_customer_id aqui, porque é outra tabela
                });
            });
    }

    if (clientNameInput && clientResults) {
        const handleClientInput = debounce(async () => {
            const term = clientNameInput.value.trim();
            if (term.length < 2) {
                clientResults.classList.add("hidden");
                clientResults.innerHTML = "";
                return;
            }

            const items = await searchCustomers(term);
            renderCustomerResults(items);
        }, 300);

        clientNameInput.addEventListener("input", handleClientInput);

        document.addEventListener("click", (e) => {
            if (
                !clientResults.contains(e.target) &&
                e.target !== clientNameInput
            ) {
                clientResults.classList.add("hidden");
            }
        });
    }

    // ========== TÉCNICO (search + valor hora) ==========
    function setupTechnicianLookup() {
        if (!technicianNameInput) return;

        setupTypeahead({
            input: technicianNameInput,
            hiddenIdInput: technicianIdInput,
            searchUrl: ROUTES.employee,
            extraQuery: "&is_technician=1",
            mapItem: (e) => ({
                id: e.id,
                label: e.full_name,
                sublabel: e.hourly_rate
                    ? `R$ ${formatCurrency(toNumber(e.hourly_rate))}/h`
                    : "",
                hourly_rate: e.hourly_rate,
            }),
            onSelect: (item) => {
                if (laborHourValueInput && item.hourly_rate != null) {
                    laborHourValueInput.value = item.hourly_rate;
                    recalcTotals();
                }
            },
        });
    }

    // ========== BLOCS DINÂMICOS ==========
    let equipmentCounter = 0;
    let serviceCounter = 0;
    let partCounter = 0;
    let laborCounter = 0;

    async function fetchFirstItem(url) {
        try {
            const res = await fetch(url, { headers: { Accept: "application/json" } });
            if (!res.ok) return null;
            const json = await res.json();
            const data = Array.isArray(json) ? json : json.data || [];
            return data[0] || null;
        } catch (e) {
            console.error(e);
            return null;
        }
    }

    function addEquipmentBlock(initial = {}) {
        if (!equipmentListEl) return;

        const wrap = document.createElement("div");
        wrap.className =
            "rounded-2xl bg-slate-50/80 border border-slate-100 p-4";
        wrap.dataset.row = "equipment";
        wrap.dataset.index = String(++equipmentCounter);

        wrap.innerHTML = `
          <div class="flex items-center justify-between mb-3">
            <span class="text-sm font-medium text-slate-700">Equipamento</span>
            <button type="button" class="btn-remove-eq inline-flex items-center gap-1 rounded-2xl border border-red-100 bg-red-50/60 px-2.5 py-1 text-xs text-red-600 hover:bg-red-100">
              <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 7h16" />
                <path d="M10 11v6" />
                <path d="M14 11v6" />
                <path d="M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12" />
                <path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2" />
              </svg>
              <span>Remover</span>
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
              <label class="block text-xs text-slate-600 mb-1">Equipamento / modelo*</label>
              <input class="js-equipment-desc w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm" placeholder="Ex.: Balança 300kg"/>
              <input type="hidden" class="js-equipment-id" />
            </div>
            <div>
              <label class="block text-xs text-slate-600 mb-1">Nº de série</label>
              <input class="js-equipment-serial w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm" placeholder="Série"/>
            </div>
            <div>
              <label class="block text-xs text-slate-600 mb-1">Localização</label>
              <input class="js-equipment-location w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm" placeholder="Ex.: Linha 1 / Setor A"/>
            </div>
            <div class="md:col-span-3">
              <label class="block text-xs text-slate-600 mb-1">Serviço executado</label>
              <textarea class="js-equipment-notes w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm min-h-[60px] resize-none" placeholder="Descreva o serviço executado"></textarea>
            </div>
          </div>
        `;

        const btnRemove = wrap.querySelector(".btn-remove-eq");
        const nameInput = wrap.querySelector(".js-equipment-desc");
        const idInput = wrap.querySelector(".js-equipment-id");
        const serialInput = wrap.querySelector(".js-equipment-serial");
        const locInput = wrap.querySelector(".js-equipment-location");
        const notesInput = wrap.querySelector(".js-equipment-notes");

        btnRemove.addEventListener("click", () => {
            wrap.remove();
        });

        if (initial.equipment_description)
            nameInput.value = initial.equipment_description;
        if (initial.serial_number) serialInput.value = initial.serial_number;
        if (initial.location) locInput.value = initial.location;
        if (initial.notes) notesInput.value = initial.notes;
        if (initial.equipment_id) idInput.value = initial.equipment_id;

        // typeahead por nome do equipamento
        setupTypeahead({
            input: nameInput,
            hiddenIdInput: idInput,
            searchUrl: ROUTES.equipment,
            mapItem: (e) => ({
                id: e.id,
                label: e.name,
                sublabel: e.code || "",
            }),
            onSelect: (e) => {
                if (!serialInput.value && e.serial_number)
                    serialInput.value = e.serial_number;
                if (!notesInput.value && e.description)
                    notesInput.value = e.description;
            },
        });

        // auto preencher por nome / série
        const autoFillEquipment = async (term) => {
            term = term.trim();
            if (!term) return;
            const eq = await fetchFirstItem(
                `${ROUTES.equipment}?q=${encodeURIComponent(term)}`
            );
            if (!eq) return;
            idInput.value = eq.id || "";
            nameInput.value = eq.name || term;
            if (!serialInput.value && eq.serial_number)
                serialInput.value = eq.serial_number;
            if (!notesInput.value && eq.description)
                notesInput.value = eq.description;
        };

        nameInput.addEventListener("blur", () =>
            autoFillEquipment(nameInput.value)
        );
        serialInput.addEventListener("blur", () =>
            autoFillEquipment(serialInput.value)
        );

        equipmentListEl.appendChild(wrap);
    }

    function addServiceRow(initial = {}) {
        if (!serviceListEl) return;

        const row = document.createElement("div");
        row.className = "grid grid-cols-12 gap-2 items-center";
        row.dataset.row = "service";
        row.dataset.index = String(++serviceCounter);

        row.innerHTML = `
          <div class="col-span-2">
            <input type="number" min="0"
              class="js-service-qty w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-right"
              value="${initial.quantity != null ? initial.quantity : 1}">
          </div>
          <div class="col-span-5">
            <input
              class="js-service-desc w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm"
              placeholder="Descrição do serviço"
              value="${initial.description || ""}">
            <input type="hidden" class="js-service-id" value="${
            initial.service_item_id || ""
        }">
          </div>
          <div class="col-span-2">
            <input
              type="number" step="0.01"
              class="js-service-unit w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-right"
              placeholder="0,00"
              value="${initial.unit_price != null ? initial.unit_price : ""}">
          </div>
          <div class="col-span-2 text-right text-sm font-semibold text-slate-800">
            <span class="js-service-total">R$ 0,00</span>
          </div>
          <div class="col-span-1 flex justify-end">
            <button type="button" class="btn-remove-service inline-flex h-8 w-8 items-center justify-center rounded-full text-red-500 hover:bg-red-50">🗑</button>
          </div>
        `;

        const qtyInput = row.querySelector(".js-service-qty");
        const descInput = row.querySelector(".js-service-desc");
        const unitInput = row.querySelector(".js-service-unit");
        const totalSpan = row.querySelector(".js-service-total");
        const idInput = row.querySelector(".js-service-id");
        const btnRemove = row.querySelector(".btn-remove-service");

        const recalcRow = () => {
            const qv = toNumber(qtyInput.value || 0);
            const p = toNumber(unitInput.value || 0);
            const t = qv * p;
            totalSpan.textContent = `R$ ${formatCurrency(t)}`;
            recalcTotals();
        };

        qtyInput.addEventListener("input", recalcRow);
        unitInput.addEventListener("input", recalcRow);

        btnRemove.addEventListener("click", () => {
            row.remove();
            recalcTotals();
        });

        // typeahead serviços
        setupTypeahead({
            input: descInput,
            hiddenIdInput: idInput,
            searchUrl: ROUTES.serviceItem,
            mapItem: (s) => ({
                id: s.id,
                label: s.name,
                sublabel: s.unit_price
                    ? `R$ ${formatCurrency(toNumber(s.unit_price))}`
                    : "",
            }),
            onSelect: (s) => {
                if (!unitInput.value && s.unit_price != null) {
                    unitInput.value = s.unit_price;
                }
                recalcRow();
            },
        });

        const autoFillService = async (term) => {
            term = term.trim();
            if (!term) return;
            const svc = await fetchFirstItem(
                `${ROUTES.serviceItem}?q=${encodeURIComponent(term)}`
            );
            if (!svc) return;
            idInput.value = svc.id || "";
            descInput.value = svc.name || term;
            if (!unitInput.value && svc.unit_price != null) {
                unitInput.value = svc.unit_price;
            }
            recalcRow();
        };

        descInput.addEventListener("blur", () =>
            autoFillService(descInput.value)
        );

        serviceListEl.appendChild(row);
        recalcRow();
    }

    function addPartRow(initial = {}) {
        if (!partListEl) return;

        const row = document.createElement("div");
        row.className = "grid grid-cols-12 gap-2 items-center";
        row.dataset.row = "part";
        row.dataset.index = String(++partCounter);

        row.innerHTML = `
          <div class="col-span-2">
            <input
              class="js-part-code w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm"
              placeholder="Código"
              value="${initial.code || ""}">
          </div>
          <div class="col-span-4">
            <input
              class="js-part-desc w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm"
              placeholder="Descrição"
              value="${initial.description || ""}">
            <input type="hidden" class="js-part-id" value="${
            initial.part_id || ""
        }">
          </div>
          <div class="col-span-1">
            <input
              type="number" min="0"
              class="js-part-qty w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-right"
              value="${initial.quantity != null ? initial.quantity : 1}">
          </div>
          <div class="col-span-2">
            <input
              type="number" step="0.01"
              class="js-part-unit w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-right"
              placeholder="0,00"
              value="${initial.unit_price != null ? initial.unit_price : ""}">
          </div>
          <div class="col-span-2 text-right text-sm font-semibold text-slate-800">
            <span class="js-part-total">R$ 0,00</span>
          </div>
          <div class="col-span-1 flex justify-end">
            <button type="button" class="btn-remove-part inline-flex h-8 w-8 items-center justify-center rounded-full text-red-500 hover:bg-red-50">🗑</button>
          </div>
        `;

        const codeInput = row.querySelector(".js-part-code");
        const descInput = row.querySelector(".js-part-desc");
        const qtyInput = row.querySelector(".js-part-qty");
        const unitInput = row.querySelector(".js-part-unit");
        const totalSpan = row.querySelector(".js-part-total");
        const idInput = row.querySelector(".js-part-id");
        const btnRemove = row.querySelector(".btn-remove-part");

        const recalcRow = () => {
            const qv = toNumber(qtyInput.value || 0);
            const p = toNumber(unitInput.value || 0);
            const t = qv * p;
            totalSpan.textContent = `R$ ${formatCurrency(t)}`;
            recalcTotals();
        };

        qtyInput.addEventListener("input", recalcRow);
        unitInput.addEventListener("input", recalcRow);

        btnRemove.addEventListener("click", () => {
            row.remove();
            recalcTotals();
        });

        setupTypeahead({
            input: descInput,
            hiddenIdInput: idInput,
            searchUrl: ROUTES.part,
            mapItem: (p) => ({
                id: p.id,
                label: p.name,
                sublabel: p.code || "",
            }),
            onSelect: (p) => {
                if (!codeInput.value && p.code) codeInput.value = p.code;
                if (!unitInput.value && p.unit_price != null) {
                    unitInput.value = p.unit_price;
                }
                recalcRow();
            },
        });

        const autoFillPart = async (term) => {
            term = term.trim();
            if (!term) return;
            const part = await fetchFirstItem(
                `${ROUTES.part}?q=${encodeURIComponent(term)}`
            );
            if (!part) return;
            idInput.value = part.id || "";
            descInput.value = part.name || term;
            codeInput.value = part.code || codeInput.value || term;
            if (!unitInput.value && part.unit_price != null) {
                unitInput.value = part.unit_price;
            }
            recalcRow();
        };

        codeInput.addEventListener("blur", () =>
            autoFillPart(codeInput.value)
        );
        descInput.addEventListener("blur", () =>
            autoFillPart(descInput.value)
        );

        partListEl.appendChild(row);
        recalcRow();
    }

    function addLaborRow(initial = {}) {
        if (!laborListEl) return;

        const row = document.createElement("div");
        row.className = "grid grid-cols-12 gap-2 items-center";
        row.dataset.row = "labor";
        row.dataset.index = String(++laborCounter);

        row.innerHTML = `
          <div class="col-span-2">
            <input type="time"
              class="js-labor-start w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm"
              value="${initial.started_at || ""}">
          </div>
          <div class="col-span-2">
            <input type="time"
              class="js-labor-end w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm"
              value="${initial.ended_at || ""}">
          </div>
          <div class="col-span-7">
            <input
              class="js-labor-desc w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm"
              placeholder="Atividade realizada"
              value="${initial.description || ""}">
          </div>
          <div class="col-span-1 flex justify-end">
            <button type="button" class="btn-remove-labor inline-flex h-8 w-8 items-center justify-center rounded-full text-red-500 hover:bg-red-50">🗑</button>
          </div>
        `;

        const startInput = row.querySelector(".js-labor-start");
        const endInput = row.querySelector(".js-labor-end");
        const btnRemove = row.querySelector(".btn-remove-labor");

        const recomputeHours = () => recalcTotals();

        startInput.addEventListener("change", recomputeHours);
        endInput.addEventListener("change", recomputeHours);

        btnRemove.addEventListener("click", () => {
            row.remove();
            recalcTotals();
        });

        laborListEl.appendChild(row);
        recalcTotals();
    }

    // ========== COLLECT ==========
    const collectEquipments = () =>
        Array.from(document.querySelectorAll('[data-row="equipment"]'))
            .map((row) => ({
                equipment_id:
                    row.querySelector(".js-equipment-id")?.value || null,
                equipment_description:
                    row
                        .querySelector(".js-equipment-desc")
                        ?.value?.trim() || "",
                serial_number:
                    row
                        .querySelector(".js-equipment-serial")
                        ?.value?.trim() || "",
                location:
                    row
                        .querySelector(".js-equipment-location")
                        ?.value?.trim() || "",
                notes:
                    row
                        .querySelector(".js-equipment-notes")
                        ?.value?.trim() || "",
            }))
            .filter(
                (e) =>
                    e.equipment_id ||
                    e.equipment_description ||
                    e.serial_number ||
                    e.location ||
                    e.notes
            );

    const collectServices = () =>
        Array.from(document.querySelectorAll('[data-row="service"]'))
            .map((row) => {
                const qty = toNumber(
                    row.querySelector(".js-service-qty")?.value || 0
                );
                const unit = toNumber(
                    row.querySelector(".js-service-unit")?.value || 0
                );
                const total = qty * unit;
                return {
                    service_item_id:
                        row.querySelector(".js-service-id")?.value || null,
                    description:
                        row
                            .querySelector(".js-service-desc")
                            ?.value?.trim() || "",
                    quantity: qty,
                    unit_price: unit,
                    total,
                };
            })
            .filter(
                (s) =>
                    s.description ||
                    s.quantity ||
                    s.unit_price ||
                    s.service_item_id
            );

    const collectParts = () =>
        Array.from(document.querySelectorAll('[data-row="part"]'))
            .map((row) => {
                const qty = toNumber(
                    row.querySelector(".js-part-qty")?.value || 0
                );
                const unit = toNumber(
                    row.querySelector(".js-part-unit")?.value || 0
                );
                const total = qty * unit;
                return {
                    part_id: row.querySelector(".js-part-id")?.value || null,
                    code:
                        row
                            .querySelector(".js-part-code")
                            ?.value?.trim() || "",
                    description:
                        row
                            .querySelector(".js-part-desc")
                            ?.value?.trim() || "",
                    quantity: qty,
                    unit_price: unit,
                    total,
                };
            })
            .filter(
                (p) =>
                    p.description ||
                    p.code ||
                    p.quantity ||
                    p.unit_price ||
                    p.part_id
            );

    const collectLaborEntries = () =>
        Array.from(document.querySelectorAll('[data-row="labor"]'))
            .map((row) => {
                const start =
                    row.querySelector(".js-labor-start")?.value || "";
                const end = row.querySelector(".js-labor-end")?.value || "";
                const desc =
                    row.querySelector(".js-labor-desc")?.value?.trim() || "";

                let hours = 0;
                if (start && end) {
                    const [sh, sm] = start.split(":").map(Number);
                    const [eh, em] = end.split(":").map(Number);
                    const startMinutes = sh * 60 + sm;
                    const endMinutes = eh * 60 + em;
                    if (endMinutes > startMinutes) {
                        hours = (endMinutes - startMinutes) / 60;
                    }
                }

                return {
                    employee_id: technicianIdInput?.value || null,
                    started_at: start
                        ? `${orderDateInput?.value || ""} ${start}:00`
                        : null,
                    ended_at: end
                        ? `${orderDateInput?.value || ""} ${end}:00`
                        : null,
                    hours,
                    description: desc,
                };
            })
            .filter((l) => l.started_at || l.ended_at || l.description);

    // ========== TOTALIZAÇÃO ==========
    function recalcTotals() {
        const services = collectServices();
        const parts = collectParts();
        const labor = collectLaborEntries();

        const servicesSubtotal = services.reduce(
            (sum, s) => sum + (s.total || 0),
            0
        );
        const partsSubtotal = parts.reduce(
            (sum, p) => sum + (p.total || 0),
            0
        );

        const totalHours = labor.reduce((sum, l) => sum + (l.hours || 0), 0);
        const hourValue = toNumber(laborHourValueInput?.value || 0);
        const laborTotal = totalHours * hourValue;

        const discount = toNumber(discountInput?.value || 0);
        const addition = toNumber(additionInput?.value || 0);

        const grand =
            servicesSubtotal + partsSubtotal + laborTotal - discount + addition;

        if (servicesSubtotalDisplay)
            servicesSubtotalDisplay.textContent = `R$ ${formatCurrency(
                servicesSubtotal
            )}`;
        if (partsSubtotalDisplay)
            partsSubtotalDisplay.textContent = `R$ ${formatCurrency(
                partsSubtotal
            )}`;
        if (laborTotalAmountDisplay)
            laborTotalAmountDisplay.textContent = `R$ ${formatCurrency(
                laborTotal
            )}`;

        if (boxServicesValue)
            boxServicesValue.textContent = `R$ ${formatCurrency(
                servicesSubtotal
            )}`;
        if (boxPartsValue)
            boxPartsValue.textContent = `R$ ${formatCurrency(partsSubtotal)}`;
        if (boxLaborValue)
            boxLaborValue.textContent = `R$ ${formatCurrency(laborTotal)}`;
        if (grandTotalDisplay)
            grandTotalDisplay.textContent = `R$ ${formatCurrency(grand)}`;

        if (footerServicesValue)
            footerServicesValue.textContent = `R$ ${formatCurrency(
                servicesSubtotal
            )}`;
        if (footerPartsValue)
            footerPartsValue.textContent = `R$ ${formatCurrency(
                partsSubtotal
            )}`;
        if (footerLaborValue)
            footerLaborValue.textContent = `R$ ${formatCurrency(laborTotal)}`;
        if (footerGrandValue)
            footerGrandValue.textContent = `R$ ${formatCurrency(grand)}`;
    }

    if (discountInput) discountInput.addEventListener("input", recalcTotals);
    if (additionInput) additionInput.addEventListener("input", recalcTotals);
    if (laborHourValueInput)
        laborHourValueInput.addEventListener("input", recalcTotals);

    // ========== PAYLOAD OS ==========
    async function buildPayload(status) {
        const equipments = collectEquipments();
        const serviceItems = collectServices();
        const partItems = collectParts();
        const laborEntries = collectLaborEntries();

        const servicesSubtotal = serviceItems.reduce(
            (sum, s) => sum + (s.total || 0),
            0
        );
        const partsSubtotal = partItems.reduce(
            (sum, p) => sum + (p.total || 0),
            0
        );
        const totalHours = laborEntries.reduce(
            (sum, l) => sum + (l.hours || 0),
            0
        );
        const laborRate = toNumber(laborHourValueInput?.value || 0);
        const laborTotal = totalHours * laborRate;

        const discount = toNumber(discountInput?.value || 0);
        const addition = toNumber(additionInput?.value || 0);
        const grand =
            servicesSubtotal + partsSubtotal + laborTotal - discount + addition;

        return {
            id: orderIdInput?.value || null,
            status: status || "draft",

            order_date: orderDateInput?.value || null,

            technician_id: technicianIdInput?.value || null,
            opened_by_employee_id: technicianIdInput?.value || null,

            secondary_customer_id: customerIdInput?.value || null,

            requester_name: requesterNameInput?.value || null,
            requester_email: null,
            requester_phone: clientPhoneInput?.value || null,
            ticket_number: ticketNumberInput?.value || null,

            address: clientAddressInput?.value || null,
            addressNumber: clientAddressNumberInput?.value || null,
            complement: clientComplementInput?.value || null,
            province: clientProvinceInput?.value || null,
            city: clientCityInput?.value || null,
            state: clientStateInput?.value || null,
            zip_code: clientZipInput?.value || null,

            labor_hour_value: laborRate,
            labor_total_hours: totalHours,
            labor_total_amount: laborTotal,

            payment_condition: paymentConditionSel?.value || null,
            notes: paymentNotesInput?.value || null,

            services_subtotal: servicesSubtotal,
            parts_subtotal: partsSubtotal,
            discount_amount: discount,
            addition_amount: addition,
            grand_total: grand,

            equipments,
            service_items: serviceItems,
            part_items: partItems,
            labor_entries: laborEntries,
        };
    }

    async function submitServiceOrder(status) {
        if (state.saving) return null;
        state.saving = true;

        try {
            const payload = await buildPayload(status);
            const id = payload.id;
            const isUpdate = !!id;

            const url = isUpdate
                ? `${ROUTES.serviceOrder}/${id}`
                : ROUTES.serviceOrder;
            const method = isUpdate ? "PUT" : "POST";

            const res = await fetch(url, {
                method,
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrf,
                    Accept: "application/json",
                },
                body: JSON.stringify(payload),
            });

            if (!res.ok) {
                console.error("Falha ao salvar OS", await res.text());
                alert("Erro ao salvar OS.");
                return null;
            }

            const json = await res.json();
            const data = json.data || json;

            if (data.id && orderIdInput && !orderIdInput.value) {
                orderIdInput.value = data.id;
            }
            if (data.order_number && orderNumberDisplay) {
                orderNumberDisplay.value = data.order_number;
            }

            console.log("OS salva:", json);
            return { payload, data };
        } catch (e) {
            console.error(e);
            alert("Erro inesperado ao salvar OS.");
            return null;
        } finally {
            state.saving = false;
            recalcTotals();
        }
    }

    // ========== SALVAR CADASTROS AUXILIARES ==========
    async function saveCatalogsFromOs(payload, opts) {
        const tasks = [];

        // Cliente (secondary_customer)
        if (opts.saveCustomer && !payload.secondary_customer_id) {
            if (clientNameInput && clientNameInput.value.trim()) {
                tasks.push(
                    postJson(ROUTES.customer, {
                        name: clientNameInput.value.trim(),
                        cpfCnpj: clientDocInput?.value || null,
                        email: clientEmailInput?.value || null,
                        mobilePhone: clientPhoneInput?.value || null,
                        address: clientAddressInput?.value || null,
                        addressNumber: clientAddressNumberInput?.value || null,
                        postalCode: clientZipInput?.value || null,
                        cityName: clientCityInput?.value || null,
                        state: clientStateInput?.value || null,
                        province: clientProvinceInput?.value || null,
                        complement: clientComplementInput?.value || null,
                    })
                );
            }
        }

        // Técnico (Employee)
        if (opts.saveTechnician) {
            if (!payload.technician_id && technicianNameInput?.value.trim()) {
                tasks.push(
                    postJson(ROUTES.employee, {
                        full_name: technicianNameInput.value.trim(),
                        email: null,
                        phone: clientPhoneInput?.value || null,
                        document_number: null,
                        position: "Técnico",
                        hourly_rate: payload.labor_hour_value || 0,
                        is_technician: true,
                        is_active: true,
                    })
                );
            }
        }

        // Serviços
        if (opts.saveServices && Array.isArray(payload.service_items)) {
            payload.service_items.forEach((srv) => {
                if (!srv.service_item_id && srv.description) {
                    tasks.push(
                        postJson(ROUTES.serviceItem, {
                            name: srv.description,
                            description: srv.description,
                            unit_price: srv.unit_price || 0,
                            service_type_id: null,
                            is_active: true,
                        })
                    );
                }
            });
        }

        // Peças
        if (opts.saveParts && Array.isArray(payload.part_items)) {
            payload.part_items.forEach((p) => {
                if (!p.part_id && (p.code || p.description)) {
                    tasks.push(
                        postJson(ROUTES.part, {
                            code: p.code || null,
                            name: p.description || p.code || "Peça",
                            description: p.description || null,
                            ncm_code: null,
                            unit_price: p.unit_price || 0,
                            supplier_id: null,
                            is_active: true,
                        })
                    );
                }
            });
        }

        // Equipamentos
        if (opts.saveEquipments && Array.isArray(payload.equipments)) {
            payload.equipments.forEach((e) => {
                if (!e.equipment_id && (e.equipment_description || e.serial_number)) {
                    tasks.push(
                        postJson(ROUTES.equipment, {
                            code: e.serial_number || null,
                            name: e.equipment_description || "Equipamento",
                            description: e.notes || null,
                            serial_number: e.serial_number || null,
                            notes: e.location || null,
                        })
                    );
                }
            });
        }

        if (!tasks.length) return;

        try {
            await Promise.all(tasks);
            console.log("Cadastros auxiliares salvos.");
        } catch (e) {
            console.error(e);
            alert(
                "Alguns cadastros auxiliares não puderam ser salvos. Veja o console."
            );
        }
    }

    // ========== MODAIS: SALVAR / FINALIZAR ==========
    if (btnSave && saveModal) {
        btnSave.addEventListener("click", (e) => {
            e.preventDefault();
            saveModal.classList.remove("hidden");
            saveModal.classList.add("flex");
        });

        document
            .querySelectorAll("[data-os-save-cancel]")
            .forEach((btn) => {
                btn.addEventListener("click", () => {
                    saveModal.classList.add("hidden");
                    saveModal.classList.remove("flex");
                });
            });

        const confirmBtn = q("#os-save-confirm");

        if (confirmBtn) {
            confirmBtn.addEventListener("click", async () => {
                const opts = {
                    saveCustomer: q("#save_customer")?.checked || false,
                    saveTechnician: q("#save_technician")?.checked || false,
                    saveServices: q("#save_services")?.checked || false,
                    saveParts: q("#save_parts")?.checked || false,
                    saveEquipments: q("#save_equipments")?.checked || false,
                };

                const result = await submitServiceOrder("draft");
                if (!result) return;

                await saveCatalogsFromOs(result.payload, opts);

                saveModal.classList.add("hidden");
                saveModal.classList.remove("flex");

                window.location.href = "/service-orders/service-order";
            });
        }
    }

    if (btnFinish && finalizeModal) {
        btnFinish.addEventListener("click", (e) => {
            e.preventDefault();

            const hasEmail =
                clientEmailInput && clientEmailInput.value.trim() !== "";
            const emailBtn = q("#os-finalize-email");
            if (emailBtn) emailBtn.disabled = !hasEmail;

            finalizeModal.classList.remove("hidden");
            finalizeModal.classList.add("flex");
        });

        document
            .querySelectorAll("[data-os-finalize-cancel]")
            .forEach((btn) => {
                btn.addEventListener("click", () => {
                    finalizeModal.classList.add("hidden");
                    finalizeModal.classList.remove("flex");
                });
            });

        async function handleFinalize(action) {
            const opts = {
                saveCustomer: q("#final_save_customer")?.checked || false,
                saveTechnician: q("#final_save_technician")?.checked || false,
                saveServices: q("#final_save_services")?.checked || false,
                saveParts: q("#final_save_parts")?.checked || false,
                saveEquipments: q("#final_save_equipments")?.checked || false,
            };

            // ao finalizar, deixo status "pending" por enquanto
            const result = await submitServiceOrder("pending");
            if (!result) return;

            await saveCatalogsFromOs(result.payload, opts);

            if (action === "tablet") {
                finalizeModal.classList.add("hidden");
                finalizeModal.classList.remove("flex");
                openSignatureModal();
            } else if (action === "email") {
                finalizeModal.classList.add("hidden");
                finalizeModal.classList.remove("flex");
                alert(
                    "OS enviada para fluxo de assinatura por e-mail (integração depois)."
                );
            } else if (action === "new") {
                finalizeModal.classList.add("hidden");
                finalizeModal.classList.remove("flex");
                window.location.href = "/service-orders/create";
            }
        }

        const btnEmail = q("#os-finalize-email");
        const btnTablet = q("#os-finalize-tablet");
        const btnNew = q("#os-finalize-new");

        if (btnEmail)
            btnEmail.addEventListener("click", () => handleFinalize("email"));
        if (btnTablet)
            btnTablet.addEventListener("click", () => handleFinalize("tablet"));
        if (btnNew)
            btnNew.addEventListener("click", () => handleFinalize("new"));
    }

    // ========== ASSINATURA ==========
    // ====== ASSINATURA ======
    const signatureModal  = q('#os-signature-modal');
    const signatureCanvas = q('#signature-pad');
    const signatureClear  = q('#signature-clear');
    const signatureClose  = q('#signature-close');
    const signatureSave   = q('#signature-save');

    let signatureCtx;
    let isDrawing         = false;
    let lastX             = 0;
    let lastY             = 0;
    let signatureInitDone = false;

    function openSignatureModal() {
        if (!signatureModal) return;

        initSignaturePad();

        signatureModal.classList.remove('hidden');
        signatureModal.classList.add('flex');

        // ajusta o canvas depois que o modal aparece
        setTimeout(() => {
            if (signatureCanvas && signatureCanvas._resizeCanvas) {
                signatureCanvas._resizeCanvas();
            }
        }, 10);
    }

    function closeSignatureModal() {
        if (!signatureModal) return;
        signatureModal.classList.add('hidden');
        signatureModal.classList.remove('flex');
    }

    if (signatureClose) {
        signatureClose.addEventListener('click', closeSignatureModal);
    }

    if (signatureClear) {
        signatureClear.addEventListener('click', () => {
            if (!signatureCtx || !signatureCanvas) return;
            signatureCtx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
        });
    }

    if (signatureSave) {
        signatureSave.addEventListener('click', async () => {
            if (!signatureCanvas || !signatureCtx) return;

            const serviceOrderId = orderIdInput?.value || null;
            if (!serviceOrderId) {
                alert('Salve a OS antes de registrar a assinatura.');
                return;
            }

            const dataUrl = signatureCanvas.toDataURL('image/png');

            const clientNameEl  = document.querySelector('#os_client_name');
            const clientEmailEl = document.querySelector('#os_client_email');

            const body = {
                image_base64: dataUrl,
                client_name:  clientNameEl?.value || null,
                client_email: clientEmailEl?.value || null,
                technician_id: technicianIdInput?.value || null,
            };

            try {
                // 1) salva a imagem da assinatura
                const resp = await fetch(`/service-orders/${serviceOrderId}/client-signature`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(body),
                });

                if (!resp.ok) {
                    console.error('Erro ao salvar assinatura:', await resp.text());
                    alert('Erro ao salvar assinatura do cliente.');
                    return;
                }

                const json = await resp.json();
                console.log('Assinatura salva:', json);

                // 2) após a assinatura, marcar OS como APROVADA
                const approveResult = await submitServiceOrder("approved");
                if (!approveResult) {
                    alert('Assinatura salva, mas houve erro ao aprovar a OS. Verifique na listagem de OS.');
                    return;
                }

                alert('Assinatura salva e OS aprovada com sucesso.');
                closeSignatureModal();
            } catch (e) {
                console.error(e);
                alert('Erro inesperado ao salvar assinatura.');
            }
        });
    }

    function initSignaturePad() {
        if (!signatureCanvas || signatureInitDone) return;

        signatureCtx = signatureCanvas.getContext('2d');
        signatureInitDone = true;

        const resizeCanvas = () => {
            const rect = signatureCanvas.getBoundingClientRect();
            if (rect.width === 0 || rect.height === 0) return;

            signatureCanvas.width  = rect.width;
            signatureCanvas.height = rect.height;
            signatureCtx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
        };

        signatureCanvas._resizeCanvas = resizeCanvas;

        const getPos = (evt) => {
            const rect = signatureCanvas.getBoundingClientRect();
            let x, y;

            if (evt.touches && evt.touches.length) {
                x = evt.touches[0].clientX - rect.left;
                y = evt.touches[0].clientY - rect.top;
            } else {
                x = evt.clientX - rect.left;
                y = evt.clientY - rect.top;
            }
            return { x, y };
        };

        const startDraw = (evt) => {
            evt.preventDefault?.();
            isDrawing = true;
            const { x, y } = getPos(evt);
            lastX = x;
            lastY = y;
        };

        const draw = (evt) => {
            if (!isDrawing) return;
            evt.preventDefault?.();
            const { x, y } = getPos(evt);

            signatureCtx.lineWidth = 2;
            signatureCtx.lineCap   = 'round';
            signatureCtx.strokeStyle = '#111827';
            signatureCtx.beginPath();
            signatureCtx.moveTo(lastX, lastY);
            signatureCtx.lineTo(x, y);
            signatureCtx.stroke();

            lastX = x;
            lastY = y;
        };

        const stopDraw = (evt) => {
            evt?.preventDefault?.();
            isDrawing = false;
        };

        // mouse
        signatureCanvas.addEventListener('mousedown', startDraw);
        signatureCanvas.addEventListener('mousemove', draw);
        signatureCanvas.addEventListener('mouseup', stopDraw);
        signatureCanvas.addEventListener('mouseleave', stopDraw);

        // touch
        signatureCanvas.addEventListener('touchstart', startDraw, { passive: false });
        signatureCanvas.addEventListener('touchmove', draw, { passive: false });
        signatureCanvas.addEventListener('touchend', stopDraw, { passive: false });
        signatureCanvas.addEventListener('touchcancel', stopDraw, { passive: false });

        window.addEventListener('resize', () => {
            if (signatureCanvas._resizeCanvas) signatureCanvas._resizeCanvas();
        });
    }

    // ========== INIT ==========
    setupTechnicianLookup();

    if (equipmentListEl && !equipmentListEl.children.length) addEquipmentBlock();
    if (serviceListEl && !serviceListEl.children.length) addServiceRow();
    if (partListEl && !partListEl.children.length) addPartRow();
    if (laborListEl && !laborListEl.children.length) addLaborRow();

    if (btnAddEquipment)
        btnAddEquipment.addEventListener("click", () => addEquipmentBlock());
    if (btnAddService)
        btnAddService.addEventListener("click", () => addServiceRow());
    if (btnAddPart)
        btnAddPart.addEventListener("click", () => addPartRow());
    if (btnAddLabor)
        btnAddLabor.addEventListener("click", () => addLaborRow());

    recalcTotals();
});
