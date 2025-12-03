@php
    $tenantId = \App\Support\CustomerContext::get();
@endphp

<section class="mx-auto max-w-7xl px-4 sm:px-6 py-8">
    <div class="rounded-3xl bg-gradient-to-r from-blue-600 to-sky-500 p-6 sm:p-8 text-white shadow-md">
        <div class="flex flex-col gap-6">
            <div>
                <p class="text-sm/5 opacity-90">Bem-vindo(a) de volta, {{ Auth::user()->name }}</p>
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
                <a href="{{ route('billing.index', auth()->id()) }}"
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
            {{-- ChatIA --}}
            @can("{$tenantId}_visualizar_dashboard")
                <li>
                    <a href="{{ route('chat.view') }}"
                       class="group flex w-28 flex-col items-center gap-2 rounded-2xl p-2 transition hover:scale-105">
                        <div
                            class="icon grid h-12 w-12 place-items-center rounded-xl border border-slate-200 text-blue-500 {{ isActive('customer.view') }}">
                            <i class="fa-solid fa-robot"></i>
                        </div>
                        <span class="text-xs font-medium text-slate-600 group-hover:text-blue-700">Chat IA</span>
                    </a>
                </li>
            @endcan

            {{-- Clientes --}}
            @can("{$tenantId}_visualizar_clientes")
                <li>
                    <a href="{{ route('customer.view') }}"
                       class="group flex w-28 flex-col items-center gap-2 rounded-2xl p-2 transition hover:scale-105">
                        <div
                            class="icon grid h-12 w-12 place-items-center rounded-xl border border-slate-200 text-blue-500 {{ isActive('customer.view') }}">
                            <i class="fa-solid fa-id-card"></i>
                        </div>
                        <span class="text-xs font-medium text-slate-600 group-hover:text-blue-700">Clientes</span>
                    </a>
                </li>
            @endcan

            {{--             Dashboard--}}
            @can("{$tenantId}_visualizar_dashboard")
                <li>
                    <a href="{{ route('dashboard') }}"
                       class="group flex w-28 flex-col items-center gap-2 rounded-2xl p-2 transition hover:scale-105">
                        <div
                            class="icon grid h-12 w-12 place-items-center rounded-xl border border-slate-200 text-blue-500 {{ isActive('dashboard') }}">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M3 3h8v8H3V3Zm10 0h8v5h-8V3ZM3 13h5v8H3v-8Zm7 0h11v8H10v-8Z"/>
                            </svg>
                        </div>
                        <span class="text-xs font-medium text-slate-600 group-hover:text-blue-700">Dashboard</span>
                    </a>
                </li>
            @endcan

            {{-- Ordens de Serviço --}}
{{--            @can("{$tenantId}_visualizar_ordem_servico")--}}
                <li>
                    <a href="{{ route('service-order.view') }}"
                       class="group flex w-28 flex-col items-center gap-2 rounded-2xl p-2 transition hover:scale-105">
                        <div
                            class="icon grid h-12 w-12 place-items-center rounded-xl border border-slate-200 text-blue-500 {{ isActive('service-order.view') }}">
                            <i class="fa-solid fa-clipboard-list"></i>
                        </div>
                        <span class="text-xs font-medium text-slate-600 group-hover:text-blue-700">Ordens serviço</span>
                    </a>
                </li>
{{--            @endcan--}}

            {{-- Equipamentos --}}
            @can("{$tenantId}_visualizar_dashboard")
                <li>
                    <a href="{{ route('equipment.view') }}"
                       class="group flex w-28 flex-col items-center gap-2 rounded-2xl p-2 transition hover:scale-105">
                        <div
                            class="icon grid h-12 w-12 place-items-center rounded-xl border border-slate-200 text-blue-500 {{ isActive('equipment.view') }}">
                            <i class="fa-solid fa-screwdriver-wrench"></i>
                        </div>
                        <span class="text-xs font-medium text-slate-600 group-hover:text-blue-700">Equipamentos</span>
                    </a>
                </li>
            @endcan

            {{-- Mais --}}
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
        </ul>
    </nav>

    {{-- DROPDOWN MAIS --}}
    <div id="more-menu" role="menu" tabindex="-1"
         class="fixed z-50 hidden min-w-[260px] rounded-2xl border border-slate-200 bg-white/95 backdrop-blur shadow-xl ring-1 ring-black/5">
        <div class="p-2 grid grid-cols-1 gap-1">

            {{-- Admin --}}
            @role('admin')
            <div class="px-3 pt-1 pb-0">
                <p class="text-[11px] font-semibold tracking-wide uppercase text-slate-400">Administração</p>
            </div>

            <a href="{{ route('roles.index') }}" role="menuitem"
               class="group flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-blue-50">
                <div
                    class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-blue-500 group-hover:bg-blue-700 group-hover:text-white">
                    <i class="fa-solid fa-lock"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-slate-800">Permissões</p>
                    <p class="text-xs text-slate-500">Roles &amp; access</p>
                </div>
            </a>

            <a href="{{ route('module.index') }}" role="menuitem"
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

            <div class="px-3 pt-1 pb-0">
                <p class="text-[11px] font-semibold tracking-wide uppercase text-slate-400">Permissões</p>
            </div>

            <a href="{{ route('roles-permissions.view') }}" role="menuitem"
               class="group flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-blue-50">
                <div
                    class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-blue-500 group-hover:bg-blue-700 group-hover:text-white">
                    <i class="fa-solid fa-lock"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-slate-800">Permissões</p>
                    <p class="text-xs text-slate-500">Perfis &amp; permissões</p>
                </div>
            </a>

            {{-- Entities --}}
            <div class="px-3 pt-3 pb-0">
                <p class="text-[11px] font-semibold tracking-wide uppercase text-slate-400">Entidades</p>
            </div>

            <a href="{{ route('user.view') }}" role="menuitem"
               class="group flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-blue-50">
                <div
                    class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-blue-500 group-hover:bg-blue-700 group-hover:text-white">
                    <i class="fa-solid fa-user-gear"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-slate-800">Usuários</p>
                    <p class="text-xs text-slate-500">Gestão de logins</p>
                </div>
            </a>

            <a href="{{ route('supplier.view') }}" role="menuitem"
               class="group flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-blue-50">
                <div
                    class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-blue-500 group-hover:bg-blue-700 group-hover:text-white">
                    <i class="fa-solid fa-user-gear"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-slate-800">Fornecedores</p>
                    <p class="text-xs text-slate-500">Gestão de fornecedores</p>
                </div>
            </a>

            {{-- Human Resources --}}
            <div class="px-3 pt-3 pb-0">
                <p class="text-[11px] font-semibold tracking-wide uppercase text-slate-400">Recursos Humanos</p>
            </div>

            <a href="{{ route('department.view') }}" role="menuitem"
               class="group flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-blue-50">
                <div
                    class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-blue-500 group-hover:bg-blue-700 group-hover:text-white">
                    <i class="fa-solid fa-diagram-project"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-slate-800">Departamentos</p>
                    <p class="text-xs text-slate-500">Setores internos</p>
                </div>
            </a>

            <a href="{{ route('employee.view') }}" role="menuitem"
               class="group flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-blue-50">
                <div
                    class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-blue-500 group-hover:bg-blue-700 group-hover:text-white">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-slate-800">Funcionários</p>
                    <p class="text-xs text-slate-500">Gestão de funcionários</p>
                </div>
            </a>

            <a href="{{ route('benefit.view') }}" role="menuitem"
               class="group flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-blue-50">
                <div
                    class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-blue-500 group-hover:bg-blue-700 group-hover:text-white">
                    <i class="fa-solid fa-gift"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-slate-800">Benefícios</p>
                    <p class="text-xs text-slate-500">Regras e vantagens</p>
                </div>
            </a>

            <a href="{{ route('employee-benefit.view') }}" role="menuitem"
               class="group flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-blue-50">
                <div
                    class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-blue-500 group-hover:bg-blue-700 group-hover:text-white">
                    <i class="fa-solid fa-link"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-slate-800">Benefícios x Funcionários</p>
                    <p class="text-xs text-slate-500">Vincular benefícios</p>
                </div>
            </a>

            {{-- Catálogo --}}
            <div class="px-3 pt-3 pb-0">
                <p class="text-[11px] font-semibold tracking-wide uppercase text-slate-400">Catálogo</p>
            </div>

            <a href="{{ route('service-type.view') }}" role="menuitem"
               class="group flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-blue-50">
                <div
                    class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-blue-500 group-hover:bg-blue-700 group-hover:text-white">
                    <i class="fa-solid fa-tags"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-slate-800">Tipos de serviço</p>
                    <p class="text-xs text-slate-500">Categorias de serviço</p>
                </div>
            </a>

            <a href="{{ route('service-item.view') }}" role="menuitem"
               class="group flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-blue-50">
                <div
                    class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-blue-500 group-hover:bg-blue-700 group-hover:text-white">
                    <i class="fa-solid fa-list-check"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-slate-800">Serviços</p>
                    <p class="text-xs text-slate-500">Catálogo de serviços</p>
                </div>
            </a>

            <a href="{{ route('part.view') }}" role="menuitem"
               class="group flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-blue-50">
                <div
                    class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-blue-500 group-hover:bg-blue-700 group-hover:text-white">
                    <i class="fa-solid fa-cubes"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-slate-800">Peças</p>
                    <p class="text-xs text-slate-500">Catálogo de peças</p>
                </div>
            </a>

            <a href="{{ route('equipment-part.view') }}" role="menuitem"
               class="group flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-blue-50">
                <div
                    class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-blue-500 group-hover:bg-blue-700 group-hover:text-white">
                    <i class="fa-solid fa-sitemap"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-slate-800">Peças x Equipamentos</p>
                    <p class="text-xs text-slate-500">Vincular peças</p>
                </div>
            </a>
        </div>
    </div>
</section>

@push('scripts')
    <script>
        (() => {
            const btn = document.getElementById('more-btn');
            const menu = document.getElementById('more-menu');

            if (menu.parentElement !== document.body) {
                document.body.appendChild(menu);
            }

            function openMenu() {
                const btn = document.getElementById('more-btn');
                const menu = document.getElementById('more-menu');
                const gap = -25;
                const margin = 12;

                menu.style.visibility = 'hidden';
                menu.classList.remove('hidden');

                const r = btn.getBoundingClientRect();
                const mw = menu.offsetWidth;
                const mh = menu.offsetHeight;

                let top = r.top + (r.height / 2) - (mh / 2);
                let left = r.left - mw - gap;

                top = Math.max(margin, Math.min(top, window.innerHeight - mh - margin));

                if (left < margin) {
                    left = Math.max(
                        margin,
                        Math.min(r.left + (r.width / 2) - (mw / 2), window.innerWidth - mw - margin)
                    );
                    top = r.bottom + gap;
                }

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

            document.addEventListener('click', (e) => {
                if (!menu.classList.contains('hidden')) {
                    if (!menu.contains(e.target) && !btn.contains(e.target)) closeMenu();
                }
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') closeMenu();
                if ((e.key === 'Enter' || e.key === ' ') && document.activeElement === btn) {
                    e.preventDefault();
                    openMenu();
                }
            });

            window.addEventListener('resize', () => {
                if (!menu.classList.contains('hidden')) openMenu();
            });
            window.addEventListener('scroll', () => {
                if (!menu.classList.contains('hidden')) openMenu();
            }, true);
        })();
    </script>
@endpush
