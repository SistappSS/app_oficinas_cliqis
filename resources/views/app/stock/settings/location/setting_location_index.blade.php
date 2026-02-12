@extends('layouts.templates.template')

@section('content')
    <main class="mx-auto max-w-7xl px-4 sm:px-6 pb-10 lg:pb-14">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mt-4">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Locais de estoque</h1>
                <p class="text-sm text-slate-500">Gerencie locais e defina 1 local padrão.</p>
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
                    <input id="loc-q" type="text"
                           class="w-full sm:w-80 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm outline-none focus:ring-2 focus:ring-blue-200"
                           placeholder="Buscar por nome..." />
                </div>

                <div>
                    <select id="loc-default"
                            class="w-full sm:w-48 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="all">Todos</option>
                        <option value="1">Somente padrão</option>
                        <option value="0">Somente não padrão</option>
                    </select>
                    <button id="loc-new"
                            class="rounded-xl bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">
                        Novo local
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-slate-600 bg-blue-50">
                    <tr>
                        <th class="px-6 py-4 first:rounded-tl-2xl">Nome</th>
                        <th class="px-3 py-4">Padrão</th>
                        <th class="px-6 py-4 text-right last:rounded-tr-2xl">Ações</th>
                    </tr>
                    </thead>
                    <tbody id="loc-tbody" class="divide-y divide-slate-100"></tbody>
                </table>
            </div>

            <div id="loc-empty" class="hidden px-6 py-10 text-center text-sm text-slate-500">
                Nenhum local encontrado.
            </div>

            <div class="flex items-center justify-between px-6 py-4 border-t border-slate-100">
                <div class="text-sm text-slate-500" id="loc-pageinfo">-</div>
                <div class="flex gap-2">
                    <button id="loc-prev"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                            disabled>Anterior</button>
                    <button id="loc-next"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                            disabled>Próxima</button>
                </div>
            </div>
        </div>
    </main>

    {{-- MODAL --}}
    <div id="loc-modal" class="fixed inset-0 z-[80] hidden">
        <div class="absolute inset-0 bg-slate-900/50" data-loc-close></div>

        <div class="absolute inset-x-0 top-10 mx-auto w-[min(720px,calc(100%-2rem))]">
            <div class="rounded-2xl bg-white shadow-xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <div class="text-sm text-slate-500" id="loc-modal-sub">Local</div>
                        <div class="text-lg font-semibold text-slate-900" id="loc-modal-title">Novo local</div>
                    </div>

                    <button type="button" class="w-10 h-10 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50"
                            data-loc-close>✕</button>
                </div>

                <div class="px-6 py-5 space-y-4">
                    <div>
                        <div class="text-xs font-medium text-slate-600 mb-1">Nome</div>
                        <input id="loc-name" type="text"
                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none focus:ring-2 focus:ring-blue-200"
                               placeholder="ex: Principal / Almoxarifado / Caminhão 01" />
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input id="loc-isdefault" type="checkbox" class="rounded border-slate-300">
                        Definir como padrão
                    </label>

                    <div id="loc-err" class="hidden text-sm text-red-600"></div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50" data-loc-close>
                            Cancelar
                        </button>
                        <button id="loc-save" type="button" class="rounded-xl bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">
                            Salvar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="locdel-modal" class="fixed inset-0 z-[90] hidden">
        <div class="absolute inset-0 bg-slate-900/50" data-locdel-close></div>

        <div class="absolute inset-x-0 top-10 mx-auto w-[min(860px,calc(100%-2rem))]">
            <div class="rounded-2xl bg-white shadow-xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <div class="text-sm text-slate-500">Locais</div>
                        <div class="text-lg font-semibold text-slate-900" id="locdel-title">Excluir local</div>
                        <div class="text-xs text-slate-500 mt-1" id="locdel-subtitle"></div>
                    </div>
                    <button type="button" class="w-10 h-10 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50" data-locdel-close>✕</button>
                </div>

                <div class="px-6 py-4">
                    <div id="locdel-alert" class="hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"></div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3">
                        <div class="rounded-xl border border-slate-200 p-3">
                            <div class="text-xs text-slate-500">Local</div>
                            <div class="text-sm font-semibold text-slate-900" id="locdel-name">-</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-3">
                            <div class="text-xs text-slate-500">SKUs com saldo</div>
                            <div class="text-sm font-semibold text-slate-900" id="locdel-skus">0</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-3">
                            <div class="text-xs text-slate-500">Qtd total</div>
                            <div class="text-sm font-semibold text-slate-900" id="locdel-qty">0</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-3">
                            <div class="text-xs text-slate-500">Custo estimado</div>
                            <div class="text-sm font-semibold text-slate-900" id="locdel-cost">R$ 0,00</div>
                        </div>
                    </div>

                    <div class="mt-4 rounded-2xl border border-slate-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="text-left text-slate-600 bg-blue-50">
                                <tr>
                                    <th class="px-6 py-4 first:rounded-tl-2xl">Código</th>
                                    <th class="px-3 py-4">Descrição</th>
                                    <th class="px-3 py-4 text-right">Qtd</th>
                                    <th class="px-6 py-4 text-right last:rounded-tr-2xl">Custo médio</th>
                                </tr>
                                </thead>
                                <tbody id="locdel-tbody" class="divide-y divide-slate-100">
                                <tr><td class="px-6 py-6 text-slate-500" colspan="4">Carregando...</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div id="locdel-empty" class="hidden px-6 py-6 text-sm text-slate-500">Sem itens com saldo neste local.</div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-4">
                        <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50" data-locdel-close>
                            Cancelar
                        </button>
                        <button id="locdel-confirm" type="button" class="rounded-xl bg-rose-600 px-4 py-2 text-sm text-white hover:bg-rose-700 disabled:opacity-50" disabled>
                            Excluir
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/js/template/views/stock/stock-location.js') }}"></script>
@endsection
