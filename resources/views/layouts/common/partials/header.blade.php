<section class="mx-auto max-w-7xl px-4 sm:px-6 py-8">
    <div class="rounded-3xl bg-gradient-to-r from-blue-600 to-sky-500 p-6 sm:p-8 text-white shadow-md">
        <div class="flex flex-col gap-6">
            <div>
                <p class="text-sm/5 opacity-90">Bem-vindo(a) de volta, {{Auth::user()->name}}</p>
                <h1 class="text-2xl sm:text-3xl font-bold">Seu painel de agência</h1>
                <p class="text-white/80 text-sm">Acompanhe métricas, clientes e cobranças em um só lugar.</p>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white/10 p-4">
                <div class="min-w-0">
                    <p id="trial-text" class="text-sm font-medium">Seu teste gratuito termina em 14 dias.</p>
                    <div class="mt-2 h-2 w-64 max-w-full rounded-full bg-white/30 overflow-hidden">
                        <div id="trial-bar" class="h-full w-[0%] rounded-full bg-white"></div>
                    </div>
                </div>
                <a href="{{route('billing.index', auth()->id())}}"
                   class="inline-flex items-center rounded-xl bg-white text-blue-700 px-4 py-2 text-sm font-semibold shadow hover:bg-slate-100">
                    Contratar plano
                </a>
            </div>
        </div>
    </div>
</section>

<section class="mx-auto max-w-7xl px-4 sm:px-6">
    <nav aria-label="Guia rápido" class="rounded-3xl bg-gradient-to-b from-slate-50 to-blue-50 p-3">
        <ul class="no-scrollbar flex items-center justify-center gap-3 overflow-x-auto rounded-3xl p-2">

            <!-- 1 -->
            @can('sales_invoice_view')
                <li>
                    <a href="{{route('invoice.index')}}"
                       class="group flex w-28 flex-col items-center gap-2 rounded-2xl p-2 transition hover:scale-105">
                        <div
                            class="icon grid h-12 w-12 place-items-center rounded-xl border border-slate-200 text-blue-500 {{isActive('invoice.index')}}">
                            <i class="fa-solid fa-file-invoice" style="font-size: 20px;"></i>
                        </div>
                        <span class="text-xs font-medium text-slate-600 group-hover:text-blue-700">Cobranças</span>
                    </a>
                </li>
            @endcan

            @can('sales_budget_view')
                <!-- 2 -->
                <li>
                    <a href="{{route('budget.view')}}"
                       class="group flex w-28 flex-col items-center gap-2 rounded-2xl p-2 transition hover:scale-105">
                        <div
                            class="icon grid h-12 w-12 place-items-center rounded-xl border border-slate-200 text-blue-500 {{isActive('budget.view')}}">
                            <i class="fa-solid fa-newspaper" style="font-size: 20px;"></i>
                        </div>
                        <span class="text-xs font-medium text-slate-600 group-hover:text-blue-700">Orçamentos</span>
                    </a>
                </li>
            @endcan

            <!-- 3 -->
            <li>
                <a href="{{route('dashboard')}}"
                   class="group flex w-28 flex-col items-center gap-2 rounded-2xl p-2 transition hover:scale-105">
                    <div
                        class="icon grid h-12 w-12 place-items-center rounded-xl border border-slate-200 text-blue-500 {{isActive('dashboard')}}">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M3 3h8v8H3V3Zm10 0h8v5h-8V3ZM3 13h5v8H3v-8Zm7 0h11v8H10v-8Z"/>
                        </svg>
                    </div>
                    <span class="text-xs font-medium text-slate-600 group-hover:text-blue-700">Dashboard</span>
                </a>
            </li>

            @can('entitie_customer_view')
                <li>
                    <a href="{{route('customer.view')}}"
                       class="group flex w-28 flex-col items-center gap-2 rounded-2xl p-2 transition hover:scale-105">
                        <div
                            class="icon grid h-12 w-12 place-items-center rounded-xl border border-slate-200 text-blue-500 {{isActive('customer.view')}}">
                            <i class="fa-solid fa-user" style="font-size: 20px;"></i>
                        </div>
                        <span class="text-xs font-medium text-slate-600 group-hover:text-blue-700">Clientes</span>
                    </a>
                </li>
            @endcan

            @can('sales_service_view')
                <li>
                    <a href="{{route('service.view')}}"
                       class="group flex w-28 flex-col items-center gap-2 rounded-2xl p-2 transition hover:scale-105">
                        <div
                            class="icon grid h-12 w-12 place-items-center rounded-xl border border-slate-200 text-blue-500 {{isActive('service.view')}}">
                            <i class="fa-solid fa-suitcase" style="font-size: 20px;"></i>
                        </div>
                        <span class="text-xs font-medium text-slate-600 group-hover:text-blue-700">Serviços</span>
                    </a>
                </li>
            @endcan

            @canany(['finance_payable_view', 'finance_receivable_view'])
                <li>
                    <button id="more-btn" type="button" aria-haspopup="menu" aria-expanded="false"
                            class="group flex w-28 flex-col items-center gap-2 rounded-2xl p-2 transition hover:scale-105 focus:outline-none">
                        <div
                            class="icon grid h-12 w-12 place-items-center rounded-xl text-blue-500 bg-white border border-slate-200 hover:bg-blue-700 hover:text-white hover:shadow-md hover:ring-2 hover:ring-blue-300">
                            <i class="fa-solid fa-ellipsis"></i>
                        </div>
                        <span class="text-xs font-semibold text-slate-700 group-hover:text-blue-700">Mais</span>
                    </button>
                </li>
            @endcanany
        </ul>
    </nav>

    <div id="more-menu" role="menu" tabindex="-1"
         class="fixed z-50 hidden min-w-[260px] rounded-2xl border border-slate-200 bg-white/95 backdrop-blur shadow-xl ring-1 ring-black/5">
        <div class="p-2 grid grid-cols-1 gap-1">

            @can('finance_payable_view')
                <a href="{{route('account-payable-view')}}" role="menuitem"
                   class="group flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-blue-50">
                    <div
                        class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-blue-500 group-hover:bg-blue-700 group-hover:text-white">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-800">A pagar</p>
                        <p class="text-xs text-slate-500">Contas e vencimentos</p>
                    </div>
                </a>
            @endcan

            @can('finance_receivable_view')
                <a href="{{route('account-receivable-view')}}" role="menuitem"
                   class="group flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-blue-50">
                    <div
                        class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-blue-500 group-hover:bg-blue-700 group-hover:text-white">
                        <i class="fa-solid fa-money-bill"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-800">A receber</p>
                        <p class="text-xs text-slate-500">Orçamentos e propostas</p>
                    </div>
                </a>
            @endcan

            @role('admin')
            <a href="{{route('roles.index')}}" role="menuitem"
               class="group flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-blue-50">
                <div
                    class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-blue-500 group-hover:bg-blue-700 group-hover:text-white">
                    <i class="fa-solid fa-lock"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-slate-800">Permissões</p>
                    <p class="text-xs text-slate-500">Roles & access</p>
                </div>
            </a>

            <a href="{{route('module.index')}}" role="menuitem"
               class="group flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-blue-50">
                <div
                    class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-blue-500 group-hover:bg-blue-700 group-hover:text-white">
                    <i class="fa-solid fa-crown"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-slate-800">Módulos</p>
                    <p class="text-xs text-slate-500">Gerenciar add-ons</p>
                </div>
            </a>
            @endrole
        </div>
    </div>
</section>

@push('scripts')
    <script>
        (() => {
            const btn = document.getElementById('more-btn');
            const menu = document.getElementById('more-menu');

            // garante que o popup não herde stacking/overflow do container
            if (menu.parentElement !== document.body) {
                document.body.appendChild(menu);
            }

            function openMenu() {
                const btn = document.getElementById('more-btn');
                const menu = document.getElementById('more-menu');
                const gap = -25;
                const margin = 12;

                // 1) mostrar invisível para medir
                menu.style.visibility = 'hidden';
                menu.classList.remove('hidden');

                // 2) medidas
                const r = btn.getBoundingClientRect();
                const mw = menu.offsetWidth;
                const mh = menu.offsetHeight;

                // 3) posição: à ESQUERDA do botão, centralizado verticalmente
                let top = r.top + (r.height / 2) - (mh / 2);
                let left = r.left - mw - gap;

                // clamp vertical
                top = Math.max(margin, Math.min(top, window.innerHeight - mh - margin));

                // fallback se não couber à esquerda: abaixo centralizado
                if (left < margin) {
                    left = Math.max(margin, Math.min(r.left + (r.width / 2) - (mw / 2), window.innerWidth - mw - margin));
                    top = r.bottom + gap;
                }

                // 4) aplica e revela
                menu.style.top = `${top}px`;
                menu.style.left = `${left}px`;
                menu.style.visibility = 'visible';
                btn.setAttribute('aria-expanded', 'true');
                menu.focus({preventScroll: true});

            }

            function closeMenu() {
                if (!menu.classList.contains('hidden')) {
                    menu.classList.add('hidden');
                    btn.setAttribute('aria-expanded', 'false');
                    btn.focus({preventScroll: true});
                }
            }

            btn.addEventListener('click', (e) => {
                e.preventDefault();
                if (menu.classList.contains('hidden')) openMenu(); else closeMenu();
            });

            // click fora
            document.addEventListener('click', (e) => {
                if (!menu.classList.contains('hidden')) {
                    if (!menu.contains(e.target) && !btn.contains(e.target)) closeMenu();
                }
            });

            // teclas
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') closeMenu();
                if ((e.key === 'Enter' || e.key === ' ') && document.activeElement === btn) {
                    e.preventDefault();
                    openMenu();
                }
            });

            // reposiciona no resize/scroll
            window.addEventListener('resize', () => {
                if (!menu.classList.contains('hidden')) openMenu();
            });
            window.addEventListener('scroll', () => {
                if (!menu.classList.contains('hidden')) openMenu();
            }, true);
        })();
    </script>
@endpush
