@extends('layouts.templates.template')

@section('content')
    <main class="mx-auto max-w-7xl px-4 sm:px-6 pb-10 lg:pb-14">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mt-4">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Motivos de movimentação</h1>
                <p class="text-sm text-slate-500">Personalize entradas/saídas (tenant) e mantenha os motivos do
                    sistema.</p>
            </div>

            <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
                <a href="{{ url('/stock/stock') }}"
                   class="flex items-center rounded-xl border border-indigo-700 bg-indigo-700 px-4 py-2 text-sm text-white hover:bg-indigo-600">
                    Voltar
                </a>
                <button id="toggle-header"
                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow hover:bg-slate-50"
                        aria-expanded="true" aria-controls="header-collapsible" type="button"
                        title="Expandir/contrair cabeçalho">
                    <i id="toggle-icon" class="fa-solid fa-up-right-and-down-left-from-center"></i>
                </button>

            </div>
        </div>

        <div class="mt-5 mb-8 rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex justify-between px-5 py-4 border-b border-slate-100">
                <div class="text-sm text-slate-600">
                    <input id="r-q" type="text"
                           class="w-full sm:w-72 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm outline-none focus:ring-2 focus:ring-blue-200"
                           placeholder="Buscar por código/label..."/>
                </div>

                <div>
                    <select id="r-active"
                            class="w-full sm:w-40 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="all">Ativos + inativos</option>
                        <option value="1" selected>Somente ativos</option>
                        <option value="0">Somente inativos</option>
                    </select>

                    <select id="r-system"
                            class="w-full sm:w-44 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="all" selected>Sistema + Tenant</option>
                        <option value="1">Somente sistema</option>
                        <option value="0">Somente tenant</option>
                    </select>
                    <button
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow hover:bg-slate-50">
                        Exportar
                    </button>
                    <button id="r-new"
                            class="rounded-xl border border-blue-700 bg-blue-700 px-4 py-2 text-sm text-white hover:bg-blue-600">
                        Novo motivo
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-slate-600 bg-blue-50">
                    <tr>
                        <th class="px-6 py-4 first:rounded-tl-2xl">Código</th>
                        <th class="px-3 py-4">Label</th>
                        <th class="px-3 py-4">Tipo</th>
                        <th class="px-3 py-4">Status</th>
                        <th class="px-6 py-4 text-right last:rounded-tr-2xl">Ações</th>
                    </tr>
                    </thead>
                    <tbody id="r-tbody" class="divide-y divide-slate-100"></tbody>
                </table>
            </div>

            <div id="r-empty" class="hidden px-6 py-10 text-center text-sm text-slate-500">
                Nenhum motivo encontrado.
            </div>

            <div class="flex items-center justify-between px-6 py-4 border-t border-slate-100">
                <div class="text-sm text-slate-500" id="r-pageinfo">-</div>
                <div class="flex gap-2">
                    <button id="r-prev"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                            disabled>Anterior
                    </button>
                    <button id="r-next"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                            disabled>Próxima
                    </button>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal create/edit -->
    <div id="r-modal" class="fixed inset-0 z-[90] hidden">
        <div class="absolute inset-0 bg-slate-900/50" data-r-close></div>

        <div class="absolute inset-x-0 top-10 mx-auto w-[min(760px,calc(100%-2rem))]">
            <div class="rounded-2xl bg-white shadow-xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <div class="text-sm text-slate-500" id="r-modal-sub">Motivo</div>
                        <div class="text-lg font-semibold text-slate-900" id="r-modal-title">Novo motivo</div>
                    </div>

                    <button type="button"
                            class="w-10 h-10 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50"
                            data-r-close>✕
                    </button>
                </div>

                <div class="px-6 py-5 space-y-4">
                    <div class="grid grid-cols-12 gap-3">
                        <div class="col-span-12 md:col-span-7">
                            <div class="text-xs font-medium text-slate-600 mb-1">Label</div>
                            <input id="r-label" type="text"
                                   class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none focus:ring-2 focus:ring-blue-200"
                                   placeholder="ex: Saída manual"/>
                        </div>

                        <div class="col-span-12 md:col-span-5">
                            <div class="text-xs font-medium text-slate-600 mb-1">Código</div>
                            <input id="r-code" type="text"
                                   class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none focus:ring-2 focus:ring-blue-200"
                                   placeholder="ex: manual_in"/>
                            <div class="text-[11px] text-slate-500 mt-1">Somente letras minúsculas, números e _</div>
                        </div>
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input id="r-active2" type="checkbox" class="rounded border-slate-300" checked>
                        Ativo
                    </label>

                    <div id="r-err" class="hidden text-sm text-red-600"></div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button"
                                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
                                data-r-close>
                            Cancelar
                        </button>
                        <button id="r-save" type="button"
                                class="rounded-xl bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">
                            Salvar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/js/template/views/stock/stock-reason.js') }}"></script>
@endsection
