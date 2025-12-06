@php
    use Illuminate\Support\Facades\Auth;

    $tenantId = \App\Support\CustomerContext::get();
    $user = Auth::user();

    $menuItems = collect([
        // ------- PRINCIPAIS / GERAIS -------
        [
            'key'   => 'dashboard',
            'label' => 'Dashboard',
            'route' => route('dashboard'),
            'icon'  => '<svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M3 3h8v8H3V3Zm10 0h8v5h-8V3ZM3 13h5v8H3v-8Zm7 0h11v8H10v-8Z"/>
                        </svg>',
            'group' => null,
            'description' => null,
            'permissions' => ["{$tenantId}_visualizar_dashboard"],
            'active' => isActive('dashboard'),
        ],
        [
            'key'   => 'chat-ia',
            'label' => 'Chat IA',
            'route' => route('chat.view'),
            'icon'  => '<i class="fa-solid fa-robot"></i>',
            'group' => null,
            'description' => null,
            'permissions' => ["{$tenantId}_visualizar_chat_ia"],
            'active' => isActive('customer.view'), // igual ao que já usava
        ],
        [
            'key'   => 'cobrancas',
            'label' => 'Cobranças',
            'route' => route('service-order-invoice.view'),
            'icon'  => '<i class="fa-solid fa-file-invoice" style="font-size: 20px;"></i>',
            'group' => 'Financeiro',
            'description' => 'Orçamentos e propostas',
            'permissions' => ["{$tenantId}_visualizar_cobrancas"],
            'active' => isActive('service-order-invoice.view'),
        ],
        [
            'key'   => 'service-order',
            'label' => 'Ordens serviço',
            'route' => route('service-order.view'),
            'icon'  => '<i class="fa-solid fa-clipboard-list"></i>',
            'group' => null,
            'description' => null,
            'permissions' => ["{$tenantId}_visualizar_ordem_servico"],
            'active' => isActive('service-order.view'),
        ],
        [
            'key'   => 'clientes',
            'label' => 'Clientes',
            'route' => route('customer.view'),
            'icon'  => '<i class="fa-solid fa-id-card"></i>',
            'group' => 'Entidades',
            'description' => 'Gestão de clientes',
            'permissions' => ["{$tenantId}_visualizar_clientes"],
            'active' => isActive('customer.view'),
        ],

        // ------- ADMIN -------
        [
            'key'   => 'roles',
            'label' => 'Permissões',
            'route' => route('roles.index'),
            'icon'  => '<i class="fa-solid fa-lock"></i>',
            'group' => 'Administração',
            'description' => 'Roles & access',
            'roles' => ['admin'],
        ],
        [
            'key'   => 'modules',
            'label' => 'Módulos',
            'route' => route('module.index'),
            'icon'  => '<i class="fa-solid fa-crown"></i>',
            'group' => 'Administração',
            'description' => 'Gerenciar add-ons',
            'roles' => ['admin'],
        ],

        // ------- FINANCEIRO -------
        [
            'key'   => 'account-receivable',
            'label' => 'A receber',
            'route' => route('account-receivable.view'),
            'icon'  => '<i class="fa-solid fa-money-bill"></i>',
            'group' => 'Financeiro',
            'description' => 'Orçamentos e propostas',
            'permissions' => ["{$tenantId}_visualizar_contas_a_receber"],
        ],
        [
            'key'   => 'account-payable',
            'label' => 'A pagar',
            'route' => route('account-payable.view'),
            'icon'  => '<i class="fa-solid fa-wallet"></i>',
            'group' => 'Financeiro',
            'description' => 'Contas e vencimentos',
            'permissions' => ["{$tenantId}_visualizar_contas_a_pagar"],
        ],

        // ------- PERMISSÕES (view de perfil/permissões) -------
        [
            'key'   => 'roles-permissions',
            'label' => 'Permissões',
            'route' => route('roles-permissions.view'),
            'icon'  => '<i class="fa-solid fa-lock"></i>',
            'group' => 'Permissões',
            'description' => 'Perfis & permissões',
            'permissions' => [
                "{$tenantId}_visualizar_permissoes",
                "{$tenantId}_visualizar_perfis",
            ],
            'permission_mode' => 'any', // se tiver qualquer uma, mostra
        ],

        // ------- ENTIDADES -------
        [
            'key'   => 'user',
            'label' => 'Usuários',
            'route' => route('user.view'),
            'icon'  => '<i class="fa-solid fa-user-gear"></i>',
            'group' => 'Entidades',
            'description' => 'Gestão de logins',
            'permissions' => ["{$tenantId}_visualizar_usuarios"],
        ],
        [
            'key'   => 'supplier',
            'label' => 'Fornecedores',
            'route' => route('supplier.view'),
            'icon'  => '<i class="fa-solid fa-user-gear"></i>',
            'group' => 'Entidades',
            'description' => 'Gestão de fornecedores',
            'permissions' => ["{$tenantId}_visualizar_fornecedores"],
        ],

        // ------- RH -------
        [
            'key'   => 'department',
            'label' => 'Departamentos',
            'route' => route('department.view'),
            'icon'  => '<i class="fa-solid fa-diagram-project"></i>',
            'group' => 'Recursos Humanos',
            'description' => 'Setores internos',
            'permissions' => ["{$tenantId}_visualizar_departamentos"],
        ],
        [
            'key'   => 'employee',
            'label' => 'Funcionários',
            'route' => route('employee.view'),
            'icon'  => '<i class="fa-solid fa-users"></i>',
            'group' => 'Recursos Humanos',
            'description' => 'Gestão de funcionários',
            'permissions' => ["{$tenantId}_visualizar_funcionarios"],
        ],
        [
            'key'   => 'benefit',
            'label' => 'Benefícios',
            'route' => route('benefit.view'),
            'icon'  => '<i class="fa-solid fa-gift"></i>',
            'group' => 'Recursos Humanos',
            'description' => 'Regras e vantagens',
            'permissions' => ["{$tenantId}_visualizar_beneficios"],
        ],
        [
            'key'   => 'employee-benefit',
            'label' => 'Benefícios x Funcionários',
            'route' => route('employee-benefit.view'),
            'icon'  => '<i class="fa-solid fa-link"></i>',
            'group' => 'Recursos Humanos',
            'description' => 'Vincular benefícios',
            'permissions' => ["{$tenantId}_visualizar_beneficios_funcionarios"],
        ],

        // ------- CATÁLOGO -------
        [
            'key'   => 'service-type',
            'label' => 'Tipos de serviço',
            'route' => route('service-type.view'),
            'icon'  => '<i class="fa-solid fa-tags"></i>',
            'group' => 'Catálogo',
            'description' => 'Categorias de serviço',
            'permissions' => ["{$tenantId}_visualizar_tipo_servico"],
        ],
        [
            'key'   => 'service-item',
            'label' => 'Serviços',
            'route' => route('service-item.view'),
            'icon'  => '<i class="fa-solid fa-list-check"></i>',
            'group' => 'Catálogo',
            'description' => 'Catálogo de serviços',
            'permissions' => ["{$tenantId}_visualizar_servico"],
        ],
        [
            'key'   => 'equipment',
            'label' => 'Equipamentos',
            'route' => route('equipment.view'),
            'icon'  => '<i class="fa-solid fa-screwdriver-wrench"></i>',
            'group' => 'Catálogo',
            'description' => 'Catálogo de equipamentos',
            'permissions' => ["{$tenantId}_visualizar_equipamentos"],
        ],
        [
            'key'   => 'part',
            'label' => 'Peças',
            'route' => route('part.view'),
            'icon'  => '<i class="fa-solid fa-cubes"></i>',
            'group' => 'Catálogo',
            'description' => 'Catálogo de peças',
            'permissions' => ["{$tenantId}_visualizar_pecas"],
        ],
        [
            'key'   => 'equipment-part',
            'label' => 'Peças x Equipamentos',
            'route' => route('equipment-part.view'),
            'icon'  => '<i class="fa-solid fa-sitemap"></i>',
            'group' => 'Catálogo',
            'description' => 'Vincular peças',
            'permissions' => ["{$tenantId}_visualizar_pecas_equipamentos"],
        ],
    ]);

    $visibleItems = $menuItems->filter(function ($item) use ($user) {
        if (!empty($item['roles'] ?? null)) {
            if (! $user->hasAnyRole($item['roles'])) {
                return false;
            }
        }

        if (!empty($item['permissions'] ?? null)) {
            $perms = (array) $item['permissions'];
            $mode  = $item['permission_mode'] ?? 'all';

            if ($mode === 'any') {
                if (! $user->canAny($perms)) {
                    return false;
                }
            } else {
                foreach ($perms as $perm) {
                    if (! $user->can($perm)) {
                        return false;
                    }
                }
            }
        }

        return true;
    })->values();

    $topItems  = $visibleItems->take(5);
    $moreItems = $visibleItems->slice(5)->groupBy(fn ($item) => $item['group'] ?? null);
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
            @foreach($topItems as $item)
                <li>
                    <a href="{{ $item['route'] }}"
                       class="group flex w-28 flex-col items-center gap-2 rounded-2xl p-2 transition hover:scale-105">
                        <div
                            class="icon grid h-12 w-12 place-items-center rounded-xl border border-slate-200 text-blue-500 {{ $item['active'] ?? '' }}">
                            {!! $item['icon'] !!}
                        </div>
                        <span class="text-xs font-medium text-slate-600 group-hover:text-blue-700">
                    {{ $item['label'] }}
                </span>
                    </a>
                </li>
            @endforeach

            @if($moreItems->flatten()->isNotEmpty())
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
            @endif
        </ul>
    </nav>

    {{-- DROPDOWN MAIS --}}
    @if($moreItems->flatten()->isNotEmpty())
        <div id="more-menu" role="menu" tabindex="-1"
             class="fixed z-50 hidden min-w-[260px] rounded-2xl border border-slate-200 bg-white/95 backdrop-blur shadow-xl ring-1 ring-black/5">
            <div class="p-2 grid grid-cols-1 gap-1">

                @foreach($moreItems as $group => $items)
                    @if($group)
                        <div class="px-3 pt-1 pb-0">
                            <p class="text-[11px] font-semibold tracking-wide uppercase text-slate-400">
                                {{ $group }}
                            </p>
                        </div>
                    @endif

                    @foreach($items as $item)
                        <a href="{{ $item['route'] }}" role="menuitem"
                           class="group flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-blue-50">
                            <div
                                class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-blue-500 group-hover:bg-blue-700 group-hover:text-white">
                                {!! $item['icon'] !!}
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-800">{{ $item['label'] }}</p>
                                @if(!empty($item['description']))
                                    <p class="text-xs text-slate-500">{{ $item['description'] }}</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                @endforeach

            </div>
        </div>
    @endif
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
