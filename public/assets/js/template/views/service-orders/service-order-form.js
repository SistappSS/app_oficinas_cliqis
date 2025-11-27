document.addEventListener("DOMContentLoaded", () => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const toNumber = (v) => {
        if (v === null || v === undefined) return 0;
        let s = String(v).trim();
        if (!s) return 0;

        // Se vier em formato pt-BR (com vírgula e sem ponto) -> converte
        if (s.includes(',') && !s.includes('.')) {
            s = s.replace(/\./g, '').replace(',', '.');
        }
        // Se já vier com ponto (padrão dos inputs type="number"), só usa direto
        const n = Number(s);
        return isNaN(n) ? 0 : n;
    };

    const formatCurrency = (n) =>
        (n || 0).toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

    const q = (sel) => document.querySelector(sel);

    const state = { saving: false };

    const orderIdInput = q('#service_order_id');

    // ====== CAMPOS PRINCIPAIS ======
    const orderDateInput       = q('#order_date');
    const requesterNameInput   = q('#requester_name');
    const technicianNameInput  = q('#service_responsible');
    const technicianIdInput    = q('#technician_id');

    const clientIdInput        = q('#secondary_customer_id');
    const clientNameInput      = q('#client_name');
    const clientDocInput       = q('#client_document');
    const clientContactInput   = q('#client_contact');
    const clientPhoneInput     = q('#client_phone');
    const clientAddressInput   = q('#client_address');
    const clientCityInput      = q('#client_city');
    const clientStateInput     = q('#client_state');
    const clientZipInput       = q('#client_zip');
    const ticketNumberInput    = q('#ticket_number');

    const laborHourValueInput  = q('#labor_hour_value');
    const paymentConditionSel  = q('#payment_condition');
    const paymentNotesInput    = q('#payment_notes');
    const discountInput        = q('#discount_amount');
    const additionInput        = q('#addition_amount');

    const equipmentListEl = q('#equipment-list');
    const serviceListEl   = q('#service-list');
    const partListEl      = q('#part-list');
    const laborListEl     = q('#labor-list');

    const servicesSubtotalDisplay     = q('#services-subtotal-display');
    const partsSubtotalDisplay        = q('#parts-subtotal-display');
    const laborTotalAmountDisplay     = q('#labor-total-amount-display');

    const boxServicesValue            = q('#box-services-value');
    const boxPartsValue               = q('#box-parts-value');
    const boxLaborValue               = q('#box-labor-value');
    const grandTotalDisplay           = q('#grand_total_display');

    const footerServicesValue         = q('#footer-services-value');
    const footerPartsValue            = q('#footer-parts-value');
    const footerLaborValue            = q('#footer-labor-value');
    const footerGrandValue            = q('#footer-grand-value');

    const btnAddEquipment = q('#btn-add-equipment');
    const btnAddService   = q('#btn-add-service');
    const btnAddPart      = q('#btn-add-part');
    const btnAddLabor     = q('#btn-add-labor');
    const btnSave         = q('#btn-save-os');
    const btnFinish       = q('#btn-finish-os');

    // ====== HELPERS DROPDOWN / API ======
    const wrapForDropdown = (input) => {
        if (!input) return null;
        if (input.parentElement && input.parentElement.classList.contains('relative')) {
            return input.parentElement;
        }
        const parent = input.parentNode;
        const wrapper = document.createElement('div');
        wrapper.className = 'relative';
        parent.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        return wrapper;
    };

    async function fetchFirstItem(url) {
        try {
            const res = await fetch(url);
            if (!res.ok) return null;
            const json = await res.json();
            const data = Array.isArray(json) ? json : (json.data || []);
            return data[0] || null;
        } catch (e) {
            console.error(e);
            return null;
        }
    }

    function setupTypeahead({ input, hiddenIdInput, searchUrl, mapItem, onSelect, extraQuery = '', minChars = 2 }) {
        if (!input) return;

        const wrapper = wrapForDropdown(input);
        if (!wrapper) return;

        const dropdown = document.createElement('div');
        dropdown.className = 'absolute z-30 mt-1 w-full rounded-xl border border-slate-200 bg-white shadow-lg max-h-60 overflow-auto hidden';
        wrapper.appendChild(dropdown);

        let abortController = null;

        input.addEventListener('input', async () => {
            const term = input.value.trim();
            if (hiddenIdInput) hiddenIdInput.value = '';

            if (term.length < minChars) {
                dropdown.classList.add('hidden');
                dropdown.innerHTML = '';
                return;
            }

            if (abortController) abortController.abort();
            abortController = new AbortController();

            try {
                const res = await fetch(`${searchUrl}?q=${encodeURIComponent(term)}${extraQuery}`, {
                    signal: abortController.signal
                });
                if (!res.ok) throw new Error('erro ao buscar');

                const json = await res.json();
                const data = Array.isArray(json) ? json : (json.data || []);
                const items = data.map(mapItem).filter(Boolean);

                dropdown.innerHTML = '';
                if (!items.length) {
                    dropdown.classList.add('hidden');
                    return;
                }

                for (const item of items) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'w-full px-3 py-2 text-left text-sm hover:bg-slate-50 flex justify-between gap-2';
                    btn.innerHTML = `
                        <span class="truncate">${item.label}</span>
                        ${item.sublabel ? `<span class="ml-2 flex-shrink-0 text-xs text-slate-500">${item.sublabel}</span>` : ''}
                    `;
                    btn.addEventListener('click', () => {
                        if (hiddenIdInput) hiddenIdInput.value = item.id || '';
                        input.value = item.label || '';
                        dropdown.classList.add('hidden');
                        dropdown.innerHTML = '';
                        onSelect && onSelect(item);
                    });
                    dropdown.appendChild(btn);
                }

                dropdown.classList.remove('hidden');
            } catch (e) {
                if (e.name !== 'AbortError') console.error(e);
            }
        });

        document.addEventListener('click', (ev) => {
            if (!wrapper.contains(ev.target)) dropdown.classList.add('hidden');
        });
    }

    // ====== CLIENTE (search + auto preencher) ======
    function setupCustomerLookup() {
        if (!clientNameInput) return;

        setupTypeahead({
            input: clientNameInput,
            hiddenIdInput: clientIdInput,
            searchUrl: '/entities/customer-api',
            mapItem: (c) => ({
                id: c.id,
                label: c.name,
                sublabel: c.cpfCnpj || c.email || ''
            }),
            onSelect: (c) => {
                if (clientDocInput)     clientDocInput.value     = c.cpfCnpj || '';
                if (clientPhoneInput)   clientPhoneInput.value   = c.mobilePhone || '';
                if (clientContactInput) clientContactInput.value = c.name || '';
                if (clientAddressInput) clientAddressInput.value = [c.address, c.addressNumber, c.complement].filter(Boolean).join(', ');
                if (clientCityInput)    clientCityInput.value    = c.cityName || '';
                if (clientStateInput)   clientStateInput.value   = c.state || '';
                if (clientZipInput)     clientZipInput.value     = c.postalCode || '';
            }
        });
    }

    async function ensureCustomerExists() {
        if (!clientNameInput) return null;
        const existingId = clientIdInput?.value || null;
        if (existingId) return existingId;

        const name = clientNameInput.value.trim();
        if (!name) return null;

        const payload = {
            name,
            cpfCnpj: clientDocInput?.value || null,
            mobilePhone: clientPhoneInput?.value || null,
            email: null,
            address: clientAddressInput?.value || null,
            addressNumber: null,
            postalCode: clientZipInput?.value || null,
            cityName: clientCityInput?.value || null,
            state: clientStateInput?.value || null,
            province: null,
            complement: null
        };

        const res = await fetch('/entities/customer-api', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf
            },
            body: JSON.stringify(payload)
        });
        if (!res.ok) throw new Error('Erro ao criar cliente');

        const json = await res.json();
        const newId = json.id || (json.data && json.data.id) || null;
        if (clientIdInput && newId) clientIdInput.value = newId;
        return newId;
    }

    // ====== TÉCNICO (search + valor hora) ======
    function setupTechnicianLookup() {
        if (!technicianNameInput) return;

        setupTypeahead({
            input: technicianNameInput,
            hiddenIdInput: technicianIdInput,
            searchUrl: '/human-resources/employee-api',
            extraQuery: '&is_technician=1',
            mapItem: (e) => ({
                id: e.id,
                label: e.full_name,
                sublabel: e.hourly_rate ? `R$ ${formatCurrency(toNumber(e.hourly_rate))}/h` : '',
                hourly_rate: e.hourly_rate // <- importante
            }),
            onSelect: (item) => {
                if (laborHourValueInput && item.hourly_rate != null) {
                    laborHourValueInput.value = item.hourly_rate;
                    recalcTotals();
                }
            }
        });
    }

    // ====== BLOCOS DINÂMICOS ======
    let equipmentCounter = 0;
    let serviceCounter   = 0;
    let partCounter      = 0;
    let laborCounter     = 0;

    function addEquipmentBlock(initial = {}) {
        if (!equipmentListEl) return;

        const wrap = document.createElement('div');
        wrap.className = 'rounded-2xl bg-slate-50/80 border border-slate-100 p-4';
        wrap.dataset.row = 'equipment';
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

        const btnRemove   = wrap.querySelector('.btn-remove-eq');
        const nameInput   = wrap.querySelector('.js-equipment-desc');
        const idInput     = wrap.querySelector('.js-equipment-id');
        const serialInput = wrap.querySelector('.js-equipment-serial');
        const locInput    = wrap.querySelector('.js-equipment-location');
        const notesInput  = wrap.querySelector('.js-equipment-notes');

        btnRemove.addEventListener('click', () => {
            wrap.remove();
            recalcTotals();
        });

        if (initial.equipment_description) nameInput.value = initial.equipment_description;
        if (initial.serial_number)        serialInput.value = initial.serial_number;
        if (initial.location)             locInput.value = initial.location;
        if (initial.notes)                notesInput.value = initial.notes;
        if (initial.equipment_id)         idInput.value = initial.equipment_id;

        // typeahead por nome do equipamento
        setupTypeahead({
            input: nameInput,
            hiddenIdInput: idInput,
            searchUrl: '/catalogs/equipment-api',
            mapItem: (e) => ({
                id: e.id,
                label: e.name,
                sublabel: e.code || ''
            }),
            onSelect: (e) => {
                if (!serialInput.value && e.serial_number) serialInput.value = e.serial_number;
                if (!notesInput.value && e.description)    notesInput.value = e.description;
            }
        });

        // auto preencher por nome / série ao sair do campo
        const autoFillEquipment = async (term) => {
            term = term.trim();
            if (!term) return;
            const eq = await fetchFirstItem(`/catalogs/equipment-api?q=${encodeURIComponent(term)}`);
            if (!eq) return;
            idInput.value = eq.id || '';
            nameInput.value = eq.name || term;
            if (!serialInput.value && eq.serial_number) serialInput.value = eq.serial_number;
            if (!notesInput.value && eq.description)    notesInput.value = eq.description;
        };

        nameInput.addEventListener('blur', () => autoFillEquipment(nameInput.value));
        serialInput.addEventListener('blur', () => autoFillEquipment(serialInput.value));

        equipmentListEl.appendChild(wrap);
    }

    function addServiceRow(initial = {}) {
        if (!serviceListEl) return;

        const row = document.createElement('div');
        row.className = 'grid grid-cols-12 gap-2 items-center';
        row.dataset.row = 'service';
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
              value="${initial.description || ''}">
            <input type="hidden" class="js-service-id" value="${initial.service_item_id || ''}">
          </div>
          <div class="col-span-2">
            <input
              type="number" step="0.01"
              class="js-service-unit w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-right"
              placeholder="0,00"
              value="${initial.unit_price != null ? initial.unit_price : ''}">
          </div>
          <div class="col-span-2 text-right text-sm font-semibold text-slate-800">
            <span class="js-service-total">R$ 0,00</span>
          </div>
          <div class="col-span-1 flex justify-end">
            <button type="button" class="btn-remove-service inline-flex h-8 w-8 items-center justify-center rounded-full text-red-500 hover:bg-red-50">🗑</button>
          </div>
        `;

        const qtyInput   = row.querySelector('.js-service-qty');
        const descInput  = row.querySelector('.js-service-desc');
        const unitInput  = row.querySelector('.js-service-unit');
        const totalSpan  = row.querySelector('.js-service-total');
        const idInput    = row.querySelector('.js-service-id');
        const btnRemove  = row.querySelector('.btn-remove-service');

        const recalcRow = () => {
            const qv = toNumber(qtyInput.value || 0);
            const p  = toNumber(unitInput.value || 0);
            const t  = qv * p;
            totalSpan.textContent = `R$ ${formatCurrency(t)}`;
            recalcTotals();
        };

        qtyInput.addEventListener('input', recalcRow);
        unitInput.addEventListener('input', recalcRow);

        btnRemove.addEventListener('click', () => {
            row.remove();
            recalcTotals();
        });

        // typeahead serviços
        setupTypeahead({
            input: descInput,
            hiddenIdInput: idInput,
            searchUrl: '/catalogs/service-item-api',
            mapItem: (s) => ({
                id: s.id,
                label: s.name,
                sublabel: s.unit_price ? `R$ ${formatCurrency(toNumber(s.unit_price))}` : ''
            }),
            onSelect: (s) => {
                if (!unitInput.value && s.unit_price != null) {
                    unitInput.value = s.unit_price;
                }
                recalcRow();
            }
        });

        // auto preencher ao sair do campo descrição
        const autoFillService = async (term) => {
            term = term.trim();
            if (!term) return;
            const svc = await fetchFirstItem(`/catalogs/service-item-api?q=${encodeURIComponent(term)}`);
            if (!svc) return;
            idInput.value = svc.id || '';
            descInput.value = svc.name || term;
            if (!unitInput.value && svc.unit_price != null) {
                unitInput.value = svc.unit_price;
            }
            recalcRow();
        };

        descInput.addEventListener('blur', () => autoFillService(descInput.value));

        serviceListEl.appendChild(row);
        recalcRow();
    }

    function addPartRow(initial = {}) {
        if (!partListEl) return;

        const row = document.createElement('div');
        row.className = 'grid grid-cols-12 gap-2 items-center';
        row.dataset.row = 'part';
        row.dataset.index = String(++partCounter);

        row.innerHTML = `
          <div class="col-span-2">
            <input
              class="js-part-code w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm"
              placeholder="Código"
              value="${initial.code || ''}">
          </div>
          <div class="col-span-4">
            <input
              class="js-part-desc w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm"
              placeholder="Descrição"
              value="${initial.description || ''}">
            <input type="hidden" class="js-part-id" value="${initial.part_id || ''}">
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
              value="${initial.unit_price != null ? initial.unit_price : ''}">
          </div>
          <div class="col-span-2 text-right text-sm font-semibold text-slate-800">
            <span class="js-part-total">R$ 0,00</span>
          </div>
          <div class="col-span-1 flex justify-end">
            <button type="button" class="btn-remove-part inline-flex h-8 w-8 items-center justify-center rounded-full text-red-500 hover:bg-red-50">🗑</button>
          </div>
        `;

        const codeInput  = row.querySelector('.js-part-code');
        const descInput  = row.querySelector('.js-part-desc');
        const qtyInput   = row.querySelector('.js-part-qty');
        const unitInput  = row.querySelector('.js-part-unit');
        const totalSpan  = row.querySelector('.js-part-total');
        const idInput    = row.querySelector('.js-part-id');
        const btnRemove  = row.querySelector('.btn-remove-part');

        const recalcRow = () => {
            const qv = toNumber(qtyInput.value || 0);
            const p  = toNumber(unitInput.value || 0);
            const t  = qv * p;
            totalSpan.textContent = `R$ ${formatCurrency(t)}`;
            recalcTotals();
        };

        qtyInput.addEventListener('input', recalcRow);
        unitInput.addEventListener('input', recalcRow);

        btnRemove.addEventListener('click', () => {
            row.remove();
            recalcTotals();
        });

        // typeahead peças pelo nome
        setupTypeahead({
            input: descInput,
            hiddenIdInput: idInput,
            searchUrl: '/catalogs/part-api',
            mapItem: (p) => ({
                id: p.id,
                label: p.name,
                sublabel: p.code || ''
            }),
            onSelect: (p) => {
                if (!codeInput.value && p.code) codeInput.value = p.code;
                if (!unitInput.value && p.unit_price != null) {
                    unitInput.value = p.unit_price;
                }
                recalcRow();
            }
        });

        // auto preencher por código ou descrição ao sair
        const autoFillPart = async (term) => {
            term = term.trim();
            if (!term) return;
            const part = await fetchFirstItem(`/catalogs/part-api?q=${encodeURIComponent(term)}`);
            if (!part) return;
            idInput.value    = part.id || '';
            descInput.value  = part.name || term;
            codeInput.value  = part.code || codeInput.value || term;
            if (!unitInput.value && part.unit_price != null) {
                unitInput.value = part.unit_price;
            }
            recalcRow();
        };

        codeInput.addEventListener('blur', () => autoFillPart(codeInput.value));
        descInput.addEventListener('blur', () => autoFillPart(descInput.value));

        partListEl.appendChild(row);
        recalcRow();
    }

    function addLaborRow(initial = {}) {
        if (!laborListEl) return;

        const row = document.createElement('div');
        row.className = 'grid grid-cols-12 gap-2 items-center';
        row.dataset.row = 'labor';
        row.dataset.index = String(++laborCounter);

        row.innerHTML = `
          <div class="col-span-2">
            <input type="time"
              class="js-labor-start w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm"
              value="${initial.started_at || ''}">
          </div>
          <div class="col-span-2">
            <input type="time"
              class="js-labor-end w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm"
              value="${initial.ended_at || ''}">
          </div>
          <div class="col-span-7">
            <input
              class="js-labor-desc w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm"
              placeholder="Atividade realizada"
              value="${initial.description || ''}">
          </div>
          <div class="col-span-1 flex justify-end">
            <button type="button" class="btn-remove-labor inline-flex h-8 w-8 items-center justify-center rounded-full text-red-500 hover:bg-red-50">🗑</button>
          </div>
        `;

        const startInput = row.querySelector('.js-labor-start');
        const endInput   = row.querySelector('.js-labor-end');
        const btnRemove  = row.querySelector('.btn-remove-labor');

        const recomputeHours = () => recalcTotals();

        startInput.addEventListener('change', recomputeHours);
        endInput.addEventListener('change', recomputeHours);

        btnRemove.addEventListener('click', () => {
            row.remove();
            recalcTotals();
        });

        laborListEl.appendChild(row);
        recalcTotals();
    }

    // ====== COLLECT ======
    const collectEquipments = () =>
        Array.from(document.querySelectorAll('[data-row="equipment"]')).map(row => {
            return {
                equipment_id:           row.querySelector('.js-equipment-id')?.value || null,
                equipment_description:  row.querySelector('.js-equipment-desc')?.value?.trim() || '',
                serial_number:          row.querySelector('.js-equipment-serial')?.value?.trim() || '',
                location:               row.querySelector('.js-equipment-location')?.value?.trim() || '',
                notes:                  row.querySelector('.js-equipment-notes')?.value?.trim() || ''
            };
        }).filter(e =>
            e.equipment_id ||
            e.equipment_description ||
            e.serial_number ||
            e.location ||
            e.notes
        );

    const collectServices = () =>
        Array.from(document.querySelectorAll('[data-row="service"]')).map(row => {
            const qty   = toNumber(row.querySelector('.js-service-qty')?.value || 0);
            const unit  = toNumber(row.querySelector('.js-service-unit')?.value || 0);
            const total = qty * unit;

            return {
                service_item_id: row.querySelector('.js-service-id')?.value || null,
                description:     row.querySelector('.js-service-desc')?.value?.trim() || '',
                quantity:        qty,
                unit_price:      unit,
                total
            };
        }).filter(s => s.description || s.quantity || s.unit_price || s.service_item_id);

    const collectParts = () =>
        Array.from(document.querySelectorAll('[data-row="part"]')).map(row => {
            const qty   = toNumber(row.querySelector('.js-part-qty')?.value || 0);
            const unit  = toNumber(row.querySelector('.js-part-unit')?.value || 0);
            const total = qty * unit;

            return {
                part_id:     row.querySelector('.js-part-id')?.value || null,
                code:        row.querySelector('.js-part-code')?.value?.trim() || '',
                description: row.querySelector('.js-part-desc')?.value?.trim() || '',
                quantity:    qty,
                unit_price:  unit,
                total
            };
        }).filter(p => p.description || p.code || p.quantity || p.unit_price || p.part_id);

    const collectLaborEntries = () =>
        Array.from(document.querySelectorAll('[data-row="labor"]')).map(row => {
            const start = row.querySelector('.js-labor-start')?.value || '';
            const end   = row.querySelector('.js-labor-end')?.value || '';
            const desc  = row.querySelector('.js-labor-desc')?.value?.trim() || '';

            let hours = 0;
            if (start && end) {
                const [sh, sm] = start.split(':').map(Number);
                const [eh, em] = end.split(':').map(Number);
                const startMinutes = sh * 60 + sm;
                const endMinutes   = eh * 60 + em;
                if (endMinutes > startMinutes) {
                    hours = (endMinutes - startMinutes) / 60;
                }
            }

            return {
                employee_id: technicianIdInput?.value || null,
                started_at: start ? `${orderDateInput?.value || ''} ${start}:00` : null,
                ended_at:   end   ? `${orderDateInput?.value || ''} ${end}:00`   : null,
                hours,
                description: desc
            };
        }).filter(l => l.started_at || l.ended_at || l.description);

    // ====== CRIA CATALOGO AUTO ======
    async function ensureEquipmentCatalogItems(equipments) {
        const result = [];
        for (const e of equipments) {
            let id = e.equipment_id;
            if (!id && e.equipment_description) {
                const body = {
                    code: null,
                    name: e.equipment_description,
                    description: e.notes || null,
                    serial_number: e.serial_number || null,
                    notes: e.notes || null
                };
                const res = await fetch('/catalogs/equipment-api', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify(body)
                });
                if (res.ok) {
                    const json = await res.json();
                    id = json.id || (json.data && json.data.id) || null;
                }
            }
            result.push({
                equipment_id: id,
                equipment_description: e.equipment_description,
                serial_number: e.serial_number,
                location: e.location,
                notes: e.notes
            });
        }
        return result;
    }

    async function ensureServiceCatalogItems(services) {
        const result = [];
        for (const s of services) {
            let id = s.service_item_id;
            if (!id && s.description) {
                const body = {
                    name: s.description,
                    description: s.description,
                    unit_price: s.unit_price || 0,
                    service_type_id: null,
                    is_active: true
                };
                const res = await fetch('/catalogs/service-item-api', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify(body)
                });
                if (res.ok) {
                    const json = await res.json();
                    id = json.id || (json.data && json.data.id) || null;
                }
            }
            result.push({
                service_item_id: id,
                service_type_id: null,
                description: s.description,
                quantity: s.quantity,
                unit_price: s.unit_price,
                total: s.total
            });
        }
        return result;
    }

    async function ensurePartCatalogItems(parts) {
        const result = [];
        for (const p of parts) {
            let id = p.part_id;
            if (!id && p.description) {
                const body = {
                    supplier_id: null,
                    code: p.code || null,
                    name: p.description,
                    description: p.description,
                    ncm_code: null,
                    unit_price: p.unit_price || 0,
                    is_active: true
                };
                const res = await fetch('/catalogs/part-api', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify(body)
                });
                if (res.ok) {
                    const json = await res.json();
                    id = json.id || (json.data && json.data.id) || null;
                }
            }
            result.push({
                part_id: id,
                description: p.description,
                quantity: p.quantity,
                unit_price: p.unit_price,
                total: p.total
            });
        }
        return result;
    }

    // ====== TOTALIZAÇÃO ======
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

        if (servicesSubtotalDisplay) servicesSubtotalDisplay.textContent = `R$ ${formatCurrency(servicesSubtotal)}`;
        if (partsSubtotalDisplay)    partsSubtotalDisplay.textContent    = `R$ ${formatCurrency(partsSubtotal)}`;
        if (laborTotalAmountDisplay) laborTotalAmountDisplay.textContent = `R$ ${formatCurrency(laborTotal)}`;

        if (boxServicesValue) boxServicesValue.textContent = `R$ ${formatCurrency(servicesSubtotal)}`;
        if (boxPartsValue)    boxPartsValue.textContent    = `R$ ${formatCurrency(partsSubtotal)}`;
        if (boxLaborValue)    boxLaborValue.textContent    = `R$ ${formatCurrency(laborTotal)}`;
        if (grandTotalDisplay) grandTotalDisplay.textContent = `R$ ${formatCurrency(grand)}`;

        if (footerServicesValue) footerServicesValue.textContent = `R$ ${formatCurrency(servicesSubtotal)}`;
        if (footerPartsValue)    footerPartsValue.textContent    = `R$ ${formatCurrency(partsSubtotal)}`;
        if (footerLaborValue)    footerLaborValue.textContent    = `R$ ${formatCurrency(laborTotal)}`;
        if (footerGrandValue)    footerGrandValue.textContent    = `R$ ${formatCurrency(grand)}`;
    }

    if (discountInput)      discountInput.addEventListener('input', recalcTotals);
    if (additionInput)      additionInput.addEventListener('input', recalcTotals);
    if (laborHourValueInput) laborHourValueInput.addEventListener('input', recalcTotals);

    // ====== PAYLOAD / SAVE ======
    async function buildPayload(status) {
        const equipmentsRaw = collectEquipments();
        const servicesRaw   = collectServices();
        const partsRaw      = collectParts();
        const laborRaw      = collectLaborEntries();

        const secondaryCustomerId = await ensureCustomerExists();
        const equipments   = await ensureEquipmentCatalogItems(equipmentsRaw);
        const serviceItems = await ensureServiceCatalogItems(servicesRaw);
        const partItems    = await ensurePartCatalogItems(partsRaw);

        const servicesSubtotal = serviceItems.reduce((sum, s) => sum + (s.total || 0), 0);
        const partsSubtotal    = partItems.reduce((sum, p) => sum + (p.total || 0), 0);
        const totalHours       = laborRaw.reduce((sum, l) => sum + (l.hours || 0), 0);
        const laborRate        = toNumber(laborHourValueInput?.value || 0);
        const laborTotal       = totalHours * laborRate;

        const discount = toNumber(discountInput?.value || 0);
        const addition = toNumber(additionInput?.value || 0);
        const grand    = servicesSubtotal + partsSubtotal + laborTotal - discount + addition;

        return {
            status: status || 'draft',
            order_date: orderDateInput?.value || null,
            technician_id: technicianIdInput?.value || null,
            opened_by_employee_id: technicianIdInput?.value || null,

            secondary_customer_id: secondaryCustomerId,

            requester_name:  requesterNameInput?.value || null,
            requester_email: null,
            requester_phone: clientPhoneInput?.value || null,
            ticket_number:   ticketNumberInput?.value || null,

            address_line1: clientAddressInput?.value || null,
            address_line2: null,
            city:           clientCityInput?.value || null,
            state:          clientStateInput?.value || null,
            zip_code:       clientZipInput?.value || null,

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
            labor_entries: laborRaw
        };
    }

    async function saveOrder(status) {
        if (state.saving) return;
        state.saving = true;

        try {
            const payload = await buildPayload(status);

            const id = orderIdInput?.value || null;
            const isUpdate = !!id;
            const url = isUpdate
                ? `/service-orders/service-order-api/${id}`
                : `/service-orders/service-order-api`;
            const method = isUpdate ? 'PUT' : 'POST';

            const res = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify(payload)
            });

            if (!res.ok) {
                console.error('Falha ao salvar OS', await res.text());
                alert('Erro ao salvar OS.');
                return;
            }

            const json = await res.json();
            const newId = json.id || (json.data && json.data.id) || null;
            if (orderIdInput && newId && !orderIdInput.value) {
                orderIdInput.value = newId;
            }

            if (status === 'completed') {
                console.log('OS finalizada com sucesso.');
                alert('OS finalizada com sucesso.');
            } else {
                console.log('OS salva com sucesso.');
                alert('OS salva com sucesso.');
            }
        } catch (e) {
            console.error(e);
            alert('Erro inesperado ao salvar OS.');
        } finally {
            state.saving = false;
            recalcTotals();
        }
    }

    // ====== BOTOES ======
    if (btnAddEquipment) btnAddEquipment.addEventListener('click', () => addEquipmentBlock());
    if (btnAddService)   btnAddService.addEventListener('click',   () => addServiceRow());
    if (btnAddPart)      btnAddPart.addEventListener('click',      () => addPartRow());
    if (btnAddLabor)     btnAddLabor.addEventListener('click',     () => addLaborRow());

    if (btnSave)   btnSave.addEventListener('click',   () => saveOrder('draft'));
    if (btnFinish) btnFinish.addEventListener('click', () => saveOrder('completed'));

    // ====== INIT ======
    setupCustomerLookup();
    setupTechnicianLookup();

    if (equipmentListEl && !equipmentListEl.children.length) addEquipmentBlock();
    if (serviceListEl   && !serviceListEl.children.length)   addServiceRow();
    if (partListEl      && !partListEl.children.length)      addPartRow();
    if (laborListEl     && !laborListEl.children.length)     addLaborRow();

    recalcTotals();
});
