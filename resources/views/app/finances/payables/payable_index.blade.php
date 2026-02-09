@extends('layouts.templates.template')
@section('content')
    <main class="mx-auto max-w-7xl px-4 sm:px-6 pb-10 lg:pb-14">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold">Contas a Pagar</h1>
                <p class="text-sm text-slate-600">Controle de saídas e baixas.</p>
            </div>
            <div>
                <div class="flex items-center gap-3">
                    <!-- (Antes do Novo) Toggle agrupar -->

                    <button id="btn-new"
                            class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-800">
                        Nova conta
                    </button>

                    <button id="toggle-header"
                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow hover:bg-slate-50"
                            aria-expanded="true" aria-controls="header-collapsible" type="button"
                            title="Expandir/contrair cabeçalho">
                        <i id="toggle-icon" class="fa-solid fa-up-right-and-down-left-from-center"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- KPIs -->
        <section class="mt-5 grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Total pendente (período)</p>
                <p id="kpi-pend" class="mt-2 text-3xl font-bold">R$ 0</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Total pago (período)</p>
                <p id="kpi-paid" class="mt-2 text-3xl font-bold">R$ 0</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Saldo líquido (período)</p>
                <p id="kpi-net" class="mt-2 text-3xl font-bold">R$ 0</p>
            </div>
        </section>

        <!-- Busca + Ações (estilo invoice) -->
        <div class="mt-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div class="relative hidden sm:block">
                <input id="q" placeholder="Buscar descrição..."
                       class="w-[22rem] rounded-xl border border-slate-300 bg-white pl-10 pr-3 py-2.5 text-sm placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none"/>
                <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="M21 21l-4.3-4.3"/>
                </svg>
            </div>

            <div class="flex items-center gap-3">
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    Período
                    <select id="period-mode"
                            class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none">
                        <option value="default12">Padrão (12 meses)</option>
                        <option value="current">Mês atual</option>
                        <option value="custom">Personalizado</option>
                    </select>
                </label>

                <div id="period-custom" class="hidden items-center gap-2">
                    <input id="start-date" type="date"
                           class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none">
                    <span class="text-slate-400">até</span>
                    <input id="end-date" type="date"
                           class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none">
                </div>
            </div>
        </div>

        <!-- Tabs (estilo invoice) -->
        <div class="mt-3 flex items-center justify-between">
            <div id="status-tabs"
                 class="inline-flex items-center gap-2 md:gap-3 rounded-full p-2 pl-3 pr-3 ">
                <button data-status="all" class="tab-btn rounded-full px-3 py-1.5 text-sm bg-blue-600 text-white">
                    Todos
                </button>
                <button data-status="pending" class="tab-btn rounded-full px-3 py-1.5 text-sm bg-slate-100">Pendentes
                </button>
                <button data-status="paid" class="tab-btn rounded-full px-3 py-1.5 text-sm bg-slate-100">Pagos</button>
                <button data-status="overdue" class="tab-btn rounded-full px-3 py-1.5 text-sm bg-slate-100">Atrasados
                </button>
                <button data-status="canceled" class="tab-btn rounded-full px-3 py-1.5 text-sm bg-slate-100">
                    Cancelados
                </button>
            </div>
            <div>
                <div class="flex items-center gap-3">
                    <!-- Toggle agrupar -->
                    <div class="flex items-center">
                        <span class="text-sm text-slate-600 mr-3">Agrupar por registro</span>
                        <button id="grp-toggle" aria-pressed="false"
                                class="relative h-6 w-11 rounded-full bg-blue-600 transition ring-1 ring-blue-200">
                            <span class="absolute left-5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition"></span>
                        </button>
                    </div>

                    <!-- Configurações -->
                    <button id="btn-settings"
                            class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-slate-50 shadow hover:bg-slate-700">
                        Configurações
                    </button>

                    <!-- Exportar -->
                    <button
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow hover:bg-slate-50">
                        Exportar
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabela (idêntico ao invoice + centralizado) -->
        <section class="mt-5 mb-8 rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-left">
                    <thead class="text-slate-600 bg-blue-50">
                    <tr>
                        <th class="px-6 py-4 first:rounded-tl-2xl text-left">Data</th>
                        <th class="px-3 py-4 text-left">Descrição</th>
                        <th class="px-3 py-4 text-left">Valor</th>
                        <th class="px-3 py-4 text-left">Status</th>
                        <th class="px-6 py-4 last:rounded-tr-2xl text-right">Ações</th>
                    </tr>
                    </thead>

                    <tbody id="tbody" class="divide-y divide-slate-100 bg-white"></tbody>
                </table>
            </div>

        </section>
    </main>

    <!-- Modal novo -->
    <div id="modal-new" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm">
        <div
            class="absolute left-1/2 top-1/2 w-[min(520px,92vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">Nova saída</h2>
                <button data-close class="rounded-lg p-2 hover:bg-slate-100">
                    <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form id="form-new" class="mt-4 grid gap-3">
                <div>
                    <label class="text-sm font-medium">Descrição</label>
                    <input name="description"
                           class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"
                           required>
                </div>

                <div class="grid sm:grid-cols-3 gap-3">
                    <div>
                        <label class="text-sm font-medium">Pagamento em</label>
                        <input name="first_payment" type="date"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"
                               required>
                    </div>
                    <div>
                        <label class="text-sm font-medium">Recorrência</label>
                        <select name="recurrence"
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5">
                            <option value="variable">Única / Parcelada</option>
                            <option value="monthly">Mensal</option>
                            <option value="yearly">Anual</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium">Valor total (R$)</label>
                        <input name="default_amount" type="number" step="0.01" min="0"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"
                               required>
                    </div>
                </div>

                <div id="times-wrap" class="hidden">
                    <label class="text-sm font-medium">Número de parcelas (apenas para “Única / Parcelada”)</label>
                    <input name="times" type="number" min="1"
                           class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5">
                </div>

                <div class="grid sm:grid-cols-2 gap-3">
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input id="has_end" type="checkbox" class="rounded border-slate-300">
                        Possui término (para Mensal/Anual)
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input id="is_installments" type="checkbox" class="rounded border-slate-300">
                        Valor é parcelado (gera N parcelas mensais)
                    </label>
                </div>

                <div id="end-wrap" class="hidden">
                    <label class="text-sm font-medium">Data de término</label>
                    <input name="end_recurrence" type="date"
                           class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5">
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" data-close
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal baixa -->
    <div id="modal-pay" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm">
        <div
            class="absolute left-1/2 top-1/2 w-[min(720px,92vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">Dar baixa</h2>
                <button data-close class="rounded-lg p-2 hover:bg-slate-100">
                    <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form id="form-pay" class="mt-4 grid gap-4">
                <input type="hidden" name="id"/>

                <div class="grid sm:grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm font-medium">Data</label>
                        <input name="paid_at" type="date"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"
                               required>
                    </div>
                    <div>
                        <label class="text-sm font-medium">Valor da parcela</label>
                        <input name="amount" type="number" step="0.01" min="0.01"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 bg-slate-50"
                               readonly>
                        <p class="mt-1 text-[11px] text-slate-500">Valor original. O total pago fica abaixo.</p>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="mt-1">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-sm font-medium">Campos adicionais</label>
                                <p class="mt-1 text-[11px] text-slate-500">Ex: ICMS, taxa, desconto... pode adicionar mais de um.</p>
                            </div>

                            <button type="button" id="btn-add-adj"
                                    class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                + Adicionar campo
                            </button>
                        </div>

                        <div id="adj-list" class="mt-2 grid gap-2"></div>

                        <div class="mt-3 grid sm:grid-cols-2 gap-3">
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-600">Valor original</span>
                                    <b id="pv-original">R$ 0,00</b>
                                </div>
                                <div class="flex items-center justify-between mt-1">
                                    <span class="text-slate-600">Ajustes</span>
                                    <b id="pv-adjust">R$ 0,00</b>
                                </div>
                                <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-200">
                                    <span class="text-slate-900 font-semibold">Valor final</span>
                                    <b id="pv-final" class="text-slate-900">R$ 0,00</b>
                                </div>
                                <div id="adj-hint" class="mt-2 text-[11px] text-slate-500"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium">Observações</label>
                    <textarea name="notes"
                              class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"
                              rows="3"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" data-close
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button
                        class="rounded-xl bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">
                        Salvar baixa
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- Modal cancelar -->
    <div id="modal-cancel" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm">
        <div class="absolute left-1/2 top-1/2 w-[min(560px,92vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">Cancelar parcela</h2>
                <button data-close-cancel class="rounded-lg p-2 hover:bg-slate-100">
                    <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="mt-4 space-y-3 text-sm">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-slate-600">Descrição</div>
                    <div id="cancel-desc" class="font-semibold text-slate-900">-</div>

                    <div class="mt-3 grid grid-cols-2 gap-3">
                        <div>
                            <div class="text-slate-600">Vencimento</div>
                            <div id="cancel-date" class="font-medium">-</div>
                        </div>
                        <div>
                            <div class="text-slate-600">Parcela</div>
                            <div id="cancel-parcel" class="font-medium">-</div>
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-3">
                        <div>
                            <div class="text-slate-600">Valor desta parcela</div>
                            <div id="cancel-amt" class="font-semibold">-</div>
                        </div>
                        <div>
                            <div class="text-slate-600">Status</div>
                            <div id="cancel-status" class="font-medium">-</div>
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-3">
                        <div>
                            <div class="text-slate-600">Total pendente no orçamento (período)</div>
                            <div id="cancel-total-before" class="font-semibold">-</div>
                        </div>
                        <div>
                            <div class="text-slate-600">Total após cancelamento</div>
                            <div id="cancel-total-after" class="font-semibold">-</div>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800">
                    <div class="font-semibold">Atenção</div>
                    <ul class="mt-2 list-disc pl-5 space-y-1">
                        <li>Essa parcela ficará com status <b>Cancelado</b>.</li>
                        <li>Se a parcela já tiver pagamento, o sistema bloqueia o cancelamento.</li>
                    </ul>
                </div>

                <div id="cancel-err" class="hidden rounded-xl border border-rose-200 bg-rose-50 p-3 text-rose-700"></div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" data-close-cancel
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Voltar
                    </button>
                    <button id="btn-confirm-cancel"
                            class="rounded-xl bg-rose-700 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-800">
                        Confirmar cancelamento
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Configurações -->
    <div id="modal-settings" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm">
        <div class="absolute left-1/2 top-1/2 w-[min(760px,92vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Configurações</h2>
                    <p class="text-sm text-slate-600">Campos personalizados para ajustes na baixa (ICMS, taxa, desconto, etc).</p>
                </div>
                <button data-close-settings class="rounded-lg p-2 hover:bg-slate-100">
                    <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Form criar/editar -->
            <form id="form-field" class="mt-4 grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <input type="hidden" name="id" value="">

                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <label class="text-sm font-medium text-slate-700">Nome</label>
                        <input name="name" placeholder="Ex: ICMS"
                               class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-200"
                               required>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700">Tipo</label>
                        <select name="type"
                                class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-200">
                            <option value="deduct">Descontar</option>
                            <option value="add">Acrescentar</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="active" checked class="rounded border-slate-300">
                        Ativo
                    </label>

                    <div class="flex items-center gap-2">
                        <button type="button" id="btn-field-reset"
                                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            Limpar
                        </button>
                        <button id="btn-field-save"
                                class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                            Salvar
                        </button>
                    </div>
                </div>

                <div id="field-form-err" class="hidden rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700"></div>
            </form>

            <!-- Lista -->
            <div class="mt-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-800">Campos cadastrados</h3>
                    <span id="fields-count" class="text-xs text-slate-500"></span>
                </div>

                <div class="mt-2 overflow-x-auto rounded-2xl border border-slate-200">
                    <table class="min-w-full text-sm">
                        <thead class="bg-white text-left text-slate-600">
                        <tr class="border-b border-slate-200">
                            <th class="px-4 py-3">Nome</th>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                        </thead>
                        <tbody id="tbody-fields" class="divide-y divide-slate-100 bg-white"></tbody>
                    </table>
                </div>

                <div id="fields-err" class="hidden mt-3 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700"></div>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" data-close-settings
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Fechar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Confirmar Exclusão (Custom Field) -->
    <div id="modal-field-delete" class="hidden fixed inset-0 z-[60] bg-black/50 backdrop-blur-sm">
        <div class="absolute left-1/2 top-1/2 w-[min(520px,92vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Excluir campo</h2>
                    <p class="text-sm text-slate-600">Confirme para remover este campo personalizado.</p>
                </div>

                <button type="button" data-close-field-del class="rounded-lg p-2 hover:bg-slate-100">
                    <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm">
                <div class="text-slate-600">Campo</div>
                <div id="del-field-name" class="font-semibold text-slate-900">-</div>

                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span id="del-field-type" class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">-</span>
                </div>
            </div>

            <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 text-sm">
                <div class="font-semibold">Atenção</div>
                <div class="mt-1">Esse campo vai sumir da lista e não poderá ser usado em novas baixas.</div>
            </div>

            <div id="del-field-err" class="hidden mt-3 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700"></div>

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" data-close-field-del
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Cancelar
                </button>

                <button id="btn-confirm-del-field"
                        class="rounded-xl bg-rose-700 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-800">
                    Excluir
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            const fmt = n => 'R$ ' + Number(n || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
            const $ = s => document.querySelector(s);

            const formatDateBr = (str) => {
                if (!str) return '-';
                const [y, m, d] = str.split('-');
                return `${String(d).padStart(2, '0')}/${String(m).padStart(2, '0')}/${y}`;
            };

            const monthLabelPtBr = (ym) => {
                const [y, m] = ym.split('-').map(Number);
                const dt = new Date(y, m - 1, 1);
                return new Intl.DateTimeFormat('pt-BR', { month: 'long', year: 'numeric' }).format(dt);
            };

            const esc = (v) => (window.CSS && CSS.escape) ? CSS.escape(v) : String(v).replace(/["\\]/g, '\\$&');

            function ymd(d){ return d.toISOString().slice(0,10); }

            function rangeDefault12() {
                const now = new Date();
                const start = new Date(now.getFullYear(), now.getMonth(), 1);
                const endRef = new Date(now.getFullYear(), now.getMonth() + 12, 1); // +12 meses (1º dia)
                const end = new Date(endRef.getFullYear(), endRef.getMonth() + 1, 0); // último dia do mês
                return { start: ymd(start), end: ymd(end) };
            }

            function rangeCurrentMonth() {
                const now = new Date();
                const start = new Date(now.getFullYear(), now.getMonth(), 1);
                const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                return { start: ymd(start), end: ymd(end) };
            }

            function getRangeFromUI() {
                const mode = $('#period-mode')?.value || 'default12';

                if (mode === 'current') return rangeCurrentMonth();
                if (mode === 'custom') {
                    const s = $('#start-date')?.value;
                    const e = $('#end-date')?.value;
                    if (s && e) return { start: s, end: e };
                    // se custom mas sem datas, cai pro padrão
                    return rangeDefault12();
                }
                return rangeDefault12();
            }

            // -------- Tabs ----------
            let statusFilter = 'all';
            document.querySelectorAll('#status-tabs .tab-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('#status-tabs .tab-btn').forEach(x => {
                        x.className = 'tab-btn rounded-full px-3 py-1.5 text-sm bg-slate-100';
                    });
                    btn.className = 'tab-btn rounded-full px-3 py-1.5 text-sm bg-blue-600 text-white';
                    statusFilter = btn.dataset.status || 'all';
                    load();
                });
            });

            // -------- Toggle agrupar ----------
            function isGroupedOn() {
                return (localStorage.getItem('payables_group_by_budget') ?? '1') === '1';
            }

            function paintGroupToggle() {
                const b = document.getElementById('grp-toggle');
                if (!b) return;
                const on = isGroupedOn();
                b.setAttribute('aria-pressed', on ? 'true' : 'false');
                b.classList.toggle('bg-blue-600', on);
                b.classList.toggle('bg-slate-300', !on);
                b.querySelector('span').style.left = on ? '1.25rem' : '0.25rem';
            }

            paintGroupToggle();

            document.getElementById('grp-toggle')?.addEventListener('click', () => {
                const current = isGroupedOn();
                localStorage.setItem('payables_group_by_budget', current ? '0' : '1');
                paintGroupToggle();
                load();
            });

            // -------- Chips / badges ----------
            function chipStatus(i) {
                if (i.status === 'paid') {
                    return '<span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">Pago</span>';
                }
                if (i.status === 'canceled') {
                    return '<span class="inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700">Cancelado</span>';
                }
                if (i.overdue) {
                    return '<span class="inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700">Atrasado</span>';
                }
                return '<span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">Pendente</span>';
            }

            function badgeKind(origin = {}) {
                const t = origin.type; // variable/monthly/yearly
                const total = Number(origin.total_recurrences || 1);

                if (t === 'monthly') {
                    return '<span class="inline-flex rounded-full bg-sky-50 px-2.5 py-1 text-xs font-medium text-sky-700">Mensal</span>';
                }
                if (t === 'yearly') {
                    return '<span class="inline-flex rounded-full bg-violet-50 px-2.5 py-1 text-xs font-medium text-violet-700">Anual</span>';
                }

                if (total <= 1) {
                    return '<span class="inline-flex rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700">Única</span>';
                }

                return `<span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">Parcelado ${total}x</span>`;
            }

            function parcelText(origin = {}) {
                const total = Number(origin.total_recurrences || 1);
                const cur = Number(origin.recurrence || 1);

                if (origin.type === 'variable' && total > 1) return `Parcela ${cur}/${total}`;
                if (origin.type !== 'variable' && total > 1) return `Parcela ${cur}/${total}`;
                return '';
            }

            // -------- Modal baixa ----------
            function bindPayModal() {
                const modal = document.getElementById('modal-pay');
                const form  = document.getElementById('form-pay');

                const listWrap  = document.getElementById('adj-list');
                const btnAddAdj = document.getElementById('btn-add-adj');

                const pvOriginal = document.getElementById('pv-original');
                const pvAdjust   = document.getElementById('pv-adjust');
                const pvFinal    = document.getElementById('pv-final');
                const adjHint    = document.getElementById('adj-hint');

                const btnSubmit  = form.querySelector('button[type="submit"]');

                let fieldsCache = null;
                let fieldsLoading = false;

                const toNum = (v) => {
                    const s = String(v ?? '').replace(',', '.').replace(/[^\d.-]/g, '');
                    const n = parseFloat(s);
                    return Number.isFinite(n) ? n : 0;
                };

                const fmtAdj = (delta) => {
                    const sign = delta < 0 ? '-' : '+';
                    return `${sign}${fmt(Math.abs(delta))}`;
                };

                async function fetchCustomFields() {
                    if (fieldsCache || fieldsLoading) return fieldsCache;
                    fieldsLoading = true;

                    try {
                        const r = await fetch('/finances/payables/custom-field-api', { headers: { 'Accept': 'application/json' } });
                        if (!r.ok) throw new Error('fail');
                        const j = await r.json();
                        const list = Array.isArray(j.data) ? j.data : [];
                        fieldsCache = list.filter(x => x && x.active);
                        return fieldsCache;
                    } catch (e) {
                        fieldsCache = [];
                        return fieldsCache;
                    } finally {
                        fieldsLoading = false;
                    }
                }

                function optionHtml(fields) {
                    return `<option value="">Selecione</option>` + fields.map(f => {
                        const label = `${f.name || 'Campo'} (${f.type === 'deduct' ? 'Descontar' : 'Acrescentar'})`;
                        return `<option value="${f.id}">${label}</option>`;
                    }).join('');
                }

                function createAdjRow(fields) {
                    const row = document.createElement('div');
                    row.className = 'grid sm:grid-cols-12 gap-2 items-end';
                    row.dataset.adjRow = '1';

                    row.innerHTML = `
      <div class="sm:col-span-6">
        <label class="text-xs font-medium text-slate-600">Campo</label>
        <select class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"
                data-adj-field>
          ${optionHtml(fields)}
        </select>
      </div>

      <div class="sm:col-span-4">
        <label class="text-xs font-medium text-slate-600">Valor do ajuste (R$)</label>
        <input type="number" step="0.01" min="0"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"
               placeholder="0,00"
               data-adj-value>
      </div>

      <div class="sm:col-span-2 flex justify-end">
        <button type="button"
                class="mt-5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                data-adj-remove>
          Remover
        </button>
      </div>
    `;

                    const sel = row.querySelector('[data-adj-field]');
                    const inp = row.querySelector('[data-adj-value]');
                    const rm  = row.querySelector('[data-adj-remove]');

                    sel.addEventListener('change', () => {
                        // quando selecionar campo, se tem total pago, tenta completar valor restante no último campo
                        autoFillRemaining();
                        calcPreview();
                    });

                    inp.addEventListener('input', () => {
                        autoFillRemaining();
                        calcPreview();
                    });

                    rm.addEventListener('click', () => {
                        row.remove();
                        ensureAtLeastOneRow(fields);
                        autoFillRemaining();
                        calcPreview();
                    });

                    return row;
                }

                function getAdjRows() {
                    return Array.from(listWrap?.querySelectorAll('[data-adj-row="1"]') || []);
                }

                function serializeAdjustments() {
                    const rows = getAdjRows();
                    const out = [];

                    for (const r of rows) {
                        const fieldId = r.querySelector('[data-adj-field]')?.value || '';
                        const val = toNum(r.querySelector('[data-adj-value]')?.value);

                        if (!fieldId) continue;
                        if (val <= 0) continue;

                        const f = (fieldsCache || []).find(x => String(x.id) === String(fieldId));
                        const type = f?.type || 'deduct';

                        out.push({
                            custom_field_id: fieldId,
                            type,
                            value: val
                        });
                    }
                    return out;
                }

                function sumDeltaFromAdjustments(adjs) {
                    return adjs.reduce((s, a) => {
                        const v = toNum(a.value);
                        return s + (a.type === 'deduct' ? -v : +v);
                    }, 0);
                }

                function ensureAtLeastOneRow(fields) {
                    if (!listWrap) return;
                    if (getAdjRows().length > 0) return;
                    listWrap.appendChild(createAdjRow(fields));
                }

                function autoFillRemaining() {
                    const paidInp = form.querySelector('input[name="amount_paid"]');
                    if (!paidInp) return;

                    const base = toNum(form.amount.value);
                    const paid = toNum(paidInp.value);

                    if (paid <= 0) return;

                    const rows = getAdjRows();
                    if (!rows.length) return;

                    // escolhe o último row com campo selecionado
                    const target = [...rows].reverse().find(r => (r.querySelector('[data-adj-field]')?.value || '') !== '');
                    if (!target) return;

                    const targetSel = target.querySelector('[data-adj-field]');
                    const targetVal = target.querySelector('[data-adj-value]');

                    // soma de todos, exceto target
                    let deltaOthers = 0;
                    for (const r of rows) {
                        if (r === target) continue;
                        const fid = r.querySelector('[data-adj-field]')?.value || '';
                        const v = toNum(r.querySelector('[data-adj-value]')?.value);
                        if (!fid || v <= 0) continue;

                        const f = (fieldsCache || []).find(x => String(x.id) === String(fid));
                        const type = f?.type || 'deduct';
                        deltaOthers += (type === 'deduct') ? -v : +v;
                    }

                    const neededDelta = (paid - base) - deltaOthers;

                    // aplica no target, respeitando tipo do campo escolhido
                    const fid = targetSel.value;
                    const f = (fieldsCache || []).find(x => String(x.id) === String(fid));
                    const type = f?.type || 'deduct';

                    // se for deduct, delta é negativo. se for add, delta é positivo.
                    // então o valor é o módulo do delta.
                    const compatible =
                        (type === 'deduct' && neededDelta <= 0) ||
                        (type === 'add' && neededDelta >= 0);

                    if (!compatible) return;

                    const absVal = Math.abs(neededDelta);
                    // só preenche se o usuário não digitou manualmente (ou se está vazio)
                    if (!targetVal.value || toNum(targetVal.value) === 0) {
                        targetVal.value = absVal > 0 ? absVal.toFixed(2) : '';
                    }
                }

                function calcPreview() {
                    const base = toNum(form.amount.value);
                    const adjs = serializeAdjustments();
                    const delta = sumDeltaFromAdjustments(adjs);

                    let ok = true;
                    let final = base + delta;

                    if (final < 0) {
                        final = 0;
                        ok = false;
                        if (adjHint) adjHint.textContent = 'O ajuste não pode deixar o valor final negativo.';
                    } else {
                        if (adjHint) adjHint.textContent = '';
                    }

                    pvOriginal.textContent = fmt(base);
                    pvAdjust.textContent   = delta === 0 ? fmt(0) : fmtAdj(delta);
                    pvFinal.textContent    = fmt(final);

                    // valida: se selecionou campo, exige valor > 0
                    for (const r of getAdjRows()) {
                        const fid = r.querySelector('[data-adj-field]')?.value || '';
                        const v = toNum(r.querySelector('[data-adj-value]')?.value);
                        if (fid && v <= 0) ok = false;
                    }

                    if (final < 0.01) ok = false;

                    if (btnSubmit) {
                        btnSubmit.disabled = !ok;
                        btnSubmit.classList.toggle('opacity-60', !ok);
                        btnSubmit.classList.toggle('cursor-not-allowed', !ok);
                    }

                    return { base, delta, final, ok };
                }

                // fechar modal
                modal.querySelectorAll('[data-close]').forEach(b =>
                    b.addEventListener('click', () => modal.classList.add('hidden'))
                );

                btnAddAdj?.addEventListener('click', async () => {
                    const fields = fieldsCache || await fetchCustomFields();
                    listWrap.appendChild(createAdjRow(fields));
                    calcPreview();
                });

                form.querySelector('input[name="amount_paid"]')?.addEventListener('input', () => {
                    // sempre que mexer no total pago, tenta completar o ajuste restante
                    autoFillRemaining();
                    calcPreview();
                });

                form.addEventListener('submit', async (e) => {
                    e.preventDefault();

                    const id = form.id.value;

                    const { base, final, ok } = calcPreview();
                    if (!ok) { alert('Preencha os ajustes corretamente.'); return; }

                    const payload = {
                        paid_at: form.paid_at.value,
                        notes: form.notes.value || '',
                        amount_original: String(base),
                        amount_final: String(final),
                        amount: String(final),                 // <- backend atual usa isso
                        adjustments: serializeAdjustments(),    // <- backend novo vai usar
                    };

                    const res = await fetch(`/finances/payable-api/${id}/pay`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        },
                        body: JSON.stringify(payload)
                    });

                    if (res.ok) {
                        modal.classList.add('hidden');
                        form.reset();
                        if (listWrap) listWrap.innerHTML = '';
                        fieldsCache = null; // força recarregar na próxima (pra pegar campos novos)
                        load();
                    } else {
                        const j = await res.json().catch(() => ({}));
                        alert(j.message || 'Erro ao baixar');
                    }
                });

                return {
                    open: async (id, amount) => {
                        form.id.value = id;
                        form.paid_at.value = new Date().toISOString().slice(0, 10);

                        // amount vira "valor da parcela"
                        form.amount.value = (Number(amount || 0)).toFixed(2);

                        // total pago começa igual ao valor da parcela
                        const paidInp = form.querySelector('input[name="amount_paid"]');
                        if (paidInp) paidInp.value = (Number(amount || 0)).toFixed(2);

                        form.notes.value = '';

                        modal.classList.remove('hidden');

                        const fields = await fetchCustomFields();
                        if (listWrap) listWrap.innerHTML = '';
                        ensureAtLeastOneRow(fields);

                        calcPreview();
                    }
                };
            }

            // --------- Modal Novo ----------
            function bindNewModal() {
                const modal = $('#modal-new');
                const btn = $('#btn-new');
                const form = $('#form-new');
                const times = $('#times-wrap');

                const hasEnd = $('#has_end');
                const endWrap = $('#end-wrap');
                const isInst = $('#is_installments');

                hasEnd.addEventListener('change', () => endWrap.classList.toggle('hidden', !hasEnd.checked));

                function toggleTimes() {
                    const rec = form.recurrence.value;
                    times.classList.toggle('hidden', !(rec === 'variable' && isInst.checked));
                }

                form.recurrence.addEventListener('change', toggleTimes);
                isInst.addEventListener('change', toggleTimes);

                btn?.addEventListener('click', () => {
                    form.reset();
                    hasEnd.checked = false;
                    endWrap.classList.add('hidden');
                    isInst.checked = false;
                    times.classList.add('hidden');
                    modal.classList.remove('hidden');
                });

                modal.querySelectorAll('[data-close]').forEach(b =>
                    b.addEventListener('click', () => modal.classList.add('hidden'))
                );

                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const fd = new FormData(form);
                    if (!hasEnd.checked) fd.delete('end_recurrence');
                    if (!(form.recurrence.value === 'variable' && isInst.checked)) fd.delete('times');

                    const res = await fetch('/finances/payable-api', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]')?.content || ''
                        },
                        body: fd
                    });
                    if (res.ok) {
                        modal.classList.add('hidden');
                        form.reset();
                        load();
                    } else alert('Erro ao salvar');
                });
            }

            // -------- Modal cancelar ----------
            function bindCancelModal() {
                const modal = document.getElementById('modal-cancel');
                const errBox = document.getElementById('cancel-err');
                const btnConfirm = document.getElementById('btn-confirm-cancel');

                let current = null;

                modal.querySelectorAll('[data-close-cancel]').forEach(b =>
                    b.addEventListener('click', () => modal.classList.add('hidden'))
                );

                btnConfirm.addEventListener('click', async () => {
                    if (!current) return;

                    errBox.classList.add('hidden');
                    errBox.textContent = '';

                    if (!current.canCancel) {
                        errBox.textContent = 'Não é possível cancelar esta parcela (já possui pagamento ou não está pendente).';
                        errBox.classList.remove('hidden');
                        return;
                    }

                    btnConfirm.disabled = true;
                    btnConfirm.classList.add('opacity-60');

                    try {
                        const res = await fetch(`/finances/payable-api/${current.id}/cancel`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                            }
                        });

                        if (res.ok) {
                            modal.classList.add('hidden');
                            current = null;
                            load();
                        } else {
                            const j = await res.json().catch(() => ({}));
                            errBox.textContent = j.message || 'Falha ao cancelar';
                            errBox.classList.remove('hidden');
                        }
                    } finally {
                        btnConfirm.disabled = false;
                        btnConfirm.classList.remove('opacity-60');
                    }
                });

                return {
                    open: (payload) => {
                        current = payload;

                        $('#cancel-desc').textContent = payload.description || '-';
                        $('#cancel-date').textContent = formatDateBr(payload.date);
                        $('#cancel-parcel').textContent = payload.parcel || '-';
                        $('#cancel-amt').textContent = fmt(payload.amount);
                        $('#cancel-status').textContent = payload.statusText || '-';
                        $('#cancel-total-before').textContent = fmt(payload.totalBefore);
                        $('#cancel-total-after').textContent = fmt(payload.totalAfter);

                        errBox.classList.add('hidden');
                        errBox.textContent = '';

                        modal.classList.remove('hidden');
                    }
                };
            }

            // -------- Render helpers ----------
            function sortAscByDate(list) {
                return [...list].sort((a, b) => (a.date || '').localeCompare(b.date || ''));
            }

            function capFirst(s) {
                if (!s) return s;
                return s.charAt(0).toUpperCase() + s.slice(1);
            }

            function remainingToPay(i) {
                if (i.status !== 'pending') return 0;
                return Math.max(0, Number(i.price || 0) - Number(i.amount_paid || 0));
            }

            function renderFlat(data) {
                const list = sortAscByDate(data);

                // total por mês (só pendente/remaining)
                const monthTotals = new Map();
                for (const it of list) {
                    const ym = (it.date || '').slice(0, 7);
                    const v  = remainingToPay(it);
                    monthTotals.set(ym, (monthTotals.get(ym) || 0) + v);
                }

                let currentYm = null;

                const rows = list.map(i => {
                    const origin = i.origin || {};
                    const ym = (i.date || '').slice(0, 7);

                    let monthHead = '';
                    if (ym && ym !== currentYm) {
                        currentYm = ym;
                        const total = monthTotals.get(ym) || 0;

                        monthHead = `
<tr class="bg-slate-50 border-t border-slate-200">
  <td colspan="5" class="px-6 py-3">
    <div class="flex items-center justify-between">
      <div class="text-xs font-semibold uppercase tracking-wide text-slate-600">${monthLabelPtBr(ym)}</div>
      <div class="text-xs text-slate-600">Total a pagar no mês: <b>${fmt(total)}</b></div>
    </div>
  </td>
</tr>`;
                    }

                    const det = parcelText(origin);
                    const paidLine = (i.paid_total > 0)
                        ? `<div class="text-xs text-emerald-700 mt-0.5">Pago ${fmt(i.paid_total)}${i.last_paid_at ? ' em ' + new Date(i.last_paid_at).toLocaleDateString('pt-BR') : ''}</div>`
                        : '';

                    const actions = (i.status === 'pending')
                        ? `
<div class="flex items-center justify-center gap-2">
  <button class="rounded-lg bg-blue-700 px-2.5 py-1 text-xs font-medium text-white shadow hover:bg-blue-800"
          data-open-pay="${i.id}" data-amount="${i.price}">
    Dar baixa
  </button>
  <button class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700 hover:bg-rose-100"
          data-cancel="${i.id}">
    Cancelar
  </button>
</div>` : '';

                    const line = `
<tr class="hover:bg-slate-50 text-center">
  <td class="px-6 py-3">${formatDateBr(i.date)}</td>
  <td class="px-3 py-3">
    <div class="font-medium">${origin.description || '-'}</div>
    ${det ? `<div class="text-xs text-slate-500">${det}</div>` : ''}
    ${paidLine}
  </td>
  <td class="px-3 py-3">${fmt(i.price)}</td>
  <td class="px-3 py-3">${chipStatus(i)}</td>
  <td class="px-6 py-3">${actions}</td>
</tr>`;

                    return monthHead + line;
                }).join('');

                $('#tbody').innerHTML = rows;
            }

            function groupByPayable(data, meta = {}) {
                const groups = new Map();

                for (const i of data) {
                    const origin = i.origin || {};
                    const key = origin.payable_id || origin.description || i.id;

                    if (!groups.has(key)) {
                        groups.set(key, {
                            key,
                            title: origin.description || '-',
                            kind: badgeKind(origin),
                            items: [],
                        });
                    }
                    groups.get(key).items.push(i);
                }

                for (const g of groups.values()) {
                    g.items = sortAscByDate(g.items);

                    const m = meta[g.key]; // ✅ vem do backend

                    // total pendente REAL (backend) > fallback (frontend)
                    const fallbackPending = g.items
                        .filter(x => x.status === 'pending')
                        .reduce((s, x) => s + remainingToPay(x), 0);

                    g.pendingTotal = m ? Number(m.pending_total || 0) : fallbackPending;

                    g.afterCancel = (recId) => {
                        const rec = g.items.find(x => x.id === recId);
                        const amt = (rec && rec.status === 'pending') ? remainingToPay(rec) : 0;
                        return Math.max(0, g.pendingTotal - amt);
                    };

                    const hasPending = m ? !!m.has_pending : g.items.some(x => x.status === 'pending');
                    const hasOverdue = m ? !!m.has_overdue : g.items.some(x => x.overdue && x.status === 'pending');

                    g.firstDue = m?.first_due || g.items[0]?.date || '';

                    g.groupStatusChip = hasPending
                        ? (hasOverdue
                            ? '<span class="inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700">Em atraso</span>'
                            : '<span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">Em aberto</span>')
                        : '<span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">Quitado</span>';
                }

                // ✅ ordena grupos por first due real (backend)
                return [...groups.values()].sort((a, b) => (a.firstDue || '').localeCompare(b.firstDue || ''));
            }

            function renderGrouped(data, meta = {}) {
                const groups = groupByPayable(data, meta);

                const html = groups.map(g => {
                    const head = `
<tr class="bg-slate-50 border-t border-slate-200">
  <td colspan="5" class="px-6 py-3 text-left">
    <div class="flex items-center justify-between gap-4">

      <!-- ESQUERDA: título + tipo + total -->
      <div class="min-w-0">
        <div class="flex items-center gap-2">
          <div class="font-semibold text-slate-900 truncate">${g.title}</div>
        </div>
        <div class="text-xs text-slate-500 mt-0.5">
          Total pendente no orçamento: <b>${fmt(g.pendingTotal)}</b>
        </div>
      </div>

      <!-- DIREITA: status + botão (juntos) -->
      <div class="flex items-center text-center gap-2 shrink-0">
       ${g.kind} ${g.groupStatusChip}
        <button class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100" data-toggle-group="${g.key}" aria-expanded="false">
          Clique para ver
        </button>
      </div>

    </div>
  </td>
</tr>`;

                    const rows = g.items.map(i => {
                        const origin = i.origin || {};
                        const det = parcelText(origin);
                        const paidLine = (i.paid_total > 0)
                            ? `<div class="text-xs text-emerald-700 mt-0.5">Pago ${fmt(i.paid_total)}${i.last_paid_at ? ' em ' + new Date(i.last_paid_at).toLocaleDateString('pt-BR') : ''}</div>`
                            : '';

                        const actions = (i.status === 'pending')
                            ? `
<div class="flex items-center justify-center gap-2">
  <button class="rounded-lg bg-blue-700 px-2.5 py-1 text-xs font-medium text-white shadow hover:bg-blue-800"
          data-open-pay="${i.id}" data-amount="${i.price}">
    Dar baixa
  </button>
  <button class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700 hover:bg-rose-100"
          data-cancel="${i.id}">
    Cancelar
  </button>
</div>` : '';

                        return `
<tr class="hover:bg-slate-50 hidden" data-group-row="${g.key}" data-rec-id="${i.id}">
  <td class="px-6 py-3 text-left">${formatDateBr(i.date)}</td>
  <td class="px-3 py-3 text-left">
    <div class="font-medium">${origin.description || '-'}</div>
    ${det ? `<div class="text-xs text-slate-500">${det}</div>` : ''}
    ${paidLine}
  </td>
  <td class="px-3 py-3 text-left">${fmt(i.price)}</td>
  <td class="px-3 py-3 text-left">${chipStatus(i)}</td>
  <td class="px-6 py-3 text-right">${actions}</td>
</tr>`;
                    }).join('');

                    return head + rows;
                }).join('');

                $('#tbody').innerHTML = html;

                document.querySelectorAll('[data-toggle-group]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const key = btn.dataset.toggleGroup;
                        const expanded = btn.getAttribute('aria-expanded') === 'true';

                        btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                        btn.textContent = expanded ? 'Clique para ver' : 'Ocultar';

                        document.querySelectorAll(`[data-group-row="${esc(key)}"]`)
                            .forEach(tr => tr.classList.toggle('hidden', expanded));
                    });
                });
            }

            function bindRowActions(dataset, meta, cancelModal, payModal) {
                document.querySelectorAll('[data-open-pay]').forEach(b => {
                    b.addEventListener('click', () => {
                        const id = b.dataset.openPay;
                        const amt = Number(b.dataset.amount || 0);
                        payModal.open(id, amt || undefined);
                    });
                });

                document.querySelectorAll('[data-cancel]').forEach(b => {
                    b.addEventListener('click', () => {
                        const id = b.dataset.cancel;

                        const rec = dataset.find(x => x.id === id);
                        if (!rec) return;

                        const origin = rec.origin || {};
                        const key = origin.payable_id || origin.description;

                        const totalBefore = meta?.[key]
                            ? Number(meta[key].pending_total || 0)
                            : dataset.filter(x => x.status === 'pending').reduce((s, x) => s + remainingToPay(x), 0);

                        const canCancel = (rec.status === 'pending') && (Number(rec.paid_total || 0) <= 0);
                        const totalAfter = Math.max(0, totalBefore - (canCancel ? remainingToPay(rec) : 0));

                        cancelModal.open({
                            id,
                            canCancel,
                            description: origin.description,
                            date: rec.date,
                            parcel: parcelText(origin) || '-',
                            amount: rec.price,
                            statusText: canCancel ? 'Pendente' : 'Bloqueado',
                            totalBefore,
                            totalAfter
                        });
                    });
                });
            }

            (function initPeriodUI(){
                const mode = localStorage.getItem('payables_period_mode') || 'default12';
                $('#period-mode').value = mode;

                const isCustom = mode === 'custom';
                $('#period-custom').classList.toggle('hidden', !isCustom);
                $('#period-custom').classList.toggle('flex', isCustom);

                if (isCustom) {
                    $('#start-date').value = localStorage.getItem('payables_period_start') || '';
                    $('#end-date').value   = localStorage.getItem('payables_period_end') || '';
                }
            })();

            // -------- Load ----------
            async function load() {
                const url = new URL('/finances/payable-api', location.origin);

                const q = $('#q')?.value?.trim();
                if (q) url.searchParams.set('q', q);

                if (statusFilter && statusFilter !== 'all') url.searchParams.set('status', statusFilter);

                const grouped = isGroupedOn();
                url.searchParams.set('grouped', grouped ? '1' : '0'); // ✅

                const range = getRangeFromUI(); // ✅ period-mode (default12/current/custom)

                // ✅ filtro da LISTA só no desagrupado
                if (!grouped && range?.start && range?.end) {
                    url.searchParams.set('start', range.start);
                    url.searchParams.set('end', range.end);
                }

                // ✅ KPIs sempre respeitam o período
                if (range?.start && range?.end) {
                    url.searchParams.set('kpi_start', range.start);
                    url.searchParams.set('kpi_end', range.end);
                }

                const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const data = await r.json();

                const dataset = (data.data || []);
                dataset.sort((a, b) => (a.date || '').localeCompare(b.date || ''));

                const meta = data.group_meta || {};

                if (grouped) renderGrouped(dataset, meta);
                else renderFlat(dataset);

                $('#kpi-pend').textContent = fmt(data.kpis?.pending_sum || 0);
                $('#kpi-paid').textContent = fmt(data.kpis?.paid_sum || 0);
                $('#kpi-net').textContent  = fmt(data.kpis?.net_outflow || 0);

                bindRowActions(dataset, meta, cancelModal, payModal);
            }

            // Modal Config
            function bindSettingsModal() {
                const modal = document.getElementById('modal-settings');
                const btnOpen = document.getElementById('btn-settings');

                const form = document.getElementById('form-field');
                const btnReset = document.getElementById('btn-field-reset');

                const tbody = document.getElementById('tbody-fields');
                const count = document.getElementById('fields-count');

                const errBox = document.getElementById('fields-err');
                const errForm = document.getElementById('field-form-err');

                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

                const typeLabel = (t) => (t === 'deduct' ? 'Descontar' : 'Acrescentar');

                const delModal   = document.getElementById('modal-field-delete');
                const delName    = document.getElementById('del-field-name');
                const delType    = document.getElementById('del-field-type');
                const delErr     = document.getElementById('del-field-err');
                const btnDelOk   = document.getElementById('btn-confirm-del-field');

                let delCurrent = null;

                function openDeleteModal(field) {
                    delCurrent = field;

                    if (delErr) { delErr.classList.add('hidden'); delErr.textContent = ''; }
                    if (delName) delName.textContent = field?.name || '-';

                    const label = `${typeLabel(field?.type)}${field?.active ? '' : ' (Inativo)'}`;
                    if (delType) delType.textContent = label;

                    if (delModal) delModal.classList.remove('hidden');
                }

                delModal?.querySelectorAll('[data-close-field-del]').forEach(b => {
                    b.addEventListener('click', () => {
                        delModal.classList.add('hidden');
                        delCurrent = null;
                        if (delErr) { delErr.classList.add('hidden'); delErr.textContent = ''; }
                    });
                });

                const escHtml = (str) => String(str ?? '').replace(/[&<>"']/g, (m) => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
                }[m]));

                function setErr(box, msg) {
                    if (!box) return;
                    if (!msg) { box.classList.add('hidden'); box.textContent = ''; return; }
                    box.textContent = msg;
                    box.classList.remove('hidden');
                }

                function resetForm() {
                    form.id.value = '';
                    form.name.value = '';
                    form.type.value = 'deduct';
                    form.active.checked = true;
                    setErr(errForm, '');
                }

                function rowHtml(f) {
                    const chip = f.active
                        ? `<span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">Ativo</span>`
                        : `<span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">Inativo</span>`;

                    const kind = (f.type === 'deduct')
                        ? `<span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">${typeLabel(f.type)}</span>`
                        : `<span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">${typeLabel(f.type)}</span>`;

                    return `
<tr>
  <td class="px-4 py-3">
    <div class="font-medium text-slate-900">${escHtml(f.name)}</div>
  </td>
  <td class="px-4 py-3">${kind}</td>
  <td class="px-4 py-3">${chip}</td>
  <td class="px-4 py-3 text-right">
    <div class="inline-flex items-center gap-2">
      <button class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
              data-edit-field="${f.id}">
        Editar
      </button>

      <button class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
              data-toggle-field="${f.id}">
        ${f.active ? 'Desativar' : 'Ativar'}
      </button>

      <button class="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700"
              data-del-field="${f.id}">
        Excluir
      </button>
    </div>
  </td>
</tr>`;
                }

                async function loadFields() {
                    setErr(errBox, '');
                    tbody.innerHTML = `<tr><td class="px-4 py-4 text-slate-500" colspan="4">Carregando...</td></tr>`;

                    try {
                        const res = await fetch('/finances/payables/custom-field-api', {
                            headers: { 'Accept': 'application/json' }
                        });

                        // ✅ se não for 200, mostra erro real
                        if (!res.ok) {
                            const txt = await res.text().catch(() => '');
                            throw new Error(`HTTP ${res.status} - ${txt.slice(0, 160)}`);
                        }

                        const j = await res.json();
                        const list = j.data || [];

                        count.textContent = `${list.length} item(ns)`;

                        if (!list.length) {
                            tbody.innerHTML = `<tr><td class="px-4 py-4 text-slate-500" colspan="4">Nenhum campo cadastrado.</td></tr>`;
                            return;
                        }

                        tbody.innerHTML = list.map(rowHtml).join('');

                        // binds
                        tbody.querySelectorAll('[data-edit-field]').forEach(b => {
                            b.addEventListener('click', () => {
                                const id = b.dataset.editField;
                                const f = list.find(x => x.id === id);
                                if (!f) return;
                                resetForm();
                                form.id.value = f.id;
                                form.name.value = f.name;
                                form.type.value = f.type;
                                form.active.checked = !!f.active;
                                form.name.focus();
                            });
                        });

                        tbody.querySelectorAll('[data-toggle-field]').forEach(b => {
                            b.addEventListener('click', async () => {
                                const id = b.dataset.toggleField;
                                const res2 = await fetch(`/finances/payables/custom-fields/${id}/toggle`, {
                                    method: 'POST',
                                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
                                });
                                if (!res2.ok) {
                                    const jj = await res2.json().catch(() => ({}));
                                    setErr(errBox, jj.message || 'Falha ao ativar/desativar.');
                                    return;
                                }
                                await loadFields();
                            });
                        });

                        tbody.querySelectorAll('[data-del-field]').forEach(b => {
                            b.addEventListener('click', () => {
                                const id = b.dataset.delField;
                                const f = list.find(x => x.id === id);
                                if (!f) return;
                                openDeleteModal(f);
                            });
                        });

                    } catch (e) {
                        console.error('[fields] load error:', e);
                        tbody.innerHTML = `<tr><td class="px-4 py-4 text-rose-700" colspan="4">Erro ao carregar.</td></tr>`;
                        setErr(errBox, 'Erro ao carregar campos.');
                    }
                }

                btnOpen?.addEventListener('click', async () => {
                    resetForm();
                    modal.classList.remove('hidden');
                    await loadFields();
                });

                modal.querySelectorAll('[data-close-settings]').forEach(b => {
                    b.addEventListener('click', () => modal.classList.add('hidden'));
                });

                btnReset.addEventListener('click', () => resetForm());

                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    setErr(errForm, '');

                    const payload = {
                        name: (form.name.value || '').trim(),
                        type: form.type.value,
                        active: !!form.active.checked
                    };

                    const isEdit = !!form.id.value;
                    const url = isEdit
                        ? `/finances/payables/custom-field-api/${form.id.value}`
                        : `/finances/payables/custom-field-api`;
                    const method = isEdit ? 'PUT' : 'POST';

                    const res = await fetch(url, {
                        method,
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf
                        },
                        body: JSON.stringify(payload)
                    });

                    if (!res.ok) {
                        const jj = await res.json().catch(() => ({}));
                        setErr(errForm, jj.message || 'Falha ao salvar.');
                        return;
                    }

                    resetForm();
                    await loadFields();
                });

                btnDelOk?.addEventListener('click', async () => {
                    if (!delCurrent?.id) return;

                    // reset erro
                    if (delErr) { delErr.classList.add('hidden'); delErr.textContent = ''; }

                    btnDelOk.disabled = true;
                    btnDelOk.classList.add('opacity-60');

                    try {
                        const res2 = await fetch(`/finances/payables/custom-field-api/${delCurrent.id}`, {
                            method: 'DELETE',
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
                        });

                        if (!res2.ok) {
                            const jj = await res2.json().catch(() => ({}));
                            if (delErr) {
                                delErr.textContent = jj.message || 'Falha ao excluir.';
                                delErr.classList.remove('hidden');
                            }
                            return;
                        }

                        // se estava editando o mesmo, limpa
                        if (form.id.value === delCurrent.id) resetForm();

                        delModal.classList.add('hidden');
                        delCurrent = null;

                        await loadFields();
                    } finally {
                        btnDelOk.disabled = false;
                        btnDelOk.classList.remove('opacity-60');
                    }
                });

                return { open: () => btnOpen?.click() };
            }

            $('#period-mode')?.addEventListener('change', () => {
                const mode = $('#period-mode').value;
                $('#period-custom').classList.toggle('hidden', mode !== 'custom');
                $('#period-custom').classList.toggle('flex', mode === 'custom');
                localStorage.setItem('payables_period_mode', mode);
                load();
            });

            ['#start-date', '#end-date'].forEach(sel => {
                $(sel)?.addEventListener('change', () => {
                    localStorage.setItem('payables_period_start', $('#start-date').value || '');
                    localStorage.setItem('payables_period_end', $('#end-date').value || '');
                    load();
                });
            });

            // init
            const payModal = bindPayModal();
            const cancelModal = bindCancelModal(); // ✅ faltava isso no seu script

            ['#q'].forEach(s => $(s)?.addEventListener('input', load));

            const settingsModal = bindSettingsModal();
            bindNewModal();
            load();
        </script>
    @endpush
@endsection

