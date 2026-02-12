@php
    use Illuminate\Support\Facades\Auth;use Illuminate\Support\Str;

    $tenantId = \App\Support\TenantUser\CustomerContext::get();
    $user = Auth::user();

    $menuItems = collect([
        // ------- PRINCIPAIS / GERAIS -------
        [
            'key'   => 'chat-ia',
            'label' => 'Chat IA',
            'route' => route('chat.view'),
            'icon'  => '<i class="fa-solid fa-robot"></i>',
            'group' => null,
            'description' => null,
            'permissions' => ["{$tenantId}_visualizar_chat_ia"],
            'active' => isActive('chat.view'),
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

        // ------- PART ORDERS -------
        [
            'key'   => 'part-order',
            'label' => 'Pedidos',
            'route' => route('part-order.view'),
            'icon'  => '<i class="fa-regular fa-file-zipper"></i>',
            'group' => 'Pedidos',
            'description' => 'Solicitação de peças',
            'permissions' => ["{$tenantId}_visualizar_pedidos"],
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
            'permission_mode' => 'any',
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

        // ------- ESTOQUE -------
        [
            'key'   => 'stock',
            'label' => 'Inventário',
            'route' => route('stock.view'),
            'icon'  => '<i class="fa-solid fa-cart-flatbed"></i>',
            'group' => 'Estoque',
            'description' => 'Gestão de estoque',
            'permissions' => ["{$tenantId}_visualizar_estoque"],
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

    $MAX_PINS = 5;

    $defaultTopItems = $visibleItems->take($MAX_PINS);
    $defaultTopKeys  = $defaultTopItems->pluck('key')->values();

    // grupos de todos itens (com fallback)
    $groupsAll = $visibleItems
        ->map(fn ($i) => $i['group'] ?? 'Outros')
        ->unique()
        ->values();

    // itens do "Mais" padrão = tudo que não está no top padrão
    $restItems = $visibleItems->slice($MAX_PINS)
        ->map(function ($item) {
            $item['group'] = $item['group'] ?? 'Outros';
            return $item;
        });

    $restByGroup = $restItems->groupBy('group');

    // default group (primeiro grupo que tem itens; se não tiver, primeiro grupo da lista)
    $defaultGroupName = $groupsAll->first(function ($g) use ($restByGroup) {
        return ($restByGroup->get($g, collect())->count() > 0);
    }) ?? ($groupsAll->first() ?? 'Outros');

    $defaultGroupId = Str::slug($defaultGroupName);

    // dataset pro JS (todos itens visíveis)
    $itemsForJs = $visibleItems->map(function ($item) {
        $group = $item['group'] ?? 'Outros';
        return [
            'key'         => $item['key'],
            'label'       => $item['label'],
            'route'       => $item['route'],
            'icon'        => $item['icon'],
            'group'       => $group,
            'groupId'     => Str::slug($group),
            'description' => $item['description'] ?? null,
        ];
    })->values();
@endphp

{{-- HERO --}}
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

{{-- GUIA RÁPIDO + MAIS --}}
<section class="mx-auto max-w-7xl px-4 sm:px-6">

    <div class="flex items-center justify-end gap-2 mb-2">
        <button id="quick-edit"
                type="button"
                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
            <i class="fa-solid fa-wand-magic-sparkles"></i>
            Personalizar
        </button>

        <button id="quick-reset"
                type="button"
                class="hidden items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
            <i class="fa-solid fa-rotate-left"></i>
            Reset
        </button>
    </div>

    <nav aria-label="Guia rápido"
         class="rounded-3xl overflow-hidden bg-gradient-to-b from-slate-50 to-blue-50 p-3">

        <ul id="quick-nav"
            data-tenant="{{ $tenantId }}"
            data-user="{{ $user->id }}"
            class="no-scrollbar flex items-center gap-2 overflow-x-auto rounded-3xl px-2 py-2 justify-start sm:justify-center">


            {{-- server render padrão (JS vai reordenar pelo localStorage) --}}
            @foreach($defaultTopItems as $item)
                <li class="shrink-0" data-qkey="{{ $item['key'] }}">
                    <a href="{{ $item['route'] }}"
                       class="group flex w-24 sm:w-28 flex-col items-center gap-2 rounded-2xl p-2 transition hover:scale-105"
                       data-qkey="{{ $item['key'] }}">
                        <div
                            class="icon grid h-11 w-11 sm:h-12 sm:w-12 place-items-center rounded-xl border border-slate-200 text-blue-500 {{ $item['active'] ?? '' }}">
                            {!! $item['icon'] !!}
                        </div>
                        <span
                            class="text-[11px] sm:text-xs font-medium text-slate-600 group-hover:text-blue-700 text-center leading-tight">
                            {{ $item['label'] }}
                        </span>
                    </a>
                </li>
            @endforeach

            @if($visibleItems->count() > $MAX_PINS)
                <li id="quick-more-slot" class="shrink-0">
                    <button id="more-btn" type="button" aria-haspopup="menu" aria-expanded="false"
                            class="group flex w-24 sm:w-28 flex-col items-center gap-2 rounded-2xl p-2 transition hover:scale-105 focus:outline-none">
                        <div
                            class="icon grid h-11 w-11 sm:h-12 sm:w-12 place-items-center rounded-xl text-blue-500 bg-white border border-slate-200 hover:bg-blue-700 hover:text-white hover:shadow-md hover:ring-2 hover:ring-blue-300">
                            <i class="fa-solid fa-ellipsis"></i>
                        </div>
                        <span
                            class="text-[11px] sm:text-xs font-semibold text-slate-700 group-hover:text-blue-700">Mais</span>
                    </button>
                </li>
            @endif
        </ul>
    </nav>

    @if($visibleItems->count() > $MAX_PINS)
        <div id="more-menu"
             role="menu"
             tabindex="-1"
             data-default-group="{{ $defaultGroupId }}"
             class="fixed z-50 hidden w-[640px] max-w-[94vw] overflow-hidden rounded-2xl border border-slate-200 bg-white/95 backdrop-blur shadow-xl ring-1 ring-black/5">

            <div class="grid grid-cols-12">
                {{-- CATEGORIAS --}}
                <div class="col-span-4 border-r border-slate-200/70 p-2 max-h-[70vh] overflow-y-auto">
                    @foreach($groupsAll as $groupName)
                        @php
                            $gid = Str::slug($groupName);
                            $count = $restByGroup->get($groupName, collect())->count();
                        @endphp

                        <button type="button"
                                data-more-group="{{ $gid }}"
                                class="more-tab w-full flex items-center justify-between gap-2 rounded-xl px-3 py-2 text-left hover:bg-blue-50 {{ $gid === $defaultGroupId ? 'bg-blue-50' : '' }}">
                            <span class="text-xs font-semibold text-slate-700">{{ $groupName }}</span>
                            <span class="more-count text-[11px] text-slate-400">{{ $count }}</span>
                        </button>
                    @endforeach
                </div>

                {{-- ITENS --}}
                <div class="col-span-8 p-2 max-h-[70vh] overflow-y-auto">
                    @foreach($groupsAll as $groupName)
                        @php
                            $gid = Str::slug($groupName);
                            $items = $restByGroup->get($groupName, collect());
                        @endphp

                        <div class="more-panel {{ $gid === $defaultGroupId ? '' : 'hidden' }}"
                             data-more-panel="{{ $gid }}">
                            <div class="sticky top-0 z-10 bg-white/95 backdrop-blur px-2 py-2">
                                <p class="text-[11px] font-semibold tracking-wide uppercase text-slate-400">{{ $groupName }}</p>
                            </div>

                            <div class="more-items grid grid-cols-1 gap-1">
                                @foreach($items as $item)
                                    <a href="{{ $item['route'] }}" role="menuitem"
                                       class="more-row group flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-blue-50"
                                       data-qkey="{{ $item['key'] }}"
                                       data-group="{{ $gid }}">
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
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    @endif
</section>

@push('scripts')
    <script>
        (() => {
            const MAX_PINS = 5;

            const ITEMS = @json($itemsForJs);
            const DEFAULT_TOP_KEYS = @json($defaultTopKeys);

            const quickNav = document.getElementById('quick-nav');
            const moreBtn = document.getElementById('more-btn');
            const moreMenu = document.getElementById('more-menu');
            const moreSlot = document.getElementById('quick-more-slot');

            const editBtn = document.getElementById('quick-edit');
            const resetBtn = document.getElementById('quick-reset');

            if (!quickNav) return;

            const tenantId = quickNav.dataset.tenant || 't';
            const userId = quickNav.dataset.user || 'u';
            const storageKey = `quicknav:${tenantId}:${userId}`;

            const byKey = Object.fromEntries(ITEMS.map(i => [i.key, i]));
            const allKeys = ITEMS.map(i => i.key);

            let pinned = [];
            let editing = false;
            let isDragging = false;
            let closeTimer = null;
            let currentGroup = moreMenu?.getAttribute('data-default-group') || null;

            function uniq(arr) {
                const out = [];
                for (const k of arr) {
                    if (k && byKey[k] && !out.includes(k)) out.push(k);
                }
                return out;
            }

            function normalizePinned(arr) {
                let p = uniq(arr);

                // completa até MAX_PINS com itens restantes
                for (const k of allKeys) {
                    if (p.length >= MAX_PINS) break;
                    if (!p.includes(k)) p.push(k);
                }

                return p.slice(0, Math.min(MAX_PINS, allKeys.length));
            }

            function loadPinned() {
                try {
                    const raw = localStorage.getItem(storageKey);
                    if (!raw) return null;
                    const parsed = JSON.parse(raw);
                    if (!Array.isArray(parsed?.pinned)) return null;
                    return parsed.pinned;
                } catch {
                    return null;
                }
            }

            function savePinned() {
                try {
                    localStorage.setItem(storageKey, JSON.stringify({pinned}));
                } catch {
                }
            }

            function isActiveUrl(href) {
                try {
                    const u = new URL(href, window.location.origin);
                    return u.pathname === window.location.pathname;
                } catch {
                    return false;
                }
            }

            function buildTopLi(item) {
                const li = document.createElement('li');
                li.className = 'shrink-0';
                li.dataset.qkey = item.key;

                const active = isActiveUrl(item.route);

                const iconBase = 'icon grid h-11 w-11 sm:h-12 sm:w-12 place-items-center rounded-xl border';
                const iconClass = active
                    ? `${iconBase} border-blue-700 bg-blue-700 text-white shadow-md ring-2 ring-blue-300`
                    : `${iconBase} border-slate-200 text-blue-500`;

                li.innerHTML = `
            <a href="${item.route}"
               data-qkey="${item.key}"
               class="group flex w-24 sm:w-28 flex-col items-center gap-2 rounded-2xl p-2 transition hover:scale-105">
                <div class="${iconClass}">
                    ${item.icon}
                </div>
                <span class="text-[11px] sm:text-xs font-medium text-slate-600 group-hover:text-blue-700 text-center leading-tight">
                    ${item.label}
                </span>
            </a>
        `;

                return li;
            }

            function buildMoreRow(item) {
                const a = document.createElement('a');
                a.href = item.route;
                a.setAttribute('role', 'menuitem');
                a.className = 'more-row group flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-blue-50';
                a.dataset.qkey = item.key;
                a.dataset.group = item.groupId;

                a.innerHTML = `
            <div class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-blue-500 group-hover:bg-blue-700 group-hover:text-white">
                ${item.icon}
            </div>
            <div class="min-w-0">
                <p class="text-sm font-medium text-slate-800">${item.label}</p>
                ${item.description ? `<p class="text-xs text-slate-500">${item.description}</p>` : ``}
            </div>
        `;

                return a;
            }

            function applyEditingState() {
                const nav = quickNav.closest('nav');
                if (nav) {
                    // mantém o shape sempre
                    nav.classList.add('rounded-3xl', 'overflow-hidden', 'relative');

                    // ✅ ring dentro (não corta)
                    nav.classList.toggle('ring-2', editing);
                    nav.classList.toggle('ring-inset', editing);
                    nav.classList.toggle('ring-blue-200', editing);

                    // ✅ garante que não fica “atrás” do próximo conteúdo
                    nav.classList.toggle('z-10', editing);
                }

                // draggable
                quickNav.querySelectorAll('li[data-qkey]').forEach(li => {
                    li.draggable = editing;
                    li.classList.toggle('cursor-move', editing);
                });

                if (moreMenu) {
                    moreMenu.querySelectorAll('a.more-row[data-qkey]').forEach(a => {
                        a.draggable = editing;
                        a.classList.toggle('cursor-move', editing);
                    });
                }

                if (resetBtn) resetBtn.classList.toggle('hidden', !editing);

                if (editBtn) {
                    editBtn.innerHTML = editing
                        ? `<i class="fa-solid fa-check"></i> Concluir`
                        : `<i class="fa-solid fa-wand-magic-sparkles"></i> Personalizar`;
                }
            }

            function setGroup(id) {
                if (!moreMenu || !id) return;
                currentGroup = id;

                const tabs = Array.from(moreMenu.querySelectorAll('[data-more-group]'));
                const panels = Array.from(moreMenu.querySelectorAll('[data-more-panel]'));

                tabs.forEach(t => t.classList.toggle('bg-blue-50', t.dataset.moreGroup === id));
                panels.forEach(p => p.classList.toggle('hidden', p.dataset.morePanel !== id));
            }

            function updateMenuCountsAndHideEmpty() {
                if (!moreMenu) return;

                const tabs = Array.from(moreMenu.querySelectorAll('[data-more-group]'));
                tabs.forEach(tab => {
                    const gid = tab.dataset.moreGroup;
                    const panel = moreMenu.querySelector(`[data-more-panel="${gid}"]`);
                    const wrap = panel?.querySelector('.more-items');
                    const count = wrap ? wrap.children.length : 0;

                    const countEl = tab.querySelector('.more-count');
                    if (countEl) countEl.textContent = count;

                    tab.classList.toggle('hidden', count === 0);
                });

                const firstVisible = tabs.find(t => !t.classList.contains('hidden'));
                if (firstVisible) setGroup(firstVisible.dataset.moreGroup);
            }

            function renderTop() {
                // remove só os lis de itens (mantém slot do Mais)
                quickNav.querySelectorAll('li[data-qkey]').forEach(li => li.remove());

                const insertBefore = moreSlot || null;

                pinned.forEach(k => {
                    const item = byKey[k];
                    if (!item) return;
                    const li = buildTopLi(item);
                    if (insertBefore) quickNav.insertBefore(li, insertBefore);
                    else quickNav.appendChild(li);
                });
            }

            function renderMore() {
                if (!moreMenu) return;

                // limpa todas as listas
                moreMenu.querySelectorAll('.more-items').forEach(wrap => wrap.innerHTML = '');

                const restKeys = allKeys.filter(k => !pinned.includes(k));

                for (const k of restKeys) {
                    const item = byKey[k];
                    if (!item) continue;

                    const panel = moreMenu.querySelector(`[data-more-panel="${item.groupId}"]`);
                    const wrap = panel?.querySelector('.more-items');
                    if (!wrap) continue;

                    wrap.appendChild(buildMoreRow(item));
                }

                updateMenuCountsAndHideEmpty();
            }

            function renderAll() {
                renderTop();
                renderMore();
                applyEditingState();
            }

            // --------- MENU OPEN/CLOSE (abaixo do botão + fecha ao sair) ----------
            function clearCloseTimer() {
                if (closeTimer) {
                    clearTimeout(closeTimer);
                    closeTimer = null;
                }
            }

            function closeMenu({focusBtn = false} = {}) {
                if (!moreMenu || !moreBtn) return;
                if (!moreMenu.classList.contains('hidden')) {
                    moreMenu.classList.add('hidden');
                    moreBtn.setAttribute('aria-expanded', 'false');
                    if (focusBtn) moreBtn.focus({preventScroll: true});
                }
            }

            function scheduleClose() {
                if (!moreMenu || !moreBtn) return;
                if (isDragging) return;
                clearCloseTimer();
                closeTimer = setTimeout(() => {
                    const hoveringMenu = moreMenu.matches(':hover');
                    const hoveringBtn = moreBtn.matches(':hover');
                    if (!hoveringMenu && !hoveringBtn) closeMenu();
                }, 120);
            }

            function openMenu() {
                if (!moreMenu || !moreBtn) return;

                // garante grupo válido visível
                const visibleTabs = Array.from(moreMenu.querySelectorAll('[data-more-group]'))
                    .filter(t => !t.classList.contains('hidden'));

                if (!currentGroup || !moreMenu.querySelector(`[data-more-group="${currentGroup}"]`) || moreMenu.querySelector(`[data-more-group="${currentGroup}"]`)?.classList.contains('hidden')) {
                    currentGroup = visibleTabs[0]?.dataset.moreGroup || currentGroup;
                }
                setGroup(currentGroup);

                const margin = 12;
                const gapY = 10;

                moreMenu.style.visibility = 'hidden';
                moreMenu.classList.remove('hidden');

                const r = moreBtn.getBoundingClientRect();
                const mw = moreMenu.offsetWidth;
                const mh = moreMenu.offsetHeight;

                let top = r.bottom + gapY;
                let left = r.left; // lado direito do botão (começa no left dele)

                left = Math.max(margin, Math.min(left, window.innerWidth - mw - margin));

                // se não couber embaixo, abre pra cima
                if (top + mh + margin > window.innerHeight) {
                    top = r.top - mh - gapY;
                }
                top = Math.max(margin, Math.min(top, window.innerHeight - mh - margin));

                moreMenu.style.top = `${top}px`;
                moreMenu.style.left = `${left}px`;
                moreMenu.style.visibility = 'visible';

                moreBtn.setAttribute('aria-expanded', 'true');
                moreMenu.focus({preventScroll: true});
            }

            // --------- EDITING ----------
            function setEditing(on) {
                editing = !!on;
                applyEditingState();
                if (!editing) savePinned();
            }

            // bloquear clique durante edição
            document.addEventListener('click', (e) => {
                if (!editing) return;
                const a = e.target.closest('a[data-qkey], a.more-row');
                if (a) e.preventDefault();
            });

            // dragstart / dragend
            document.addEventListener('dragstart', (e) => {
                if (!editing) return;
                const el = e.target.closest('[data-qkey]');
                if (!el) return;

                isDragging = true;
                clearCloseTimer();

                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', el.dataset.qkey);
            });

            document.addEventListener('dragend', () => {
                isDragging = false;
                scheduleClose();
            });

            // drop no TOP (reordena / puxa do Mais)
            quickNav.addEventListener('dragover', (e) => {
                if (!editing) return;
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
            });

            quickNav.addEventListener('drop', (e) => {
                if (!editing) return;
                e.preventDefault();

                const key = e.dataTransfer.getData('text/plain');
                if (!byKey[key]) return;

                const without = pinned.filter(k => k !== key);

                const targetLi = e.target.closest('li[data-qkey]');
                let idx = without.length;

                if (targetLi?.dataset?.qkey) {
                    const tkey = targetLi.dataset.qkey;
                    const tIndex = without.indexOf(tkey);
                    if (tIndex >= 0) idx = tIndex;
                }

                without.splice(idx, 0, key);
                pinned = normalizePinned(without);
                savePinned();
                renderAll();
            });

            // drop no MENU (remove do top)
            if (moreMenu) {
                moreMenu.addEventListener('dragover', (e) => {
                    if (!editing) return;
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                });

                moreMenu.addEventListener('drop', (e) => {
                    if (!editing) return;
                    e.preventDefault();

                    const key = e.dataTransfer.getData('text/plain');
                    if (!byKey[key]) return;

                    pinned = normalizePinned(pinned.filter(k => k !== key));
                    savePinned();
                    renderAll();
                });
            }

            // --------- INIT ----------
            const stored = loadPinned();
            pinned = normalizePinned(stored && stored.length ? stored : DEFAULT_TOP_KEYS);

            renderAll();

            // --------- BUTTONS ----------
            if (editBtn) {
                editBtn.addEventListener('click', () => setEditing(!editing));
            }

            if (resetBtn) {
                resetBtn.addEventListener('click', () => {
                    try {
                        localStorage.removeItem(storageKey);
                    } catch {
                    }
                    pinned = normalizePinned(DEFAULT_TOP_KEYS);
                    savePinned();
                    renderAll();
                });
            }

            // --------- MENU EVENTS ----------
            if (moreMenu && moreBtn) {
                if (moreMenu.parentElement !== document.body) document.body.appendChild(moreMenu);

                moreBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (moreMenu.classList.contains('hidden')) openMenu();
                    else closeMenu({focusBtn: true});
                });

                // tabs hover/click
                moreMenu.addEventListener('click', (e) => {
                    const tab = e.target.closest('[data-more-group]');
                    if (tab) setGroup(tab.dataset.moreGroup);
                });
                moreMenu.addEventListener('mousemove', (e) => {
                    const tab = e.target.closest('[data-more-group]');
                    if (tab) setGroup(tab.dataset.moreGroup);
                });

                // fecha ao sair com mouse (btn + menu)
                moreBtn.addEventListener('mouseenter', clearCloseTimer);
                moreMenu.addEventListener('mouseenter', clearCloseTimer);
                moreBtn.addEventListener('mouseleave', scheduleClose);
                moreMenu.addEventListener('mouseleave', scheduleClose);

                // clique fora fecha
                document.addEventListener('click', (e) => {
                    if (!moreMenu.classList.contains('hidden')) {
                        if (!moreMenu.contains(e.target) && !moreBtn.contains(e.target)) closeMenu();
                    }
                });

                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') closeMenu({focusBtn: true});
                });

                window.addEventListener('resize', () => {
                    if (!moreMenu.classList.contains('hidden')) openMenu();
                });
                window.addEventListener('scroll', () => {
                    if (!moreMenu.classList.contains('hidden')) openMenu();
                }, true);
            }
        })();
    </script>
@endpush
