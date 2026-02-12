@extends('layouts.templates.template')

@section('content')
    <main class="mx-auto max-w-7xl px-4 sm:px-6 pb-10 lg:pb-14">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mt-4">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Estoque</h1>
                <p class="text-sm text-slate-500">Saldo global e por local.</p>
            </div>

            <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
                <select id="stock-location"
                        class="w-full sm:w-56 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none focus:ring-2 focus:ring-blue-200">
                    <option value="">Global (todos locais)</option>
                </select>

                <label
                    class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 bg-white shadow-sm text-sm text-slate-700">
                    <input id="stock-active" type="checkbox" class="rounded border-slate-300" checked>
                    Somente ativos
                </label>
                <button
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow hover:bg-slate-50">
                    Exportar
                </button>
                <button id="toggle-header"
                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow hover:bg-slate-50"
                        aria-expanded="true" aria-controls="header-collapsible" type="button"
                        title="Expandir/contrair cabeçalho">
                    <i id="toggle-icon" class="fa-solid fa-up-right-and-down-left-from-center"></i>
                </button>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3" id="stock-kpis">
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-4">
                <div class="text-xs text-slate-500">Total SKUs</div>
                <div class="mt-1 text-xl font-semibold text-slate-900" id="kpi-total-skus">—</div>
                <div class="mt-1 text-xs text-slate-500" id="kpi-skus-note">—</div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-4">
                <div class="text-xs text-slate-500">Qtd total</div>
                <div class="mt-1 text-xl font-semibold text-slate-900" id="kpi-total-qty">—</div>
                <div class="mt-1 text-xs text-slate-500">Somando o saldo atual</div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-4">
                <div class="text-xs text-slate-500">Valor custo total</div>
                <div class="mt-1 text-xl font-semibold text-slate-900" id="kpi-total-cost">—</div>
                <div class="mt-1 text-xs text-slate-500">qty × custo médio</div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-4">
                <div class="text-xs text-slate-500">Valor venda total</div>
                <div class="mt-1 text-xl font-semibold text-slate-900" id="kpi-total-sale">—</div>
                <div class="mt-1 text-xs text-slate-500" id="kpi-sale-note">Somente com venda > 0</div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-4 lg:col-span-1">
                <div class="text-xs text-slate-500">Entradas</div>
                <div class="mt-2 text-sm text-slate-700">
                    <div class="flex items-center justify-between">
                        <span class="text-lg text-slate-500">7d</span>
                        <span class="text-lg font-semibold text-slate-900" id="kpi-in-7">—</span>
                    </div>
                    <div class="flex items-center justify-between mt-1">
                        <span class="text-lg text-slate-500">30d</span>
                        <span class="text-lg font-semibold text-slate-900" id="kpi-in-30">—</span>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-4 lg:col-span-1">
                <div class="text-xs text-slate-500">Saídas</div>
                <div class="mt-2 text-sm text-slate-700">
                    <div class="flex items-center justify-between">
                        <span class="text-lg text-slate-500">7d</span>
                        <span class="text-lg font-semibold text-slate-900" id="kpi-out-7">—</span>
                    </div>
                    <div class="flex items-center justify-between mt-1">
                        <span class="text-lg text-slate-500">30d</span>
                        <span class="text-lg font-semibold text-slate-900" id="kpi-out-30">—</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-5 mb-8 rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <div class="text-sm text-slate-600">
                    <input id="stock-q" type="text"
                           class="w-full sm:w-80 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm outline-none focus:ring-2 focus:ring-blue-200"
                           placeholder="Buscar por código/descrição..."/>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('stock-location.view') }}"
                       class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        Locais de Estoque
                    </a>
                    <a href="{{ route('stock-reasons.view') }}"
                       class="rounded-xl border border-slate-900 bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-700">
                        Tipo de Movimentação
                    </a>
                    <a href="{{ route('movements.view') }}"
                       class="rounded-xl border border-blue-700 bg-blue-700 px-4 py-2 text-sm text-white hover:bg-blue-600">
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
                            disabled>Anterior
                    </button>
                    <button id="stock-next"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                            disabled>Próxima
                    </button>
                </div>
            </div>
        </div>
    </main>

    {{-- Modal Manual In/Out --}}
    <div id="stock-move-modal" class="fixed inset-0 z-[90] hidden">
        <div class="absolute inset-0 bg-slate-900/50" data-mv-close></div>

        <div class="absolute inset-x-0 top-10 mx-auto w-[min(980px,calc(100%-2rem))]">
            <div class="rounded-2xl bg-white shadow-xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <div class="text-sm text-slate-500">Movimentar estoque</div>
                        <div class="text-lg font-semibold text-slate-900" id="sm-title">-</div>
                        <div class="text-xs text-slate-500 mt-1" id="sm-subtitle"></div>
                    </div>

                    <button type="button"
                            class="w-10 h-10 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50"
                            data-mv-close>✕
                    </button>
                </div>

                <div class="px-6 py-4">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                        <div class="rounded-xl border border-slate-200 p-3">
                            <div class="text-xs text-slate-500">Saldo no local</div>
                            <div class="text-sm font-semibold text-slate-900" id="sm-cur-qty">0</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-3">
                            <div class="text-xs text-slate-500">Custo médio no local</div>
                            <div class="text-sm font-semibold text-slate-900" id="sm-cur-avg">R$ 0,00</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-3">
                            <div class="text-xs text-slate-500">Mínimo no local</div>
                            <div class="text-sm font-semibold text-slate-900" id="sm-cur-min">0</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-3">
                            <div class="text-xs text-slate-500">Custo global</div>
                            <div class="text-sm font-semibold text-slate-900" id="sm-global-avg">R$ 0,00</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-medium text-slate-600">Tipo</label>
                            <select id="sm-type"
                                    class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-200">
                                <option value="in">Entrada</option>
                                <option value="out">Saída</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-xs font-medium text-slate-600">Local</label>
                            <select id="sm-location"
                                    class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-200"></select>
                        </div>

                        <div>
                            <label class="text-xs font-medium text-slate-600">Quantidade</label>
                            <input id="sm-qty" type="number" min="1" step="1"
                                   class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-200"
                                   placeholder="0">
                        </div>

                        <div>
                            <label class="text-xs font-medium text-slate-600">Custo unitário</label>
                            <input id="sm-unit-cost" type="number" min="0" step="0.0001"
                                   class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-200"
                                   placeholder="0,00">
                            <div class="mt-1 text-[11px] text-slate-500" id="sm-cost-hint"></div>
                        </div>

                        <div>
                            <label class="text-xs font-medium text-slate-600">Preço de venda (opcional)</label>
                            <input id="sm-sale-price" type="number" min="0" step="0.01"
                                   class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-200"
                                   placeholder="0,00">
                        </div>

                        <div>
                            <label class="text-xs font-medium text-slate-600">Margem % (opcional)</label>
                            <input id="sm-markup" type="number" min="0" max="100" step="0.01"
                                   class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-200"
                                   placeholder="0">
                        </div>

                        <div class="md:col-span-2">
                            <div class="text-xs font-medium text-slate-600 mb-1">Motivo</div>
                            <select id="sm-reason"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none focus:ring-2 focus:ring-blue-200">
                                <option value="">—</option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="text-xs font-medium text-slate-600">Observações</label>
                            <textarea id="sm-notes" rows="3"
                                      class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-200"
                                      placeholder="Ex: brinde, ajuste manual, etc."></textarea>
                            <div class="mt-1 text-[11px] text-slate-500" id="sm-notes-hint"></div>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-between">
                        <div class="text-sm text-red-600 hidden" id="sm-error"></div>

                        <div class="flex gap-2">
                            <button type="button"
                                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
                                    data-mv-close>Cancelar
                            </button>

                            <button type="button"
                                    id="sm-submit"
                                    class="rounded-xl bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800">
                                Confirmar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="sm-toast"
                 class="fixed right-4 top-4 z-[95] hidden rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-lg">
                <div class="text-sm font-semibold text-slate-900" id="sm-toast-title">OK</div>
                <div class="text-xs text-slate-600 mt-0.5" id="sm-toast-msg"></div>
            </div>
        </div>
    </div>

    {{-- Modal Ajustar Item --}}
    <div id="stock-adjust-modal" class="fixed inset-0 z-[95] hidden">
        <div class="absolute inset-0 bg-slate-900/50" data-sa-close></div>

        <div class="absolute inset-x-0 top-10 mx-auto w-[min(980px,calc(100%-2rem))]">
            <div class="rounded-2xl bg-white shadow-xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <div class="text-sm text-slate-500">Ajustar item</div>
                        <div class="text-lg font-semibold text-slate-900" id="sa-title">Carregando…</div>
                        <div class="text-xs text-slate-500 mt-1" id="sa-subtitle"></div>
                    </div>

                    <button type="button"
                            class="w-10 h-10 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50"
                            data-sa-close>✕
                    </button>
                </div>

                <div class="px-6 py-4">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                        <div class="rounded-xl border border-slate-200 p-3">
                            <div class="text-xs text-slate-500">Saldo no local</div>
                            <div class="text-sm font-semibold text-slate-900" id="sa-cur-qty">0</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-3">
                            <div class="text-xs text-slate-500">Custo médio no local</div>
                            <div class="text-sm font-semibold text-slate-900" id="sa-cur-avg">R$ 0,00</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-3">
                            <div class="text-xs text-slate-500">Mínimo no local</div>
                            <div class="text-sm font-semibold text-slate-900" id="sa-cur-min">0</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-3">
                            <div class="text-xs text-slate-500">Global (qtd / custo médio)</div>
                            <div class="text-sm font-semibold text-slate-900" id="sa-global">0 • R$ 0,00</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="md:col-span-2">
                            <label class="text-xs font-medium text-slate-600">Local</label>
                            <select id="sa-location"
                                    class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-200"></select>
                            <div class="mt-1 text-[11px] text-slate-500" id="sa-loc-hint"></div>
                        </div>

                        <div>
                            <label class="text-xs font-medium text-slate-600">Quantidade (local)</label>
                            <input id="sa-qty" type="number" min="0" step="1"
                                   class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-200">
                        </div>

                        <div>
                            <label class="text-xs font-medium text-slate-600">Custo médio (local)</label>
                            <input id="sa-avg-cost" type="number" min="0" step="0.0001"
                                   class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-200">
                            <div class="mt-1 text-[11px] text-slate-500">Ajuste por localidade.</div>
                        </div>

                        <div>
                            <label class="text-xs font-medium text-slate-600">Último custo (global)</label>
                            <input id="sa-last-cost" type="number" min="0" step="0.0001"
                                   class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-200">
                        </div>

                        <div>
                            <label class="text-xs font-medium text-slate-600">Venda padrão (global)</label>
                            <input id="sa-sale-price" type="number" min="0" step="0.01"
                                   class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-200">
                        </div>

                        <div>
                            <label class="text-xs font-medium text-slate-600">Markup padrão % (global)</label>
                            <input id="sa-markup" type="number" min="0" max="999.99" step="0.01"
                                   class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-200">
                            <div class="mt-1 text-[11px] text-slate-500" id="sa-markup-hint"></div>
                        </div>

                        <div class="md:col-span-2">
                            <label class="text-xs font-medium text-slate-600">Observações</label>
                            <textarea id="sa-notes" rows="3"
                                      class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-200"
                                      placeholder="Ex: ajuste por inventário, correção custo, etc."></textarea>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-between">
                        <div class="text-sm text-red-600 hidden" id="sa-error"></div>

                        <div class="flex gap-2">
                            <button type="button"
                                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
                                    data-sa-close>Cancelar
                            </button>

                            <button type="button"
                                    id="sa-submit"
                                    class="rounded-xl bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800">
                                Salvar ajuste
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Visualizar Item --}}
    <div id="stock-view-modal" class="fixed inset-0 z-[90] hidden">
        <div class="absolute inset-0 bg-slate-900/50" data-sv-close></div>

        <div class="absolute inset-x-0 top-10 mx-auto w-[min(980px,calc(100%-2rem))]">
            <div class="rounded-2xl bg-white shadow-xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <div class="text-sm text-slate-500">Visualizar item</div>
                        <div class="text-lg font-semibold text-slate-900" id="sv-title">Carregando…</div>
                        <div class="text-xs text-slate-500 mt-1" id="sv-subtitle"></div>
                    </div>

                    <button type="button"
                            class="w-10 h-10 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50"
                            data-sv-close>✕
                    </button>
                </div>

                <div class="px-6 py-4">
                    <div class="flex flex-wrap gap-2 mb-4">
                        <button type="button" class="rounded-xl border px-3 py-2 text-sm" data-sv-tab="summary">
                            Resumo
                        </button>
                        <button type="button" class="rounded-xl border px-3 py-2 text-sm" data-sv-tab="locations">
                            Por locais
                        </button>
                        <button type="button" class="rounded-xl border px-3 py-2 text-sm duration-150" data-sv-tab="logs">
                            Logs
                        </button>
                        <button type="button" class="rounded-xl border px-3 py-2 text-sm text-slate-700" data-sv-tab="prices">
                            Ajustes de preços
                        </button>
                        <button type="button" class="rounded-xl border px-3 py-2 text-sm" data-sv-tab="transfer">
                            Transferências
                        </button>
                    </div>

                    <div class="min-h-[260px]">
                        <div data-sv-pane="summary">
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                <div class="rounded-xl border border-slate-200 p-3">
                                    <div class="text-xs text-slate-500">Qtd global</div>
                                    <div class="text-sm font-semibold text-slate-900" id="sv-qty-global">—</div>
                                </div>
                                <div class="rounded-xl border border-slate-200 p-3">
                                    <div class="text-xs text-slate-500">Custo médio global</div>
                                    <div class="text-sm font-semibold text-slate-900" id="sv-avg-global">—</div>
                                </div>
                                <div class="rounded-xl border border-slate-200 p-3">
                                    <div class="text-xs text-slate-500">Valor custo total</div>
                                    <div class="text-sm font-semibold text-slate-900" id="sv-total-cost">—</div>
                                </div>
                                <div class="rounded-xl border border-slate-200 p-3">
                                    <div class="text-xs text-slate-500">Valor venda total</div>
                                    <div class="text-sm font-semibold text-slate-900" id="sv-total-sale">—</div>
                                </div>

                                <div class="rounded-xl border border-slate-200 p-3">
                                    <div class="text-xs text-slate-500">Último custo</div>
                                    <div class="text-sm font-semibold text-slate-900" id="sv-last-cost">—</div>
                                </div>

                                <div class="rounded-xl border border-slate-200 p-3">
                                    <div class="text-xs text-slate-500">Venda padrão / Markup</div>
                                    <div class="text-sm font-semibold text-slate-900" id="sv-sale-mk">—</div>
                                </div>
                            </div>
                        </div>

                        <div class="hidden" data-sv-pane="locations">
                            <div class="rounded-2xl border border-slate-200 transition-colors overflow-hidden">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="text-left text-slate-600 bg-blue-50">
                                        <tr>
                                            <th class="px-6 py-4 first:rounded-tl-2xl">Local</th>
                                            <th class="px-3 py-4 text-right">Qtd</th>
                                            <th class="px-3 py-4 text-right">Custo médio</th>
                                            <th class="px-3 py-4 text-right">Valor custo</th>
                                            <th class="px-3 py-4 text-right">Mínimo</th>
                                            <th class="px-6 py-4 text-right last:rounded-tr-2xl">Valor venda</th>
                                        </tr>
                                        </thead>
                                        <tbody id="sv-locs-tbody" class="divide-y divide-slate-100"></tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="mt-2 text-xs text-slate-500" id="sv-locs-footnote">—</div>
                        </div>

                        <div class="hidden" data-sv-pane="logs">
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-sm text-slate-600">Últimas movimentações (preview)</div>
                                <button type="button"
                                        class="rounded-xl border border-slate-200 transition-colors bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
                                        id="sv-open-full-log">
                                    Abrir log completo
                                </button>
                            </div>

                            <div class="rounded-2xl border border-slate-200 overflow-hidden">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="text-left text-slate-600 bg-blue-50">
                                        <tr>
                                            <th class="px-6 py-4 first:rounded-tl-2xl">Data</th>
                                            <th class="px-3 py-4">Tipo</th>
                                            <th class="px-3 py-4">Motivo</th>
                                            <th class="px-3 py-4">Ajustes</th>
                                            <th class="px-3 py-4 text-right">Total qty</th>
                                            <th class="px-6 py-4 text-right last:rounded-tr-2xl">Total custo</th>
                                        </tr>
                                        </thead>
                                        <tbody id="sv-logs-tbody" class="divide-y divide-slate-100"></tbody>
                                    </table>
                                </div>
                            </div>

                            <div id="sv-logs-empty" class="hidden py-6 text-center text-sm text-slate-500">
                                Sem movimentações.
                            </div>
                        </div>

                        <div class="hidden" data-sv-pane="prices">
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-sm text-slate-600">Últimos ajustes (type=adjust)</div>
                                <button type="button"
                                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
                                        id="sv-open-full-adjust">
                                    Abrir log completo
                                </button>
                            </div>

                            <div class="rounded-2xl border border-slate-200 overflow-hidden">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="text-left text-slate-600 bg-blue-50">
                                        <tr>
                                            <th class="px-6 py-4 first:rounded-tl-2xl">Data</th>
                                            <th class="px-3 py-4">Tipo</th>
                                            <th class="px-3 py-4">Motivo</th>
                                            <th class="px-3 py-4">Ajustes</th>
                                            <th class="px-3 py-4 text-right">Total qty</th>
                                            <th class="px-6 py-4 text-right last:rounded-tr-2xl">Total custo</th>
                                        </tr>
                                        </thead>
                                        <tbody id="sv-prices-tbody" class="divide-y divide-slate-100"></tbody>
                                    </table>
                                </div>
                            </div>

                            <div id="sv-prices-empty" class="hidden py-6 text-center text-sm text-slate-500">
                                Sem ajustes.
                            </div>
                        </div>

                        <div class="hidden" data-sv-pane="transfer">
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-sm text-slate-600">Últimas transferências (type=transfer)</div>
                                <button type="button"
                                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
                                        id="sv-open-full-transfer">
                                    Abrir log completo
                                </button>
                            </div>

                            <div class="rounded-2xl border border-slate-200 overflow-hidden">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="text-left text-slate-600 bg-blue-50">
                                        <tr>
                                            <th class="px-6 py-4 first:rounded-tl-2xl">Data</th>
                                            <th class="px-3 py-4">Tipo</th>
                                            <th class="px-3 py-4">Motivo</th>
                                            <th class="px-3 py-4">Ajustes</th>
                                            <th class="px-3 py-4 text-right">Total qty</th>
                                            <th class="px-6 py-4 text-right last:rounded-tr-2xl">Total custo</th>
                                        </tr>
                                        </thead>
                                        <tbody id="sv-transfer-tbody" class="divide-y divide-slate-100"></tbody>
                                    </table>
                                </div>
                            </div>

                            <div id="sv-transfer-empty" class="hidden py-6 text-center text-sm text-slate-500">
                                Sem transferências.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Log Item --}}
    <div id="stklog-modal" class="fixed inset-0 z-[100] hidden">
        <div class="absolute inset-0 bg-slate-900/50" data-stklog-close></div>

        <div class="absolute inset-x-0 top-10 mx-auto w-[min(980px,calc(100%-2rem))]">
            <div class="rounded-2xl bg-white shadow-xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <div class="text-sm text-slate-500">Log do item</div>
                        <div class="text-lg font-semibold text-slate-900" id="stklog-title">Carregando…</div>
                        <div class="text-xs text-slate-500 mt-1" id="stklog-subtitle"></div>
                    </div>

                    <button type="button"
                            class="w-10 h-10 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50"
                            data-stklog-close>✕
                    </button>
                </div>

                <div class="px-6 py-4">
                    <div class="flex flex-col md:flex-row gap-2 md:items-center md:justify-between mb-3">
                        <input id="stklog-q" type="text"
                               class="w-full md:w-72 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm outline-none focus:ring-2 focus:ring-blue-200"
                               placeholder="Buscar por código/descrição..."/>

                        <select id="stklog-type"
                                class="w-full md:w-52 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none focus:ring-2 focus:ring-blue-200">
                            <option value="">Todos tipos</option>
                            <option value="in">Entrada</option>
                            <option value="out">Saída</option>
                            <option value="adjust">Ajuste</option>
                            <option value="transfer">Transferência</option>
                        </select>
                    </div>

                    <div class="rounded-2xl border border-slate-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="text-left text-slate-600 bg-blue-50">
                                <tr>
                                    <th class="px-6 py-4 first:rounded-tl-2xl">Data</th>
                                    <th class="px-3 py-4">Tipo</th>
                                    <th class="px-3 py-4">Motivo</th>
                                    <th class="px-3 py-4">Ajustes</th>
                                    <th class="px-3 py-4 text-right">Total qty</th>
                                    <th class="px-3 py-4 text-right">Total custo</th>
                                    <th class="px-6 py-4 text-right last:rounded-tr-2xl">Ações</th>
                                </tr>
                                </thead>
                                <tbody id="stklog-tbody" class="divide-y divide-slate-100"></tbody>
                            </table>
                        </div>
                    </div>

                    <div id="stklog-empty" class="hidden py-8 text-center text-sm text-slate-500">
                        Nenhuma movimentação encontrada.
                    </div>

                    <div class="flex items-center justify-between py-4">
                        <div class="text-sm text-slate-500" id="stklog-pageinfo">-</div>
                        <div class="flex gap-2">
                            <button id="stklog-prev"
                                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                                    disabled>Anterior
                            </button>
                            <button id="stklog-next"
                                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                                    disabled>Próxima
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/js/template/views/stock/stock.js') }}"></script>
@endsection
