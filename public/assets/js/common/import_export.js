export function initImportExportModal() {
    const modal = document.querySelector("#io-modal");
    if (!modal) return;

    const closeBtn = document.querySelector("#io-close");
    const cancelBtn = document.querySelector("#io-cancel");

    const tabExport = document.querySelector("#io-tab-export");
    const tabImport = document.querySelector("#io-tab-import");
    const paneExport = document.querySelector("#io-pane-export");
    const paneImport = document.querySelector("#io-pane-import");

    const title = document.querySelector("#io-title");

    const exportHint = document.querySelector("#io-export-hint");
    const exportColumns = document.querySelector("#io-export-columns");

    const importHint = document.querySelector("#io-import-hint");
    const importReq = document.querySelector("#io-import-required");
    const importOpt = document.querySelector("#io-import-optional");
    const importTemplate = document.querySelector("#io-import-template");

    const importFormats = document.querySelector("#io-import-formats");
    const importMaxRows = document.querySelector("#io-import-max-rows");
    const importMaxMb = document.querySelector("#io-import-max-mb");

    const btnDownload = document.querySelector("#io-export-download");
    const createdFrom = document.querySelector("#io-created-from");
    const createdTo   = document.querySelector("#io-created-to");
    const statusSel   = document.querySelector("#io-status");
    const supplierSel = document.querySelector("#io-supplier");
    const codePrefix  = document.querySelector("#io-code-prefix");

    const btnImport = document.querySelector("#io-import-submit");
    const importFile = document.querySelector("#io-import-file");
    const importMode = document.querySelector("#io-import-mode");
    const importDelimiter = document.querySelector("#io-import-delimiter");
    const btnTpl = document.querySelector("#io-import-template-download");

    const resultBox = document.querySelector("#io-import-result");
    const rCreated = document.querySelector("#io-import-created");
    const rUpdated = document.querySelector("#io-import-updated");
    const rSkipped = document.querySelector("#io-import-skipped");
    const errWrap = document.querySelector("#io-import-errors-wrap");
    const errList = document.querySelector("#io-import-errors");

    let currentOptions = null;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    // Accordion filtros
    const ft = document.querySelector("#io-filters-toggle");
    const fb = document.querySelector("#io-filters-body");
    const fi = document.querySelector("#io-filters-icon");

    ft?.addEventListener("click", () => {
        const isHidden = fb.classList.toggle("hidden");
        fi.textContent = isHidden ? "▾" : "▴";
    });

// Botão baixar no footer só no Export
    function syncFooterButtons() {
        const isExport = !paneExport.classList.contains("hidden");
        btnDownload?.classList.toggle("hidden", !isExport);
        btnImport?.classList.toggle("hidden", isExport);
    }

    syncFooterButtons();

    function show() {
        modal.classList.remove("hidden");
        modal.classList.add("flex");
    }
    function hide() {
        modal.classList.add("hidden");
        modal.classList.remove("flex");
    }

    function setTab(which) {
        const isExport = which === "export";
        paneExport.classList.toggle("hidden", !isExport);
        paneImport.classList.toggle("hidden", isExport);

        tabExport.classList.toggle("bg-blue-700", isExport);
        tabExport.classList.toggle("text-white", isExport);
        tabExport.classList.toggle("border-blue-700", isExport);

        tabImport.classList.toggle("bg-blue-700", !isExport);
        tabImport.classList.toggle("text-white", !isExport);
        tabImport.classList.toggle("border-blue-700", !isExport);

        syncFooterButtons();
    }

    function chip(text) {
        const el = document.createElement("span");
        el.className = "inline-flex items-center rounded-full bg-white border border-slate-200 px-3 py-1 text-xs text-slate-700";
        el.textContent = text;
        return el;
    }

    async function openByCurrentPath(defaultTab = "export") {
        setTab(defaultTab);

        exportColumns.innerHTML = "";
        importReq.innerHTML = "";
        importOpt.innerHTML = "";
        importTemplate.innerHTML = "";

        title.textContent = "Carregando…";
        exportHint.textContent = "";
        importHint.textContent = "";

        const path = window.location.pathname;

        const url = new URL("/io/options", window.location.origin);
        url.searchParams.set("path", path);

        const res = await fetch(url, {
            headers: {
                "Accept": "application/json",
                ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
            },
        });

        if (!res.ok) {
            title.textContent = "Importar / Exportar";
            exportHint.textContent = "Não foi possível carregar as opções deste módulo.";
            return show();
        }

        const data = await res.json();
        currentOptions = data;

        // ✅ hide/show filtros conforme config
        const filtersCfg = data.export?.filters || {};

        // created range
        createdFrom?.closest("[data-filter='created_at_range']")?.classList.toggle("hidden", !filtersCfg.created_at_range);

        // status
        statusSel?.closest("[data-filter='status']")?.classList.toggle("hidden", !(filtersCfg.status?.enabled));

        // supplier
        await hydrateSupplierFilter(filtersCfg); // já esconde se não enabled

        // code_prefix
        codePrefix?.closest("[data-filter='code_prefix']")?.classList.toggle("hidden", !(filtersCfg.code_prefix?.enabled));

        title.textContent = data.label ? `${data.label}` : "Importar / Exportar";

        // Export
        exportHint.textContent = data.export?.hint || "";
        (data.export?.columns || []).forEach((c) => {
            const label = document.createElement("label");
            label.className = "flex items-center gap-2 rounded-xl bg-white border border-slate-200 px-3 py-2 text-sm text-slate-700";
            label.innerHTML = `
      <input type="checkbox" checked class="rounded border-slate-300" data-key="${c.key}">
      <span>${c.label}</span>
    `;
            exportColumns.appendChild(label);
        });

        // reset resultado import
        if (resultBox) {
            resultBox.classList.add("hidden");
            errWrap?.classList.add("hidden");
            if (errList) errList.innerHTML = "";
        }

        // Import
        importHint.textContent = data.import?.hint || "";

        (data.import?.required || []).forEach((f) => {
            const li = document.createElement("li");
            li.textContent = `${f.label} (${f.key})`;
            importReq.appendChild(li);
        });

        (data.import?.optional || []).forEach((f) => {
            const li = document.createElement("li");
            li.textContent = `${f.label} (${f.key})`;
            importOpt.appendChild(li);
        });

        (data.import?.template_columns || []).forEach((k) => {
            importTemplate.appendChild(chip(k));
        });

        importFormats.textContent = (data.import?.formats || []).join(", ").toUpperCase();
        importMaxRows.textContent = data.import?.limits?.max_rows ? `${data.import.limits.max_rows}` : "—";
        importMaxMb.textContent = data.import?.limits?.max_file_mb ? `${data.import.limits.max_file_mb} MB` : "—";

        show();
    }

    btnTpl?.addEventListener("click", () => {
        if (!currentOptions?.import?.template_columns?.length) {
            alert("Template não disponível.");
            return;
        }

        const cols = currentOptions.import.template_columns;
        const csv = "\uFEFF" + cols.join(";") + "\n"; // BOM + header

        const blob = new Blob([csv], { type: "text/csv;charset=utf-8" });
        const url = URL.createObjectURL(blob);

        const a = document.createElement("a");
        a.href = url;
        a.download = `template_${(currentOptions.resource || "import")}.csv`;
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    });

    btnImport?.addEventListener("click", async () => {
        if (!importFile?.files?.length) {
            alert("Selecione um arquivo CSV.");
            return;
        }

        const file = importFile.files[0];

        // limite básico no front (back valida também)
        const maxMb = currentOptions?.import?.limits?.max_file_mb;
        if (maxMb && file.size > (maxMb * 1024 * 1024)) {
            alert(`Arquivo maior que ${maxMb}MB.`);
            return;
        }

        const fd = new FormData();
        fd.append("path", window.location.pathname);
        fd.append("file", file);
        fd.append("mode", importMode?.value || "create_only");
        fd.append("delimiter", importDelimiter?.value || ";");

        btnImport.disabled = true;
        btnImport.textContent = "Importando...";

        try {
            const res = await fetch("/io/import", {
                method: "POST",
                headers: {
                    ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
                    "Accept": "application/json",
                },
                body: fd,
            });

            const json = await res.json().catch(() => null);

            if (!res.ok) {
                console.error(json);
                alert(json?.message || "Erro ao importar.");
                return;
            }

            const s = json.summary || {};
            if (resultBox) resultBox.classList.remove("hidden");
            if (rCreated) rCreated.textContent = String(s.created ?? 0);
            if (rUpdated) rUpdated.textContent = String(s.updated ?? 0);
            if (rSkipped) rSkipped.textContent = String(s.skipped ?? 0);

            const errs = Array.isArray(s.errors) ? s.errors : [];
            if (errs.length) {
                errWrap?.classList.remove("hidden");
                if (errList) {
                    errList.innerHTML = "";
                    errs.slice(0, 200).forEach(e => {
                        const li = document.createElement("li");
                        li.textContent = `Linha ${e.line ?? "?"}: ${e.message ?? "erro"}`;
                        errList.appendChild(li);
                    });
                }
            } else {
                errWrap?.classList.add("hidden");
                if (errList) errList.innerHTML = "";
            }

        } catch (e) {
            console.error(e);
            alert("Erro ao importar.");
        } finally {
            btnImport.disabled = false;
            btnImport.textContent = "Importar";
        }
    });


    async function downloadCsv() {
        // colunas marcadas
        const checks = [...document.querySelectorAll("#io-export-columns input[type='checkbox']")];
        const labels = [...document.querySelectorAll("#io-export-columns label")];

        // pega key pelo texto do label? melhor: setar data-key no options (ajuste pequeno)
        // então: vamos setar data-key quando montar as colunas (abaixo).
    }

    btnDownload?.addEventListener("click", async () => {
        const path = window.location.pathname;

        // pega colunas via data-key
        const selected = [...document.querySelectorAll("#io-export-columns input[type='checkbox']:checked")]
            .map(i => i.dataset.key)
            .filter(Boolean);

        if (!selected.length) {
            alert("Selecione ao menos uma coluna.");
            return;
        }

        const filtersCfg = currentOptions?.export?.filters || {};
        const filters = {};

// created range
        if (filtersCfg.created_at_range) {
            if (createdFrom?.value) filters.created_from = createdFrom.value;
            if (createdTo?.value) filters.created_to = createdTo.value;
        }

// status
        if (filtersCfg.status?.enabled) {
            filters[filtersCfg.status.key || "status"] = statusSel?.value || "all";
        }

// supplier select (dinâmico)
        if (filtersCfg.supplier?.enabled) {
            const k = filtersCfg.supplier.key || "supplier_id";
            if (supplierSel?.value) filters[k] = supplierSel.value;
        }

// code prefix
        if (filtersCfg.code_prefix?.enabled) {
            const k = filtersCfg.code_prefix.key || "code_prefix";
            const v = codePrefix?.value?.trim();
            if (v) filters[k] = v;
        }

        const payload = { path, columns: selected, filters };

        const res = await fetch("/io/export", {
            method: "POST",
            headers: {
                "Accept": "text/csv",
                "Content-Type": "application/json",
                ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
            },
            body: JSON.stringify(payload),
        });

        if (!res.ok) {
            const err = await res.text();
            console.error(err);
            alert("Erro ao exportar.");
            return;
        }

        const blob = await res.blob();
        const url = window.URL.createObjectURL(blob);

        const a = document.createElement("a");
        a.href = url;
        a.download = "export.csv";
        document.body.appendChild(a);
        a.click();
        a.remove();

        window.URL.revokeObjectURL(url);
    });

    async function hydrateSupplierFilter(filtersCfg) {
        if (!supplierSel) return;

        const cfg = filtersCfg?.supplier;
        const enabled = !!cfg?.enabled;

        supplierSel.closest("[data-filter='supplier']")?.classList.toggle("hidden", !enabled);

        if (!enabled) return;

        supplierSel.innerHTML = `<option value="">Todos</option>`;

        if (!cfg.endpoint) return;

        try {
            const r = await fetch(cfg.endpoint, { headers: { "Accept": "application/json" }});
            const j = await r.json();
            const list = j.data || j || [];

            const valueField = cfg.value_field || "id";
            const labelField = cfg.label_field || "name";

            supplierSel.innerHTML += list.map(s => {
                const val = s[valueField];
                const label = s[labelField] || "(sem nome)";
                return `<option value="${val}">${label}</option>`;
            }).join("");
        } catch(e) {}
    }

    // Eventos
    closeBtn?.addEventListener("click", hide);
    cancelBtn?.addEventListener("click", hide);
    modal.addEventListener("click", (e) => {
        if (e.target === modal) hide();
    });

    tabExport?.addEventListener("click", () => setTab("export"));
    tabImport?.addEventListener("click", () => setTab("import"));

    // API pública: você chama isso nos botões
    window.IO_MODAL = {
        openExport: () => openByCurrentPath("export"),
        openImport: () => openByCurrentPath("import"),
        close: hide,
    };
}
