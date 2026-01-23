export class ModelCrud {
    constructor(config) {
        this.config = config;
        this.data = [];
        this.tbody = document.querySelector(config.tbody);
        this.modal = document.querySelector(config.modal);
        this.form = document.querySelector(config.form);
        this.btnAdd = document.querySelector(config.btnAdd);
        this.search = document.querySelector(config.search);
        this.title = document.querySelector(config.modalTitle);
        this.btnClose = document.querySelector(config.btnClose);
        this.btnCancel = document.querySelector(config.btnCancel);
        this.btnDelete = document.querySelector(config.btnDelete);

        this.btnSubmit = document.querySelector(
            config.btnSubmit || "#m-submit"
        );
        this.errorBox = document.querySelector("#modal-errors");

        // modal de confirmação
        this.confirmDlg = document.getElementById("confirm-delete");
        this.confirmYes = document.getElementById("confirm-delete-yes");
        this.confirmNo = document.getElementById("confirm-delete-no");
        this._pendingDeleteId = null;

        // toast
        this.toast = document.getElementById("toast");
        this.toastTitle = document.getElementById("toast-title");
        this.toastSub = document.getElementById("toast-sub");
        this.toastClose = document.getElementById("toast-close");
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadData();
    }

    async loadData() {
        try {
            const res = await fetch(this.config.routes.index);
            const json = await res.json();
            this.data = this.config.normalize
                ? this.config.normalize(json)
                : json.data ?? json;
            this.draw();
        } catch (err) {
            console.error(`Erro ao carregar ${this.config.name}`, err);
        }
    }

    draw(filter = "") {
        const rows = this.data
            .filter((item) =>
                this.config.searchKeys.some((k) =>
                    (item[k] || "").toLowerCase().includes(filter.toLowerCase())
                )
            )
            .map(this.config.renderRow)
            .join("");
        this.tbody.innerHTML = rows;
    }

    showErrors(messages = []) {
        const box = document.querySelector("#modal-errors");
        if (!box) return;
        if (!messages.length) {
            box.classList.add("hidden");
            box.innerHTML = "";
            return;
        }
        box.innerHTML = `<ul class="list-disc list-inside space-y-1">${messages
            .map((m) => `<li>${m}</li>`)
            .join("")}</ul>`;
        box.classList.remove("hidden");
    }

    setFormDisabled(disabled) {
        this.form
            .querySelectorAll("input,select,textarea,button")
            .forEach((el) => {
                if (el.id === "m-submit" || el === this.btnDelete) return; // não mexe
                el.disabled = disabled;
            });
        const submit = document.querySelector("#m-submit");
        submit?.classList.toggle("hidden", disabled); // esconde ao visualizar
        this.btnDelete?.classList.toggle("hidden", disabled);
    }

    openModal(editing = null, readOnly = false) {
        this.showErrors();
        this.modal.classList.remove("hidden");
        if (editing) {
            this.title.textContent = `${readOnly ? "Visualizar" : "Editar"} ${
                this.config.label
            }`;
            this.config.fillForm(editing);
            this.setFormDisabled(!!readOnly);
            if (!readOnly)
                this.btnDelete.onclick = () => this.openConfirm(editing.id);
        } else {
            this.title.textContent = `Novo ${this.config.label}`;
            this.setFormDisabled(false);
            this.btnDelete.classList.add("hidden");
            this.form.reset();

            this.form.querySelectorAll('input[type="hidden"]').forEach(i => i.value = "");
        }
    }

    closeModal() {
        this.modal.classList.add("hidden");
        this.showErrors();
        this.form?.querySelectorAll('input[type="hidden"]').forEach(i => i.value = "");
    }

    async saveItem(payload, id = null) {
        const url = id
            ? `${this.config.routes.update}/${id}`
            : this.config.routes.store;
        const method = id ? "PUT" : "POST";
        try {
            const res = await fetch(url, {
                method,
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content,
                },
                body: JSON.stringify(payload),
            });

            if (!res.ok) {
                let messages = [];
                try {
                    const err = await res.json();
                    console.log(err)
                    if (err.errors)
                        Object.values(err.errors).forEach((arr) =>
                            messages.push(...arr)
                        );
                } catch (_) {
                    messages.push("Erro ao salvar.");
                }
                this.showErrors(messages);
                return;
            }

            this.closeModal();
            this.loadData();
            this.showToast(
                id ? "Atualizado com sucesso!" : "Salvo com sucesso!",
                id ? "O registro foi atualizado." : "Novo registro cadastrado."
            );
        } catch (err) {
            this.showErrors(["Erro inesperado ao salvar registro"]);
        }
    }

    openConfirm(id) {
        this._pendingDeleteId = id;
        if (typeof this.confirmDlg.showModal === "function")
            this.confirmDlg.showModal();
        else this.confirmDlg.classList.remove("hidden"); // fallback
    }

    closeConfirm() {
        if (typeof this.confirmDlg.close === "function")
            this.confirmDlg.close();
        else this.confirmDlg.classList.add("hidden");
    }

    async doDelete(id) {
        try {
            const res = await fetch(`${this.config.routes.delete}/${id}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content,
                },
            });
            if (!res.ok) throw await res.json();
            this.loadData();
            this.showToast("Excluído", "O registro foi removido.");
        } catch (err) {
            alert(`Erro ao excluir ${this.config.label}`);
        }
    }

    showToast(
        title = "Successfully saved!",
        sub = "Anyone with a link can now view this file."
    ) {
        if (!this.toast) return;
        this.toastTitle.textContent = title;
        this.toastSub.textContent = sub;
        this.toast.classList.remove("hidden");
        clearTimeout(this._toastTimer);
        this._toastTimer = setTimeout(
            () => this.toast.classList.add("hidden"),
            3500
        );
    }

    bindEvents() {
        this.btnAdd?.addEventListener("click", () => this.openModal());
        this.search?.addEventListener("input", (e) =>
            this.draw(e.target.value)
        );
        this.btnClose?.addEventListener("click", () => this.closeModal());
        this.btnCancel?.addEventListener("click", () => this.closeModal());

        const btnSubmit = document.querySelector("#m-submit");
        btnSubmit?.addEventListener("click", (e) => {
            e.preventDefault();
            const payload = this.config.getPayload();
            const id = this.config.getId();
            this.saveItem(payload, id || null);
        });

        this.form?.addEventListener("submit", (e) => {
            e.preventDefault();
            const payload = this.config.getPayload();
            const id = this.config.getId();
            this.saveItem(payload, id || null);
        });

        // menus + ações (delegação)
        document.addEventListener("click", (e) => {
            const trigger = e.target.closest("[data-menu-trigger]");
            if (trigger) {
                const id = trigger.dataset.id;
                const menu = trigger.parentElement.querySelector(`#menu-${id}`);
                if (!menu) return;

                document.querySelectorAll("[data-menu]").forEach((m) => {
                    if (m !== menu) m.classList.add("hidden");
                });

                menu.classList.toggle("hidden");
                return;
            }

            if (!e.target.closest("[data-menu]")) {
                document
                    .querySelectorAll("[data-menu]")
                    .forEach((m) => m.classList.add("hidden"));
            }

            const btn = e.target.closest("[data-customer-area],[data-view],[data-edit],[data-del]");
            if (!btn) return;

            document
                .querySelectorAll("[data-menu]")
                .forEach((m) => m.classList.add("hidden"));

            const id = btn.dataset.id;
            const item = this.data.find((x) => String(x.id) === String(id));

            if (btn.hasAttribute("data-customer-area")) {
                window.location.href = 'customer/customer-area/' + id;
            }

            if (btn.hasAttribute("data-view")) this.openModal(item, true);
            if (btn.hasAttribute("data-edit")) this.openModal(item, false);
            if (btn.hasAttribute("data-del")) this.openConfirm(id);
        });

        // confirmação excluir
        this.confirmYes?.addEventListener("click", () => {
            const id = this._pendingDeleteId;
            this.closeConfirm();
            if (id) this.doDelete(id);
            this._pendingDeleteId = null;
        });
        this.confirmNo?.addEventListener("click", () => this.closeConfirm());

        // fechar toast
        this.toastClose?.addEventListener("click", () =>
            this.toast.classList.add("hidden")
        );
    }
}
