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

    async function detectMissingCatalogs(payload) {
        const missing = {
            customer: null,
            services: [],
            parts: [],
            equipments: [],
            tech_rate: null,
        };

        // ---- CLIENTE (secondary_customer)
        if (!payload.secondary_customer_id && (clientNameInput?.value || "").trim()) {
            const name = (clientNameInput.value || "").trim();
            const doc = (clientDocInput?.value || "").trim();
            const email = (clientEmailInput?.value || "").trim().toLowerCase();

            try {
                const items = await searchCustomers(name);

                const byDoc = doc
                    ? items.find(c => (c.cpfCnpj || "").replace(/\D/g, "") === doc.replace(/\D/g, ""))
                    : null;

                const byEmail = email
                    ? items.find(c => (c.email || "").trim().toLowerCase() === email)
                    : null;

                const byExactName = items.find(c => (c.name || "").trim().toLowerCase() === name.toLowerCase());

                const found = byDoc || byEmail || byExactName;
                if (!found?.id) {
                    missing.customer = {
                        name,
                        cpfCnpj: doc || null,
                        email: email || null,
                        mobilePhone: clientPhoneInput?.value || null,
                        address: clientAddressInput?.value || null,
                        addressNumber: clientAddressNumberInput?.value || null,
                        postalCode: clientZipInput?.value || null,
                        cityName: clientCityInput?.value || null,
                        state: clientStateInput?.value || null,
                        province: clientProvinceInput?.value || null,
                        complement: clientComplementInput?.value || null,
                    };
                }
            } catch (e) {}
        }

        // ---- SERVIÇOS (service_items do catálogo)
        (payload.services || []).forEach((s) => {
            const label = (s.description || "").trim();
            if (!s.service_item_id && label) {
                missing.services.push({
                    label,
                    unit_price: s.unit_price || 0,
                });
            }
        });

        // ---- PEÇAS (parts do catálogo)
        // precisa do payload.parts conter `code` (do collectParts)
        (payload.parts || []).forEach((p) => {
            const code = (p.code || "").trim();
            const desc = (p.description || "").trim();
            if (!p.part_id && (code || desc)) {
                missing.parts.push({
                    code: code || null,
                    name: desc || code || "Peça",
                    description: desc || null,
                    unit_price: p.unit_price || 0,
                    ncm_code: null,
                    supplier_id: null,
                    is_active: true,
                });
            }
        });

        // ---- EQUIPAMENTOS (equipments do catálogo)
        (payload.equipments || []).forEach((e) => {
            const name = (e.equipment_description || "").trim();
            const serial = (e.serial_number || "").trim();
            if (!e.equipment_id && (name || serial)) {
                missing.equipments.push({
                    code: (serial || null),                 // opcional
                    name: name || "Equipamento",
                    description: (e.notes || "").trim() || null,
                    serial_number: serial || null,
                    notes: (e.location || "").trim() || null,
                });
            }
        });

        const techId = payload.technician_id || null;
        const uiRate = toNumber(laborHourValueInput?.value || 0);
        const dbRate = toNumber(technicianIdInput?.dataset?.hourlyRate || 0);

        if (techId && Math.abs(uiRate - dbRate) > 0.0001) {
            missing.tech_rate = {
                technician_id: techId,
                old_rate: dbRate,
                new_rate: uiRate,
            };
        }

        missing.services = Array.from(
            new Map(missing.services.map(x => [String(x.label || "").toLowerCase(), x])).values()
        );

        missing.parts = Array.from(
            new Map(missing.parts.map(x => [`${x.code || ""}|${x.name || ""}`.toLowerCase(), x])).values()
        );

        missing.equipments = Array.from(
            new Map(missing.equipments.map(x => [`${x.name || ""}|${x.serial_number || ""}`.toLowerCase(), x])).values()
        );

        const hasAny =
            !!missing.customer ||
            missing.services.length ||
            missing.parts.length ||
            missing.equipments.length ||
            !!missing.tech_rate;

        return hasAny ? missing : null;
    }

    function renderCatalogChecklist(missing) {
        if (!catalogListEl) return;

        const blocks = [];

        const section = (title, itemsHtml) => `
    <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
      <div class="text-xs font-semibold text-slate-800 mb-2">${title}</div>
      <div class="space-y-2">${itemsHtml}</div>
    </div>
  `;

        // cliente
        if (missing.customer) {
            blocks.push(section("Cliente", `
      <label class="flex items-start gap-3">
        <input type="checkbox" class="mt-1 h-4 w-4" data-kind="customer" checked>
        <div class="text-sm">
          <div class="font-medium text-slate-900">${missing.customer.name}</div>
          <div class="text-xs text-slate-600">
            ${missing.customer.cpfCnpj ? `Doc: ${missing.customer.cpfCnpj}` : "Sem documento"}
            ${missing.customer.email ? ` • ${missing.customer.email}` : ""}
          </div>
        </div>
      </label>
    `));
        }

        // equipamentos
        if (missing.equipments.length) {
            blocks.push(section("Equipamentos", missing.equipments.map((e, i) => `
      <label class="flex items-start gap-3">
        <input type="checkbox" class="mt-1 h-4 w-4" data-kind="equipment" data-index="${i}" checked>
        <div class="text-sm">
          <div class="font-medium text-slate-900">${e.name}</div>
          <div class="text-xs text-slate-600">${e.serial_number ? `Série: ${e.serial_number}` : "Sem série"}</div>
        </div>
      </label>
    `).join("")));
        }

        // serviços
        if (missing.services.length) {
            blocks.push(section("Serviços", missing.services.map((s, i) => `
      <label class="flex items-start gap-3">
        <input type="checkbox" class="mt-1 h-4 w-4" data-kind="service" data-index="${i}" checked>
        <div class="text-sm">
          <div class="font-medium text-slate-900">${s.label}</div>
          <div class="text-xs text-slate-600">R$ ${formatCurrency(toNumber(s.unit_price))}</div>
        </div>
      </label>
    `).join("")));
        }

        // peças
        if (missing.parts.length) {
            blocks.push(section("Peças", missing.parts.map((p, i) => `
      <label class="flex items-start gap-3">
        <input type="checkbox" class="mt-1 h-4 w-4" data-kind="part" data-index="${i}" checked>
        <div class="text-sm">
          <div class="font-medium text-slate-900">${p.name}</div>
          <div class="text-xs text-slate-600">
            ${p.code ? `Código: ${p.code}` : "Sem código"} • R$ ${formatCurrency(toNumber(p.unit_price))}
          </div>
        </div>
      </label>
    `).join("")));
        }

        if (missing.tech_rate) {
            blocks.push(section("Técnico (valor hora)", `
    <label class="flex items-start gap-3">
      <input type="checkbox" class="mt-1 h-4 w-4" data-kind="tech_rate" checked>
      <div class="text-sm">
        <div class="font-medium text-slate-900">Atualizar valor hora</div>
        <div class="text-xs text-slate-600">
          Cadastrado: R$ ${formatCurrency(missing.tech_rate.old_rate)} • Novo: R$ ${formatCurrency(missing.tech_rate.new_rate)}
        </div>
      </div>
    </label>
  `));
        }

        catalogListEl.innerHTML = blocks.join("");

        if (!blocks.length) {
            catalogListEl.innerHTML = `
      <div class="text-sm text-slate-700">Nenhum cadastro pendente.</div>
    `;
        }
    }

    async function createSelectedCatalogs(missing) {
        // lê checkboxes marcados
        const checked = Array.from(catalogListEl.querySelectorAll("input[type=checkbox]:checked"));

        const want = {
            customer: checked.some(x => x.dataset.kind === "customer"),
            equipments: checked.filter(x => x.dataset.kind === "equipment").map(x => Number(x.dataset.index)),
            services: checked.filter(x => x.dataset.kind === "service").map(x => Number(x.dataset.index)),
            parts: checked.filter(x => x.dataset.kind === "part").map(x => Number(x.dataset.index)),
            tech_rate: checked.some(x => x.dataset.kind === "tech_rate"),
        };

        // loading
        const original = catalogConfirmBtn.innerHTML;
        catalogConfirmBtn.disabled = true;
        catalogConfirmBtn.innerHTML = `
            <span class="inline-flex items-center gap-2">
              <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
              </svg>
              Salvando cadastros...
            </span>
          `;

        try {
            // 1) cliente
            if (want.customer && missing.customer && customerIdInput) {
                const created = await postJson(ROUTES.customer, missing.customer);
                const data = created.data || created;
                if (data?.id) {
                    customerIdInput.value = data.id;
                }
            }

            // 2) equipamentos do catálogo (cria e joga o ID em cada bloco da OS)
            if (want.equipments.length) {
                for (const idx of want.equipments) {
                    const e = missing.equipments[idx];
                    const created = await postJson(ROUTES.equipment, e);
                    const data = created.data || created;
                    if (data?.id) {
                        // seta no primeiro bloco da OS que bate (mesmo nome/serie) e ainda tá sem id
                        const rows = Array.from(document.querySelectorAll('[data-row="equipment"]'));
                        for (const row of rows) {
                            const desc = row.querySelector(".js-equipment-desc")?.value?.trim() || "";
                            const serial = row.querySelector(".js-equipment-serial")?.value?.trim() || "";
                            const hid = row.querySelector(".js-equipment-id");
                            if (hid && !hid.value && desc === e.name && (serial || "") === (e.serial_number || "")) {
                                hid.value = data.id;
                                break;
                            }
                        }
                    }
                }
            }

            const norm = (str) =>
                (str || "")
                    .toString()
                    .trim()
                    .toLowerCase()
                    .normalize("NFD")
                    .replace(/[\u0300-\u036f]/g, "");

            if (want.services.length) {
                for (const idx of want.services) {
                    const s = missing.services[idx];
                    const created = await postJson(ROUTES.serviceItem, {
                        name: s.label,
                        description: s.label,
                        unit_price: toNumber(s.unit_price || 0),
                        service_type_id: null,
                        is_active: true,
                    });
                    const data = created.data || created;
                    if (data?.id) {
                        const target = norm(s.label);

                        const rows = Array.from(document.querySelectorAll('[data-row="service"]'));
                        for (const row of rows) {
                            const desc = norm(row.querySelector(".js-service-desc")?.value);
                            const hid = row.querySelector(".js-service-id");

                            if (hid && !hid.value && desc === target) {
                                hid.value = data.id;
                                break;
                            }
                        }
                    }
                }
            }

            // 4) peças do catálogo (cria e seta part_id nos rows)
            if (want.parts.length) {
                for (const idx of want.parts) {
                    const p = missing.parts[idx];
                    const created = await postJson(ROUTES.part, p);
                    const data = created.data || created;
                    if (data?.id) {
                        const rows = Array.from(document.querySelectorAll('[data-row="part"]'));
                        for (const row of rows) {
                            const code = row.querySelector(".js-part-code")?.value?.trim() || "";
                            const desc = row.querySelector(".js-part-desc")?.value?.trim() || "";
                            const hid = row.querySelector(".js-part-id");
                            // bate por code OU descrição
                            if (hid && !hid.value && ((p.code && code === p.code) || (desc && desc === p.name))) {
                                hid.value = data.id;
                            }
                        }
                    }
                }
            }

            if (want.tech_rate && missing.tech_rate?.technician_id) {
                const id = missing.tech_rate.technician_id;
                await fetch(`${ROUTES.employee}/${id}`, {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrf,
                        Accept: "application/json",
                    },
                    body: JSON.stringify({
                        full_name: technicianNameInput?.value?.trim() || "", // obrigatório
                        hourly_rate: toNumber(laborHourValueInput?.value || 0),
                        is_technician: true,
                        is_active: true,
                    }),
                });
                // atualiza o dataset local também
                technicianIdInput.dataset.hourlyRate = String(missing.tech_rate.new_rate);
            }

        } finally {
            catalogConfirmBtn.disabled = false;
            catalogConfirmBtn.innerHTML = original;
        }
    }


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

        const catalogModal = q("#os-catalog-modal");
        const catalogListEl = q("#os-catalog-list");
        const catalogConfirmBtn = q("#os-catalog-confirm");
        const catalogCancelBtns = document.querySelectorAll("[data-os-catalog-cancel]");

        function openCatalogModal() {
            if (!catalogModal) return;
            catalogModal.classList.remove("hidden");
            catalogModal.classList.add("flex");
        }

        function closeCatalogModal() {
            if (!catalogModal) return;
            catalogModal.classList.add("hidden");
            catalogModal.classList.remove("flex");
        }

        catalogCancelBtns.forEach((b) => b.addEventListener("click", closeCatalogModal));

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
                                    minChars = 0,           // 👈 agora pode abrir sem digitar
                                    initialLimit = 5,       // 👈 mostra 5 no foco
                                }) {
            if (!input) return;

            const wrapper = wrapForDropdown(input);
            if (!wrapper) return;

            const dropdown = document.createElement("div");
            dropdown.className =
                "absolute z-30 mt-1 w-full rounded-xl border border-slate-200 bg-white shadow-lg max-h-60 overflow-auto hidden";
            wrapper.appendChild(dropdown);

            let abortController = null;

            const render = (items) => {
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
            };

            const fetchItems = async (term) => {
                if (abortController) abortController.abort();
                abortController = new AbortController();

                const qParam = term ?? "";
                const url = `${searchUrl}?q=${encodeURIComponent(qParam)}&typeahead=1&limit=${initialLimit}${extraQuery}`;

                const res = await fetch(url, {
                    signal: abortController.signal,
                    headers: { Accept: "application/json" },
                });

                if (!res.ok) return [];

                const json = await res.json();
                const data = Array.isArray(json) ? json : json.data || [];
                return data.map(mapItem).filter(Boolean);
            };

            const openList = async () => {
                const term = input.value.trim();

                if (hiddenIdInput) hiddenIdInput.value = ""; // digitou/abriu, invalida seleção anterior

                if (term.length < minChars) {
                    const items = await fetchItems(""); // 👈 lista “top 5”
                    render(items);
                    return;
                }

                const items = await fetchItems(term);
                render(items);
            };

            // abre no foco e no click
            input.addEventListener("focus", openList);
            input.addEventListener("click", openList);

            // filtra ao digitar
            input.addEventListener(
                "input",
                debounce(async () => {
                    await openList();
                }, 200)
            );

            document.addEventListener("click", (ev) => {
                if (!wrapper.contains(ev.target)) dropdown.classList.add("hidden");
            });
        }

        // ========== CLIENTE (search + preencher) ==========
        // ========== CLIENTE (search + preencher + auto-create) ==========
        async function searchCustomers(term) {
            const url = new URL(ROUTES.customerSearch, window.location.origin);
            url.searchParams.set("q", term);
            url.searchParams.set("typeahead", "1"); // opcional

            const resp = await fetch(url.toString(), {
                headers: {Accept: "application/json"},
            });

            if (!resp.ok) {
                console.error("Erro ao buscar clientes:", await resp.text());
                return [];
            }

            const json = await resp.json();
            const data = Array.isArray(json) ? json : (json.data || []);
            return data;
        }

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

            // >>> AQUI: seta o id do cliente selecionado
            if (customerIdInput) customerIdInput.value = c.id || "";
        }

        if (clientNameInput) {
            setupTypeahead({
                input: clientNameInput,
                hiddenIdInput: customerIdInput,
                searchUrl: ROUTES.customerSearch,
                extraQuery: "&typeahead=1",
                mapItem: (c) => ({
                    id: c.id,
                    label: c.name,
                    sublabel: `${(c.cpfCnpj || "")} ${(c.cityName || "")}${c.state ? "/" + c.state : ""}`.trim(),
                    raw: c,
                }),
                onSelect: (item) => {
                    applyCustomerToForm(item.raw);
                },
            });

            // se a pessoa digitar algo e trocar o texto, já invalida o id
            clientNameInput.addEventListener("input", () => {
                if (customerIdInput) customerIdInput.value = "";
            });
        }

        if (clientDocInput) {
            setupTypeahead({
                input: clientDocInput,
                hiddenIdInput: customerIdInput,
                searchUrl: ROUTES.customerSearch,
                extraQuery: "&typeahead=1",
                minChars: 3,
                mapItem: (c) => ({
                    id: c.id,
                    label: c.cpfCnpj || c.document_number || "-",
                    sublabel: c.name ? `${c.name}${c.cityName ? ` • ${c.cityName}/${c.state || ""}` : ""}` : "",
                    raw: c,
                }),
                onSelect: (item) => {
                    // item.raw tem o cliente completo
                    applyCustomerToForm(item.raw);
                },
            });

            // se mexer no doc manualmente, invalida o id (igual nome)
            clientDocInput.addEventListener("input", () => {
                if (customerIdInput) customerIdInput.value = "";
            });
        }

        async function ensureCustomerIdOrCreate({allowCreate = false} = {}) {
            if (!clientNameInput) return null;

            const name = clientNameInput.value.trim();
            if (!name) return null;

            // se já selecionou um cliente existente
            if (customerIdInput?.value) return customerIdInput.value;

            const doc = (clientDocInput?.value || "").trim();
            const email = (clientEmailInput?.value || "").trim().toLowerCase();

            // 1) match forte por documento/email (anti-duplicado)
            try {
                const items = await searchCustomers(name);

                const byDoc = doc
                    ? items.find(c => (c.cpfCnpj || "").replace(/\D/g, "") === doc.replace(/\D/g, ""))
                    : null;

                const byEmail = email
                    ? items.find(c => (c.email || "").trim().toLowerCase() === email)
                    : null;

                // 2) fallback: match exato por nome (fraco, mas evita duplicar quando só “arruma” o texto)
                const normalized = name.toLowerCase();
                const byExactName = items.find(c => (c.name || "").trim().toLowerCase() === normalized);

                const found = byDoc || byEmail || byExactName;

                if (found?.id) {
                    applyCustomerToForm(found);
                    return found.id;
                }
            } catch (e) {
            }

            // se não achou e não pode criar, para aqui
            if (!allowCreate) return null;

            // pedir confirmação simples antes de criar
            const ok = confirm(`Cliente "${name}" não encontrado. Criar novo cadastro?`);
            if (!ok) return null;

            // 3) cria
            const created = await postJson(ROUTES.customer, {
                name,
                cpfCnpj: doc || null,
                email: email || null,
                mobilePhone: clientPhoneInput?.value || null,
                address: clientAddressInput?.value || null,
                addressNumber: clientAddressNumberInput?.value || null,
                postalCode: clientZipInput?.value || null,
                cityName: clientCityInput?.value || null,
                state: clientStateInput?.value || null,
                province: clientProvinceInput?.value || null,
                complement: clientComplementInput?.value || null,
            });

            const data = created.data || created;
            if (data?.id) {
                if (customerIdInput) customerIdInput.value = data.id;
                return data.id;
            }

            return null;
        }

        // ========== TÉCNICO (search + valor hora) ==========
        function setupTechnicianLookup() {
            if (!technicianNameInput) return;

            setupTypeahead({
                input: technicianNameInput,
                hiddenIdInput: technicianIdInput,
                searchUrl: ROUTES.employee,
                extraQuery: "&is_technician=1&only_active=1&typeahead=1",
                mapItem: (e) => ({
                    id: e.id,
                    label: e.full_name,
                    sublabel: e.hourly_rate ? `R$ ${formatCurrency(toNumber(e.hourly_rate))}/h` : "",
                    hourly_rate: e.hourly_rate,
                }),
                onSelect: (item) => {
                    if (laborHourValueInput && item.hourly_rate != null) {
                        laborHourValueInput.value = item.hourly_rate;
                        recalcTotals();
                    }
                    if (technicianIdInput) technicianIdInput.dataset.hourlyRate = String(item.hourly_rate ?? 0);
                },
            });

            technicianNameInput.addEventListener("blur", debounce(async () => {
                // se já escolheu da lista, não faz nada
                if (technicianIdInput?.value) return;

                const id = await ensureEntityId({
                    inputEl: technicianNameInput,
                    hiddenIdEl: technicianIdInput,
                    searchUrl: ROUTES.employee,
                    createUrl: ROUTES.employee,
                    buildCreateBody: (label) => ({
                        full_name: label,
                        email: null,
                        phone: null,
                        document_number: null,
                        position: "Técnico",
                        hourly_rate: toNumber(laborHourValueInput?.value || 0),
                        is_technician: true,
                        is_active: true,
                    }),
                    pickFirstMatch: (json) => {
                        const data = Array.isArray(json) ? json : (json.data || []);
                        const label = technicianNameInput.value.trim().toLowerCase();
                        return data.find(e => (e.full_name || "").trim().toLowerCase() === label) || null;
                    }
                });

                if (technicianIdInput) technicianIdInput.dataset.hourlyRate = String(toNumber(laborHourValueInput?.value || 0));

                if (id) recalcTotals();
            }, 350));
        }

        // ========== BLOCS DINÂMICOS ==========
        let equipmentCounter = 0;
        let serviceCounter = 0;
        let partCounter = 0;
        let laborCounter = 0;

        async function fetchFirstItem(url) {
            try {
                const res = await fetch(url, {headers: {Accept: "application/json"}});
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

            setupTypeahead({
                input: nameInput,
                hiddenIdInput: idInput,
                searchUrl: ROUTES.equipment,
                minChars: 2,
                mapItem: (e) => ({
                    id: e.id,
                    label: e.name,
                    sublabel: e.serial_number ? `Série: ${e.serial_number}` : (e.code || ""),
                    serial_number: e.serial_number,
                    description: e.description,
                    notes: e.notes,
                }),
                onSelect: (e) => {
                    if (!serialInput.value && e.serial_number) serialInput.value = e.serial_number;
                    if (!notesInput.value && e.description) notesInput.value = e.description;
                },
            });

            setupTypeahead({
                input: serialInput,
                hiddenIdInput: idInput,
                searchUrl: ROUTES.equipment,
                minChars: 2,
                mapItem: (e) => ({
                    id: e.id,
                    label: e.serial_number || e.code || "-",
                    sublabel: e.name || "",
                    name: e.name,
                    serial_number: e.serial_number,
                    description: e.description,
                    notes: e.notes,
                }),
                onSelect: (e) => {
                    // ao selecionar pela série, preenche o nome também
                    if (!nameInput.value && e.name) nameInput.value = e.name;
                    if (e.serial_number) serialInput.value = e.serial_number;
                    if (!notesInput.value && e.description) notesInput.value = e.description;
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

            nameInput.addEventListener("blur", debounce(async () => {
                if (idInput.value) return;
                const label = nameInput.value.trim();
                if (!label) return;

                await ensureEntityId({
                    inputEl: nameInput,
                    hiddenIdEl: idInput,
                    searchUrl: ROUTES.equipment,
                    createUrl: ROUTES.equipment,
                    buildCreateBody: (name) => ({
                        code: serialInput.value.trim() || null,
                        name,
                        description: notesInput.value.trim() || null,
                        serial_number: serialInput.value.trim() || null,
                        notes: locInput.value.trim() || null,
                    }),
                    pickFirstMatch: (json) => {
                        const data = Array.isArray(json) ? json : (json.data || []);
                        const v = label.toLowerCase();
                        return data.find(e => (e.name || "").trim().toLowerCase() === v) || null;
                    }
                });
            }, 350));

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

            setupTypeahead({
                input: descInput,
                hiddenIdInput: idInput,
                searchUrl: ROUTES.serviceItem,
                mapItem: (s) => ({
                    id: s.id,
                    label: s.name,
                    sublabel: s.unit_price ? `R$ ${formatCurrency(toNumber(s.unit_price))}` : "",
                    unit_price: s.unit_price,
                }),
                onSelect: (s) => {
                    if (!unitInput.value && s.unit_price != null) unitInput.value = s.unit_price;
                    recalcRow();
                },
            });

            // auto preencher se achar pelo texto
            const autoFillService = async (term) => {
                term = (term || "").trim();
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

            descInput.addEventListener(
                "blur",
                debounce(async () => {
                    if (idInput.value) return;

                    const term = descInput.value.trim();
                    if (!term) return;

                    // tenta achar, mas NÃO cria
                    const svc = await fetchFirstItem(`${ROUTES.serviceItem}?q=${encodeURIComponent(term)}&typeahead=1`);
                    if (svc?.id) {
                        idInput.value = svc.id;
                        descInput.value = svc.name || term;
                        if (!unitInput.value && svc.unit_price != null) unitInput.value = svc.unit_price;
                        recalcRow();
                    }
                }, 250)
            );

            descInput.addEventListener(
                "change",
                debounce(() => {
                    if (!idInput.value) autoFillService(descInput.value);
                }, 250)
            );

            // adiciona na lista e calcula
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

            // TYPEAHEAD por NOME
            setupTypeahead({
                input: descInput,
                hiddenIdInput: idInput,
                searchUrl: ROUTES.part,
                mapItem: (p) => ({
                    id: p.id,
                    label: p.name,
                    sublabel: p.code || "",
                    code: p.code,
                    unit_price: p.unit_price,
                }),
                onSelect: (p) => {
                    if (!codeInput.value && p.code) codeInput.value = p.code;
                    if (!unitInput.value && p.unit_price != null) unitInput.value = p.unit_price;
                    recalcRow();
                },
            });

// TYPEAHEAD por CÓDIGO
            setupTypeahead({
                input: codeInput,
                hiddenIdInput: idInput,
                searchUrl: ROUTES.part,
                mapItem: (p) => ({
                    id: p.id,
                    label: p.code || p.name,      // 👈 mostra código como principal
                    sublabel: p.name || "",
                    name: p.name,
                    code: p.code,
                    unit_price: p.unit_price,
                }),
                onSelect: (p) => {
                    if (p.code) codeInput.value = p.code;
                    if (!descInput.value && p.name) descInput.value = p.name;
                    if (!unitInput.value && p.unit_price != null) unitInput.value = p.unit_price;
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
                        code: row.querySelector(".js-part-code")?.value?.trim() || "",
                        description: row.querySelector(".js-part-desc")?.value?.trim() || "",
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
                    const rate = toNumber(laborHourValueInput?.value || 0);

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
                        started_at: start ? `${orderDateInput?.value || ""} ${start}:00` : null,
                        ended_at: end ? `${orderDateInput?.value || ""} ${end}:00` : null,
                        hours,
                        rate,
                        total: hours * rate,
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

                address_line1: clientAddressInput?.value || null,
                address_line2: [
                    (clientAddressNumberInput?.value || "").trim(),
                    (clientComplementInput?.value || "").trim(),
                    (clientProvinceInput?.value || "").trim(),
                ].filter(Boolean).join(" - ") || null,

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

                // ✅ AQUI O FIX:
                equipments: equipments,
                services: serviceItems,
                parts: partItems,
                labor_entries: laborEntries,
            };
        }

        function ensureTechnicianSelected() {
            const name = technicianNameInput?.value?.trim() || "";
            const id = technicianIdInput?.value || "";

            if (!name) {
                alert("Selecione um técnico.");
                technicianNameInput?.focus();
                return false;
            }

            // se digitou algo e não selecionou da lista
            if (!id) {
                alert("Selecione um técnico na lista (não só digite).");
                technicianNameInput?.focus();
                return false;
            }

            return true;
        }

        async function ensureTechnicianIdOrCreate() {
            const name = technicianNameInput?.value?.trim() || "";
            if (!name) {
                alert("Informe o técnico.");
                technicianNameInput?.focus();
                return null;
            }

            if (technicianIdInput?.value) return technicianIdInput.value;

            // cria se não existe
            const id = await ensureEntityId({
                inputEl: technicianNameInput,
                hiddenIdEl: technicianIdInput,
                searchUrl: ROUTES.employee,
                createUrl: ROUTES.employee,
                buildCreateBody: (label) => ({
                    full_name: label,
                    email: null,
                    phone: null,
                    document_number: null,
                    position: "Técnico",
                    hourly_rate: toNumber(laborHourValueInput?.value || 0),
                    is_technician: true,
                    is_active: true,
                }),
                pickFirstMatch: (json) => {
                    const data = Array.isArray(json) ? json : (json.data || []);
                    const label = technicianNameInput.value.trim().toLowerCase();
                    return data.find(e => (e.full_name || "").trim().toLowerCase() === label) || null;
                }
            });

            if (!id) {
                alert("Não foi possível criar/selecionar o técnico.");
                return null;
            }

            return id;
        }

        async function submitServiceOrder(status) {
            if (state.saving) return null;
            state.saving = true;

            try {
                const techId = await ensureTechnicianIdOrCreate();
                if (!techId) return null;

                const custId = await ensureCustomerIdOrCreate({allowCreate: true});
                if (custId && customerIdInput) customerIdInput.value = custId;

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
                return {payload, data};
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
        let pendingAfterCatalog = null;
// { status: "draft"|"pending", opts: {...}, redirectTo: string|null, openSignature: bool }

        async function runCatalogPipelineAndContinue(status, opts, after) {
            // after: { redirectTo?: string, openSignature?: boolean }

            const payloadPreview = await buildPayload(status);
            const missing = await detectMissingCatalogs(payloadPreview);

            if (!missing) {
                // segue normal
                const result = await submitServiceOrder(status);
                if (!result) return;

                // (depois a gente remove o saveCatalogsFromOs, pq agora é tudo aqui)
                if (after?.openSignature) openSignatureModal();
                if (after?.redirectTo) window.location.href = after.redirectTo;
                return;
            }

            // tem pendências → abre modal e espera confirmar
            pendingAfterCatalog = { status, opts, after };
            renderCatalogChecklist(missing);
            openCatalogModal();

            // guarda missing no modal
            catalogModal._missing = missing;
        }

        // ========== MODAIS: SALVAR / FINALIZAR ==========
        if (btnSave && saveModal) {
            btnSave.addEventListener("click", (e) => {
                e.preventDefault();
                if (!ensureTechnicianSelected()) return;
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

                    saveModal.classList.add("hidden");
                    saveModal.classList.remove("flex");

                    await runCatalogPipelineAndContinue("draft", opts, {
                        redirectTo: "/service-orders/service-order",
                    });
                });
            }
        }

        if (btnFinish && finalizeModal) {
            btnFinish.addEventListener("click", (e) => {
                e.preventDefault();
                if (!ensureTechnicianSelected()) return;

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

                finalizeModal.classList.add("hidden");
                finalizeModal.classList.remove("flex");

                await runCatalogPipelineAndContinue("pending", opts, {
                    openSignature: action === "tablet",
                    redirectTo: action === "new" ? "/service-orders/service-order/create" : null,
                });
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
        const signatureModal = q('#os-signature-modal');
        const signatureCanvas = q('#signature-pad');
        const signatureClear = q('#signature-clear');
        const signatureClose = q('#signature-close');
        const signatureSave = q('#signature-save');

        let signatureCtx;
        let isDrawing = false;
        let lastX = 0;
        let lastY = 0;
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

                // loading no botão
                const originalHtml = signatureSave.innerHTML;
                signatureSave.disabled = true;
                signatureSave.innerHTML = `
      <span class="inline-flex items-center gap-2">
        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
        </svg>
        Salvando...
      </span>
    `;

                await new Promise(requestAnimationFrame);
                await new Promise((r) => setTimeout(r, 50));

                const restoreBtn = () => {
                    signatureSave.disabled = false;
                    signatureSave.innerHTML = originalHtml;
                };

                try {
                    const dataUrl = signatureCanvas.toDataURL('image/png');

                    const body = {
                        image_base64: dataUrl,
                        client_name: clientNameInput?.value || null,
                        client_email: clientEmailInput?.value || null,
                        technician_id: technicianIdInput?.value || null,
                    };

                    // 1) salva assinatura
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
                        restoreBtn();
                        alert('Erro ao salvar assinatura do cliente.');
                        return;
                    }

                    // 2) aprova OS
                    const approveResult = await submitServiceOrder("approved");
                    if (!approveResult) {
                        restoreBtn();
                        alert('Assinatura salva, mas houve erro ao aprovar a OS.');
                        return;
                    }

                    await new Promise((r) => setTimeout(r, 3000));

                    closeSignatureModal();
                    window.location.href = "/service-orders/service-order";

                } catch (e) {
                    console.error(e);
                    restoreBtn();
                    alert('Erro inesperado ao salvar assinatura.');
                }
            });
        }

    if (catalogConfirmBtn) {
        catalogConfirmBtn.addEventListener("click", async () => {
            const missing = catalogModal?._missing;
            if (!missing || !pendingAfterCatalog) {
                closeCatalogModal();
                return;
            }

            // cria antes
            await createSelectedCatalogs(missing);

            closeCatalogModal();

            // agora salva OS (com IDs preenchidos)
            const { status, after } = pendingAfterCatalog;
            pendingAfterCatalog = null;

            const result = await submitServiceOrder(status);
            if (!result) return;

            if (after?.openSignature) openSignatureModal();
            if (after?.redirectTo) window.location.href = after.redirectTo;
        });
    }

    function initSignaturePad() {
            if (!signatureCanvas || signatureInitDone) return;

            signatureCtx = signatureCanvas.getContext('2d');
            signatureInitDone = true;

            const resizeCanvas = () => {
                const rect = signatureCanvas.getBoundingClientRect();
                if (rect.width === 0 || rect.height === 0) return;

                signatureCanvas.width = rect.width;
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
                return {x, y};
            };

            const startDraw = (evt) => {
                evt.preventDefault?.();
                isDrawing = true;
                const {x, y} = getPos(evt);
                lastX = x;
                lastY = y;
            };

            const draw = (evt) => {
                if (!isDrawing) return;
                evt.preventDefault?.();
                const {x, y} = getPos(evt);

                signatureCtx.lineWidth = 2;
                signatureCtx.lineCap = 'round';
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
            signatureCanvas.addEventListener('touchstart', startDraw, {passive: false});
            signatureCanvas.addEventListener('touchmove', draw, {passive: false});
            signatureCanvas.addEventListener('touchend', stopDraw, {passive: false});
            signatureCanvas.addEventListener('touchcancel', stopDraw, {passive: false});

            window.addEventListener('resize', () => {
                if (signatureCanvas._resizeCanvas) signatureCanvas._resizeCanvas();
            });
        }

        async function ensureEntityId({
                                          inputEl,
                                          hiddenIdEl,
                                          searchUrl,
                                          createUrl,
                                          buildCreateBody,
                                          pickFirstMatch, // (json) => item|null
                                          minChars = 2,
                                      }) {
            if (!inputEl) return null;

            const label = inputEl.value.trim();
            const currentId = hiddenIdEl?.value || "";

            if (currentId) return currentId;          // já selecionado
            if (!label || label.length < minChars) return null;

            // 1) tenta achar "igual" via search (pra evitar duplicar)
            try {
                const res = await fetch(`${searchUrl}?q=${encodeURIComponent(label)}&typeahead=1`, {
                    headers: {Accept: "application/json"},
                });
                if (res.ok) {
                    const json = await res.json();
                    const found = pickFirstMatch ? pickFirstMatch(json) : null;
                    if (found?.id) {
                        if (hiddenIdEl) hiddenIdEl.value = found.id;
                        // garante nome bonitinho
                        if (found.full_name) inputEl.value = found.full_name;
                        if (found.name) inputEl.value = found.name;
                        return found.id;
                    }
                }
            } catch (e) {
                // ignora e segue pra criar
            }

            // 2) cria
            const body = buildCreateBody(label);
            const created = await postJson(createUrl, body);
            const createdData = created.data || created;

            if (createdData?.id) {
                if (hiddenIdEl) hiddenIdEl.value = createdData.id;
                // normaliza label
                if (createdData.full_name) inputEl.value = createdData.full_name;
                if (createdData.name) inputEl.value = createdData.name;
                return createdData.id;
            }

            return null;
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
    }
)
;
