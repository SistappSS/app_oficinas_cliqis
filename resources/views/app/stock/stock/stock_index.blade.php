@extends('layouts.templates.template')

@section('content')
    <main class="mx-auto max-w-7xl px-4 sm:px-6 pb-10 lg:pb-14">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mt-4">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Estoque</h1>
                <p class="text-sm text-slate-500">Saldo global e por local.</p>
            </div>

            <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
                <div class="relative">
                    <input id="stock-q" type="text"
                           class="w-full sm:w-80 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm outline-none focus:ring-2 focus:ring-blue-200"
                           placeholder="Buscar por código/descrição..." />
                </div>

                <select id="stock-location"
                        class="w-full sm:w-56 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none focus:ring-2 focus:ring-blue-200">
                    <option value="">Global (todos locais)</option>
                </select>

                <label class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 bg-white shadow-sm text-sm text-slate-700">
                    <input id="stock-active" type="checkbox" class="rounded border-slate-300" checked>
                    Somente ativos
                </label>
            </div>
        </div>

        <div class="mt-5 mb-8 rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <div class="text-sm text-slate-600">
                    <span id="stock-count">0</span> itens
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ url('/stock/movements') }}"
                       class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        Ver movimentações
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-slate-600 bg-blue-50">
                    <tr>
                        <th class="px-6 py-4 first:rounded-tl-2xl">Código</th>
                        <th class="px-3 py-4">Descrição</th>
                        <th class="px-3 py-4 text-right">Qtd</th>
                        <th class="px-3 py-4 text-right">Custo Médio</th>
                        <th class="px-3 py-4 text-right">Último Custo</th>
                        <th class="px-3 py-4 text-right">Venda</th>
                        <th class="px-3 py-4 text-right">Margem</th>
                        <th class="px-6 py-4 text-right last:rounded-tr-2xl">Ações</th>
                    </tr>
                    </thead>

                    <tbody id="tbody" class="divide-y divide-slate-100"></tbody>
                </table>
            </div>

            <div id="stock-empty" class="hidden px-6 py-10 text-center text-sm text-slate-500">
                Nenhum item encontrado.
            </div>

            <div class="flex items-center justify-between px-6 py-4 border-t border-slate-100">
                <div class="text-sm text-slate-500" id="stock-pageinfo">-</div>
                <div class="flex gap-2">
                    <button id="stock-prev"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                            disabled>Anterior</button>
                    <button id="stock-next"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                            disabled>Próxima</button>
                </div>
            </div>
        </div>
    </main>

    <script src="{{ asset('assets/js/template/views/stock/stock.js') }}"></script>
@endsection
