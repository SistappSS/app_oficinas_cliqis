@extends('layouts.templates.template')

@section('content')
    <main class="mx-auto max-w-7xl px-4 sm:px-6 pb-10 lg:pb-14">
        {{-- HEADER --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mt-4">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Perfis & Permissões</h1>
                <p class="text-xs text-slate-500 mt-1">
                    Crie perfis por empresa e defina quais permissões cada um possui.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <button
                    id="btn-new-role"
                    type="button"
                    data-role-modal-open
                    class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-800">
                    Novo perfil
                </button>
                <button id="toggle-header"
                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow hover:bg-slate-50"
                        aria-expanded="true" aria-controls="header-collapsible" type="button"
                        title="Expandir/contrair cabeçalho">
                    <i id="toggle-icon" class="fa-solid fa-up-right-and-down-left-from-center"></i>
                </button>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,280px)_minmax(0,1fr)]">
            {{-- COLUNA ESQUERDA: ROLES --}}
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm p-4 flex flex-col">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <h2 class="text-sm font-semibold text-slate-900">Perfis</h2>
                </div>

                <div class="mb-3">
                    <div class="relative">
                        <input
                            id="roles-search"
                            type="text"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none"
                            placeholder="Buscar perfil..."
                        />
                        <svg class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <circle cx="11" cy="11" r="7"/>
                            <path d="M21 21l-4.3-4.3"/>
                        </svg>
                    </div>
                </div>

                <div id="roles-list" class="flex-1 space-y-2 overflow-y-auto text-xs no-scrollbar max-h-[520px]">
                    {{-- preenchido via JS --}}
                </div>

                <p id="roles-empty" class="mt-3 text-[11px] text-slate-400 hidden">
                    Nenhum perfil encontrado. Crie o primeiro usando o botão acima.
                </p>
            </section>

            {{-- COLUNA DIREITA: DETALHE + PERMISSÕES --}}
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm p-5 flex flex-col">
                <div id="role-detail-empty" class="flex-1 flex flex-col items-center justify-center text-center text-slate-400 text-xs">
                    <p class="font-medium mb-1">Selecione um perfil à esquerda</p>
                    <p>Clique em um perfil para editar nome e permissões.</p>
                </div>

                <div id="role-detail" class="hidden flex-1 flex flex-col">
                    {{-- CABEÇALHO ROLE --}}
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                        <div class="flex-1">
                            <label class="text-xs font-medium text-slate-600 mb-1 block">
                                Nome do perfil
                            </label>
                            <input
                                id="role-name-input"
                                type="text"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-900 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none"
                                placeholder="Ex.: Técnico, Financeiro, Supervisor"
                            />
                            <p class="mt-1 text-[11px] text-slate-400">
                                O nome salvo será exclusivo por empresa. No banco ficará como <code>{tenantId}_nome</code>.
                            </p>
                        </div>

                        <div class="flex gap-2 shrink-0">
                            <button
                                id="btn-save-role-name"
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">
                                Salvar nome do perfil
                            </button>

                            <button
                                id="btn-delete-role"
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-100">
                                Excluir perfil
                            </button>
                        </div>
                    </div>

                    <div class="border-t border-slate-100 my-3"></div>

                    {{-- PERMISSÕES --}}
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Permissões do perfil</h3>
                            <p class="text-[11px] text-slate-400">
                                Marque as ações que este perfil poderá executar no sistema.
                            </p>
                        </div>
                        <button
                            id="btn-save-role-permissions"
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">
                            Salvar permissões
                        </button>
                    </div>

                    <div id="permissions-container" class="grid gap-2 overflow-y-auto no-scrollbar text-xs max-h-[480px]">
                        {{-- preenchido via JS --}}
                    </div>
                </div>
            </section>
        </div>

        {{-- MODAL: NOVO PERFIL --}}
        <div id="role-modal"
             class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5">
                    <h2 class="text-sm font-semibold text-slate-900">
                        Novo perfil
                    </h2>
                    <button type="button" data-role-modal-close
                            class="rounded-full p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                        ✕
                    </button>
                </div>

                <div class="px-5 py-4 space-y-4 text-sm">
                    <div>
                        <label for="role-modal-name" class="block text-xs font-medium text-slate-600 mb-1">
                            Nome do perfil
                        </label>
                        <input
                            id="role-modal-name"
                            type="text"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none"
                            placeholder="Ex.: Técnico, Financeiro, Supervisor"
                        />
                        <p class="mt-1 text-[11px] text-slate-400">
                            No banco será salvo com o prefixo <code>{tenantId}_</code> automaticamente.
                        </p>
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-100 px-5 py-3">
                    <button type="button" data-role-modal-close
                            class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button type="button" id="role-modal-save"
                            class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-700">
                        Salvar perfil
                    </button>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('assets/js/template/views/entities/permission_user.js') }}"></script>
@endpush
