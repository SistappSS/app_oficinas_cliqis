@extends('layouts.templates.template')

@section('content')
    @push('styles')
        <style>
            .skeleton {
                position: relative !important;
                overflow: hidden !important;
                background-color: rgb(226 232 240) !important; /* slate-200 */
                border-radius: .5rem !important;
            }

            .skeleton::after {
                content: "" !important;
                position: absolute !important;
                inset: 0 !important;
                background-image: linear-gradient(
                        90deg,
                        rgba(255, 255, 255, 0) 0%,
                        rgba(255, 255, 255, .6) 50%,
                        rgba(255, 255, 255, 0) 100%
                ) !important;
                animation: shimmer 1.2s infinite !important;
                transform: translateX(-100%) !important;
            }

            @keyframes shimmer {
                100% {
                    transform: translateX(100%);
                }
            }

            /* card skeleton layout */
            .sk-card {
                border-radius: 1rem !important;
                border: 1px solid rgb(226 232 240) !important; /* slate-200 */
                background-color: white !important;
                box-shadow: 0 1px 2px rgb(0 0 0 / .05) !important;
                padding: 1.25rem !important;
                display: flex !important;
                flex-direction: column !important;
                gap: .75rem !important;
            }

            .desc-clamp {
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            .title-clamp {
                display: -webkit-box;
                -webkit-line-clamp: 1;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            .grid-contents {
                display: contents;
            }
        </style>
    @endpush

    <main class="mx-auto max-w-7xl w-full px-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-semibold">Nova Proposta</h1>
                <p class="text-sm text-slate-600">
                    Selecione itens únicos e recorrentes. Desconto global é aplicado no resumo.
                    Condições de pagamento ficam no modal.
                </p>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('budget.view') }}"
                   class="inline-flex items-center rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow hover:bg-blue-800">
                    Orçamentos
                </a>

                <button id="toggle-header"
                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow hover:bg-slate-50"
                        aria-expanded="true"
                        aria-controls="header-collapsible"
                        type="button"
                        title="Expandir/contrair cabeçalho">
                    <i id="toggle-icon" class="fa-solid fa-up-right-and-down-left-from-center"></i>
                </button>
            </div>
        </div>

        <div class="mx-auto max-w-7xl w-full grid grid-cols-1 lg:grid-cols-[1fr_330px] gap-6">

            <!-- Coluna esquerda (seleção + listagem) -->
            <section class="min-h-0 overflow-auto pr-1">
                <div class="grid sm:grid-cols-2 gap-3">
                    <div class="relative w-full">
                        <input id="search" type="text" placeholder="Pesquisar serviço .."
                               class="w-full rounded-2xl border border-slate-300 bg-white pl-10 pr-4 py-2.5 outline-none placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200"/>
                        <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <circle cx="11" cy="11" r="7"/>
                            <path d="M21 21l-4.3-4.3"/>
                        </svg>
                    </div>

                    <div class="relative" id="client-wrap">
                        <input id="client" type="text" placeholder="Selecione o cliente"
                               class="w-full rounded-2xl border border-slate-300 bg-white pl-5 py-2.5 outline-none placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200"/>
                        <input id="client_id" type="hidden">
                        <ul id="client-list"
                            class="absolute z-50 mt-1 hidden w-full max-h-64 overflow-auto rounded-xl border border-slate-200 bg-white shadow-xl"></ul>
                    </div>
                </div>

                <!-- Toolbar -->
                <div class="mt-3 flex flex-col">
                    <div class="inline-flex rounded-full mt-3">
                        <button class="type-btn rounded-full px-3 py-1.5 text-sm bg-blue-600 text-white"
                                data-type="all">Todos
                        </button>
                        <button class="type-btn rounded-full px-3 py-1.5 text-sm bg-slate-100"
                                data-type="payment_unique">Único
                        </button>
                        <button class="type-btn rounded-full px-3 py-1.5 text-sm bg-slate-100" data-type="monthly">
                            Mensal
                        </button>
                        <button class="type-btn rounded-full px-3 py-1.5 text-sm bg-slate-100" data-type="yearly">
                            Anual
                        </button>
                    </div>
                </div>

                <!-- Catálogo -->
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-lg font-semibold">Serviços</h2>
                    </div>

                    <div id="cards-skeleton" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        <!-- repete 6 skeleton cards só pra ficar cheio -->
                        <div class="sk-card">
                            <div class="flex items-start gap-3">
                                <div class="flex-1">
                                    <div class="skeleton h-4 w-40"></div>     <!-- título -->
                                    <div class="mt-2 space-y-2">
                                        <div class="skeleton h-3 w-full"></div>
                                        <div class="skeleton h-3 w-5/6"></div>
                                        <div class="skeleton h-3 w-2/3"></div>
                                    </div>
                                </div>
                                <div class="text-right shrink-0 w-20">
                                    <div class="skeleton h-4 w-full rounded"></div>
                                    <div class="mt-2 skeleton h-3 w-12 rounded"></div>
                                </div>
                            </div>
                        </div>

                        <div class="sk-card">
                            <div class="flex items-start gap-3">
                                <div class="flex-1">
                                    <div class="skeleton h-4 w-40"></div>
                                    <div class="mt-2 space-y-2">
                                        <div class="skeleton h-3 w-full"></div>
                                        <div class="skeleton h-3 w-5/6"></div>
                                        <div class="skeleton h-3 w-2/3"></div>
                                    </div>
                                </div>
                                <div class="text-right shrink-0 w-20">
                                    <div class="skeleton h-4 w-full rounded"></div>
                                    <div class="mt-2 skeleton h-3 w-12 rounded"></div>
                                </div>
                            </div>
                        </div>

                        <div class="sk-card">
                            <div class="flex items-start gap-3">
                                <div class="flex-1">
                                    <div class="skeleton h-4 w-40"></div>
                                    <div class="mt-2 space-y-2">
                                        <div class="skeleton h-3 w-full"></div>
                                        <div class="skeleton h-3 w-5/6"></div>
                                        <div class="skeleton h-3 w-2/3"></div>
                                    </div>
                                </div>
                                <div class="text-right shrink-0 w-20">
                                    <div class="skeleton h-4 w-full rounded"></div>
                                    <div class="mt-2 skeleton h-3 w-12 rounded"></div>
                                </div>
                            </div>
                        </div>

                        <div class="sk-card">
                            <div class="flex items-start gap-3">
                                <div class="flex-1">
                                    <div class="skeleton h-4 w-40"></div>
                                    <div class="mt-2 space-y-2">
                                        <div class="skeleton h-3 w-full"></div>
                                        <div class="skeleton h-3 w-5/6"></div>
                                        <div class="skeleton h-3 w-2/3"></div>
                                    </div>
                                </div>
                                <div class="text-right shrink-0 w-20">
                                    <div class="skeleton h-4 w-full rounded"></div>
                                    <div class="mt-2 skeleton h-3 w-12 rounded"></div>
                                </div>
                            </div>
                        </div>

                        <div class="sk-card">
                            <div class="flex items-start gap-3">
                                <div class="flex-1">
                                    <div class="skeleton h-4 w-40"></div>
                                    <div class="mt-2 space-y-2">
                                        <div class="skeleton h-3 w-full"></div>
                                        <div class="skeleton h-3 w-5/6"></div>
                                        <div class="skeleton h-3 w-2/3"></div>
                                    </div>
                                </div>
                                <div class="text-right shrink-0 w-20">
                                    <div class="skeleton h-4 w-full rounded"></div>
                                    <div class="mt-2 skeleton h-3 w-12 rounded"></div>
                                </div>
                            </div>
                        </div>

                        <div class="sk-card">
                            <div class="flex items-start gap-3">
                                <div class="flex-1">
                                    <div class="skeleton h-4 w-40"></div>
                                    <div class="mt-2 space-y-2">
                                        <div class="skeleton h-3 w-full"></div>
                                        <div class="skeleton h-3 w-5/6"></div>
                                        <div class="skeleton h-3 w-2/3"></div>
                                    </div>
                                </div>
                                <div class="text-right shrink-0 w-20">
                                    <div class="skeleton h-4 w-full rounded"></div>
                                    <div class="mt-2 skeleton h-3 w-12 rounded"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="services-grid" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        <button
                                type="button"
                                id="card-new-service"
                                class="flex flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50/70 p-5 text-slate-600 hover:border-blue-500 hover:bg-blue-50 hover:text-blue-700 transition"
                        >
                            <span
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-current">
                                <i class="fa-solid fa-plus text-lg"></i>
                            </span>
                            <div class="text-center">
                                <p class="text-sm font-semibold">Novo serviço</p>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Cadastrar serviço rápido para usar no orçamento
                                </p>
                            </div>
                        </button>

                        {{-- Cards de serviço via JS --}}
                        <div id="cards-all" class="grid-contents"></div>
                    </div>
                </div>
            </section>

            <!-- Sidebar (Resumo) -->
            <aside class="min-h-0">
                <div class="sticky top-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold">Resumo da Proposta</h3>
                        <span id="status-pill"
                              class="inline-flex items-center gap-1 rounded-full bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-800 border border-slate-200">Rascunho</span>
                    </div>

                    <div class="mt-3 space-y-1 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600">Cliente</span>
                            <span id="client-mini" class="font-medium text-slate-900">—</span>
                        </div>
                    </div>

                    <!-- Lista -->
                    <div class="mt-4">
                        <p class="text-sm font-semibold">Serviços adicionais</p>
                        <ul id="list-all" class="mt-2 divide-y divide-slate-100 text-sm"></ul>
                    </div>

                    <!-- Desconto global -->
                    <div class="mt-5">
                        <label class="text-sm text-slate-600">Desconto global (%)</label>
                        <div class="mt-1 flex items-center gap-2">
                            <input id="discount" type="number" min="0" max="100" value="0"
                                   class="w-24 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200"/>
                            <div class="text-xs text-slate-600">Aplicar em:</div>
                            <div class="inline-flex rounded-xl border border-slate-200 p-1 bg-slate-50">
                                <button type="button" data-scope="all" id="disc-all" aria-pressed="true"
                                        class="disc-btn rounded-lg px-2.5 py-1 text-xs font-medium bg-white shadow">
                                    Total
                                </button>
                                <button type="button" data-scope="one" id="disc-one" aria-pressed="false"
                                        class="disc-btn rounded-lg px-2.5 py-1 text-xs font-medium text-slate-700 hover:text-slate-900">
                                    Cobranças únicas
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Totais -->
                    <div class="mt-5 text-sm">
                        <details id="subtotal-details" class="mt-3 rounded-xl border border-slate-200 bg-white">
                            <summary
                                    class="cursor-pointer list-none px-3 py-2 rounded-xl hover:bg-slate-50 flex items-center justify-between">
                                <span class="font-medium">Detalhes subtotal</span>
                                <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2">
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>
                            </summary>
                            <div class="px-3 py-2 space-y-2">
                                <div class="flex items-center justify-between"><span class="text-slate-600">Subtotal único</span><span
                                            id="sub-one">R$ 0,00</span></div>
                                <div class="flex items-center justify-between"><span class="text-slate-600">Subtotal recorrente/mês</span><span
                                            id="sub-m">R$ 0,00</span></div>
                                <div class="flex items-center justify-between"><span class="text-slate-600">Subtotal recorrente/ano</span><span
                                            id="sub-y">R$ 0,00</span></div>
                                <hr>
                                <div class="flex items-center justify-between"><span class="text-slate-600">Desconto aplicado</span><span
                                            id="disc-val">R$ 0,00</span></div>
                                <hr>
                                <div class="flex items-center justify-between">
                                    <span>Total único (após desc.)</span><span id="tot-one">R$ 0,00</span></div>
                                <div class="flex items-center justify-between">
                                    <span>Total mensal (após desc.*)</span><span id="tot-m">R$ 0,00</span></div>
                                <div class="flex items-center justify-between">
                                    <span>Total anual (após desc.*)</span><span id="tot-y">R$ 0,00</span></div>
                                <p class="mt-1 text-[11px] text-slate-500">*Desconto mensal/anual só se escopo =
                                    “Total”.</p>
                            </div>
                        </details>

                        <div class="rounded-xl mt-5 mb-2">
                            <div class="flex items-end justify-between">
                                <div>
                                    <div id="total-label" class="text-sm text-slate-600">Total do orçamento</div>
                                    <div id="total-main" class="text-2xl font-bold">R$ 0,00</div>
                                </div>
                            </div>
                            <div id="billing-note" class="mt-1 text-xs text-slate-500">
                            </div>
                        </div>
                    </div>

                    <!-- Ações -->
                    <div class="mt-6 space-y-2">
                        <button id="btn-conditions" type="button"
                                class="w-full inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow hover:bg-slate-50">
                            Condições de pagamento
                        </button>

                        <button id="btn-generate" type="button" disabled
                                class="w-full inline-flex items-center justify-center rounded-xl bg-blue-700 px-5 py-2.5 font-semibold text-white shadow hover:bg-blue-800 disabled:opacity-50 disabled:cursor-not-allowed">
                            Gerar orçamento
                        </button>

                        <p id="hint" class="text-xs text-slate-500 text-center">
                        </p>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <!-- Modal Condições -->
    <div id="modal-cond" class="hidden fixed inset-0 z-50 bg-black/40 backdrop-blur-sm">
        <div
                class="absolute left-1/2 top-1/2 w-[min(980px,96vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">Condições de pagamento</h2>
                <button data-close-cond class="rounded-lg p-2 hover:bg-slate-100">
                    <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="grid md:grid-cols-4 gap-4 mt-4">
                <div>
                    <label class="text-sm font-medium">Pagamento em</label>
                    <input id="c-paydate" type="date"
                           class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none"/>
                </div>

                <div id="wrap-deadline">
                    <label class="text-sm font-medium">Prazo de entrega</label>
                    <input id="c-deadline" type="number" min="0" value="0"
                           class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none"/>
                </div>

                <div id="wrap-signal">
                    <label class="text-sm font-medium">Sinal (%)</label>
                    <input id="c-signal" type="number" min="0" max="100" value="0" step="0.01"
                           class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none"/>
                </div>

                <div id="wrap-install">
                    <label class="text-sm font-medium">Parcelamento (únicos)</label>
                    <select id="c-install"
                            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none">
                        @for ($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}">{{ $i }}x</option>
                        @endfor
                    </select>
                </div>
            </div>

            <div class="mt-3">
                <label class="text-sm font-medium">Cobrar recorrente nesse mês?</label>
                <div class="mt-1 flex items-center gap-2">
                    <div class="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1">
                        <button id="btn-rec-no" type="button"
                                class="rec-onoff rounded-lg px-2.5 py-1 text-xs font-medium bg-white shadow"
                                data-val="no" aria-pressed="true">Não
                        </button>
                        <button id="btn-rec-yes" type="button"
                                class="rec-onoff rounded-lg px-2.5 py-1 text-xs font-medium" data-val="yes"
                                aria-pressed="false">Sim
                        </button>
                    </div>

                    <div id="rec-mode" class="hidden inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1">
                        <span class="mr-2 self-center text-xs text-slate-600">Somar em:</span>
                        <button type="button" class="rec-mode-btn rounded-lg px-2.5 py-1 text-xs font-medium"
                                data-mode="signal" aria-pressed="false">Sinal
                        </button>
                        <button type="button" class="rec-mode-btn rounded-lg px-2.5 py-1 text-xs font-medium"
                                data-mode="installment" aria-pressed="true">Data de entrega
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-6 grid lg:grid-cols-3 gap-4">

                <!-- Serviços do orçamento -->
                <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-semibold text-slate-900">Serviços</h4>
                        <span class="text-xs text-slate-500" id="svc-count"></span>
                    </div>

                    <ul id="v-services" class="max-h-48 overflow-auto text-sm space-y-3 pr-1"></ul>
                </div>

                <!-- Condições -->
                <div class="flex flex-col justify-between">
                    <dl class="text-sm divide-y divide-slate-100 border border-slate-200 rounded-lg overflow-hidden">
                        <div class="flex items-start justify-between p-3">
                            <dt class="text-slate-600">Pagamento em</dt>
                            <dd class="text-slate-900 font-medium" id="v-paydate">-</dd>
                        </div>

                        <div id="row-deadline" class="flex items-start justify-between p-3">
                            <dt class="text-slate-600">Prazo de entrega</dt>
                            <dd class="text-slate-900 font-medium" id="v-deadline">0 dia(s)</dd>
                        </div>

                        <div id="row-signal" class="flex items-start justify-between p-3">
                            <dt class="text-slate-600">Sinal</dt>
                            <dd class="text-slate-900 font-medium" id="v-signal">0%</dd>
                        </div>

                        <div id="row-parc" class="flex items-start justify-between p-3">
                            <dt class="text-slate-600">Parcelamento</dt>
                            <dd class="text-slate-900 font-medium" id="v-install">1x</dd>
                        </div>
                    </dl>

                    <div
                            class="mt-3 rounded-lg bg-slate-50 border border-slate-200 p-3 text-[12px] leading-relaxed text-slate-600">
                        <div class="flex items-start justify-between">
                            <span class="text-slate-600">Recorrência ativa</span>
                            <span id="v-recurring-info" class="text-right font-medium text-slate-900">R$ 0,00</span>
                        </div>
                        <div class="text-[11px] text-slate-500 mt-1">
                            Valores cobrados mensal/anual após a entrega.
                        </div>
                    </div>
                </div>

                <!-- Totais -->
                <div>
                    <div class="rounded-lg border border-blue-200 bg-blue-50/50 p-4 mb-4">
                        <div class="text-[12px] font-medium text-slate-600" id="tot-label-head">
                            Total do contrato
                        </div>
                        <div class="text-2xl font-bold text-slate-900 leading-tight" id="tot-main-head">
                            R$ 0,00
                        </div>
                        <div class="text-[12px] text-slate-500 leading-snug mt-1" id="tot-desc-head">
                            sendo R$ 500,00 /mês + R$ 75,00 /ano
                        </div>
                    </div>

                    <dl class="text-sm divide-y divide-slate-100 border border-slate-200 rounded-lg overflow-hidden">
                        <div id="row-subone" class="flex items-start justify-between p-3">
                            <dt class="text-slate-600">Subtotal (únicos)</dt>
                            <dd id="v-subtotal" class="text-slate-900 font-medium">R$ 0,00</dd>
                        </div>

                        <div id="row-entry" class="flex items-start justify-between p-3">
                            <dt class="text-slate-600">Entrada / Sinal</dt>
                            <dd id="v-signal-price" class="text-slate-900 font-medium">R$ 0,00</dd>
                        </div>

                        <div id="row-parc-info" class="flex items-start justify-between p-3">
                            <dt class="text-slate-600">Parcelamento (únicos)</dt>
                            <dd id="v-parc-info" class="text-slate-900 font-medium">—</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Cronograma -->
            <div class="mt-5">
                <div class="max-h-64 overflow-auto rounded-lg border border-slate-200">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-slate-600 text-sm border-b border-slate-200">
                        <tr class="text-center">
                            <th class="px-4 py-2 font-medium">Data</th>
                            <th class="px-4 py-2 font-medium text-center">Valor</th>
                        </tr>
                        </thead>
                        <tbody id="sched-body" class="divide-y divide-slate-100 text-slate-900"></tbody>
                    </table>
                </div>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button data-close-cond
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Fechar
                </button>
                <button id="cond-apply"
                        class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                    Aplicar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Pós-Geração -->
    <div id="modal-after" class="hidden fixed inset-0 z-50 bg-black/40 backdrop-blur-sm">
        <div
                class="absolute left-1/2 top-1/2 w-[min(480px,96vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Orçamento gerado</h2>
                    <p class="text-sm text-slate-600 mt-1">
                        O orçamento <span id="after-code" class="font-medium text-slate-900">#--</span> foi criado
                        para <span id="after-client" class="font-medium text-slate-900">—</span>.
                    </p>
                    <p class="text-xs text-slate-500 mt-1">
                        Escolha o próximo passo:
                    </p>
                </div>

                <button id="after-close-x" class="rounded-lg p-2 hover:bg-slate-100">
                    <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- ALERTA DE STATUS (sucesso/erro de envio de e-mail) -->
            <div id="after-alert" class="hidden mt-4 rounded-lg border p-3 text-sm flex items-start gap-2">
                <div id="after-alert-icon" class="shrink-0">
                    <!-- ícone entra via JS -->
                </div>
                <div class="flex-1 leading-relaxed">
                    <p id="after-alert-text" class="font-medium"></p>
                </div>
            </div>

            <div class="mt-5 space-y-2 text-sm">
                <!-- ENVIAR POR E-MAIL -->
                <button id="after-send"
                        class="w-full rounded-xl bg-blue-700 px-4 py-3 font-semibold text-white shadow hover:bg-blue-800 flex items-center justify-between disabled:opacity-70 disabled:cursor-not-allowed">
    <span class="flex items-center gap-2">
        <span>Enviar orçamento por e-mail</span>

        <!-- SPINNER -->
        <span id="after-send-spinner"
              class="hidden h-4 w-4 rounded-full border-2 border-white/40 border-t-white animate-spin"></span>
    </span>

                    <span class="text-[11px] font-normal opacity-80" id="after-send-hint">
        (<span id="after-email">—</span>)
    </span>
                </button>


                <!-- VER PDF -->
                <button id="after-view"
                        class="w-full inline-flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 font-medium text-slate-700 shadow hover:bg-slate-50">
                    <span>Visualizar orçamento (PDF)</span>
                    <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2">
                        <path d="M14 3h7v7M10 14 21 3M5 21h14a2 2 0 0 0 2-2v-7M5 21a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7"/>
                    </svg>
                </button>

                <!-- LISTA -->
                <button id="after-go-list"
                        class="w-full inline-flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 font-medium text-slate-700 shadow hover:bg-slate-50">
                    <span>Voltar para listagem de orçamentos</span>
                    <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2">
                        <path d="M15 18 9 12l6-6"/>
                    </svg>
                </button>

                <!-- NOVO ORÇAMENTO -->
                <button id="after-new"
                        class="w-full inline-flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 font-medium text-slate-700 shadow hover:bg-slate-50">
                    <span>Gerar novo orçamento</span>
                    <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                </button>
            </div>

            <p class="text-[11px] text-slate-400 mt-4 leading-relaxed" id="after-note">
                Obs.: Você pode reenviar o e-mail ou baixar o PDF depois pela listagem.
            </p>
        </div>
    </div>

    @include('app.old.sales.service.service_modal', ['input' => $input ?? []])

    @push('scripts')
        <script>
            // ========= Helpers =========
            const $ = s => document.querySelector(s);
            const $$ = s => document.querySelectorAll(s);
            const BRL = v => (Number(v) || 0).toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'});

            const clampPct = n => Math.min(100, Math.max(0, Number(n || 0)));
            const todayISO = () => new Date().toISOString().slice(0, 10);

            const addDays = (d, n) => {
                const x = new Date(d);
                x.setDate(x.getDate() + n);
                return x;
            };
            const addMonths = (d, n) => {
                const x = new Date(d);
                x.setMonth(x.getMonth() + n);
                return x;
            };
            const addYears = (d, n) => {
                const x = new Date(d);
                x.setFullYear(x.getFullYear() + n);
                return x;
            };

            const round2 = x => Math.round((Number(x) || 0) * 100) / 100;

            function fmtDateBR(iso) {
                if (!iso) return '-';
                const [y, m, d] = iso.split('-');
                return `${d}/${m}/${y}`;
            }

            function dateLocalFromISO(iso) {
                if (!iso) return null;
                const [y, m, d] = iso.split('-').map(Number);
                return new Date(y, m - 1, d); // local
            }

            function localISOFromDate(d) {
                const y = d.getFullYear();
                const m = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${y}-${m}-${day}`;
            }

            function showSkeleton() {
                // mostra skeleton, esconde cards reais
                $('#cards-skeleton').classList.remove('hidden');
                $('#cards-all').classList.add('hidden');
            }

            function hideSkeleton() {
                // esconde skeleton, mostra cards reais
                $('#cards-skeleton').classList.add('hidden');
                $('#cards-all').classList.remove('hidden');
            }

            function getQueryParam(key) {
                return new URLSearchParams(window.location.search).get(key);
            }

            // ========= LocalStorage Keys =========
            const LS = {
                one: 'cliqis_prop_one',
                m: 'cliqis_prop_rec_month',
                y: 'cliqis_prop_rec_year',
                disc: 'cliqis_prop_disc',
                discScope: 'cliqis_prop_disc_scope',
                recUpfront: 'cliqis_rec_upfront',
                recMode: 'cliqis_rec_mode',
                draft: 'cliqis_prop_draft'
            };

            let __draftDisabled = false;

            // ========= Estado =========
            let SERVICES = [], CUSTOMERS = [];
            let q = '';

            let selectedOne = new Set(JSON.parse(localStorage.getItem(LS.one) || '[]'));
            let selectedM = new Set(JSON.parse(localStorage.getItem(LS.m) || '[]'));
            let selectedY = new Set(JSON.parse(localStorage.getItem(LS.y) || '[]'));

            let discount = Number(localStorage.getItem(LS.disc) || 0);
            let discScope = localStorage.getItem(LS.discScope) || 'all'; // all | one
            let recUpfront = localStorage.getItem(LS.recUpfront) === 'true';
            if (localStorage.getItem(LS.recUpfront) === null) {
                recUpfront = false;
                localStorage.setItem(LS.recUpfront, 'false');
            }
            let recMode = localStorage.getItem(LS.recMode) || 'installment';
            if (localStorage.getItem(LS.recMode) === null) {
                recMode = 'installment';
                localStorage.setItem(LS.recMode, 'installment');
            }

            let cond = {
                paydate: null,
                deadline: 0,
                signal: 0,
                install: 1,
                subtotal: 0,
                signalPrice: 0,
                avista: 0,
                schedule: []
            };
            let condApplied = false;
            let __lastBudget = null; // resposta do back do orçamento recém criado

            // ========= LS helpers =========
            function clearProposalStorage() {
                [LS.one, LS.m, LS.y, LS.disc, LS.discScope, LS.recUpfront, LS.recMode, LS.draft]
                    .forEach(k => localStorage.removeItem(k));
            }

            // usada quando quero limpar e NÃO salvar mais nada desse orçamento
            function clearProposalAndDisableDraft() {
                clearProposalStorage();
                __draftDisabled = true;
            }


            function saveDraft() {
                // se desabilitado, não grava mais nada no localStorage
                if (__draftDisabled) return;

                try {
                    const draft = {
                        client_id: $('#client_id')?.value || '',
                        client_text: $('#client')?.value || '',
                        discount, discScope, recUpfront, recMode,
                        cond, condApplied,
                        selectedOne: [...selectedOne],
                        selectedM: [...selectedM],
                        selectedY: [...selectedY]
                    };
                    localStorage.setItem(LS.draft, JSON.stringify(draft));
                    // retrocompat
                    localStorage.setItem(LS.one, JSON.stringify(draft.selectedOne));
                    localStorage.setItem(LS.m, JSON.stringify(draft.selectedM));
                    localStorage.setItem(LS.y, JSON.stringify(draft.selectedY));
                    localStorage.setItem(LS.disc, String(discount));
                    localStorage.setItem(LS.discScope, discScope);
                    localStorage.setItem(LS.recUpfront, String(recUpfront));
                    localStorage.setItem(LS.recMode, recMode);
                } catch (_) {
                }
            }


            async function loadDuplicateIfAny() {
                const dupId = getQueryParam('duplicate');
                if (!dupId) return false; // nada pra duplicar

                // limpa qualquer rascunho salvo pra não misturar
                clearProposalStorage();

                // busca dados do orçamento original
                const resp = await fetch(`/budgets/${dupId}/json`, {
                    headers: {'Accept': 'application/json'}
                });

                if (!resp.ok) {
                    console.error('Falha ao carregar duplicação', await resp.text());
                    return false;
                }

                const payload = await resp.json();
                if (!payload || !payload.ok) return false;

                const data = payload.data || {};

                // ---------- CLIENTE ----------
                if (data.customer) {
                    $('#client_id').value = data.customer.id || '';
                    $('#client').value = data.customer.name || '';
                    $('#client-mini').textContent = data.customer.name || '—';
                }

                // ---------- ITENS ----------
                selectedOne = new Set(
                    (data.items_unique || []).map(it => String(it.service_id))
                );
                selectedM = new Set(
                    (data.items_monthly || []).map(it => String(it.service_id))
                );
                selectedY = new Set(
                    (data.items_yearly || []).map(it => String(it.service_id))
                );

                // ---------- DESCONTO / ESCOPO ----------
                discount = Number(data.discount_percent ?? 0);
                discScope = data.discount_scope || 'all';
                $('#discount').value = discount;

                // pinta os botões de escopo de desconto
                $$('.disc-btn').forEach(x => {
                    const on = x.dataset.scope === discScope;
                    x.classList.toggle('bg-white', on);
                    x.classList.toggle('shadow', on);
                    x.setAttribute('aria-pressed', on ? 'true' : 'false');
                });

                // ---------- RECORRÊNCIA (cobrar recorrente antes ou depois) ----------
                recUpfront = !!data.rec_upfront;
                recMode = data.rec_mode || 'installment';
                localStorage.setItem(LS.recUpfront, String(recUpfront));
                localStorage.setItem(LS.recMode, recMode);

                // ---------- CONDIÇÕES / PARCELAMENTO ----------
                cond = {
                    paydate: data.cond?.paydate || todayISO(),
                    deadline: Number(data.cond?.deadline ?? 0),
                    signal: Number(data.cond?.signal ?? 0),
                    install: Number(data.cond?.install ?? 1),

                    subtotal: 0,
                    signalPrice: 0,
                    avista: 0,
                    schedule: []
                };

                // nesse cenário, já existe condição válida, então vamos assumir que pode gerar
                condApplied = true;

                // precisamos refletir esses valores na UI e recalcular cronograma/sumários
                // 1) colocar os valores nos inputs do modal de condições
                // 2) recalcular tudo (renderCond() já faz o resto)
                paintRecControls();   // atualiza botões "recorrência ativa? sim/não"
                hydrateCondUI();      // joga cond -> inputs e chama renderCond()

                // salva esse estado como "draft" novo pra se o cara recarregar a página continuar igual
                saveDraft();

                // terminou com sucesso
                return true;
            }

            function loadDraft() {
                const raw = localStorage.getItem(LS.draft);
                if (!raw) return false;
                try {
                    const d = JSON.parse(raw);

                    if (d.client_id) $('#client_id').value = d.client_id;
                    if (d.client_text) {
                        $('#client').value = d.client_text;
                        $('#client-mini').textContent = d.client_text;
                    }

                    if (Array.isArray(d.selectedOne)) selectedOne = new Set(d.selectedOne.map(String));
                    if (Array.isArray(d.selectedM)) selectedM = new Set(d.selectedM.map(String));
                    if (Array.isArray(d.selectedY)) selectedY = new Set(d.selectedY.map(String));

                    if (typeof d.discount === 'number') discount = d.discount;
                    if (d.discScope) discScope = d.discScope;
                    if (typeof d.recUpfront === 'boolean') recUpfront = d.recUpfront;
                    if (d.recMode) recMode = d.recMode;
                    if (d.cond && typeof d.cond === 'object') cond = d.cond;
                    if (typeof d.condApplied === 'boolean') condApplied = d.condApplied;
                    return true;
                } catch (_) {
                    return false;
                }
            }

            function persistSelected() {
                localStorage.setItem(LS.one, JSON.stringify([...selectedOne]));
                localStorage.setItem(LS.m, JSON.stringify([...selectedM]));
                localStorage.setItem(LS.y, JSON.stringify([...selectedY]));
                saveDraft();
            }

            // ========= Fetch =========
            async function loadServices() {
                showSkeleton(); // começa mostrando skeleton

                const r = await fetch('/sales/service-api', {headers: {'Accept': 'application/json'}});
                const j = await r.json();
                const arr = Array.isArray(j.data) ? j.data : Object.values(j.data || {});

                SERVICES = arr.map(s => ({...s, id: String(s.id), price: Number(s.price)}));

                // depois que carregou a lista em memória
                hideSkeleton();
            }

            async function loadCustomers() {
                const r = await fetch('/entities/customer-api', {headers: {'Accept': 'application/json'}});
                const j = await r.json();
                const arr = Array.isArray(j.data) ? j.data : Object.values(j.data || {});
                CUSTOMERS = arr;
            }

            // ========= Cliente dropdown =========
            const clientInput = $('#client'),
                clientList = $('#client-list'),
                clientWrap = $('#client-wrap');
            let clientOpen = false;

            function openClient() {
                if (!clientOpen) {
                    clientList.classList.remove('hidden');
                    clientOpen = true;
                }
            }

            function closeClient() {
                if (clientOpen) {
                    clientList.classList.add('hidden');
                    clientOpen = false;
                }
            }

            function renderClientList(term = '') {
                const t = term.trim().toLowerCase();
                const items = CUSTOMERS
                    .filter(c =>
                        (c.name || '').toLowerCase().includes(t) ||
                        (c.email || '').toLowerCase().includes(t)
                    )
                    .slice(0, 20);

                clientList.innerHTML = items.map(c => `
                    <li data-id="${c.id}" class="px-3 py-2 cursor-pointer hover:bg-blue-50 hover:text-blue-700">
                        <span class="font-medium">${c.name}</span>
                        <span class="text-slate-500"> — ${c.email || ''}</span>
                    </li>`
                ).join('');

                openClient();
            }

            clientInput.addEventListener('input', e => {
                renderClientList(e.target.value);
            });
            clientInput.addEventListener('focus', () => renderClientList(clientInput.value));
            clientList.addEventListener('click', e => {
                const li = e.target.closest('[data-id]');
                if (!li) return;
                const c = CUSTOMERS.find(x => String(x.id) === String(li.dataset.id));
                if (!c) return;
                $('#client_id').value = c.id;
                clientInput.value = c.name;
                $('#client-mini').textContent = c.name;
                closeClient();
                updateButtons();
                saveDraft();
            });
            document.addEventListener('click', e => {
                if (!clientWrap.contains(e.target)) closeClient();
            });

            // ========= Catálogo =========
            const wrapAll = $('#cards-all');
            let typeFilter = 'all';

            const unitLabel = t => t === 'monthly' ? ' /mês' : t === 'yearly' ? ' /ano' : ' /único';
            const byId = id => SERVICES.find(s => String(s.id) === String(id));

            function isSelected(s) {
                return s.type === 'payment_unique'
                    ? selectedOne.has(s.id)
                    : (s.type === 'monthly'
                        ? selectedM.has(s.id)
                        : selectedY.has(s.id));
            }

            function toggleSelected(s, checked) {
                const set = s.type === 'payment_unique'
                    ? selectedOne
                    : (s.type === 'monthly' ? selectedM : selectedY);

                if (checked) set.add(s.id); else set.delete(s.id);

                persistSelected();
                condApplied = false;

                renderCards();
                renderSummary();
            }

            function buildTooltipText(item) {
                const raw = `${item.name || ''}\n\n${item.description || ''}`;

                const tmp = document.createElement('div');
                tmp.innerHTML = raw;

                const txt = tmp.textContent || tmp.innerText || '';
                return txt.replace(/\r\n/g, '\n').replace(/\n{3,}/g, '\n\n').trim();
            }

            function escAttr(str) {
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/"/g, '&quot;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
            }

            function cardHTML(item, checked, unit) {
                const desc = item.description || '';
                const tooltip = escAttr(buildTooltipText(item));

                return `
        <label
            class="group relative cursor-pointer rounded-2xl border ${checked ? 'border-blue-600 ring-2 ring-blue-100' : 'border-slate-200'} bg-white p-5 shadow-sm transition hover:border-blue-500"
            data-id="${item.id}"
            title="${tooltip}"
        >
            <input type="checkbox" class="peer sr-only" ${checked ? 'checked' : ''}>

            <!-- Linha título + preço -->
            <div class="flex items-center gap-3">
                <div class="flex-1">
                    <p class="font-semibold text-slate-900 title-clamp">
                        ${item.name}
                    </p>
                </div>
                <div class="text-right shrink-0">
                    <span class="text-sm font-medium text-slate-900">
                        ${BRL(item.price)}
                    </span>
                    <span class="text-xs text-slate-500">
                        ${unit}
                    </span>
                </div>
            </div>

            <!-- Descrição em 2 linhas -->
            <p class="mt-1 text-sm text-slate-600 text-justify desc-clamp">
                ${desc}
            </p>

            <div class="pointer-events-none absolute right-4 top-4 ${checked ? '' : 'hidden'} rounded-full bg-blue-600 p-1 text-white peer-checked:block">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    <path d="M20 6 9 17l-5-5" />
                </svg>
            </div>
        </label>
    `;
            }

            function renderCards() {
                const wrapAll = $('#cards-all');
                wrapAll.innerHTML = '';

                const filterTxt = s => (s.name + ' ' + (s.description || '')).toLowerCase().includes(q);

                SERVICES
                    .filter(s => (typeFilter === 'all' ? true : s.type === typeFilter))
                    .filter(filterTxt)
                    .forEach(s => {
                        const checked = isSelected(s);
                        const c = document.createElement('div');
                        c.innerHTML = cardHTML(s, checked, unitLabel(s.type));
                        const el = c.firstElementChild;
                        el.querySelector('input').addEventListener('change', e => toggleSelected(s, e.target.checked));
                        wrapAll.appendChild(el);
                    });
            }

            // ========= Totais / Resumo =========
            function getPricesSelected() {
                const one = [...selectedOne].map(id => byId(id)?.price || 0);
                const m = [...selectedM].map(id => byId(id)?.price || 0);
                const y = [...selectedY].map(id => byId(id)?.price || 0);
                return {
                    sumOne: one.reduce((s, v) => s + v, 0),
                    sumM: m.reduce((s, v) => s + v, 0),
                    sumY: y.reduce((s, v) => s + v, 0)
                };
            }

            function computeTotals() {
                const {sumOne, sumM, sumY} = getPricesSelected();
                const d = clampPct(discount) / 100;
                const discOne = (discScope === 'all' || discScope === 'one') ? sumOne * d : 0;
                const discM = (discScope === 'all') ? sumM * d : 0;
                const discY = (discScope === 'all') ? sumY * d : 0;
                const totOne = Math.max(sumOne - discOne, 0);
                const totM = Math.max(sumM - discM, 0);
                const totY = Math.max(sumY - discY, 0);
                return {
                    sumOne, sumM, sumY,
                    discTotal: discOne + discM + discY,
                    totOne, totM, totY
                };
            }

            function calcHeadlineTexts() {
                const {totOne, totM, totY} = computeTotals();

                const hasUnique = totOne > 0;
                const hasMonthly = totM > 0;
                const hasYearly = totY > 0;
                const hasRecurr = hasMonthly || hasYearly;

                const onlyMonthly = hasMonthly && !hasYearly && !hasUnique;
                const onlyYearly = hasYearly && !hasMonthly && !hasUnique;

                if (onlyMonthly) {
                    return {
                        labelText: 'Valor recorrente',
                        mainText: `${BRL(totM)} /mês`,
                        descText: 'Cobrança mensal estimada.'
                    };
                }

                if (onlyYearly) {
                    return {
                        labelText: 'Valor recorrente',
                        mainText: `${BRL(totY)} /ano`,
                        descText: 'Cobrança anual estimada.'
                    };
                }

                const grandTotal = totOne + totM + totY;

                let labelText;
                if (hasUnique && hasRecurr) {
                    labelText = 'Total do contrato';
                } else if (hasRecurr && !hasUnique) {
                    labelText = 'Total estimado';
                } else {
                    labelText = 'Total do orçamento';
                }

                const recurBits = [];
                if (hasMonthly) recurBits.push(`${BRL(totM)} /mês`);
                if (hasYearly) recurBits.push(`${BRL(totY)} /ano`);

                const noteParts = [];
                if (recurBits.length) {
                    noteParts.push(`sendo ${recurBits.join(' + ')}`);
                }

                if (hasUnique && hasRecurr) {
                    noteParts.push(`Inclui ${BRL(totOne)} em cobranças únicas.`);
                } else if (hasUnique && !hasRecurr) {
                    noteParts.push('Pagamento único estimado.');
                }

                return {
                    labelText,
                    mainText: BRL(grandTotal),
                    descText: noteParts.join(' ')
                };
            }

            function condBaseAmount() {
                const {totOne} = computeTotals();
                return totOne;
            }

            function buildSchedule() {
                const {totM, totY} = computeTotals();

                const base = condBaseAmount(); // total único (c/ desconto)
                const extra = recUpfront ? (totM + totY) : 0;
                const pct = clampPct(cond.signal) / 100;

                const entryBase = round2(base * pct);
                const entryVal = round2(entryBase + (recUpfront && recMode === 'signal' ? extra : 0));

                const remainingBase = Math.max(base - entryBase, 0);
                const n = Math.max(1, Number(cond.install || 1));

                const baseDate = cond.paydate ? dateLocalFromISO(cond.paydate) : null;
                if (!baseDate) return [];

                const firstDue = addDays(new Date(baseDate), Number(cond.deadline || 0));

                const rows = [];

                if (entryVal > 0) {
                    rows.push({
                        dateObj: new Date(baseDate),
                        amount: entryVal,
                        label: 'Entrada'
                    });
                }

                if (n === 1) {
                    let amount = round2(remainingBase);
                    if (recUpfront && recMode === 'installment' && (totM + totY) > 0) {
                        amount = round2(amount + (totM + totY));
                    }
                    rows.push({
                        dateObj: new Date(firstDue),
                        amount,
                        label: 'À vista'
                    });
                } else {
                    const each = round2(remainingBase / n);
                    let acc = 0;
                    for (let i = 1; i <= n; i++) {
                        const due = addMonths(new Date(firstDue), i - 1);
                        let amount = (i < n) ? each : round2(remainingBase - acc);
                        acc = round2(acc + amount);

                        if (recUpfront && recMode === 'installment' && i === 1 && (totM + totY) > 0) {
                            amount = round2(amount + (totM + totY));
                        }

                        rows.push({
                            dateObj: new Date(due),
                            amount,
                            label: `Parcela ${i}/${n}`
                        });
                    }
                }

                if (!recUpfront) {
                    if (totM > 0) {
                        rows.push({
                            dateObj: addMonths(new Date(firstDue), 1),
                            amount: round2(totM),
                            label: '1ª recorrência (mensal)'
                        });
                    }
                    if (totY > 0) {
                        rows.push({
                            dateObj: addYears(new Date(firstDue), 1),
                            amount: round2(totY),
                            label: '1ª recorrência (anual)'
                        });
                    }
                }

                rows.sort((a, b) => a.dateObj - b.dateObj);
                return rows;
            }

            function renderSummary() {
                // lista de serviços
                const listAll = $('#list-all');
                listAll.innerHTML = '';
                const rows = [
                    ...[...selectedOne].map(id => ({id, type: 'payment_unique'})),
                    ...[...selectedM].map(id => ({id, type: 'monthly'})),
                    ...[...selectedY].map(id => ({id, type: 'yearly'})),
                ];
                rows.forEach(({id, type}) => {
                    const s = byId(id);
                    if (!s) return;
                    const li = document.createElement('li');
                    li.className = 'py-2 flex items-center justify-between';
                    li.innerHTML = `<span>${s.name}</span><span class="text-slate-700">${BRL(s.price)} ${type === 'payment_unique' ? ' / único' : (type === 'monthly' ? '/ mês' : '/ anual')}</span>`;
                    listAll.appendChild(li);
                });

                // totais
                const {sumOne, sumM, sumY, discTotal, totOne, totM, totY} = computeTotals();
                $('#sub-one').textContent = BRL(sumOne);
                $('#sub-m').textContent = BRL(sumM);
                $('#sub-y').textContent = BRL(sumY);
                $('#disc-val').textContent = BRL(discTotal);
                $('#tot-one').textContent = BRL(totOne);
                $('#tot-m').textContent = BRL(totM);
                $('#tot-y').textContent = BRL(totY);

                const totalMainEl = $('#total-main');
                const totalLabelEl = $('#total-label');
                const noteEl = $('#billing-note');

                const hl = calcHeadlineTexts();
                totalLabelEl.textContent = hl.labelText;
                totalMainEl.textContent = hl.mainText;
                noteEl.textContent = hl.descText || '';

                // cronograma preview na sidebar
                const tbody = $('#sched-body');
                const sched = buildSchedule();
                tbody.innerHTML = sched.map(s => {
                    const isoLocal = localISOFromDate(s.dateObj);
                    const dBR = fmtDateBR(isoLocal);
                    return `
        <tr>
            <td class="py-1">${dBR}</td>
            <td class="py-1 text-right">${BRL(s.amount)}</td>
        </tr>`;
                }).join('') || `
    <tr>
        <td colspan="2" class="py-1 text-slate-500">
            Defina as condições para ver o cronograma.
        </td>
    </tr>`;

                const onlyRec = hasRecurringOnly();
                toggleRecurringOnlyUI(onlyRec);

                updateButtons();
            }

            function render() {
                renderCards();
                renderSummary();
            }

            // ========= Modal Condições =========
            function paintRecControls() {
                const noBtn = $('#btn-rec-no'),
                    yesBtn = $('#btn-rec-yes'),
                    box = $('#rec-mode');

                [noBtn, yesBtn].forEach(b => {
                    const on = (b.dataset.val === (recUpfront ? 'yes' : 'no'));
                    b.classList.toggle('bg-white', on);
                    b.classList.toggle('shadow', on);
                    b.setAttribute('aria-pressed', on ? 'true' : 'false');
                });

                box.classList.toggle('hidden', !recUpfront);

                $$('.rec-mode-btn').forEach(b => {
                    const on = b.dataset.mode === recMode;
                    b.classList.toggle('bg-white', on);
                    b.classList.toggle('shadow', on);
                    b.setAttribute('aria-pressed', on ? 'true' : 'false');
                });
            }

            function openCond() {
                cond.subtotal = condBaseAmount();
                if (!cond.paydate) cond.paydate = todayISO();
                document.getElementById('modal-cond').classList.remove('hidden');
                paintRecControls();
                hydrateCondUI();
                toggleRecurringOnlyUI(hasRecurringOnly());
            }

            function closeCond() {
                $('#modal-cond').classList.add('hidden');
            }

            function hydrateCondUI() {
                $('#c-paydate').value = cond.paydate || todayISO();
                $('#c-deadline').value = cond.deadline || 0;
                $('#c-signal').value = cond.signal || 0;
                $('#c-install').value = cond.install || 1;

                renderCond();
            }

            function renderCond() {
                cond.paydate = $('#c-paydate').value || todayISO();
                cond.deadline = Number($('#c-deadline').value || 0);
                cond.signal = Number($('#c-signal').value || 0);
                cond.install = Number($('#c-install').value || 1);

                const {totM, totY} = computeTotals();
                const base = condBaseAmount();
                const sched = buildSchedule();
                const pct = clampPct(cond.signal) / 100;
                const entryBase = round2(base * pct);
                const extra = recUpfront ? (totM + totY) : 0;
                const entryVal = round2(entryBase + (recUpfront && recMode === 'signal' ? extra : 0));

                cond.signalPrice = entryVal;
                cond.subtotal = base;
                cond.avista = entryVal;
                cond.schedule = sched;

                // bloco meio
                $('#v-paydate').textContent = fmtDateBR(cond.paydate);
                $('#v-deadline').textContent = (cond.deadline || 0) + ' dia(s)';
                $('#v-signal').textContent = (cond.signal || 0) + '%';
                $('#v-install').textContent = (cond.install || 1) + 'x';

                $('#v-subtotal').textContent = BRL(cond.subtotal);
                $('#v-signal-price').textContent = BRL(cond.signalPrice);

                const remaining = Math.max(base - entryBase, 0);
                const parcEach = cond.install > 1 ? round2(remaining / cond.install) : remaining;
                $('#v-parc-info').textContent = cond.install > 1
                    ? `${cond.install}x de ${BRL(parcEach)}`
                    : 'À vista';

                const onlyRec = hasRecurringOnly();
                toggleRecurringOnlyUI(onlyRec);

                // cabeçalho azul
                const hl = calcHeadlineTexts();
                $('#tot-label-head').textContent = hl.labelText;
                $('#tot-main-head').textContent = hl.mainText;
                $('#tot-desc-head').textContent = hl.descText || '';

                const recText = [
                    (totM ? `${BRL(totM)}/mês` : null),
                    (totY ? `${BRL(totY)}/ano` : null),
                ].filter(Boolean).join(' + ') || '—';
                $('#v-recurring-info').textContent = recText;

                // lista de serviços
                const vs = $('#v-services');
                const rowsSvc = [
                    ...[...selectedOne].map(id => ({id, type: 'payment_unique'})),
                    ...[...selectedM].map(id => ({id, type: 'monthly'})),
                    ...[...selectedY].map(id => ({id, type: 'yearly'})),
                ].map(({id, type}) => {
                    const s = SERVICES.find(x => String(x.id) === String(id));
                    if (!s) return null;
                    const badgeTxt = type === 'payment_unique'
                        ? 'único'
                        : (type === 'monthly' ? 'mensal' : 'anual');

                    const badgeClass =
                        type === 'payment_unique'
                            ? 'bg-slate-100 text-slate-700'
                            : (type === 'monthly'
                                ? 'bg-indigo-50 text-indigo-700'
                                : 'bg-violet-50 text-violet-700');

                    return `
            <li class="flex items-start justify-between">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-medium text-slate-900">${s.name}</span>
                        <span class="inline-flex items-center rounded-full ${badgeClass} px-2 py-0.5 text-[11px] font-medium">(${badgeTxt})</span>
                    </div>
                </div>
                <div class="text-right shrink-0">
                    <div class="text-sm font-medium text-slate-900">${BRL(s.price)}</div>
                </div>
            </li>`;
                }).filter(Boolean);

                vs.innerHTML = rowsSvc.join('') || '<li class="text-slate-500 text-sm">Nenhum serviço selecionado.</li>';
                $('#svc-count').textContent = rowsSvc.length ? `${rowsSvc.length} itens` : '';

                // tabela cronograma na modal
                const tbody = $('#sched-body');
                tbody.innerHTML = sched.map(s => {
                    const isoLocal = localISOFromDate(s.dateObj);
                    return `
            <tr>
                <td class="py-1 text-center">${fmtDateBR(isoLocal)}</td>
                <td class="py-1 text-center">${BRL(s.amount)}</td>
            </tr>`;
                }).join('') || `
        <tr>
            <td colspan="2" class="py-1 text-slate-500">
                Defina as condições para ver o cronograma.
            </td>
        </tr>`;
            }

            // recorrência só
            function hasRecurringOnly() {
                const {totOne, totM, totY} = computeTotals();
                return totOne === 0 && (totM > 0 || totY > 0);
            }

            function toggleRecurringOnlyUI(isRecurringOnly) {
                [
                    'wrap-deadline', 'wrap-signal', 'wrap-install',
                    'row-deadline', 'row-signal', 'row-parc', 'row-parc-info',
                    'row-subone', 'row-entry'
                ].forEach(id => document.getElementById(id)?.classList.toggle('hidden', isRecurringOnly));

                // oculta sempre a caixinha "Somar em:" pra não confundir
                document.getElementById('rec-mode')?.classList.toggle('hidden', true);
            }

            // ========= Submit / pós-geração =========
            function updateButtons() {
                const hasClient = !!$('#client_id').value;
                const hasItems = (selectedOne.size + selectedM.size + selectedY.size) > 0;
                const readyCond = condApplied && !!cond.paydate && cond.install >= 1;

                $('#btn-generate').disabled = !(hasClient && hasItems && readyCond);
                $('#hint').textContent = $('#btn-generate').disabled
                    ? 'Defina cliente, itens e aplique as condições no modal.'
                    : 'Pronto para gerar e revisar.';
            }

            async function generateBudget() {
                const hasClient = !!$('#client_id').value;
                const hasItems = (selectedOne.size + selectedM.size + selectedY.size) > 0;
                const readyCond = condApplied && !!cond.paydate && cond.install >= 1;
                if (!(hasClient && hasItems && readyCond)) {
                    updateButtons();
                    return;
                }

                const {totOne, totM, totY} = computeTotals();
                const hasUnique = selectedOne.size > 0 && totOne > 0;

                const iso = d => localISOFromDate(d);

                const form = new FormData();
                form.append('_token', '{{ csrf_token() }}');
                form.append('customer_id', $('#client_id').value);
                form.append('payment_date', cond.paydate || todayISO());
                form.append('deadline', String(cond.deadline || 0));
                form.append('discount_percent', String(discount || 0));
                form.append('discount_scope', discScope);
                form.append('rec_upfront', recUpfront ? '1' : '0');

                if (hasUnique) {
                    form.append('signal', String(cond.signal || 0));
                    form.append('signal_price', (cond.signalPrice || 0).toFixed(2));
                    form.append('subtotal_price', String(cond.subtotal || 0));

                    const instRows = (cond.schedule || [])
                        .filter(s => (s.label === 'À vista' || s.label.startsWith('Parcela')) && Number(s.amount) > 0);

                    instRows.forEach(s => {
                        form.append('installments_price[]', Number(s.amount).toFixed(2));
                        form.append('installments_date[]', iso(s.dateObj));
                    });
                } else {
                    form.append('signal', '0');
                    form.append('signal_price', '0');
                    form.append('subtotal_price', '0');
                }

                const pushItem = (id, p, pm) => {
                    form.append('service_id[]', id);
                    form.append('price[]', String(p));
                    form.append('payment_method[]', String(pm)); // únicos: nº parcelas; recorrentes: 1
                    form.append('discount_price[]', '0');
                };

                [...selectedOne].forEach(id => {
                    const s = byId(id);
                    if (s) pushItem(id, s.price, cond.install || 1);
                });
                [...selectedM].forEach(id => {
                    const s = byId(id);
                    if (s) pushItem(id, s.price, 1);
                });
                [...selectedY].forEach(id => {
                    const s = byId(id);
                    if (s) pushItem(id, s.price, 1);
                });

                try {
                    const r = await fetch('/sales/budgets/store', {
                        method: 'POST',
                        body: form,
                        headers: {'Accept': 'application/json'}
                    });
                    const txt = await r.text();
                    let data = null;
                    try {
                        data = JSON.parse(txt);
                    } catch (_) {
                    }

                    if (!r.ok || !data || !data.ok) {
                        console.error('Falha /sales/budgets/store', txt);
                        alert('Falha ao salvar orçamento.');
                        return;
                    }

                    __lastBudget = data.data || data; // espera {id, code, ...}
                    openAfterModal();
                } catch (err) {
                    console.error(err);
                    alert('Erro de rede ao salvar orçamento.');
                }
            }

            function openAfterModal() {
                const cliName = $('#client').value || '—';
                const cliObj = CUSTOMERS.find(c => String(c.id) === String($('#client_id').value));
                const rawEmail = (cliObj && (cliObj.company_email || cliObj.email)) || '';
                const email = (rawEmail || '').trim();

                $('#after-client').textContent = cliName;

                const btnSend = $('#after-send');
                const emailSpan = $('#after-email');
                const hintSpan = $('#after-send-hint');

                if (email) {
                    emailSpan.textContent = email;
                    hintSpan.classList.remove('hidden');
                    btnSend.disabled = false;
                } else {
                    emailSpan.textContent = 'sem e-mail cadastrado';
                    hintSpan.classList.remove('hidden');
                    btnSend.disabled = true; // bloqueia o botão
                }

                if (__lastBudget && (__lastBudget.code || __lastBudget.id)) {
                    $('#after-code').textContent = '#' + (__lastBudget.code || __lastBudget.id);
                } else {
                    $('#after-code').textContent = '#—';
                }

                // limpa alerta visual
                $('#after-alert').classList.add('hidden');

                // reseta visual do botão (mas respeitando o disabled acima)
                $('#after-send-spinner').classList.add('hidden');
                hintSpan.classList.remove('opacity-30');

                // abre modal
                $('#modal-after').classList.remove('hidden');
            }

            function closeAfterModal() {
                $('#modal-after').classList.add('hidden');
            }

            async function sendLastBudgetEmail() {
                if (!__lastBudget || !__lastBudget.id) {
                    showAfterAlert('Não encontrei o orçamento salvo.', 'error');
                    return;
                }

                const currentEmail = ($('#after-email').textContent || '').trim().toLowerCase();
                if (!currentEmail || currentEmail === 'sem e-mail cadastrado') {
                    showAfterAlert('Cliente sem e-mail cadastrado.', 'error');
                    return;
                }

                const btn = $('#after-send');
                const spinner = $('#after-send-spinner');
                const hint = $('#after-send-hint');

                // estado "enviando"
                btn.disabled = true;
                spinner.classList.remove('hidden');
                hint.classList.add('opacity-30');

                try {
                    const resp = await fetch(`/sales/budget/${__lastBudget.id}/send-email`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({})
                    });

                    const txt = await resp.text();
                    let data = null;
                    try {
                        data = JSON.parse(txt);
                    } catch (_) {
                    }

                    if (!resp.ok) {
                        const msg = (data && (data.message || data.error)) || 'Falha ao enviar e-mail.';
                        showAfterAlert(msg, 'error');
                        return;
                    }

                    // sucesso
                    showAfterAlert('E-mail enviado com sucesso.', 'success');

                } catch (e) {
                    console.error(e);
                    showAfterAlert('Erro de rede ao enviar e-mail.', 'error');

                } finally {
                    // volta estado do botão
                    btn.disabled = false;
                    spinner.classList.add('hidden');
                    hint.classList.remove('opacity-30');
                }
            }

            async function openLastBudgetPDF() {
                if (!__lastBudget || !__lastBudget.id) {
                    alert('Não encontrei o orçamento salvo.');
                    return;
                }

                try {
                    // faz o POST pra rota que devolve o PDF
                    const resp = await fetch(`/sales/budget/${__lastBudget.id}/view-budget`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/pdf'
                        }
                    });

                    if (!resp.ok) {
                        const txt = await resp.text();
                        console.error('Erro ao visualizar PDF:', txt);
                        alert('Falha ao gerar o PDF.');
                        return;
                    }

                    // transforma resposta binária em Blob
                    const blob = await resp.blob();

                    // cria URL temporária e abre nova aba
                    const url = URL.createObjectURL(blob);
                    window.open(url, '_blank');

                } catch (e) {
                    console.error(e);
                    alert('Erro de rede ao gerar o PDF.');
                }
            }

            function goToList() {
                clearProposalAndDisableDraft();
                window.location.href = "{{ route('budget.view') }}";
            }

            // limpa tudo e prepara pra novo orçamento
            function resetProposalUI() {
                discount = 0;
                discScope = 'all';
                $('#discount').value = 0;

                document.querySelectorAll('.disc-btn').forEach(x => {
                    x.classList.remove('bg-white', 'shadow');
                    x.setAttribute('aria-pressed', 'false');
                });
                const allBtn = $('#disc-all');
                allBtn.classList.add('bg-white', 'shadow');
                allBtn.setAttribute('aria-pressed', 'true');

                $('#client').value = '';
                $('#client_id').value = '';
                $('#client-mini').textContent = '—';
            }

            function startFreshBudget() {
                // limpa LS do orçamento anterior, mas vamos reativar o draft pro novo
                clearProposalStorage();
                __draftDisabled = false;

                // zera estado em memória
                selectedOne = new Set();
                selectedM = new Set();
                selectedY = new Set();

                discount = 0;
                discScope = 'all';
                recUpfront = false;
                recMode = 'installment';
                condApplied = false;
                cond = {
                    paydate: todayISO(),
                    deadline: 0,
                    signal: 0,
                    install: 1,
                    subtotal: 0,
                    signalPrice: 0,
                    avista: 0,
                    schedule: []
                };

                // limpa UI
                resetProposalUI();

                // re-render
                renderCards();
                renderSummary();
                updateButtons();

                // fecha modal
                closeAfterModal();

                // salva rascunho novo vazio
                saveDraft();
            }

            // ========= Listeners =========
            $('#btn-generate').addEventListener('click', generateBudget);

            $$('.type-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    $$('.type-btn').forEach(x => x.className = 'type-btn rounded-full px-3 py-1.5 text-sm bg-slate-100');
                    btn.className = 'type-btn rounded-full px-3 py-1.5 text-sm bg-blue-600 text-white';
                    typeFilter = btn.dataset.type;
                    renderCards();
                });
            });

            $('#search').addEventListener('input', e => {
                q = e.target.value.trim().toLowerCase();
                renderCards();
            });

            const discInput = $('#discount');
            discInput.value = discount;
            discInput.addEventListener('input', e => {
                discount = Number(e.target.value || 0);
                localStorage.setItem(LS.disc, String(discount));
                condApplied = false;
                renderSummary();
                saveDraft();
            });

            $$('.disc-btn').forEach(b => {
                b.addEventListener('click', () => {
                    $$('.disc-btn').forEach(x => {
                        x.classList.remove('bg-white', 'shadow');
                        x.setAttribute('aria-pressed', 'false');
                    });
                    b.classList.add('bg-white', 'shadow');
                    b.setAttribute('aria-pressed', 'true');
                    discScope = b.dataset.scope;
                    condApplied = false;
                    renderSummary();
                    saveDraft();
                });
            });

            $('#client').addEventListener('input', e => {
                const name = e.target.value.trim();
                $('#client-mini').textContent = name || '—';
                const c = CUSTOMERS.find(x => (x.name || '').toLowerCase() === name.toLowerCase());
                $('#client_id').value = c ? c.id : '';
                updateButtons();
                saveDraft();
            });

            // modal condições
            $('#btn-conditions').addEventListener('click', openCond);
            $$('[data-close-cond]').forEach(b => b.addEventListener('click', closeCond));
            $('#c-paydate').addEventListener('change', () => {
                renderCond();
                saveDraft();
            });
            $('#c-deadline').addEventListener('input', () => {
                renderCond();
                saveDraft();
            });
            $('#c-signal').addEventListener('input', () => {
                renderCond();
                saveDraft();
            });
            $('#c-install').addEventListener('change', () => {
                renderCond();
                saveDraft();
            });

            $('#btn-rec-no').addEventListener('click', () => {
                recUpfront = false;
                localStorage.setItem(LS.recUpfront, 'false');
                paintRecControls();
                renderCond();
                saveDraft();
            });
            $('#btn-rec-yes').addEventListener('click', () => {
                recUpfront = true;
                localStorage.setItem(LS.recUpfront, 'true');
                paintRecControls();
                renderCond();
                saveDraft();
            });
            $$('.rec-mode-btn').forEach(b => b.addEventListener('click', () => {
                recMode = b.dataset.mode;
                localStorage.setItem(LS.recMode, recMode);
                paintRecControls();
                renderCond();
                saveDraft();
            }));

            $('#cond-apply').addEventListener('click', () => {
                if (!$('#c-paydate').value) {
                    alert('Informe a data de pagamento.');
                    return;
                }
                if (Number($('#c-install').value || 0) < 1) {
                    alert('Parcelas deve ser >= 1.');
                    return;
                }
                condApplied = true;
                renderCond();
                closeCond();
                updateButtons();
                saveDraft();
            });

            function showAfterAlert(message, type) {
                // type: 'success' | 'error'

                const box = $('#after-alert');
                const icon = $('#after-alert-icon');
                const text = $('#after-alert-text');

                // base classes
                box.className = "mt-4 rounded-lg border p-3 text-sm flex items-start gap-2";

                if (type === 'success') {
                    box.classList.add('bg-emerald-50', 'border-emerald-200', 'text-emerald-700');
                    icon.innerHTML = `
            <svg class="h-5 w-5 text-emerald-600" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2">
                <path d="M20 6 9 17l-5-5"/>
            </svg>`;
                } else {
                    box.classList.add('bg-rose-50', 'border-rose-200', 'text-rose-700');
                    icon.innerHTML = `
            <svg class="h-5 w-5 text-rose-600" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2">
                <path d="M18 6 6 18M6 6l12 12"/>
            </svg>`;
                }

                text.textContent = message;
                box.classList.remove('hidden');
            }

            // modal pós-geração
            $('#after-send').addEventListener('click', sendLastBudgetEmail);
            $('#after-view').addEventListener('click', openLastBudgetPDF);
            $('#after-go-list').addEventListener('click', goToList);
            $('#after-new').addEventListener('click', startFreshBudget);

            function cleanupAndGoToList() {
                clearProposalAndDisableDraft();
                window.location.href = "{{ route('budget.view') }}?created=1";
            }

            function dismissAfterModal() {
                startFreshBudget();
            }

            const afterOverlay = document.getElementById('modal-after');

            document.getElementById('after-close-x')
                .addEventListener('click', dismissAfterModal);

            afterOverlay.addEventListener('click', (e) => {
                if (e.target === afterOverlay) dismissAfterModal();
            });

            document.addEventListener('keydown', (e) => {
                const visible = !afterOverlay.classList.contains('hidden');
                if (visible && e.key === 'Escape') dismissAfterModal();
            });

            // salvar rascunho antes de sair
            window.addEventListener('beforeunload', saveDraft);
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden') saveDraft();
            });

            // ========= init =========
            (async function init() {
                // carrega catálogo de serviços e clientes primeiro
                await Promise.all([loadServices(), loadCustomers()]);

                // tenta duplicação primeiro
                const duplicated = await loadDuplicateIfAny();

                if (duplicated) {
                    // já veio tudo pronto do orçamento original
                    renderCards();     // monta cards marcados
                    renderSummary();   // atualiza resumo lateral e totais
                    updateButtons();   // isso já deve habilitar "Gerar orçamento"
                    return;
                }

                // se não é duplicação, tenta rascunho salvo no localStorage
                const hasDraft = loadDraft();

                if (!cond.paydate) cond.paydate = todayISO();

                if (!hasDraft) {
                    // começo vazio normal
                    renderCards();
                    renderSummary();
                    updateButtons();
                } else {
                    // rascunho existente
                    $('#discount').value = discount;

                    // pinta botões de escopo de desconto
                    $$('.disc-btn').forEach(x => {
                        const on = x.dataset.scope === discScope;
                        x.classList.toggle('bg-white', on);
                        x.classList.toggle('shadow', on);
                        x.setAttribute('aria-pressed', on ? 'true' : 'false');
                    });

                    renderCards();
                    renderSummary();
                    updateButtons();
                }
            })();


            (function () {
                const modal = document.getElementById('modalService');
                const opener = document.getElementById('card-new-service');
                if (!modal || !opener) return;

                const form = modal.querySelector('#formService');
                const btnClose = modal.querySelector('#m-close');
                const btnCancel = modal.querySelector('#m-cancel');
                const btnSubmit = modal.querySelector('#m-submit');
                const errorsBox = modal.querySelector('#modal-errors');

                function openModal() {
                    modal.classList.remove('hidden');
                    document.body.classList.add('overflow-hidden');
                }

                function closeModal() {
                    modal.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                }

                function showErrors(msgs) {
                    if (!errorsBox) return;
                    const arr = Array.isArray(msgs) ? msgs : [msgs];
                    errorsBox.innerHTML = arr.map(m => `<p>${m}</p>`).join('');
                    errorsBox.classList.remove('hidden');
                }

                function clearErrors() {
                    if (!errorsBox) return;
                    errorsBox.classList.add('hidden');
                    errorsBox.innerHTML = '';
                }

                // abrir modal no clique do card
                opener.addEventListener('click', openModal);

                // fechar no X, Cancelar, fundo e ESC
                btnClose && btnClose.addEventListener('click', closeModal);
                btnCancel && btnCancel.addEventListener('click', closeModal);

                modal.addEventListener('click', (e) => {
                    if (e.target === modal) closeModal();
                });

                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                        closeModal();
                    }
                });

                // submit AJAX para criar serviço
                if (btnSubmit && form) {
                    btnSubmit.addEventListener('click', async () => {
                        clearErrors();

                        const fd = new FormData(form);

                        const name = (fd.get('name') || '').toString().trim();
                        const price = (fd.get('price') || '').toString().trim();
                        const type = (fd.get('type') || '').toString().trim();

                        const errs = [];
                        if (!name) errs.push('Informe o nome do serviço.');
                        if (!price) errs.push('Informe o valor do serviço.');
                        if (!type) errs.push('Selecione o tipo de pagamento.');

                        if (errs.length) {
                            showErrors(errs);
                            return;
                        }

                        btnSubmit.disabled = true;
                        const oldLabel = btnSubmit.textContent;
                        btnSubmit.textContent = 'Salvando...';

                        try {
                            const resp = await fetch("{{ route('service-api.store') }}", {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json'
                                },
                                body: fd
                            });

                            if (!resp.ok) {
                                let data = null;
                                try {
                                    data = await resp.json();
                                } catch (_) {
                                }

                                if (data && data.errors) {
                                    const flat = Object.values(data.errors).flat();
                                    showErrors(flat);
                                } else if (data && data.message) {
                                    showErrors(data.message);
                                } else {
                                    showErrors('Falha ao salvar serviço.');
                                }
                                return;
                            }

                            // sucesso: recarrega serviços e re-renderiza cards
                            await loadServices();
                            renderCards();

                            form.reset();
                            clearErrors();
                            closeModal();
                        } catch (e) {
                            console.error(e);
                            showErrors('Erro de rede ao salvar serviço.');
                        } finally {
                            btnSubmit.disabled = false;
                            btnSubmit.textContent = oldLabel;
                        }
                    });
                }
            })();
        </script>
    @endpush
@endsection
