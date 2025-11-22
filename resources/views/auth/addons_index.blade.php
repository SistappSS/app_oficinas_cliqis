<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Cliqis — Opcionais do plano</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>
</head>
<body class="h-screen overflow-hidden grid grid-rows-[auto_1fr_auto] bg-white text-slate-900">
<header class="border-b border-slate-200">
    <div class="mx-auto max-w-7xl px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-blue-700 text-white shadow">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                   stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round"
                                                                  d="M7 13h4v8l6-12h-4V1L7 13Z"/></svg>
            </span>
            <span class="text-lg font-semibold">Cliqis</span>
        </div>
        <div class="hidden sm:flex items-center gap-6">
            <a class="text-sm text-slate-600 hover:text-slate-900"
               href="https://wa.me/5511988313151?text=Ol%C3%A1%2C%20preciso%20de%20ajuda%20no%20Cliqis." target="_blank"
               rel="noopener">Ajuda</a>
            <a class="text-sm text-slate-600 hover:text-slate-900"
               href="https://wa.me/5511988313151?text=Ol%C3%A1%2C%20gostaria%20de%20falar%20com%20o%20suporte%20Cliqis."
               target="_blank" rel="noopener">Contato</a>
            <a href="{{ route('logout') }}"
               class="rounded-xl bg-white px-4 py-2 text-slate-700 shadow border border-slate-200 hover:bg-slate-50">Sair</a>
        </div>
    </div>
</header>

<main class="mx-auto max-w-7xl w-full px-6">
    <section class="h-full grid grid-cols-1 lg:grid-cols-[1fr_330px] gap-6 py-6">
        <!-- Coluna esquerda -->
        <div class="min-h-0 overflow-auto pr-1">
            <div class="text-center mb-8">
                <h1 class="text-3xl sm:text-4xl font-bold">Monte seu plano em minutos</h1>
                <p class="mt-2 text-slate-600">Escolha só o que precisa. Comece pelo essencial e cresça no seu
                    ritmo.</p>
            </div>

            <!-- Busca -->
            <div class="mx-auto max-w-3xl">
                <div class="relative">
                    <input id="search" type="text" placeholder="Buscar módulos (ex.: financeiro, estoque, faturas)…"
                           class="w-full rounded-2xl border border-slate-300 bg-white pl-11 pr-4 py-3 outline-none placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200"/>
                    <svg class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400"
                         xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="M21 21l-4.3-4.3"/>
                    </svg>
                </div>
                <div id="chips" class="mt-3 flex flex-wrap gap-2"></div>
            </div>

            <!-- FORM REAL (POST) -->
            <form id="addons-form" class="mt-6" action="{{ route('addons.store') }}" method="POST">
                @csrf

                <input type="hidden" name="selected" id="selected-input">
                <input type="hidden" name="total" id="total-input">

                <fieldset>
                    <legend class="sr-only">Seleção de módulos</legend>
                    <div id="cards" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"></div>
                </fieldset>

                <!-- Ações inferiores (mobile) -->
                <div class="mt-6 lg:hidden">
                    <button id="continue-mobile" type="submit" disabled
                            class="w-full inline-flex items-center justify-center rounded-xl bg-blue-700 px-5 py-3 font-semibold text-white shadow transition hover:bg-blue-800 disabled:opacity-50 disabled:cursor-not-allowed">
                        Iniciar teste grátis de 14 dias
                    </button>
                </div>
            </form>
        </div>

        <!-- Sidebar -->
        <aside class="min-h-0">
            <div class="sticky top-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold">Seu Plano</h2>
                <ul id="summary-list" class="mt-3 divide-y divide-slate-100 text-sm"></ul>

                <!-- Total -->
                <div class="mt-5">
                    <div class="flex items-end justify-between">
                        <div>
                            <div class="text-sm text-slate-600">O valor de</div>
                            <div id="total" class="text-2xl font-bold">R$ 0/mês</div>
                        </div>
                    </div>
                    <div id="billing-note" class="mt-1 text-xs text-slate-500">Nenhuma cobrança hoje. O valor acima é
                        por mês.
                    </div>
                </div>

                <!-- Botões -->
                <div class="mt-6 space-y-3">
                    <button id="continue" type="submit" form="addons-form" disabled
                            class="w-full inline-flex items-center justify-center rounded-xl bg-blue-700 px-5 py-3 font-semibold text-white shadow transition hover:bg-blue-800 disabled:opacity-50 disabled:cursor-not-allowed">
                        Iniciar teste grátis de 14 dias
                    </button>
                    <a href="{{ url()->previous() }}"
                       class="w-full inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-3 font-medium text-slate-700 shadow hover:bg-slate-50">
                        Voltar
                    </a>
                </div>
            </div>
        </aside>
    </section>
</main>

<footer class="py-6 text-center text-xs text-slate-500">
    © 2025 Cliqis. Todos os direitos reservados.
</footer>

@php
    $catalog = $modules->map(function ($m) use ($requiredIds) {
        $req = in_array((string)$m->id, $requiredIds->all(), true);
        return [
            'id'        => (string) $m->id,
            'name'      => $m->name,
            'desc'      => $m->description,
            'price'     => (float) $m->price,
            'icon'      => $m->icon,
            'required'  => $req, // <--- flag p/ travar
        ];
    })->values();

    $initialSelected = $requiredIds; // começa com os obrigatórios
@endphp

<script>
    const CATALOG = @json($catalog);
    const INITIAL_SELECTED = new Set(@json($initialSelected)); // <- começa com obrigatórios

    let cycle = 'monthly';
    let selected = new Set(INITIAL_SELECTED);

    // ====== Refs ======
    const cardsWrap = document.getElementById("cards");
    const chipsWrap = document.getElementById("chips");
    const searchInput = document.getElementById("search");
    const listWrap = document.getElementById("summary-list");
    const totalEl = document.getElementById("total");
    const noteEl = document.getElementById("billing-note");
    const continueBtn = document.getElementById("continue");
    const continueMob = document.getElementById("continue-mobile");
    const monthlyBtn = document.getElementById("cycle-monthly");
    const yearlyBtn = document.getElementById("cycle-yearly");
    const selectedInput = document.getElementById("selected-input");
    const totalInput = document.getElementById("total-input");

    const brl = (n) => n.toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'});

    function updateCycleUI() {
        [monthlyBtn, yearlyBtn].forEach(btn => {
            btn.classList.remove("bg-white", "shadow");
            btn.setAttribute("aria-pressed", "false");
        });
        const active = (cycle === "yearly") ? yearlyBtn : monthlyBtn;
        active.classList.add("bg-white", "shadow");
        active.setAttribute("aria-pressed", "true");
        cycleInput.value = cycle;
    }

    function renderCards() {
        const q = searchInput.value.trim().toLowerCase();
        cardsWrap.innerHTML = "";

        CATALOG.forEach(m => {
            const hit = (m.name + " " + (m.desc || '')).toLowerCase().includes(q);
            const checked = selected.has(m.id);

            const fullDesc = (m.desc || '').trim();

            // descrição curta: controla quantos chars aparecem no card
            const previewDesc = fullDesc.length > 40
                ? fullDesc.slice(0, 40) + "…"
                : fullDesc;

            // tooltip abre PRA CIMA (bottom-full) e fica acima de tudo (z-[99999])
            const tooltipHtml = (fullDesc.length > 40)
                ? `
                <div class="absolute left-0 bottom-full mb-2 z-[99999] hidden group-hover/desc:block w-56 max-w-[14rem] rounded-xl border border-slate-200 bg-white p-3 text-[13px] leading-relaxed text-slate-700 shadow-xl">
                    ${fullDesc.replace(/\n/g, '<br>')}
                </div>
              `
                : "";

            const card = document.createElement("label");
            card.className =
                "group relative cursor-pointer rounded-2xl border bg-white p-5 shadow-sm transition hover:border-blue-500 " +
                (checked ? "border-blue-600" : "border-slate-200");

            card.dataset.id = m.id;

            const tagObrig = m.required
                ? `<span class="ml-2 text-[10px] font-semibold rounded bg-blue-50 text-blue-700 px-2 py-0.5">obrigatório</span>`
                : '';

            card.innerHTML = `
        <!-- halo azul quando marcado -->
        <span class="pointer-events-none absolute inset-0 rounded-2xl ring-2 ring-blue-600 ring-offset-0 ${checked ? "" : "hidden"} peer-checked:block"></span>

        <input type="checkbox" class="peer sr-only module-checkbox" ${checked ? "checked" : ""} ${m.required ? "disabled" : ""}>

        <div class="flex items-start gap-3 relative z-[1]">
          <!-- ícone -->
          <span class="rounded-xl bg-blue-50 p-3 text-blue-700 min-w-[2.5rem] min-h-[2.5rem] flex items-center justify-center">
            <i class="fa-solid fa-${m.icon || ''}"></i>
          </span>

          <!-- bloco texto -->
          <div class="flex-1">
            <div class="flex flex-wrap items-start gap-x-2">
                <p class="font-semibold text-slate-900">${m.name}</p>
                ${tagObrig}
            </div>

            <!-- descrição com preview + tooltip -->
            <div class="mt-2 text-sm leading-relaxed text-slate-600 relative group/desc">
                <p>${previewDesc}</p>
                ${tooltipHtml}
            </div>
          </div>

          <!-- preço (desce pra não bater no check) -->
          <div class="text-right pt-7">
            <div class="text-sm font-semibold text-slate-900">${brl(Number(m.price))}</div>
            <div class="text-xs text-slate-500">/mód. mês</div>
          </div>
        </div>

        <!-- check azul canto -->
        <div class="pointer-events-none absolute right-3 top-3 ${checked ? "" : "hidden"} h-6 w-6 rounded-full bg-blue-600 text-white shadow flex items-center justify-center peer-checked:flex">
          <svg xmlns="http://www.w3.org/2000/svg"
               class="h-3.5 w-3.5"
               viewBox="0 0 24 24"
               fill="none"
               stroke="currentColor"
               stroke-width="3"
               stroke-linecap="round"
               stroke-linejoin="round">
            <path d="M4.5 12.75l6 6 9-13.5"/>
          </svg>
        </div>
      `;

            // interações
            const input = card.querySelector(".module-checkbox");
            input.addEventListener("change", (e) => {
                // módulo obrigatório não pode ser removido
                if (m.required) {
                    e.target.checked = true;
                    return;
                }
                if (e.target.checked) {
                    selected.add(m.id);
                } else {
                    selected.delete(m.id);
                }
                render();
            });

            card.style.display = hit ? "" : "none";
            cardsWrap.appendChild(card);
        });
    }

    function renderChips() {
        chipsWrap.innerHTML = "";
        [...selected].forEach(id => {
            const m = CATALOG.find(x => x.id === id);
            const chip = document.createElement("button");
            chip.type = "button";
            chip.className = "inline-flex items-center gap-1 rounded-full bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-800 hover:bg-blue-100";
            chip.innerHTML = `
                ${m.name}${m.required ? ' <span class="opacity-60">(obrigatório)</span>' : ''}
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>`;
            chip.addEventListener("click", () => {
                if (m.required) return; // não remove obrigatório
                selected.delete(id);
                render();
            });
            chipsWrap.appendChild(chip);
        });
    }

    function computeTotal() {
        return [...selected].reduce((sum, id) => {
            const mod = CATALOG.find(m => m.id === id);
            return sum + (mod ? Number(mod.price) : 0);
        }, 0);
    }

    function renderSummary() {
        listWrap.innerHTML = "";
        [...selected].forEach(id => {
            const m = CATALOG.find(x => x.id === id);
            const li = document.createElement("li");
            li.className = "py-2 flex items-center justify-between";
            li.innerHTML = `
                <span>${m.name}${m.required ? ' <span class="ml-1 text-[10px] px-1 rounded bg-slate-100 text-slate-600">obrigatório</span>' : ''}</span>
                <span class="text-slate-700">${brl(Number(m.price))}</span>
            `;
            listWrap.appendChild(li);
        });

        const total = computeTotal();
        totalEl.textContent = `${brl(total)}/mês`;
        noteEl.textContent = "deverá ser pago para continuar utilizando o sistema com as funcionalidades escolhida.";

        const has = selected.size > 0;
        continueBtn.disabled = !has;
        continueMob.disabled = !has;

        selectedInput.value = JSON.stringify([...selected]);
        totalInput.value = total;
    }

    // Eventos
    searchInput.addEventListener("input", renderCards);

    function render() {
        renderCards();
        renderChips();
        renderSummary();
    }

    render();
    updateCycleUI();
</script>
</body>
</html>
