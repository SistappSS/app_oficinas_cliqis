@extends('layouts.templates.template')

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6 py-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-xl font-semibold">Roles</h1>
            <button id="btn-open-role-modal"
                    class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-800">
                Nova Role
            </button>
        </div>

        <div class="mt-3 rounded-2xl border border-slate-200 bg-white shadow-sm overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-slate-500">
                <tr>
                    <th class="px-4 py-3">Nome</th>
                    <th class="px-4 py-3">Permissões</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @foreach($roles as $role)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-700">{{ $role->name }}</td>
                        <td class="px-4 py-3 text-slate-600">
                            <div class="flex flex-wrap gap-2">
                                @php
                                    $permissions = $role->permissions->pluck('name')->toArray();
                                    $displayed = array_slice($permissions, 0, 5);

                                    // === AGRUPAMENTO POR DOIS SEGMENTOS ===
                                    $grouped = collect($permissions)->groupBy(function ($perm) {
                                        $parts = explode('_', $perm);
                                        $p0 = $parts[0] ?? 'outros';
                                        $p1 = $parts[1] ?? 'geral';
                                        return strtolower($p0 . '_' . $p1); // exemplo: entitie_user
                                    })->map(function($items){
                                        return array_values($items->toArray());
                                    });
                                @endphp

                                <div class="flex flex-wrap gap-2">
                                    @foreach($displayed as $perm)
                                        <span class="inline-block rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-700">
                                            {{ $perm }}
                                        </span>
                                    @endforeach

                                    @if(count($permissions) > 5)
                                        <button type="button"
                                                class="text-sm text-blue-600 hover:underline show-role-perms"
                                                data-role="{{ $role->name }}"
                                                data-permissions='@json($grouped)'>
                                            Ver todas permissões
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal de Nova Role -->
    <div id="role-modal" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm">
        <div class="absolute left-1/2 top-1/2 w-[min(900px,95vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold">Nova Role</h2>
                <button id="btn-close-role-modal" class="rounded-lg p-2 hover:bg-slate-100">
                    <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="border border-slate-200 rounded-xl p-4 mb-6">
                <h3 class="text-sm font-semibold text-slate-700 mb-2">Criar nova permissão</h3>

                <form id="perm-form" class="flex flex-col gap-4">
                    <div class="flex flex-col sm:flex-row gap-2">
                        <input type="text" name="name" id="perm-name"
                               class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-blue-600 focus:ring-2 focus:ring-blue-200"
                               placeholder="Ex: entitie_user" required>

                        <div class="flex gap-2 flex-wrap">
                            @foreach(['view', 'create', 'edit', 'delete', 'update'] as $action)
                                <label class="flex items-center gap-1 text-sm">
                                    <input type="checkbox" name="actions[]" value="{{ $action }}">
                                    {{ $action }}
                                </label>
                            @endforeach
                        </div>

                        <button type="submit"
                                class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                            Adicionar
                        </button>
                    </div>
                    <p id="perm-feedback" class="text-xs text-green-600 hidden">Permissão(s) criada(s) com sucesso.</p>
                </form>
            </div>

            <form action="{{ route('roles.store') }}" method="POST" class="space-y-6">
                @csrf

                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nome da Role</label>
                    <input type="text" name="name" id="name"
                           class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none"
                           required>
                </div>

                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <button type="button" class="text-sm text-blue-600 hover:underline" id="select-all-global">
                            Selecionar todas permissões
                        </button>
                        <span class="text-slate-300">|</span>
                        <input type="text" id="perm-search" placeholder="Buscar..." class="border px-2 py-1 text-sm rounded" />
                    </div>
                </div>

                @php
    // Reagrupar $groupedPermissions para DOIS SEGMENTOS (ex.: entitie_user)
    $__regrouped = collect();

    foreach ($groupedPermissions as $groupTitle => $perms) {
        foreach ($perms as $permission) {
            $name = is_object($permission) ? $permission->name
                   : (is_array($permission) ? ($permission['name'] ?? '')
                   : (string) $permission);

            $parts = explode('_', $name);
            $key = strtolower(($parts[0] ?? 'outros') . '_' . ($parts[1] ?? 'geral'));

            $__regrouped->put($key, array_merge($__regrouped->get($key, []), [$permission]));
        }
    }

    // >>> AQUI estava o ksort($__regrouped); <<<
    $__regrouped = $__regrouped->sortKeys(); // OK com Collection
@endphp


                <!-- GRID DE 3 COLUNAS PARA OS GRUPOS -->
                <div id="permissions-container" class="grid grid-cols-1 md:grid-cols-3 gap-4 max-h-[60vh] overflow-y-auto">
                    @foreach($__regrouped as $groupKey => $permissions)
                        <div class="group-permissions border border-slate-200 rounded-xl p-4" data-group="{{ $groupKey }}">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-base font-semibold text-slate-600 capitalize">
                                    {{ str_replace('_', ' ', $groupKey) }}
                                </h3>
                                <button type="button" class="text-sm text-blue-600 hover:underline select-all-group"
                                        data-group="{{ $groupKey }}">
                                    Selecionar todos
                                </button>
                            </div>

                            <div class="grid grid-cols-1 gap-2">
                                @foreach($permissions as $permission)
                                    @php $pname = is_object($permission) ? $permission->name : (is_array($permission) ? ($permission['name'] ?? '') : (string)$permission); @endphp
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                        <input type="checkbox" name="permissions[]" value="{{ $pname }}"
                                               class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                               data-permission="{{ $groupKey }}">
                                        {{ $pname }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" id="btn-cancel-role"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                        Salvar Role
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de permissões da Role -->
    <div id="permissions-modal" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm">
        <div class="absolute left-1/2 top-1/2 w-[min(700px,95vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold" id="permissions-modal-title">Permissões</h2>
                <button id="permissions-modal-close" class="rounded-lg p-2 hover:bg-slate-100">
                    <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div id="permissions-modal-body" class="space-y-4"></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const openBtn = document.getElementById('btn-open-role-modal');
            const closeBtn = document.getElementById('btn-close-role-modal');
            const cancelBtn = document.getElementById('btn-cancel-role');
            const modal = document.getElementById('role-modal');

            function open() { modal.classList.remove('hidden'); }
            function close() { modal.classList.add('hidden'); }

            openBtn?.addEventListener('click', open);
            closeBtn?.addEventListener('click', close);
            cancelBtn?.addEventListener('click', close);
        });
    </script>

    <script>
        const permForm = document.getElementById('perm-form');
        const permFeedback = document.getElementById('perm-feedback');

        permForm?.addEventListener('submit', async e => {
            e.preventDefault();

            const formData = new FormData(permForm);
            const name = (formData.get('name') || '').toString().trim();
            const actions = formData.getAll('actions[]');

            if (!name) return alert("Informe o nome base da permissão (ex: entitie_user).");

            try {
                const payload = new URLSearchParams();
                payload.append('name', name);
                actions.forEach((a, i) => payload.append(`actions[${i}]`, a));

                const res = await fetch("{{ route('permissions.store') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: payload
                });

                if (!res.ok) {
                    let msg = 'Erro ao criar permissão.';
                    try { const err = await res.json(); msg = err.message || msg; } catch {}
                    alert(msg);
                    return;
                }

                permForm.reset();
                permFeedback.classList.remove('hidden');
                setTimeout(() => permFeedback.classList.add('hidden'), 3000);

                await refreshPermissionList(); // recarrega lista com agrupamento 2 segmentos
            } catch (err) {
                console.error(err);
                alert("Erro inesperado.");
            }
        });

        // Busca e redesenha os grupos (3 colunas), agrupando por dois segmentos
        async function refreshPermissionList() {
            const res = await fetch("{{ route('permissions.list') }}");
            const data = await res.json(); // pode vir plano original; vamos reagrupar no cliente

            // Achata e reagrupa
            const flat = [];
            Object.keys(data).forEach(k => {
                data[k].forEach(p => flat.push(typeof p === 'string' ? {name: p} : p));
            });

            const byTwoSegs = {};
            flat.forEach(p => {
                const name = p.name || '';
                const parts = name.split('_');
                const key = ((parts[0] || 'outros') + '_' + (parts[1] || 'geral')).toLowerCase();
                if (!byTwoSegs[key]) byTwoSegs[key] = [];
                byTwoSegs[key].push(p);
            });

            const container = document.getElementById('permissions-container');
            container.innerHTML = ''; // limpa

            // Render: grid de 3 colunas (já está no container via classes)
            Object.keys(byTwoSegs).sort().forEach(groupKey => {
                const perms = byTwoSegs[groupKey];
                const card = document.createElement('div');
                card.className = 'group-permissions border border-slate-200 rounded-xl p-4';
                card.dataset.group = groupKey;

                card.innerHTML = `
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-base font-semibold text-slate-600 capitalize">${groupKey.replace(/_/g, ' ')}</h3>
                        <button type="button" class="text-sm text-blue-600 hover:underline select-all-group" data-group="${groupKey}">
                            Selecionar todos
                        </button>
                    </div>
                    <div class="grid grid-cols-1 gap-2">
                        ${perms.map(p => `
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="permissions[]" value="${p.name}"
                                       class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                       data-permission="${groupKey}">
                                ${p.name}
                            </label>
                        `).join('')}
                    </div>
                `;

                container.appendChild(card);
            });

            // Reanexa listeners de seleção por grupo
            attachGroupSelectHandlers();
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Global select/deselect
            const globalBtn = document.getElementById('select-all-global');
            let globalChecked = false;

            globalBtn?.addEventListener('click', () => {
                const checkboxes = document.querySelectorAll('#permissions-container input[type="checkbox"]');
                globalChecked = !globalChecked;

                checkboxes.forEach(cb => cb.checked = globalChecked);
                globalBtn.textContent = globalChecked ? 'Desmarcar todas permissões' : 'Selecionar todas permissões';
            });

            // Attach group handlers initially
            attachGroupSelectHandlers();

            // Filtro de busca (oculta cards sem match)
            const searchInput = document.getElementById('perm-search');
            searchInput?.addEventListener('input', () => {
                const term = searchInput.value.toLowerCase();

                document.querySelectorAll('#permissions-container .group-permissions').forEach(group => {
                    const title = group.querySelector('h3')?.textContent.toLowerCase() || '';
                    const labels = Array.from(group.querySelectorAll('label')).map(l => l.textContent.toLowerCase());
                    const match = title.includes(term) || labels.some(txt => txt.includes(term));
                    group.style.display = match ? '' : 'none';
                });
            });
        });

        function attachGroupSelectHandlers() {
            document.querySelectorAll('.select-all-group').forEach(btn => {
                btn.onclick = () => {
                    const group = btn.dataset.group;
                    const container = document.querySelector(`.group-permissions[data-group="${group}"]`);
                    const checkboxes = container.querySelectorAll('input[type="checkbox"]');

                    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                    const toggle = !allChecked;

                    checkboxes.forEach(cb => cb.checked = toggle);
                    btn.textContent = toggle ? 'Desmarcar todos' : 'Selecionar todos';
                };
            });
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('permissions-modal');
            const modalBody = document.getElementById('permissions-modal-body');
            const modalTitle = document.getElementById('permissions-modal-title');
            const closeBtn = document.getElementById('permissions-modal-close');

            // Fechar
            closeBtn?.addEventListener('click', () => modal.classList.add('hidden'));

            // Abrir e renderizar (agrupando por 2 segmentos)
            document.querySelectorAll('.show-role-perms').forEach(btn => {
                btn.addEventListener('click', () => {
                    const raw = JSON.parse(btn.dataset.permissions || '{}');

                    // raw já vem agrupado por 2 segmentos do Blade da tabela, mas
                    // garantimos aqui também:
                    const grouped = {};
                    Object.entries(raw).forEach(([grp, arr]) => {
                        arr.forEach(name => {
                            const parts = String(name).split('_');
                            const key = ((parts[0] || 'outros') + '_' + (parts[1] || 'geral')).toLowerCase();
                            if (!grouped[key]) grouped[key] = [];
                            grouped[key].push(name);
                        });
                    });

                    modalTitle.textContent = `Permissões da Role: ${btn.dataset.role}`;
                    modalBody.innerHTML = '';

                    Object.keys(grouped).sort().forEach(group => {
                        const section = document.createElement('div');
                        section.innerHTML = `
                            <h3 class="font-semibold text-slate-700 mb-1 capitalize">${group.replace(/_/g, ' ')}</h3>
                            <div class="flex flex-wrap gap-2 mb-2">
                                ${grouped[group].map(p => `
                                    <span class="inline-block rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-700">${p}</span>
                                `).join('')}
                            </div>
                        `;
                        modalBody.appendChild(section);
                    });

                    modal.classList.remove('hidden');
                });
            });
        });
    </script>
@endpush
