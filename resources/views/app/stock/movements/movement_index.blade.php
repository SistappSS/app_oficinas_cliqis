@extends('layouts.templates.template')

@section('content')
    <main class="mx-auto max-w-7xl px-4 sm:px-6 pb-10 lg:pb-14">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mt-4">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Movimentações</h1>
                <p class="text-sm text-slate-500">Log auditável do estoque.</p>
            </div>

            <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
                <input id="mov-q" type="text"
                       class="w-full sm:w-80 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm outline-none focus:ring-2 focus:ring-blue-200"
                       placeholder="Buscar por código/descrição..." />

                <select id="mov-type"
                        class="w-full sm:w-44 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none focus:ring-2 focus:ring-blue-200">
                    <option value="">Todos tipos</option>
                    <option value="in">Entrada</option>
                    <option value="out">Saída</option>
                    <option value="adjust">Ajuste</option>
                    <option value="transfer">Transferência</option>
                </select>

                <a href="{{ url('/stock/stock') }}"
                   class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                    Voltar
                </a>
            </div>
        </div>

        <div class="mt-5 mb-8 rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto" id="stock-movements-fragment">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-slate-600 bg-blue-50">
                    <tr>
                        <th class="px-6 py-4 first:rounded-tl-2xl">Data</th>
                        <th class="px-3 py-4">Tipo</th>
                        <th class="px-3 py-4">Origem</th>
                        <th class="px-3 py-4">Usuário</th>
                        <th class="px-6 py-4 text-right last:rounded-tr-2xl">Ações</th>
                    </tr>
                    </thead>
                    <tbody id="mov-tbody" class="divide-y divide-slate-100"></tbody>
                </table>
            </div>

            <div id="mov-empty" class="hidden px-6 py-10 text-center text-sm text-slate-500">
                Nenhuma movimentação encontrada.
            </div>

            <div class="flex items-center justify-between px-6 py-4 border-t border-slate-100">
                <div class="text-sm text-slate-500" id="mov-pageinfo">-</div>
                <div class="flex gap-2">
                    <button id="mov-prev"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                            disabled>Anterior</button>
                    <button id="mov-next"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                            disabled>Próxima</button>
                </div>
            </div>
        </div>
    </main>

    <div id="mv-modal" class="fixed inset-0 z-[80] hidden">
        <div class="absolute inset-0 bg-slate-900/50" data-mv-close></div>

        <div class="absolute inset-x-0 top-10 mx-auto w-[min(980px,calc(100%-2rem))]">
            <div class="rounded-2xl bg-white shadow-xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <div class="text-sm text-slate-500">Movimentação</div>
                        <div class="text-lg font-semibold text-slate-900" id="mv-title">Carregando…</div>
                        <div class="text-xs text-slate-500 mt-1" id="mv-subtitle"></div>
                    </div>

                    <button type="button" class="w-10 h-10 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50"
                            data-mv-close>
                        ✕
                    </button>
                </div>

                <div class="px-6 py-4">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                        <div class="rounded-xl border border-slate-200 p-3">
                            <div class="text-xs text-slate-500">Tipo</div>
                            <div class="text-sm font-semibold text-slate-900" id="mv-type">-</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-3">
                            <div class="text-xs text-slate-500">Motivo</div>
                            <div class="text-sm font-semibold text-slate-900" id="mv-reason">-</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-3">
                            <div class="text-xs text-slate-500">Total Itens (qty)</div>
                            <div class="text-sm font-semibold text-slate-900" id="mv-total-qty">0</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-3">
                            <div class="text-xs text-slate-500">Total Custo</div>
                            <div class="text-sm font-semibold text-slate-900" id="mv-total-cost">R$ 0,00</div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="text-left text-slate-600 bg-blue-50">
                                <tr>
                                    <th class="px-6 py-4 first:rounded-tl-2xl">Local</th>
                                    <th class="px-3 py-4">Código</th>
                                    <th class="px-3 py-4">Descrição</th>
                                    <th class="px-3 py-4 text-right">Qtd</th>
                                    <th class="px-3 py-4 text-right">Custo</th>
                                    <th class="px-6 py-4 text-right last:rounded-tr-2xl">Total</th>
                                </tr>
                                </thead>
                                <tbody id="mv-tbody" class="divide-y divide-slate-100"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-4 text-xs text-slate-500" id="mv-notes"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/js/template/views/stock/stock-movements.js') }}"></script>
@endsection
