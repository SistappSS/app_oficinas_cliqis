// assets/js/template/views/service-orders/service-order-index.js

document.addEventListener("DOMContentLoaded", () => {
    const searchInput   = document.querySelector("#search");
    const statusButtons = document.querySelectorAll("[data-status-filter]");

    const state = {
        status: "", // "", "draft", "pending", "approved", "completed", "rejected"
    };

    // load inicial
    loadOrders(state);

    // busca com debounce
    if (searchInput) {
        searchInput.addEventListener(
            "input",
            debounce(() => loadOrders(state), 300)
        );
    }

    // filtros de status (chips)
    statusButtons.forEach((btn) => {
        btn.addEventListener("click", () => {
            statusButtons.forEach((b) => b.classList.remove("active-status"));
            btn.classList.add("active-status");
            state.status = btn.getAttribute("data-status-filter") || "";
            loadOrders(state);
        });
    });

    // se o botão "Nova OS" for <button>, redireciona
    const btnAdd = document.querySelector("#btn-add");
    if (btnAdd && btnAdd.tagName === "BUTTON") {
        btnAdd.addEventListener("click", () => {
            window.location.href = "/service-orders/create";
        });
    }
});

function debounce(fn, delay = 300) {
    let t;
    return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), delay);
    };
}

async function loadOrders(state, pageUrl) {
    const tbody       = document.querySelector("#tbody");
    const searchInput = document.querySelector("#search");

    if (!tbody) return;

    const baseUrl = pageUrl || "/service-orders/service-order-api";
    const url     = new URL(baseUrl, window.location.origin);

    const term = (searchInput?.value || "").trim();
    if (term) url.searchParams.set("q", term);
    if (state.status) url.searchParams.set("status", state.status);

    try {
        const resp = await fetch(url.toString(), {
            headers: { Accept: "application/json" },
        });

        if (!resp.ok) {
            console.error("Erro ao carregar OS:", await resp.text());
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-6 py-6 text-center text-sm text-rose-500">
                        Erro ao carregar ordens de serviço.
                    </td>
                </tr>`;
            return;
        }

        const json  = await resp.json();
        const items = json.data || json;

        if (!Array.isArray(items) || !items.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-6 py-6 text-center text-sm text-slate-500">
                        Nenhuma OS encontrada.
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = items.map(renderRow).join("");

        // depois de renderizar, liga os menus e ações
        bindRowActions();
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

    const total     = Number(os.grand_total || 0).toFixed(2);
    const orderDate = os.order_date || "-";
    const ticket    = os.ticket_number || "-";

    return `
<tr class="hover:bg-slate-50">
    <td class="px-6 py-3 text-left whitespace-nowrap">
        ${os.order_number || "-"}
    </td>
    <td class="px-3 py-3 text-left">
        ${customerName}
    </td>
    <td class="px-3 py-3 text-left whitespace-nowrap">
        ${orderDate}
    </td>
    <td class="px-3 py-3 text-left">
        ${ticket}
    </td>
    <td class="px-3 py-3 text-right whitespace-nowrap">
        R$ ${total}
    </td>
    <td class="px-3 py-3 text-center">
        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${badgeClass}">
            ${status}
        </span>
    </td>

    <td class="px-6 py-3 text-center whitespace-nowrap">
        <div class="relative inline-block text-left">
            <button type="button"
                    class="rounded-lg p-2 hover:bg-slate-100 focus:outline-none"
                    data-menu-trigger
                    data-id="${os.id}"
                    aria-haspopup="true"
                    aria-expanded="false">⋮</button>

            <div id="menu-${os.id}"
                 class="hidden absolute right-0 top-full z-50 mt-2 w-48 rounded-2xl overflow-hidden border border-slate-200 bg-white shadow-xl ring-1 ring-black/5"
                 data-menu
                 data-for="${os.id}">
                <ul class="py-1 text-sm text-slate-700">
                    <li>
                        <button type="button"
                                class="block w-full px-3 py-2 text-left bg-transparent rounded-none hover:bg-slate-50 focus:outline-none"
                                data-view
                                data-id="${os.id}">
                            Visualizar OS
                        </button>
                    </li>
                    <li>
                        <button type="button"
                                class="block w-full px-3 py-2 text-left bg-transparent rounded-none hover:bg-slate-50 focus:outline-none"
                                data-edit
                                data-id="${os.id}">
                            Editar OS
                        </button>
                    </li>
                    <li class="border-t border-slate-200 mt-1 pt-1">
                        <button type="button"
                                class="block w-full px-3 py-2 text-left bg-transparent rounded-none text-rose-600 hover:bg-rose-50 focus:outline-none"
                                data-del
                                data-id="${os.id}">
                            Excluir OS
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </td>
</tr>`;
}

/**
 * Liga menus dropdown e ações de view/edit/delete
 */
function bindRowActions() {
    const triggers = document.querySelectorAll("[data-menu-trigger]");
    const menus    = document.querySelectorAll("[data-menu]");

    const closeAllMenus = () => {
        menus.forEach((m) => m.classList.add("hidden"));
    };

    // abrir/fechar menu
    triggers.forEach((btn) => {
        btn.addEventListener("click", (e) => {
            e.stopPropagation();
            const id   = btn.dataset.id;
            const menu = document.querySelector(`#menu-${id}`);
            if (!menu) return;

            const isHidden = menu.classList.contains("hidden");
            closeAllMenus();
            if (isHidden) menu.classList.remove("hidden");
        });
    });

    // fechar ao clicar fora
    document.addEventListener("click", () => closeAllMenus(), { once: true });

    // ações
    document.querySelectorAll("[data-view]").forEach((btn) => {
        btn.addEventListener("click", (e) => {
            e.preventDefault();
            const id = btn.dataset.id;
            if (!id) return;
            window.location.href = `/service-orders/${id}`;
        });
    });

    document.querySelectorAll("[data-edit]").forEach((btn) => {
        btn.addEventListener("click", (e) => {
            e.preventDefault();
            const id = btn.dataset.id;
            if (!id) return;
            window.location.href = `/service-orders/${id}/edit`;
        });
    });

    document.querySelectorAll("[data-del]").forEach((btn) => {
        btn.addEventListener("click", async (e) => {
            e.preventDefault();
            const id = btn.dataset.id;
            if (!id) return;

            if (!confirm("Deseja realmente excluir esta OS?")) return;

            try {
                const resp = await fetch(`/service-orders/service-order-api/${id}`, {
                    method: "DELETE",
                    headers: {
                        "Accept": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": getCsrfToken(),
                    },
                });

                if (!resp.ok) {
                    console.error("Erro ao excluir OS:", await resp.text());
                    alert("Não foi possível excluir esta OS.");
                    return;
                }

                // recarrega lista após excluir
                loadOrders({ status: getCurrentStatusFilter() });
            } catch (err) {
                console.error(err);
                alert("Erro inesperado ao excluir OS.");
            }
        });
    });
}

function getCurrentStatusFilter() {
    const active = document.querySelector("[data-status-filter].active-status");
    return active ? active.getAttribute("data-status-filter") || "" : "";
}

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute("content") : "";
}
