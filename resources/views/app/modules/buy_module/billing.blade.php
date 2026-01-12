@extends('layouts.templates.template')

@section('content')
    @push('styles')
        <style>
            .rotate-180 { transform: rotate(180deg); }
        </style>
    @endpush

    <main class="mx-auto max-w-7xl w-full pt-3 px-6">
        @if (session('error'))
            <div id="alert-error"
                 class="mb-4 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800"
                 role="alert">
                <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-4a1 1 0 00-1 1v4a1 1 0 002 0V7a1 1 0 00-1-1zm0 10a1.25 1.25 0 100-2.5 1.25 1.25 0 000 2.5z" clip-rule="evenodd"/>
                </svg>
                <div class="flex-1">
                    <p class="font-semibold">Assinatura necessária</p>
                    <p class="text-sm">{{ session('error') }}</p>
                </div>
                <button type="button" aria-label="Fechar"
                        class="rounded-md p-1 text-red-600/70 transition hover:bg-red-100 hover:text-red-700"
                        onclick="document.getElementById('alert-error').remove()">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        @endif

        <section class="h-full grid grid-cols-1 lg:grid-cols-[1fr_330px] gap-6 py-6">
            <!-- Coluna esquerda -->
            <div class="min-h-0 overflow-auto pr-1">
                <div class="text-center mb-8">
                    <h1 class="text-3xl sm:text-4xl font-bold">Monte seu plano</h1>
                    <p class="mt-2 text-slate-600">Escolha os módulos. Os obrigatórios do seu segmento já vêm marcados.</p>
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

                <!-- FORM (só para armazenar inputs) -->
                <form id="billing-form" class="mt-6">
                    @csrf
                    <input type="hidden" id="selected-input">
                    <input type="hidden" id="total-input">
                    <input type="hidden" id="scope-input" value="standard">

                    <fieldset>
                        <legend class="sr-only">Seleção de módulos</legend>
                        <div id="cards" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"></div>
                    </fieldset>

                    <!-- Ações inferiores (mobile) -->
                    <div class="mt-6 lg:hidden">
                        <button id="continue-mobile" type="button" disabled
                                class="w-full inline-flex items-center justify-center rounded-xl bg-blue-700 px-5 py-3 font-semibold text-white shadow transition hover:bg-blue-800 disabled:opacity-50 disabled:cursor-not-allowed">
                            Realizar pagamento
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sidebar -->
            <aside class="min-h-0">
                <div class="sticky top-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="">
                        <h2 class="text-lg font-semibold">Seu Plano</h2>
                    </div>

                    <ul id="summary-list" class="mt-3 divide-y divide-slate-100 text-sm"></ul>

                    <!-- Total -->
                    <div class="mt-5">
                        <div class="flex items-end justify-between">
                            <div>
                                <div class="text-sm text-slate-600">Próxima cobrança</div>
                                <div id="total" class="text-2xl font-bold">R$ 0/mês</div>
                            </div>
                        </div>
                        <div id="billing-note" class="mt-1 text-xs text-slate-500">Plano mensal.</div>
                    </div>

                    <!-- Botões -->
                    <div class="mt-6 space-y-3">
                        <button id="continue" type="button" disabled
                                class="w-full inline-flex items-center justify-center rounded-xl bg-blue-700 px-5 py-3 font-semibold text-white shadow transition hover:bg-blue-800 disabled:opacity-50 disabled:cursor-not-allowed">
                            Realizar pagamento
                        </button>

                        <a href="{{ route('my-account.index', auth()->id()) }}"
                           class="w-full inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-3 font-medium text-slate-700 shadow hover:bg-slate-50">
                            Voltar
                        </a>
                    </div>
                </div>
            </aside>
        </section>
    </main>

    {{-- Modal PIX (mantido) --}}
    <div id="payment-modal" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm">
        <div class="absolute left-1/2 top-1/2 w-[min(600px,95vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold">Pagamento</h2>
                <button id="payment-modal-close" class="rounded-lg p-2 hover:bg-slate-100">
                    <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <ul class="flex border-b mb-4">
                <li class="mr-1"><button class="px-4 py-2 border-b-2 border-blue-600 text-blue-600" disabled>PIX</button></li>
                <li class="mr-1"><button class="px-4 py-2 text-slate-400" disabled>Boleto</button></li>
                <li><button class="px-4 py-2 text-slate-400" disabled>Cartão</button></li>
            </ul>

            <div id="tab-pix">
                <div id="pix-loading" class="text-sm text-slate-600 mt-2 hidden">Gerando cobrança…</div>

                <div id="pix-result" class="mt-4 hidden">
                    <img id="qrCodeImage" src="" alt="QR Code PIX" class="mb-2">
                    <p><strong>PIX copia e cola:</strong> <span id="pix-copia-cola"></span></p>
                    <p><strong>Válido até:</strong> <span id="due-date"></span></p>
                    <p class="text-sm">
                        Status do pagamento: <strong><span data-pay-status>PENDING</span></strong>
                    </p>
                </div>
            </div>
        </div>
    </div>

    @php
        $catalog = $modules->map(function ($m) use (
            $requiredIds,
            $ownedModuleIds,
            $ownedFeaturesByModule,
            $expiryByModule,
            $renewalByModule,
            $trialFeaturesByModule // <— NOVO
        ) {
            $owned     = $ownedModuleIds->contains((string)$m->id);
            $ownedFeat = collect($ownedFeaturesByModule->get($m->id, []));
            $trialFeat = collect($trialFeaturesByModule->get($m->id, [])); // <— NOVO

            return [
                'id'       => (string) $m->id,
                'name'     => $m->name,
                'desc'     => $m->description,
                'price'    => (float) $m->price,
                'icon'     => $m->icon,
                'required' => in_array((string)$m->id, $requiredIds->all(), true),
                'owned'    => $owned, // só vira true se já pagou/subscription
                'owned_features' => $ownedFeat->values(),
                'expires_at' => $expiryByModule->get($m->id),
                'in_renewal' => (bool) $renewalByModule->get($m->id, false),

                'features' => $m->features->map(function($f) use ($ownedFeat, $trialFeat){
                    $isOwned    = $ownedFeat->contains((int)$f->id);
                    $isSelected = $isOwned || $trialFeat->contains((int)$f->id) || ($f->is_required ?? false);
                    return [
                        'id'          => (int)$f->id,
                        'name'        => $f->name,
                        'price'       => (float)$f->price,
                        'is_required' => (bool) ($f->is_required ?? false),
                        'selected'    => $isSelected, // <— mantém ligado no trial
                        'owned'       => $isOwned,    // <— só “já possui” após pagar
                    ];
                })->values(),
            ];
        })->values();
    @endphp

    <script>
        // ===== Helpers =====
        const CATALOG          = @json($catalog);
        const INITIAL_SELECTED = new Set(@json($initialSelected ?? [])); // ativos + obrigatórios vindos do controller

        let selected = new Set(INITIAL_SELECTED);
        let paying = false;

        // Refs
        const cardsWrap     = document.getElementById("cards");
        const chipsWrap     = document.getElementById("chips");
        const searchInput   = document.getElementById("search");
        const listWrap      = document.getElementById("summary-list");
        const totalEl       = document.getElementById("total");
        const noteEl        = document.getElementById("billing-note");
        const continueBtn   = document.getElementById("continue");
        const continueMob   = document.getElementById("continue-mobile");
        const selectedInput = document.getElementById("selected-input");
        const totalInput    = document.getElementById("total-input");
        const countSelected = m => (m.features||[]).filter(f => f.selected).length;
        const countTotal    = m => (m.features||[]).length;

        const brl = n => Number(n||0).toLocaleString('pt-BR',{style:'currency',currency:'BRL'});

        function sumSelectedFeatures(m){
            return (m.features||[])
                .filter(f => f.selected)
                .reduce((s,f) => s + Number(f.price || 0), 0);
        }

        function getModuleTotal(m){
            const hasFeatures = Array.isArray(m.features) && m.features.length > 0;
            if (!hasFeatures) return Number(m.price || 0);
            return sumSelectedFeatures(m);
        }

        function renderCards(){
            const q = (searchInput.value||'').trim().toLowerCase();
            cardsWrap.innerHTML = "";

            CATALOG.forEach(m => {
                const modLocked  = m.required || m.owned;

                const hit = (m.name + " " + (m.desc || '')).toLowerCase().includes(q);
                const checked = selected.has(m.id);

                // lista de features (toggles)
                const featuresHtml = (m.features||[]).map((f, idx) => {
                    const lockedByRenewal = m.owned && m.in_renewal;
                    const lockedOwned     = m.owned && f.owned;
                    const lockedRequired  = !!f.is_required;

                    const disabled = lockedByRenewal || lockedOwned || lockedRequired;

                    const badge = lockedByRenewal
                        ? '<span class="ml-2 text-[10px] rounded bg-amber-50 text-amber-700 px-1.5">renovação</span>'
                        : lockedOwned
                            ? '<span class="ml-2 text-[10px] rounded bg-emerald-50 text-emerald-700 px-1.5">já possui</span>'
                            : lockedRequired
                                ? '<span class="ml-2 text-[10px] rounded bg-slate-100 text-slate-600 px-1.5">obrigatória</span>'
                                : '';

                    return `
   <div class="flex items-center justify-between py-1">
     <span class="text-sm text-slate-700">${f.name} ${badge}</span>
     <label class="relative inline-flex items-center cursor-pointer ${disabled ? 'opacity-60 cursor-not-allowed' : ''}">
       <input type="checkbox" class="sr-only peer feature-toggle"
              data-module="${m.id}" data-index="${idx}"
              ${f.selected ? 'checked' : ''} ${disabled ? 'disabled' : ''}>
       <div class="w-10 h-5 bg-slate-200 rounded-full transition peer-checked:bg-blue-600"></div>
       <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow transition ${f.selected ? 'translate-x-5' : ''} peer-checked:translate-x-5"></div>
     </label>
     <span class="text-sm font-medium text-slate-700">${brl(Number(f.price||0))}</span>
   </div>
  `;
                }).join("");

                const card = document.createElement("label");
                card.className = `group relative cursor-pointer rounded-2xl border ${checked ? "border-blue-600 ring-2 ring-blue-100" : "border-slate-200"} bg-white p-5 shadow-sm transition hover:border-blue-500`;
                card.dataset.id = m.id;

                // contador selecionadas/total
                const selCount = countSelected(m);
                const totCount = countTotal(m);
                const counter  = `${selCount}/${totCount || 0}`;

                const toggleHtml = `
  <div class="features-toggle mt-3">
    <button type="button"
            class="toggle-features flex w-full items-center justify-between text-sm font-medium text-slate-700 hover:text-slate-900">
      <span>
        Expandir funcionalidades
        <span class="feat-count ml-2 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700">${counter}</span>
      </span>
      <i class="fa-solid fa-chevron-down transition-transform duration-200"></i>
    </button>

    <div class="features-panel mt-2 border-t pt-3 ${checked ? '' : 'hidden'} space-y-2 text-sm text-slate-700">
      ${featuresHtml || '<p class="text-sm text-slate-500">Este módulo não possui features adicionais.</p>'}
    </div>
  </div>
`;

                card.innerHTML = `
  <input type="checkbox" class="peer sr-only module-checkbox"
         ${checked ? 'checked' : ''} ${modLocked ? 'disabled' : ''}>

  <!-- Linha do título + preço -->
  <div class="flex items-start gap-3">
    <span class="rounded-xl bg-blue-50 p-3 text-blue-700">
      <i class="fa-solid fa-${m.icon || ''}"></i>
    </span>

    <div class="flex-1 min-w-0">
      <p class="font-semibold">
        ${m.name}
        ${m.required ? '<span class="ml-2 text-[10px] font-semibold rounded bg-blue-50 text-blue-700 px-2 py-0.5">obrigatório</span>' : ''}
      </p>
    </div>

    <div class="ml-3 text-right shrink-0">
      <div class="text-sm font-medium module-total">${brl(getModuleTotal(m))}</div>
      <div class="text-xs text-slate-500">/mód. mês</div>
    </div>
  </div>

  <!-- Descrição ocupando a largura inteira -->
  <p class="mt-2 text-sm text-slate-600">${m.desc || ''}</p>

  ${toggleHtml}

  <div class="pointer-events-none absolute right-1 top-1 ${checked ? "" : "hidden"} rounded-full bg-blue-600 p-1 text-white peer-checked:block">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg>
  </div>
`;

                const panel     = card.querySelector(".features-panel");
                const chkModule = card.querySelector(".module-checkbox");
                const totalCell = card.querySelector(".module-total");

                if (!checked) panel.style.maxHeight = "0px";

                // Impede clique nos toggles de borbulhar pro label
                card.querySelectorAll('.feature-toggle').forEach(el => {
                    el.addEventListener('click', ev => ev.stopPropagation());
                    el.addEventListener('keydown', ev => ev.stopPropagation());
                });

                // Toggle de features
                card.querySelectorAll(".feature-toggle").forEach(input => {
                    input.addEventListener("change", () => {
                        const idx = parseInt(input.dataset.index,10);
                        const feat = m.features[idx];
                        feat.selected = input.checked;

                        const featCountEl = card.querySelector('.feat-count');
                        featCountEl.textContent = `${countSelected(m)}/${countTotal(m)}`;

                        totalCell.textContent = brl(priceForModule(m));
                        renderSummary();
                    });
                });

                // Seleção do módulo (abre/fecha painel)
                chkModule.addEventListener("change", (e) => {
                    if (modLocked){ e.target.checked = true; return; }

                    if (m.required){ e.target.checked = true; return; }

                    if (e.target.checked) {
                        selected.add(m.id);
                        panel.classList.add("open");
                        panel.style.maxHeight = panel.scrollHeight + "px";
                    } else {
                        selected.delete(m.id);
                        panel.style.maxHeight = panel.scrollHeight + "px";
                        requestAnimationFrame(()=>{ panel.style.maxHeight = "0px"; panel.classList.remove("open"); });
                    }
                    renderChips();
                    renderSummary();
                });

                // Toggle accordion — por card
                const toggleBtn = card.querySelector(".toggle-features");
                toggleBtn.addEventListener("click", (ev) => {
                    ev.stopPropagation(); // não deixar clicar no checkbox do módulo
                    const icon = toggleBtn.querySelector("i");
                    panel.classList.toggle("hidden");
                    icon.classList.toggle("rotate-180");
                });


                // Filtro
                card.style.display = hit ? "" : "none";
                cardsWrap.appendChild(card);
            });
        }

        function renderChips(){
            chipsWrap.innerHTML = "";
            if (selected.size === 0) { chipsWrap.classList.add("hidden"); return; }
            chipsWrap.classList.remove("hidden");

            [...selected].forEach(id => {
                const m = CATALOG.find(x => x.id === id);
                const chip = document.createElement("button");
                chip.type = "button";
                chip.className = "inline-flex items-center gap-1 rounded-full bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-800 hover:bg-blue-100";
                chip.innerHTML = `${m.name}${m.required ? ' <span class="opacity-60">(obrigatório)</span>' : ''}<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>`;
                chip.addEventListener("click", () => {
                    if (m.required) return;
                    selected.delete(id);
                    render();
                });
                chipsWrap.appendChild(chip);
            });
        }

        function priceForModule(m){
            const hasFeatures = Array.isArray(m.features) && m.features.length > 0;

            // MÓDULO JÁ CONTRATADO: nunca cobra o módulo; só novas features (fora da renovação)
            if (m.owned) {
                if (m.in_renewal) return 0; // na janela de renovação não permite add-ons
                return (m.features||[])
                    .filter(f => f.selected && !f.owned)  // cobra apenas as NÃO possuídas
                    .reduce((s,f)=>s+Number(f.price||0),0);
            }

            // MÓDULO NOVO
            if (!hasFeatures) return Number(m.price||0); // sem features, cobra o módulo
            return (m.features||[]).filter(f=>f.selected).reduce((s,f)=>s+Number(f.price||0),0);
        }

        function computeTotal(){
            return [...selected].reduce((sum, id) => {
                const m = CATALOG.find(x => x.id === id);
                return sum + (m ? priceForModule(m) : 0);
            }, 0);
        }

        function renderSummary(){
            listWrap.innerHTML = "";
            [...selected].forEach(id => {
                const m = CATALOG.find(x => x.id === id);
                const li = document.createElement("li");
                li.className = "py-2";
                li.innerHTML = `<div class="flex justify-between"><span>${m.name}${m.required ? ' <span class="ml-1 text-[10px] px-1 rounded bg-slate-100 text-slate-600">obrigatório</span>' : ''}</span><span class="text-slate-700">${brl(getModuleTotal(m))}</span></div>`;

                (m.features||[]).filter(f => f.selected).forEach(f => {
                    const tag = f.owned ? ' (já possui)' : (f.is_required ? ' (obrigatória)' : '');
                    const sub = document.createElement("div");
                    sub.className = "flex justify-between text-xs text-slate-600 pl-3";
                    sub.innerHTML = `<span>• ${f.name}${tag}</span><span>${brl(Number(f.price||0))}</span>`;
                    li.appendChild(sub);
                });

                listWrap.appendChild(li);
            });

            const total = computeTotal();
            totalEl.textContent = `${brl(total)}/mês`;
            noteEl.textContent = "Plano mensal.";

            const has = total > 0;
            continueBtn.disabled = !has;
            continueMob && (continueMob.disabled = !has);

            selectedInput.value = JSON.stringify([...selected]);
            totalInput.value    = total;
        }

        searchInput.addEventListener("input", renderCards);

        // ===== Pagamento (PIX) =====
        const modal = document.getElementById('payment-modal');
        document.getElementById('payment-modal-close')?.addEventListener('click', () => modal.classList.add('hidden'));

        function collectSelectedFeatures(){
            const map = {};
            CATALOG.forEach(m => {
                if (!selected.has(m.id)) return;

                // Somente features NOVAS quando o módulo é owned
                const picked = (m.features||[])
                    .filter(f => f.selected && (!m.owned || !f.owned))
                    .map(f => f.id);

                if (!m.owned && m.features && m.features.length === 0) {
                    // módulo sem features novo → nada aqui; cobrado pelo preço do módulo
                }
                if (picked.length) map[m.id] = picked;
            });
            return map;
        }

        function abrirModalPagamento(){
            if (paying) return;
            const total = computeTotal();
            if (total <= 0) { paying = false; return; } // evita abrir modal sem valor
            paying = true;
            modal.classList.remove('hidden');
            gerarPix([...selected], collectSelectedFeatures());
        }

        async function gerarPix(moduleIds, selectedFeatures){
            const loader = document.getElementById('pix-loading');
            const result = document.getElementById('pix-result');
            loader.classList.remove('hidden'); result.classList.add('hidden');

            try{
                const res = await fetch("{{ route('gerar-qrcode.module') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type':'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        user_id: {{ auth()->id() }},
                        module_ids: moduleIds,
                        selected_features: selectedFeatures,
                        scope: document.getElementById('scope-input').value
                    })
                });
                const json = await res.json();

                if(!json.success) throw new Error('Falha ao gerar cobrança');

                const data = json.data;
                document.getElementById('qrCodeImage').src   = `data:image/png;base64,${data.qrCode.encodedImage}`;
                document.getElementById('pix-copia-cola').innerText = data.qrCode.payload;
                document.getElementById('due-date').innerText       = data.qrCode.expirationDate;

                const $status = document.querySelector('[data-pay-status]');
                if ($status) $status.textContent = data.status ?? 'PENDING';
                verificarStatusPagamento(data.payment_id);

                result.classList.remove('hidden');
            }catch(e){
                alert('Erro ao gerar cobrança PIX.');
                console.error(e);
            }finally{
                loader.classList.add('hidden');
                paying = false;
            }
        }

        async function verificarStatusPagamento(paymentId){
            const pollMs = 2000;
            let polling = null;

            function startPolling(paymentId){
                const $status = document.querySelector('[data-pay-status]');
                stopPolling();
                polling = setInterval(async () => {
                    try{
                        const res  = await fetch(`/modules/buy-module/checar-status-pagamento/${paymentId}`);
                        const data = await res.json();

                        if ($status) $status.textContent = data.status ?? '...';

                        const ok = ['RECEIVED','CONFIRMED','RECEIVED_IN_CASH','COMPLETED'];
                        if (ok.includes(data.status) && data.done === true){
                            stopPolling();
                            window.location.href = '/my-account';
                        }
                    }catch(e){ /* ignora e tenta no próximo ciclo */ }
                }, pollMs);
            }

            function stopPolling(){
                if (polling){ clearInterval(polling); polling = null; }
            }

            // >>> ESSA LINHA FALTAVA <<<
            startPolling(paymentId);
        }

        continueBtn.addEventListener("click", abrirModalPagamento);
        continueMob?.addEventListener("click", abrirModalPagamento);

        // Render inicial
        function render(){ renderCards(); renderChips(); renderSummary(); }
        render();
    </script>
@endsection
