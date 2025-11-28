// assets/js/template/views/service-orders/service-order-form.js

document.addEventListener("DOMContentLoaded", () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || "";

    const q = (sel) => document.querySelector(sel);

    const toNumber = (v) => {
        if (v === null || v === undefined) return 0;
        let s = String(v).trim();
        if (!s) return 0;

        // trata formato pt-BR
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

    // ================== ELEMENTOS PRINCIPAIS ==================

    const orderIdInput        = q("#service_order_id");
    const orderNumberDisplay  = q("#order_number_display");
    const orderDateInput      = q("#order_date");

    const requesterNameInput  = q("#requester_name");
    const technicianNameInput = q("#service_responsible");
    const technicianIdInput   = q("#technician_id");

    // cliente (prefixo os_)
    const clientIdInput      = q("#os_customer_id");
    const clientNameInput    = q("#os_client_name");
    const clientDocInput     = q("#os_client_document");
    const clientEmailInput   = q("#os_client_email");
    const clientPhoneInput   = q("#os_client_phone");
    const ticketNumberInput  = q("#ticket_number");
    const clientAddressInput = q("#os_client_address");
    const clientCityInput    = q("#os_client_city");
    const clientStateInput   = q("#os_client_state");
    const clientZipInput     = q("#os_client_zip");

    // atendimento / pagamento
    const laborHourValueInput = q("#labor_hour_value");
    const paymentConditionSel = q("#payment_condition");
    const paymentNotesInput   = q("#payment_notes");
    const discountInput       = q("#discount");
    const additionInput       = q("#addition");

    // listas de itens
    const equipmentListEl = q("#equipment-list");
    const serviceListEl   = q("#service-list");
    const partListEl      = q("#part-list");
    const laborListEl     = q("#labor-list");

    // displays de totais
    const servicesSubtotalDisplay = q("#services-subtotal-display");
    const partsSubtotalDisplay    = q("#parts-subtotal-display");
    const laborTotalAmountDisplay = q("#labor-total-amount-display");

    const boxServicesValue = q("#box-services-value");
    const boxPartsValue    = q("#box-parts-value");
    const boxLaborValue    = q("#box-labor-value");
    const grandTotalDisplay = q("#grand_total_display");

    const footerServicesValue = q("#footer-services-value");
    const footerPartsValue    = q("#footer-parts-value");
    const footerLaborValue    = q("#footer-labor-value");
    const footerGrandValue    = q("#footer-grand-value");

    // botões
    const btnAddEquipment = q("#btn-add-equipment");
    const btnAddService   = q("#btn-add-service");
    const btnAddPart      = q("#btn-add-part");
    const btnAddLabor     = q("#btn-add-labor");
    const btnSave         = q("#btn-save-os");
    const btnFinish       = q("#btn-finish-os");

    // modais
    const saveModal     = q("#os-save-modal");
    const finalizeModal = q("#os-finalize-modal");
    const signModal     = q("#os-signature-modal");

    const state = { saving: false };

    // ================== HELPERS DROPDOWN / TYPEAHEAD ==================

    function wrapForDropdown(input) {
        if (!input) return null;
        if (input.parentElement && input.parentElement.classList.contains("relative")) {
            return input.parentElement;
        }
        const parent  = input.parentNode;
        const wrapper = document.createElement("div");
        wrapper.className = "relative";
        parent.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        return wrapper;
    }

    async function fetchFirstItem(url) {
        try {
            const res = await fetch(url, { headers: { Accept: "application/json" } });
            if (!res.ok) return null;
            const json = await res.json();
            const data = Array.isArray(json) ? json : (json.data || []);
            return data[0] || null;
        } catch (e) {
            console.error(e);
            return null;
        }
    }

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
                    `${searchUrl}?q=${encodeURIComponent(term)}${extraQuery}`,
                    {
                        signal: abortController.signal,
                        headers: { Accept: "application/json" },
                    }
                );
                if (!res.ok) throw new Error("erro ao buscar");

                const json = await res.json();
                const data = Array.isArray(json) ? json : (json.data || []);
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

    // ================== LOOKUP CLIENTE ==================

    function setupCustomerLookup() {
        const clientInput   = q("#os_client_name");
        const clientResults = q("#os_client_results");
        const clientHidden  = q("#os_customer_id");

        if (!clientInput || !clientResults) return;

        let lastCustomerResults = [];

        async function searchCustomers(term) {
            const url = new URL("/entities/customer-api", window.location.origin);
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

        function applyCustomerToForm(c) {
            if (!c) return;

            if (clientHidden) clientHidden.value = c.id || "";

            if (clientNameInput) clientNameInput.value = c.name || "";
            if (clientDocInput) clientDocInput.value = c.cpfCnpj || "";
            if (clientPhoneInput) clientPhoneInput.value = c.mobilePhone || "";
            if (clientEmailInput) clientEmailInput.value = c.email || "";

            if (clientAddressInput) {
                const parts = [];
                if (c.address)       parts.push(c.address);
                if (c.addressNumber) parts.push(c.addressNumber);
                if (c.province)      parts.push(c.province);
                if (c.complement)    parts.push(c.complement);
                clientAddressInput.value = parts.join(", ");
            }

            if (clientCityInput)  clientCityInput.value  = c.cityName || "";
            if (clientStateInput) clientStateInput.value = c.state || "";
            if (clientZipInput)   clientZipInput.value   = c.postalCode || "";
        }

        function renderCustomerResults(items) {
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

            clientResults.querySelectorAll("button[data-index]").forEach((btn) => {
                btn.addEventListener("click", () => {
                    const idx = parseInt(btn.dataset.index, 10);
                    const c   = lastCustomerResults[idx];
                    applyCustomerToForm(c);
                    clientResults.classList.add("hidden");
                    clientResults.innerHTML = "";
                });
            });
        }

        const handleClientInput = debounce(async () => {
            const term = clientInput.value.trim();
            if (term.length < 2) {
                clientResults.classList.add("hidden");
                clientResults.innerHTML = "";
                return;
            }

            const items = await searchCustomers(term);
            renderCustomerResults(items);
        }, 300);

        clientInput.addEventListener("input", handleClientInput);

        document.addEventListener("click", (e) => {
            if (!clientResults.contains(e.target) && e.target !== clientInput) {
                clientResults.classList.add("hidden");
            }
        });
    }
    // ================== LOOKUP TÉCNICO ==================

    function setupTechnicianLookup() {
        if (!technicianNameInput) return;

        setupTypeahead({
            input: technicianNameInput,
            hiddenIdInput: technicianIdInput,
            searchUrl: "/human-resources/employee-api",
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
    // ================== BLOCOS DINÂMICOS ==================

    let equipmentCounter = 0;
    let serviceCounter   = 0;
    let partCounter      = 0;
    let laborCounter     = 0;

    function addEquipmentBlock(initial = {}) {
        if (!equipmentListEl) return;

        const wrap = document.createElement("div");
        wrap.className =
            "rounded-2xl bg-slate-50/80 border border-slate-100 p-4";
        wrap.dataset.row   = "equipment";
        wrap.dataset.index = String(++equipmentCounter);

        wrap.innerHTML = `
          <div class="flex items-center justify-between mb-3">
            <span class="text-sm font-medium text-slate-700">Equipamento</span>
            <button type="button"
                    class="btn-remove-eq inline-flex items-center gap-1 rounded-2xl border border-red-100 bg-red-50/60 px-2.5 py-1 text-xs text-red-600 hover:bg-red-100">
              🗑 <span>Remover</span>
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

        const btnRemove   = wrap.querySelector(".btn-remove-eq");
        const nameInput   = wrap.querySelector(".js-equipment-desc");
        const idInput     = wrap.querySelector(".js-equipment-id");
        const serialInput = wrap.querySelector(".js-equipment-serial");
        const locInput    = wrap.querySelector(".js-equipment-location");
        const notesInput  = wrap.querySelector(".js-equipment-notes");

        btnRemove.addEventListener("click", () => {
            wrap.remove();
        });

        if (initial.equipment_description) nameInput.value = initial.equipment_description;
        if (initial.serial_number)        serialInput.value = initial.serial_number;
        if (initial.location)             locInput.value = initial.location;
        if (initial.notes)                notesInput.value = initial.notes;
        if (initial.equipment_id)         idInput.value = initial.equipment_id;

        // typeahead por equipamento
        setupTypeahead({
            input: nameInput,
            hiddenIdInput: idInput,
            searchUrl: "/catalogs/equipment-api",
            mapItem: (e) => ({
                id: e.id,
                label: e.name,
                sublabel: e.code || "",
            }),
            onSelect: (e) => {
                if (!serialInput.value && e.serial_number) serialInput.value = e.serial_number;
                if (!notesInput.value && e.description)    notesInput.value = e.description;
            },
        });

        // auto fill por nome / série
        const autoFillEquipment = async (term) => {
            term = term.trim();
            if (!term) return;
            const eq = await fetchFirstItem(
                `/catalogs/equipment-api?q=${encodeURIComponent(term)}`
            );
            if (!eq) return;
            idInput.value    = eq.id || "";
            nameInput.value  = eq.name || term;
            if (!serialInput.value && eq.serial_number) serialInput.value = eq.serial_number;
            if (!notesInput.value && eq.description)    notesInput.value = eq.description;
        };

        nameInput.addEventListener("blur", () => autoFillEquipment(nameInput.value));
        serialInput.addEventListener("blur", () => autoFillEquipment(serialInput.value));

        equipmentListEl.appendChild(wrap);
    }

    function addServiceRow(initial = {}) {
        if (!serviceListEl) return;

        const row = document.createElement("div");
        row.className = "grid grid-cols-12 gap-2 items-center";
        row.dataset.row   = "service";
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
            <input type="hidden" class="js-service-id" value="${initial.service_item_id || ""}">
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
            <button type="button"
                    class="btn-remove-service inline-flex h-8 w-8 items-center justify-center rounded-full text-red-500 hover:bg-red-50">
              🗑
            </button>
          </div>
        `;

        const qtyInput  = row.querySelector(".js-service-qty");
        const descInput = row.querySelector(".js-service-desc");
        const unitInput = row.querySelector(".js-service-unit");
        const totalSpan = row.querySelector(".js-service-total");
        const idInput   = row.querySelector(".js-service-id");
        const btnRemove = row.querySelector(".btn-remove-service");

        const recalcRow = () => {
            const qv = toNumber(qtyInput.value || 0);
            const p  = toNumber(unitInput.value || 0);
            const t  = qv * p;
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
            searchUrl: "/catalogs/service-item-api",
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

        // auto fill pela descrição
        const autoFillService = async (term) => {
            term = term.trim();
            if (!term) return;
            const svc = await fetchFirstItem(
                `/catalogs/service-item-api?q=${encodeURIComponent(term)}`
            );
            if (!svc) return;
            idInput.value   = svc.id || "";
            descInput.value = svc.name || term;
            if (!unitInput.value && svc.unit_price != null) {
                unitInput.value = svc.unit_price;
            }
            recalcRow();
        };

        descInput.addEventListener("blur", () => autoFillService(descInput.value));

        serviceListEl.appendChild(row);
        recalcRow();
    }

    function addPartRow(initial = {}) {
        if (!partListEl) return;

        const row = document.createElement("div");
        row.className = "grid grid-cols-12 gap-2 items-center";
        row.dataset.row   = "part";
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
            <input type="hidden" class="js-part-id" value="${initial.part_id || ""}">
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
            <button type="button"
                    class="btn-remove-part inline-flex h-8 w-8 items-center justify-center rounded-full text-red-500 hover:bg-red-50">
              🗑
            </button>
          </div>
        `;

        const codeInput = row.querySelector(".js-part-code");
        const descInput = row.querySelector(".js-part-desc");
        const qtyInput  = row.querySelector(".js-part-qty");
        const unitInput = row.querySelector(".js-part-unit");
        const totalSpan = row.querySelector(".js-part-total");
        const idInput   = row.querySelector(".js-part-id");
        const btnRemove = row.querySelector(".btn-remove-part");

        const recalcRow = () => {
            const qv = toNumber(qtyInput.value || 0);
            const p  = toNumber(unitInput.value || 0);
            const t  = qv * p;
            totalSpan.textContent = `R$ ${formatCurrency(t)}`;
            recalcTotals();
        };

        qtyInput.addEventListener("input", recalcRow);
        unitInput.addEventListener("input", recalcRow);

        btnRemove.addEventListener("click", () => {
            row.remove();
            recalcTotals();
        });

        // typeahead por nome
        setupTypeahead({
            input: descInput,
            hiddenIdInput: idInput,
            searchUrl: "/catalogs/part-api",
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

        // auto fill por código/descrição
        const autoFillPart = async (term) => {
            term = term.trim();
            if (!term) return;
            const part = await fetchFirstItem(
                `/catalogs/part-api?q=${encodeURIComponent(term)}`
            );
            if (!part) return;
            idInput.value   = part.id || "";
            descInput.value = part.name || term;
            if (!codeInput.value && part.code) codeInput.value = part.code;
            if (!unitInput.value && part.unit_price != null) {
                unitInput.value = part.unit_price;
            }
            recalcRow();
        };

        codeInput.addEventListener("blur", () => autoFillPart(codeInput.value));
        descInput.addEventListener("blur", () => autoFillPart(descInput.value));

        partListEl.appendChild(row);
        recalcRow();
    }

    function addLaborRow(initial = {}) {
        if (!laborListEl) return;

        const row = document.createElement("div");
        row.className = "grid grid-cols-12 gap-2 items-center";
        row.dataset.row   = "labor";
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
            <button type="button"
                    class="btn-remove-labor inline-flex h-8 w-8 items-center justify-center rounded-full text-red-500 hover:bg-red-50">
              🗑
            </button>
          </div>
        `;

        const startInput = row.querySelector(".js-labor-start");
        const endInput   = row.querySelector(".js-labor-end");
        const btnRemove  = row.querySelector(".btn-remove-labor");

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

    // ================== COLLECT ==================

    const collectEquipments = () =>
        Array.from(document.querySelectorAll('[data-row="equipment"]'))
            .map((row) => ({
                equipment_id:          row.querySelector(".js-equipment-id")?.value || null,
                equipment_description: row.querySelector(".js-equipment-desc")?.value?.trim() || "",
                serial_number:         row.querySelector(".js-equipment-serial")?.value?.trim() || "",
                location:              row.querySelector(".js-equipment-location")?.value?.trim() || "",
                notes:                 row.querySelector(".js-equipment-notes")?.value?.trim() || "",
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
                const qty   = toNumber(row.querySelector(".js-service-qty")?.value || 0);
                const unit  = toNumber(row.querySelector(".js-service-unit")?.value || 0);
                const total = qty * unit;
                return {
                    service_item_id: row.querySelector(".js-service-id")?.value || null,
                    description:     row.querySelector(".js-service-desc")?.value?.trim() || "",
                    quantity:        qty,
                    unit_price:      unit,
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
                const qty   = toNumber(row.querySelector(".js-part-qty")?.value || 0);
                const unit  = toNumber(row.querySelector(".js-part-unit")?.value || 0);
                const total = qty * unit;
                return {
                    part_id:     row.querySelector(".js-part-id")?.value || null,
                    code:        row.querySelector(".js-part-code")?.value?.trim() || "",
                    description: row.querySelector(".js-part-desc")?.value?.trim() || "",
                    quantity:    qty,
                    unit_price:  unit,
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
                const start = row.querySelector(".js-labor-start")?.value || "";
                const end   = row.querySelector(".js-labor-end")?.value || "";
                const desc  = row.querySelector(".js-labor-desc")?.value?.trim() || "";

                let hours = 0;
                if (start && end) {
                    const [sh, sm] = start.split(":").map(Number);
                    const [eh, em] = end.split(":").map(Number);
                    const startMinutes = sh * 60 + sm;
                    const endMinutes   = eh * 60 + em;
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

    // ================== TOTALIZAÇÃO ==================

    function recalcTotals() {
        const services = collectServices();
        const parts    = collectParts();
        const labor    = collectLaborEntries();

        const servicesSubtotal = services.reduce((sum, s) => sum + (s.total || 0), 0);
        const partsSubtotal    = parts.reduce((sum, p) => sum + (p.total || 0), 0);

        const totalHours = labor.reduce((sum, l) => sum + (l.hours || 0), 0);
        const hourValue  = toNumber(laborHourValueInput?.value || 0);
        const laborTotal = totalHours * hourValue;

        const discount = toNumber(discountInput?.value || 0);
        const addition = toNumber(additionInput?.value || 0);

        const grand = servicesSubtotal + partsSubtotal + laborTotal - discount + addition;

        if (servicesSubtotalDisplay)
            servicesSubtotalDisplay.textContent = `R$ ${formatCurrency(servicesSubtotal)}`;
        if (partsSubtotalDisplay)
            partsSubtotalDisplay.textContent = `R$ ${formatCurrency(partsSubtotal)}`;
        if (laborTotalAmountDisplay)
            laborTotalAmountDisplay.textContent = `R$ ${formatCurrency(laborTotal)}`;

        if (boxServicesValue)
            boxServicesValue.textContent = `R$ ${formatCurrency(servicesSubtotal)}`;
        if (boxPartsValue)
            boxPartsValue.textContent = `R$ ${formatCurrency(partsSubtotal)}`;
        if (boxLaborValue)
            boxLaborValue.textContent = `R$ ${formatCurrency(laborTotal)}`;
        if (grandTotalDisplay)
            grandTotalDisplay.textContent = `R$ ${formatCurrency(grand)}`;

        if (footerServicesValue)
            footerServicesValue.textContent = `R$ ${formatCurrency(servicesSubtotal)}`;
        if (footerPartsValue)
            footerPartsValue.textContent = `R$ ${formatCurrency(partsSubtotal)}`;
        if (footerLaborValue)
            footerLaborValue.textContent = `R$ ${formatCurrency(laborTotal)}`;
        if (footerGrandValue)
            footerGrandValue.textContent = `R$ ${formatCurrency(grand)}`;
    }

    if (discountInput)       discountInput.addEventListener("input", recalcTotals);
    if (additionInput)       additionInput.addEventListener("input", recalcTotals);
    if (laborHourValueInput) laborHourValueInput.addEventListener("input", recalcTotals);

    // ================== PAYLOAD / API ==================

    const ROUTES = {
        serviceOrder:      "/service-orders/service-order-api",
        customer: "/entities/customer-api",
        employee:          "/human-resources/employee-api",
        serviceItem:       "/catalogs/service-item-api",
        part:              "/catalogs/part-api",
        equipment:         "/catalogs/equipment-api",
    };

    function getCsrf() {
        return csrfToken;
    }

    async function postJson(url, body) {
        const res = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": getCsrf(),
                Accept: "application/json",
            },
            body: JSON.stringify(body),
        });
        if (!res.ok) {
            console.error("Erro ao salvar em", url, await res.text());
            throw new Error("Falha no POST " + url);
        }
        return await res.json();
    }

    async function findFirst(url, query) {
        try {
            const u = new URL(url, window.location.origin);

            if (typeof query === "string" && query.trim()) {
                u.searchParams.set("q", query.trim());
            } else if (query && typeof query === "object") {
                Object.entries(query).forEach(([k, v]) => {
                    if (v !== null && v !== undefined && String(v).trim() !== "") {
                        u.searchParams.set(k, String(v).trim());
                    }
                });
            }

            const res = await fetch(u.toString(), {
                headers: { Accept: "application/json" },
            });

            if (!res.ok) {
                console.warn("findFirst falhou em", url, await res.text());
                return null;
            }

            const json = await res.json();
            const data = Array.isArray(json) ? json : json.data || [];
            return data[0] || null;
        } catch (e) {
            console.error("findFirst erro:", e);
            return null;
        }
    }

    async function buildOsPayload(status = "draft") {
        const equipments = collectEquipments();
        const services   = collectServices();
        const parts      = collectParts();
        const labor      = collectLaborEntries();

        const servicesSubtotal = services.reduce((sum, s) => sum + (s.total || 0), 0);
        const partsSubtotal    = parts.reduce((sum, p) => sum + (p.total || 0), 0);

        const totalHours = labor.reduce((sum, l) => sum + (l.hours || 0), 0);
        const laborRate  = toNumber(laborHourValueInput?.value || 0);
        const laborTotal = totalHours * laborRate;

        const discount = toNumber(discountInput?.value || 0);
        const addition = toNumber(additionInput?.value || 0);
        const grand    = servicesSubtotal + partsSubtotal + laborTotal - discount + addition;

        return {
            id: orderIdInput?.value || null,

            order_number: orderNumberDisplay?.value || null,
            status: status || "draft",
            order_date: orderDateInput?.value || null,

            technician_id:          technicianIdInput?.value || null,
            opened_by_employee_id:  technicianIdInput?.value || null,

            customer_id: clientIdInput?.value || null,

            requester_name:  requesterNameInput?.value || null,
            requester_email: null,
            requester_phone: clientPhoneInput?.value || null,
            ticket_number:   ticketNumberInput?.value || null,

            address_line1: clientAddressInput?.value || null,
            address_line2: null,
            city:           clientCityInput?.value || null,
            state:          clientStateInput?.value || null,
            zip_code:       clientZipInput?.value || null,

            labor_hour_value:   laborRate,
            labor_total_hours:  totalHours,
            labor_total_amount: laborTotal,

            payment_condition: paymentConditionSel?.value || null,
            notes:             paymentNotesInput?.value || null,

            services_subtotal: servicesSubtotal,
            parts_subtotal:    partsSubtotal,
            discount_amount:   discount,
            addition_amount:   addition,
            grand_total:       grand,

            equipments,
            service_items: services,
            part_items:    parts,
            labor_entries: labor,
        };
    }

    async function submitServiceOrder(status = "draft") {
        const payload = await buildOsPayload(status);
        const isUpdate = !!payload.id;

        const url = isUpdate
            ? `${ROUTES.serviceOrder}/${payload.id}`
            : ROUTES.serviceOrder;

        const method = isUpdate ? "PUT" : "POST";

        const res = await fetch(url, {
            method,
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": getCsrf(),
                Accept: "application/json",
            },
            body: JSON.stringify(payload),
        });

        if (!res.ok) {
            console.error("Erro ao salvar OS:", await res.text());
            alert("Erro ao salvar ordem de serviço.");
            throw new Error("erro salvar OS");
        }

        const data = await res.json();
        if (!payload.id && orderIdInput && (data.id || (data.data && data.data.id))) {
            orderIdInput.value = data.id || data.data.id;
        }

        console.log("OS salva:", data);
        return { payload, data };
    }

    async function saveCatalogsFromOs(payload, opts) {
        const tasks = [];

        // ========== CLIENTE (secondary_customer) ==========
        if (opts.saveCustomer && clientNameInput && clientNameInput.value.trim()) {
            const name = clientNameInput.value.trim();
            const doc  = clientDocInput?.value?.trim() || "";

            // tenta achar cliente já cadastrado (por documento ou nome)
            const query = doc || name;
            const existing = await findFirst(ROUTES.customer, query);

            if (!existing) {
                // não existe -> cria
                tasks.push(
                    postJson(ROUTES.customer, {
                        name,
                        cpfCnpj: doc || null,
                        email: clientEmailInput?.value || null,
                        mobilePhone: clientPhoneInput?.value || null,
                        address: clientAddressInput?.value || null,
                        postalCode: clientZipInput?.value || null,
                        cityName: clientCityInput?.value || null,
                        state: clientStateInput?.value || null,
                    }).then((data) => {
                        const id = data.id || (data.data && data.data.id);
                        if (clientIdInput && id && !clientIdInput.value) {
                            clientIdInput.value = id;
                        }
                    })
                );
            } else {
                // já existe -> só garante que o hidden tenha o id
                if (clientIdInput && !clientIdInput.value) {
                    clientIdInput.value = existing.id;
                }
            }
        }

        // ========== TÉCNICO (employee is_technician=1) ==========
        if (opts.saveTechnician && technicianNameInput && technicianNameInput.value.trim()) {
            const techName = technicianNameInput.value.trim();

            let techId = technicianIdInput?.value || null;

            if (!techId) {
                // tenta achar técnico já cadastrado
                const existing = await findFirst(
                    ROUTES.employee + "?is_technician=1",
                    techName
                );

                if (existing) {
                    techId = existing.id;
                    if (technicianIdInput) technicianIdInput.value = techId;
                } else {
                    // não existe -> cria
                    tasks.push(
                        postJson(ROUTES.employee, {
                            full_name: techName,
                            email: null,
                            phone: null,
                            document_number: null,
                            position: "Técnico",
                            hourly_rate: payload.labor_hour_value || 0,
                            is_technician: true,
                            is_active: true,
                        }).then((data) => {
                            const id = data.id || (data.data && data.data.id);
                            if (technicianIdInput && id && !technicianIdInput.value) {
                                technicianIdInput.value = id;
                            }
                        })
                    );
                }
            }
        }

        // ========== SERVIÇOS (service_item) ==========
        if (opts.saveServices && Array.isArray(payload.service_items)) {
            for (const s of payload.service_items) {
                if (!s.description) continue;
                if (s.service_item_id) continue; // já está vinculado a catálogo

                const existing = await findFirst(ROUTES.serviceItem, s.description);
                if (!existing) {
                    tasks.push(
                        postJson(ROUTES.serviceItem, {
                            name: s.description,
                            description: s.description,
                            unit_price: s.unit_price || 0,
                            service_type_id: null,
                            is_active: true,
                        })
                    );
                } else {
                    // se quiser, dá pra atualizar s.service_item_id aqui
                    s.service_item_id = existing.id;
                }
            }
        }

        // ========== PEÇAS (part) ==========
        if (opts.saveParts && Array.isArray(payload.part_items)) {
            for (const p of payload.part_items) {
                const code = p.code?.trim() || "";
                const desc = p.description?.trim() || "";
                if (!code && !desc) continue;
                if (p.part_id) continue;

                const query = code || desc;
                const existing = await findFirst(ROUTES.part, query);

                if (!existing) {
                    tasks.push(
                        postJson(ROUTES.part, {
                            code: code || null,
                            name: desc || code || "Peça",
                            description: desc || null,
                            ncm_code: null,
                            unit_price: p.unit_price || 0,
                            supplier_id: null,
                            is_active: true,
                        })
                    );
                } else {
                    p.part_id = existing.id;
                }
            }
        }

        // ========== EQUIPAMENTOS (equipment) ==========
        if (opts.saveEquipments && Array.isArray(payload.equipments)) {
            for (const e of payload.equipments) {
                const serial = e.serial_number?.trim() || "";
                const name   = e.equipment_description?.trim() || "";
                if (!serial && !name) continue;
                if (e.equipment_id) continue;

                const query = serial || name;
                const existing = await findFirst(ROUTES.equipment, query);

                if (!existing) {
                    tasks.push(
                        postJson(ROUTES.equipment, {
                            code: serial || null,
                            name: name || "Equipamento",
                            description: e.notes || null,
                            serial_number: serial || null,
                            notes: e.location || null,
                        })
                    );
                } else {
                    e.equipment_id = existing.id;
                }
            }
        }

        if (!tasks.length) return;

        try {
            await Promise.all(tasks);
            console.log("Cadastros auxiliares verificados/criados com sucesso.");
        } catch (e) {
            console.error(e);
            alert(
                "Alguns cadastros auxiliares não puderam ser salvos/validados. Veja o console."
            );
        }
    }

    // ================== MODAIS / AÇÕES ==================

    // abrir modal salvar
    if (btnSave && saveModal) {
        btnSave.addEventListener("click", (e) => {
            e.preventDefault();
            saveModal.classList.remove("hidden");
            saveModal.classList.add("flex");
        });

        document.querySelectorAll("[data-os-save-cancel]").forEach((btn) => {
            btn.addEventListener("click", () => {
                saveModal.classList.add("hidden");
                saveModal.classList.remove("flex");
            });
        });

        const saveConfirm = q("#os-save-confirm");
        if (saveConfirm) {
            saveConfirm.addEventListener("click", async () => {
                const opts = {
                    saveCustomer:   q("#save_customer")?.checked || false,
                    saveTechnician: q("#save_technician")?.checked || false,
                    saveServices:   q("#save_services")?.checked || false,
                    saveParts:      q("#save_parts")?.checked || false,
                    saveEquipments: q("#save_equipments")?.checked || false,
                };

                const { payload } = await submitServiceOrder("draft");
                await saveCatalogsFromOs(payload, opts);

                saveModal.classList.add("hidden");
                saveModal.classList.remove("flex");
                alert("OS salva com sucesso.");
            });
        }
    }

    // abrir modal finalizar
    if (btnFinish && finalizeModal) {
        btnFinish.addEventListener("click", (e) => {
            e.preventDefault();

            // habilita/desabilita botão de e-mail
            const emailBtn = q("#os-finalize-email");
            if (emailBtn) {
                const hasEmail = clientEmailInput && clientEmailInput.value.trim() !== "";
                emailBtn.disabled = !hasEmail;
            }

            finalizeModal.classList.remove("hidden");
            finalizeModal.classList.add("flex");
        });

        document.querySelectorAll("[data-os-finalize-cancel]").forEach((btn) => {
            btn.addEventListener("click", () => {
                finalizeModal.classList.add("hidden");
                finalizeModal.classList.remove("flex");
            });
        });

        async function handleFinalize(action) {
            const opts = {
                saveCustomer:   q("#final_save_customer")?.checked || false,
                saveTechnician: q("#final_save_technician")?.checked || false,
                saveServices:   q("#final_save_services")?.checked || false,
                saveParts:      q("#final_save_parts")?.checked || false,
                saveEquipments: q("#final_save_equipments")?.checked || false,
            };

            // aqui usei "pending" como status ao finalizar
            const { payload } = await submitServiceOrder("pending");
            await saveCatalogsFromOs(payload, opts);

            if (action === "tablet") {
                finalizeModal.classList.add("hidden");
                finalizeModal.classList.remove("flex");
                openSignatureModal();
            } else if (action === "email") {
                finalizeModal.classList.add("hidden");
                finalizeModal.classList.remove("flex");
                alert(
                    "OS enviada para fluxo de assinatura por e-mail (integração vem depois)."
                );
            } else if (action === "new") {
                finalizeModal.classList.add("hidden");
                finalizeModal.classList.remove("flex");
                window.location.href = "/service-orders/create";
            }
        }

        const btnEmail   = q("#os-finalize-email");
        const btnTablet  = q("#os-finalize-tablet");
        const btnNew     = q("#os-finalize-new");

        if (btnEmail)  btnEmail.addEventListener("click", () => handleFinalize("email"));
        if (btnTablet) btnTablet.addEventListener("click", () => handleFinalize("tablet"));
        if (btnNew)    btnNew.addEventListener("click", () => handleFinalize("new"));
    }

    function openSignatureModal() {
        if (!signModal) return;
        signModal.classList.remove("hidden");
        signModal.classList.add("flex");
    }

    // assinatura (canvas)
    if (signModal) {
        const canvas = q("#signature-pad");
        if (canvas) {
            const ctx   = canvas.getContext("2d");
            let drawing = false;
            let lastX = 0,
                lastY = 0;

            function resizeCanvas() {
                const rect = canvas.getBoundingClientRect();
                canvas.width  = rect.width;
                canvas.height = rect.height;
            }
            resizeCanvas();
            window.addEventListener("resize", resizeCanvas);

            function startDraw(x, y) {
                drawing = true;
                lastX = x;
                lastY = y;
            }
            function drawTo(x, y) {
                if (!drawing) return;
                ctx.lineWidth   = 2;
                ctx.lineCap     = "round";
                ctx.strokeStyle = "#111827";
                ctx.beginPath();
                ctx.moveTo(lastX, lastY);
                ctx.lineTo(x, y);
                ctx.stroke();
                lastX = x;
                lastY = y;
            }
            function stopDraw() {
                drawing = false;
            }

            canvas.addEventListener("mousedown", (e) => {
                const rect = canvas.getBoundingClientRect();
                startDraw(e.clientX - rect.left, e.clientY - rect.top);
            });
            canvas.addEventListener("mousemove", (e) => {
                const rect = canvas.getBoundingClientRect();
                drawTo(e.clientX - rect.left, e.clientY - rect.top);
            });
            window.addEventListener("mouseup", stopDraw);

            canvas.addEventListener(
                "touchstart",
                (e) => {
                    e.preventDefault();
                    const t   = e.touches[0];
                    const rect = canvas.getBoundingClientRect();
                    startDraw(t.clientX - rect.left, t.clientY - rect.top);
                },
                { passive: false }
            );
            canvas.addEventListener(
                "touchmove",
                (e) => {
                    e.preventDefault();
                    const t   = e.touches[0];
                    const rect = canvas.getBoundingClientRect();
                    drawTo(t.clientX - rect.left, t.clientY - rect.top);
                },
                { passive: false }
            );
            canvas.addEventListener("touchend", stopDraw);

            q("#signature-clear")?.addEventListener("click", () => {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
            });

            q("#signature-close")?.addEventListener("click", () => {
                signModal.classList.add("hidden");
                signModal.classList.remove("flex");
            });

            q("#signature-save")?.addEventListener("click", () => {
                const dataUrl = canvas.toDataURL("image/png");
                console.log("Assinatura base64:", dataUrl);
                alert("Assinatura capturada (enviar para backend depois).");
                signModal.classList.add("hidden");
                signModal.classList.remove("flex");
            });
        }
    }

    // ================== NÚMERO DA OS (próximo código) ==================

    async function loadNextOrderNumberIfNeeded() {
        if (!orderNumberDisplay) return;

        // se já tiver valor (edição), só mantém
        if (orderNumberDisplay.value && orderNumberDisplay.value.trim() !== "") return;

        // só busca próximo se for nova OS
        if (orderIdInput && orderIdInput.value) return;

        try {
            const res = await fetch(
                "/service-orders/service-order-api?per_page=1",
                { headers: { Accept: "application/json" } }
            );
            if (!res.ok) throw new Error("erro ao buscar última OS");
            const json = await res.json();
            const data = json.data || [];
            const last = data[0];

            let next = "000001";
            if (last && last.order_number) {
                const n = parseInt(String(last.order_number).replace(/\D/g, ""), 10);
                const inc = isNaN(n) ? 1 : n + 1;
                next = String(inc).padStart(6, "0");
            }

            orderNumberDisplay.value = next;
        } catch (e) {
            console.error("Erro ao buscar próximo número de OS:", e);
        }
    }

    // ================== INIT ==================

    if (btnAddEquipment) btnAddEquipment.addEventListener("click", () => addEquipmentBlock());
    if (btnAddService)   btnAddService.addEventListener("click",   () => addServiceRow());
    if (btnAddPart)      btnAddPart.addEventListener("click",      () => addPartRow());
    if (btnAddLabor)     btnAddLabor.addEventListener("click",     () => addLaborRow());

    setupCustomerLookup();
    setupTechnicianLookup();

    if (equipmentListEl && !equipmentListEl.children.length) addEquipmentBlock();
    if (serviceListEl   && !serviceListEl.children.length)   addServiceRow();
    if (partListEl      && !partListEl.children.length)      addPartRow();
    if (laborListEl     && !laborListEl.children.length)     addLaborRow();

    loadNextOrderNumberIfNeeded();
    recalcTotals();
});
