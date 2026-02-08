@extends('layouts.templates.template')

@section('content')
    @push('styles')
        <style>
            .no-scrollbar {
                scrollbar-width: none
            }

            .no-scrollbar::-webkit-scrollbar {
                display: none
            }

            .parts-grid {
                grid-template-columns:.8fr 2fr .9fr 1fr .8fr .7fr 1.1fr .8fr 1.2fr .7fr
            }

            .parts-head {
                grid-template-columns:.8fr 2fr .9fr 1fr .8fr .7fr 1.1fr .8fr 1.2fr .7fr
            }

            @media (max-width: 1280px) {
                .parts-grid {
                    grid-template-columns:.7fr 1.6fr .8fr .9fr .7fr .6fr .9fr .7fr 1fr .6fr
                }

                .parts-head {
                    grid-template-columns:.7fr 1.6fr .8fr .9fr .7fr .6fr .9fr .7fr 1fr .6fr
                }
            }

            @media (max-width: 1100px) {
                .parts-grid input {
                    font-size: 11px
                }

                .parts-head {
                    font-size: 10px
                }
            }

            @media print {
                #modal-view .no-print {
                    display: none
                }

                #modal-view .print-area {
                    padding: 0 !important;
                    box-shadow: none !important
                }

                body {
                    background: white
                }
            }
        </style>
    @endpush

    <main id="orders-parts-fragment" class="mx-auto max-w-7xl px-4 sm:px-6 pb-10 lg:pb-14" data-fragment>
        {{-- Banner de rascunho --}}
        <div id="draft-banner"
             class="hidden mb-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <p><strong>Atenção:</strong> você salvou um pedido como <strong>rascunho</strong> e ele ainda <strong>não
                        foi enviado</strong>.</p>
                <div class="flex gap-2">
                    <button id="btn-draft-view"
                            class="rounded-lg border border-rose-300 bg-white px-3 py-1.5 font-medium text-rose-700 hover:bg-rose-100">
                        Visualizar
                    </button>
                    <button id="btn-draft-send"
                            class="rounded-lg bg-rose-600 px-3 py-1.5 font-semibold text-white hover:bg-rose-700">Enviar
                        agora
                    </button>
                    <button id="btn-draft-dismiss"
                            class="rounded-lg border border-rose-300 bg-rose-50 px-3 py-1.5 text-rose-700 hover:bg-rose-100">
                        Dispensar
                    </button>
                </div>

                <select id="draft-picker"
                        class="ml-2 rounded-lg border border-slate-200 bg-white px-2 py-1 text-sm"></select>
                <span id="draft-count" class="ml-2 text-sm text-slate-600"></span>
            </div>
        </div>

        {{-- Título + ações --}}
        <section class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Pedidos de Peças</h1>
                <p class="text-sm text-slate-600">Crie, visualize e envie propostas formais de peças.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button id="btn-parts-settings" type="button"
                        class="rounded-xl border border-slate-200 bg-slate-950 px-4 py-2 text-sm font-medium text-slate-100 hover:bg-slate-800">
                    Configurações
                </button>
                <button id="btn-new-parts" type="button" data-action="newParts"
                        class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                    Criar pedido de peças
                </button>
                <button data-action="regPart" id="btn-add"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Cadastrar peça
                </button>
                <button id="toggle-header"
                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow hover:bg-slate-50"
                        aria-expanded="true" aria-controls="header-collapsible" type="button"
                        title="Expandir/contrair cabeçalho">
                    <i id="toggle-icon" class="fa-solid fa-up-right-and-down-left-from-center"></i>
                </button>
            </div>
        </section>

        {{-- Cards --}}
        <section class="grid gap-3 sm:grid-cols-3 mb-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="text-xs text-slate-500">Pedidos (filtrados)</div>
                <div id="card-count" class="mt-1 text-2xl font-semibold">0</div>
                <div class="mt-1 text-xs text-slate-500" id="card-filter-label">Todos os status</div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="text-xs text-slate-500">Valor total</div>
                <div id="card-value" class="mt-1 text-2xl font-semibold">R$ 0,00</div>
                <div class="mt-1 text-xs text-slate-500">Somatório do total final</div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="text-xs text-slate-500">Itens</div>
                <div id="card-items" class="mt-1 text-2xl font-semibold">0</div>
                <div class="mt-1 text-xs text-slate-500">Quantidade total de itens</div>
            </div>
        </section>

        {{-- Filtros / Busca --}}
        <section class="mt-2 mb-2 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-wrap items-center gap-2">
                <button data-status="all"
                        class="flt rounded-full bg-blue-50 px-3.5 py-1.5 text-sm font-medium text-blue-700 ring-1 ring-inset ring-blue-200">
                    Todos
                </button>
                <button data-status="aberto"
                        class="flt rounded-full bg-white px-3.5 py-1.5 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-200 hover:bg-slate-50">
                    Em aberto
                </button>
                <button data-status="pendente"
                        class="flt rounded-full bg-white px-3.5 py-1.5 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-200 hover:bg-slate-50">
                    Pendente
                </button>
                <button data-status="parcial"
                        class="flt rounded-full bg-white px-3.5 py-1.5 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-200 hover:bg-slate-50">
                    Parcial
                </button>
                <button data-status="atraso"
                        class="flt rounded-full bg-white px-3.5 py-1.5 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-200 hover:bg-slate-50">
                    Em atraso
                </button>
                <button data-status="concluido"
                        class="flt rounded-full bg-white px-3.5 py-1.5 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-200 hover:bg-slate-50">
                    Concluído
                </button>
            </div>
            <div class="flex items-center gap-2">
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400"
                         viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M10 2a8 8 0 1 1 5.29 14.29l4.21 4.2-1.42 1.42-4.2-4.21A8 8 0 0 1 10 2Zm0 2a6 6 0 1 0 0 12 6 6 0 0 0 0-12Z"/>
                    </svg>
                    <input id="search"
                           class="w-[min(320px,70vw)] rounded-xl border border-slate-300 bg-white pl-9 pr-3 py-2.5 text-sm outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-200"
                           placeholder="Buscar por nº, CNPJ, título..."/>
                </div>
            </div>
        </section>

        {{-- Lista --}}
        <section id="ordersp-list"
                 class="mt-2 rounded-2xl border border-slate-200 bg-white shadow-sm overflow-y-auto">
            <div class="h-full overflow-x-auto overflow-y-auto no-scrollbar">
                <table class="min-w-[980px] w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Pedido</th>
                        <th class="px-4 py-3 text-left">CNPJ</th>
                        <th class="px-4 py-3 text-left">Título</th>
                        <th class="px-4 py-3 text-left">Data</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-right">Ações</th>
                    </tr>
                    </thead>
                    <tbody id="ordersp-body" class="divide-y divide-slate-100"></tbody>
                </table>

                <div id="empty-state" class="hidden p-10 text-center text-slate-500">
                    Nenhum pedido encontrado.
                </div>
            </div>
        </section>
    </main>

    {{-- Modal: Pedido de Peças --}}
    <div id="modal-parts" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm">
        <div
            class="absolute left-1/2 top-1/2 w-[min(1400px,98vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h2 id="parts-modal-title" class="text-lg font-semibold">Novo pedido de peças</h2>
                <button data-close-parts class="rounded-lg p-2 hover:bg-slate-100" aria-label="Fechar">
                    <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="grid md:grid-cols-[1fr_320px]">
                <form id="form-parts" class="p-6 space-y-5 max-h-[78vh] overflow-y-auto" autocomplete="off">
                    <div class="grid gap-4 md:grid-cols-6">
                        <div class="md:col-span-2">
                            <label class="text-sm font-medium">Nome do pedido</label>
                            <input id="pp-title"
                                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                   placeholder="Ex.: Peças balança industrial"/>
                        </div>

                        <div class="md:col-span-2">
                            <label class="text-sm font-medium">Fornecedor</label>

                            <input type="hidden" id="pp-supplier-id">
                            <input type="hidden" id="pp-supplier-email" value="">

                            <div class="relative mt-1">
                                <input id="pp-supplier-name" autocomplete="off"
                                       class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                       placeholder="Buscar fornecedor..."/>
                                <div id="pp-supplier-dd"
                                     class="hidden absolute z-[70] mt-1 w-[min(520px,90vw)] rounded-xl border border-slate-200 bg-white shadow-lg overflow-hidden">
                                </div>
                            </div>
                        </div>

                        <div class="md:col-span-1">
                            <label class="text-sm font-medium">CNPJ de faturamento</label>
                            <input id="pp-cnpj"
                                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                   placeholder="00.000.000/0000-00" inputmode="numeric" maxlength="18"/>
                        </div>

                        <div class="md:col-span-1">
                            <label class="text-sm font-medium">Data desejada</label>
                            <input id="pp-date" type="date"
                                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"/>
                        </div>

                        <div class="md:col-span-2">
                            <label class="text-sm font-medium">UF de faturamento</label>

                            <div class="relative mt-1">
                                <input id="pp-uf" autocomplete="off"
                                       class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                       placeholder="Ex.: SP" maxlength="2"/>
                                <div id="pp-uf-dd"
                                     class="hidden absolute z-[70] mt-1 w-56 rounded-xl border border-slate-200 bg-white shadow-lg overflow-hidden">
                                </div>
                            </div>
                        </div>

                        <div class="md:col-span-4">
                            <label class="text-xs text-slate-500">
                                Obs.: ICMS é aplicado sobre o Subtotal (ajustável futuramente, se desejar outra base de
                                cálculo).
                            </label>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200">
                        <div
                            class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-4 py-3">
                            <h3 class="text-sm font-semibold">Itens do pedido</h3>
                            <div class="flex items-center gap-2">
                                <button type="button" id="btn-add-item"
                                        class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100">
                                    Adicionar item
                                </button>
                                <button type="button" id="btn-open-reg"
                                        class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                    Cadastrar peça
                                </button>
                            </div>
                        </div>

                        <div class="parts-head grid gap-3 bg-slate-50 px-4 py-2 text-[11px] font-medium text-slate-600">
                            <div>Código</div>
                            <div>Descrição</div>
                            <div>NCM</div>
                            <div class="text-right">Valor item</div>
                            <div class="text-right">IPI %</div>
                            <div class="text-right">Qtd</div>
                            <div class="text-right">Valor c/ IPI</div>
                            <div class="text-right">Desc. %</div>
                            <div class="text-right">Valor c/ desc.</div>
                            <div class="text-right">—</div>
                        </div>

                        <div id="items-body" class="divide-y divide-slate-100 px-4"></div>
                        <datalist id="parts-codes"></datalist>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" data-close-parts
                                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            Cancelar
                        </button>
                        <button id="btn-save-draft"
                                class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:opacity-90">
                            Salvar rascunho
                        </button>
                    </div>
                </form>

                <aside class="border-t md:border-t-0 md:border-l border-slate-200 p-6 space-y-4">
                    <h3 class="text-sm font-semibold">Resumo do pedido</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span>Itens</span><span id="sum-items"
                                                                                  class="font-medium">0</span></div>
                        <div class="flex justify-between"><span>Subtotal</span><span id="sum-sub" class="font-medium">R$ 0,00</span>
                        </div>
                        <div class="flex justify-between"><span>IPI</span><span id="sum-ipi"
                                                                                class="font-medium">R$ 0,00</span></div>
                        <div class="flex justify-between"><span>ICMS <span id="sum-icms-tag"
                                                                           class="text-slate-500">(—)</span></span><span
                                id="sum-icms" class="font-medium">R$ 0,00</span></div>
                        <div class="flex justify-between"><span>Descontos</span><span id="sum-disc"
                                                                                      class="font-medium text-emerald-700">- R$ 0,00</span>
                        </div>
                        <div class="flex justify-between border-t pt-2 text-base font-semibold"><span>Total</span><span
                                id="sum-total">R$ 0,00</span></div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 p-4 space-y-3">
                        <h4 class="text-sm font-semibold">Pagamento</h4>

                        <div class="flex gap-2">
                            <button type="button" data-pay-type-btn="avista"
                                    class="payTypeBtn flex-1 rounded-xl border border-slate-200 bg-slate-900 px-3 py-2 text-xs font-semibold text-white">
                                À vista
                            </button>

                            <button type="button" data-pay-type-btn="sinal"
                                    class="payTypeBtn flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                Sinal + parcelas
                            </button>
                        </div>

                        <input type="hidden" id="pp-pay-type" value="avista">

                        <div class="grid gap-3">
                            <div>
                                <label class="text-xs font-medium text-slate-600">Vencimento</label>
                                <input id="pp-pay-due" type="date"
                                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"/>
                            </div>

                            <div id="pp-pay-sinal-wrap" class="hidden">
                                <label class="text-xs font-medium text-slate-600">Sinal (R$) — pode ser 0</label>
                                <input id="pp-pay-sinal" inputmode="decimal" placeholder="0,00"
                                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"/>
                            </div>

                            <div id="pp-pay-parc-wrap" class="hidden">
                                <label class="text-xs font-medium text-slate-600">Parcelas</label>
                                <input id="pp-pay-parc" type="number" min="1" step="1" value="1"
                                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"/>
                                <div class="mt-1 text-[11px] text-slate-500" id="pp-pay-preview">—</div>
                            </div>
                        </div>
                    </div>

                    <button id="btn-send"
                            class="w-full rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">
                        Enviar pedido
                    </button>
                </aside>
            </div>
        </div>
    </div>

    {{-- Modal: Confirmar Envio --}}
    <div id="modal-confirm" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm">
        <div
            class="absolute left-1/2 top-1/2 w-[min(720px,94vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-start gap-4">
                <div class="grid size-12 place-items-center rounded-full bg-blue-600 text-white shadow">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 12h16M12 4v16"/>
                    </svg>
                </div>

                <div class="flex-1">
                    <h3 class="text-lg font-semibold">Enviar este pedido agora?</h3>
                    <p class="mt-1 text-sm text-slate-600">Você poderá visualizar a proposta após o envio.</p>

                    <!-- Destinatário -->
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-slate-600">
                            Destinatário: <span id="confirm-to" class="font-semibold text-slate-900"></span>
                        </div>

                        <button id="btn-edit-recipient"
                                type="button"
                                class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            Alterar
                        </button>
                    </div>

                    <!-- aviso de email ausente/inválido -->
                    <div id="confirm-no-email"
                         class="mt-2 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-medium text-rose-700 hidden">
                    </div>

                    <!-- Assunto -->
                    <div class="mt-3 rounded-xl border border-slate-200 bg-white p-3 text-sm">
                        <div class="text-slate-500">Assunto</div>
                        <div id="confirm-subject" class="mt-1 font-medium text-slate-800">—</div>
                    </div>

                    <!-- Corpo -->
                    <div class="mt-3 rounded-xl border border-slate-200 bg-white p-3 text-sm">
                        <div class="text-slate-500">Corpo do e-mail</div>
                        <pre id="confirm-body" class="mt-1 whitespace-pre-wrap font-sans text-slate-800">—</pre>
                    </div>

                    <!-- PDF + Config -->
                    <div class="mt-3 rounded-xl border border-slate-200 bg-white p-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div
                                    class="grid h-9 w-9 place-items-center rounded-lg bg-slate-100 text-slate-600 font-semibold">
                                    PDF
                                </div>
                                <div>
                                    <div class="font-medium text-slate-800">Anexo</div>
                                    <div class="text-slate-500" id="confirm-pdf-name">Proposta-—.pdf</div>
                                </div>
                            </div>

                            <button
                                id="btn-open-part-order-settings"
                                type="button"
                                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                            >
                                Configurações
                            </button>
                        </div>

                        <div class="mt-2 text-xs text-slate-500">
                            O envio seguirá as configurações do sistema (assunto/corpo) e incluirá o PDF em anexo.
                        </div>
                    </div>

                    <!-- Botões -->
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button id="btn-confirm-send"
                                class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                            Confirmar envio
                        </button>

                        <button id="btn-return-edit"
                                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            Voltar a editar
                        </button>
                    </div>
                </div>

                <button id="btn-confirm-x" class="rounded-lg p-2 hover:bg-slate-100" aria-label="Fechar">
                    <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Modal: Sucesso --}}
    <div id="modal-success" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm">
        <div
            class="absolute left-1/2 top-1/2 w-[min(520px,94vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-start gap-4">
                <div class="grid size-12 place-items-center rounded-full bg-blue-600 text-white shadow">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m20 6-11 11-5-5"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold">Pedido enviado com sucesso</h3>
                    <p class="mt-1 text-sm text-slate-600">Sua proposta foi gerada e enviada em formato PDF ao
                        fornecedor.</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button id="btn-success-view"
                                class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                            Ver pedido
                        </button>
                        <button id="btn-success-close"
                                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            Fechar
                        </button>
                    </div>
                </div>
                <button id="btn-success-x" class="rounded-lg p-2 hover:bg-slate-100" aria-label="Fechar">
                    <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Modal: Visualização / Proposta --}}
    <div id="modal-view" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm">
        <div
            class="absolute left-1/2 top-1/2 w-[min(1200px,98vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white shadow-2xl">
            <div class="no-print flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <div class="flex items-center gap-3">
                    <h2 class="text-lg font-semibold">Visualizar proposta</h2>
                    <span id="badge-draft-view"
                          class="hidden rounded-md bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-700">Rascunho</span>
                </div>
                <div class="flex gap-2">
                    <button id="btn-view-resend"
                            class="hidden rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-700 hover:bg-blue-100">
                        Reenviar
                    </button>
                    <button id="btn-edit-draft"
                            class="hidden rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-700 hover:bg-blue-100">
                        Editar rascunho
                    </button>
                    <button id="btn-print"
                            class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Imprimir
                    </button>
                    <button data-close-view class="rounded-lg p-2 hover:bg-slate-100" aria-label="Fechar">
                        <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2">
                            <path d="M18 6 6 18M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="print-area p-8 overflow-y-auto max-h-[80vh]">
                <div id="view-content" class="prose max-w-none"></div>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div id="toast" class="pointer-events-none fixed bottom-5 right-5 z-50 hidden">
        <div class="rounded-xl bg-slate-900/90 px-4 py-3 text-sm font-medium text-white shadow-lg">Mensagem</div>
    </div>

    {{-- Modal: Configurações do Pedido de Peças --}}
    <div id="modal-parts-settings" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm">
        <div
            class="absolute left-1/2 top-1/2 w-[min(860px,94vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <div>
                    <h2 class="text-lg font-semibold">Configurações do pedido</h2>
                    <p class="text-sm text-slate-600">Define padrões que já entram preenchidos ao criar um pedido.</p>
                </div>
                <button data-close-settings class="rounded-lg p-2 hover:bg-slate-100" aria-label="Fechar">✕</button>
            </div>

            <form id="form-parts-settings" class="p-6 space-y-5">
                <div class="grid gap-4 md:grid-cols-6">
                    <div class="md:col-span-3">
                        <label class="text-sm font-medium">Fornecedor principal</label>
                        <input type="hidden" id="ps-supplier-id">
                        <div class="relative mt-1">
                            <input id="ps-supplier-name" autocomplete="off"
                                   class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                   placeholder="Buscar fornecedor..."/>
                            <div id="ps-supplier-dd"
                                 class="hidden absolute z-[80] mt-1 w-[min(520px,90vw)] rounded-xl border border-slate-200 bg-white shadow-lg overflow-hidden">
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-sm font-medium">CNPJ de faturamento padrão</label>
                        <input id="ps-cnpj" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                               placeholder="00.000.000/0000-00" inputmode="numeric" maxlength="18"/>
                    </div>

                    <div class="md:col-span-1">
                        <label class="text-sm font-medium">UF padrão</label>
                        <div class="relative mt-1">
                            <input id="ps-uf" autocomplete="off"
                                   class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                   placeholder="SP" maxlength="2"/>
                            <div id="ps-uf-dd"
                                 class="hidden absolute z-[80] mt-1 w-56 rounded-xl border border-slate-200 bg-white shadow-lg overflow-hidden">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium">Assunto padrão do e-mail</label>
                        <input id="ps-email-subject"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                               placeholder="Ex.: Pedido @{{partOrderNumber}}"/>
                    </div>

                    <div>
                        <label class="text-sm font-medium">Variáveis (clique pra inserir)</label>
                        <div class="mt-1 flex flex-wrap gap-2 text-xs">
                            <button type="button" class="ps-var rounded-lg border px-2 py-1 hover:bg-slate-50"
                                    data-var="@{{partOrderNumber}}">@{{partOrderNumber}}
                            </button>
                            <button type="button" class="ps-var rounded-lg border px-2 py-1 hover:bg-slate-50"
                                    data-var="@{{supplierName}}">@{{supplierName}}
                            </button>
                            <button type="button" class="ps-var rounded-lg border px-2 py-1 hover:bg-slate-50"
                                    data-var="@{{orderDate}}">@{{orderDate}}
                            </button>
                            <button type="button" class="ps-var rounded-lg border px-2 py-1 hover:bg-slate-50"
                                    data-var="@{{itemsCount}}">@{{itemsCount}}
                            </button>
                            <button type="button" class="ps-var rounded-lg border px-2 py-1 hover:bg-slate-50"
                                    data-var="@{{total}}">@{{total}}
                            </button>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-sm font-medium">Corpo padrão do e-mail</label>
                        <textarea id="ps-email-body" rows="7"
                                  class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                  placeholder="Digite o texto..."></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" data-close-settings
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button id="btn-save-settings"
                            class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                        Salvar configurações
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: confirmação troca de fornecedor -->
    <div id="modal-supplier-choice" class="fixed inset-0 z-[999] hidden">
        <div class="absolute inset-0 bg-slate-900/40" data-close-supplier-choice></div>

        <div class="relative mx-auto mt-24 w-full max-w-md px-4">
            <div class="rounded-2xl bg-white p-6 shadow-xl ring-1 ring-slate-200">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Atualizar fornecedor padrão?</h3>
                        <p class="mt-1 text-sm text-slate-600">
                            Você alterou o fornecedor deste pedido para
                            <span id="sup-choice-name" class="font-semibold text-slate-900">—</span>.
                            Quer usar só neste pedido ou salvar como padrão no sistema?
                        </p>
                    </div>

                    <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-50"
                            data-close-supplier-choice>
                        ✕
                    </button>
                </div>

                <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:justify-end">
                    <button id="btn-sup-order-only"
                            type="button"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Apenas neste pedido
                    </button>

                    <button id="btn-sup-set-default"
                            type="button"
                            class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Salvar como padrão
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{--    Mini modal editar nome/e-mail destinatario --}}
    <div id="modal-recipient-edit" class="hidden fixed inset-0 z-[120]">
        <div class="absolute inset-0 bg-black/40"></div>

        <div class="relative mx-auto mt-24 w-full max-w-md rounded-2xl bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div class="text-sm font-semibold text-slate-900">Alterar destinatário</div>
                <button type="button" data-close-recipient-edit class="text-slate-500 hover:text-slate-700">✕</button>
            </div>

            <div class="px-5 py-4 space-y-3">
                <div>
                    <label class="text-xs font-medium text-slate-600">Nome</label>
                    <input id="rec-name" class="mt-1 h-10 w-full rounded-xl border border-slate-200 px-3 text-sm"/>
                </div>

                <div>
                    <label class="text-xs font-medium text-slate-600">E-mail</label>
                    <input id="rec-email" class="mt-1 h-10 w-full rounded-xl border border-slate-200 px-3 text-sm"/>
                </div>

                <div id="rec-error"
                     class="hidden rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-medium text-rose-700"></div>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4">
                <button id="btn-rec-cancel" type="button"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Cancelar
                </button>
                <button id="btn-rec-save" type="button"
                        class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Continuar
                </button>
            </div>
        </div>
    </div>

    {{--    alteração destinatario - Modal “apenas nesse pedido / no sistema --}}
    <div id="modal-recipient-scope" class="hidden fixed inset-0 z-[130]">
        <div class="absolute inset-0 bg-black/40"></div>

        <div class="relative mx-auto mt-28 w-full max-w-md rounded-2xl bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div class="text-sm font-semibold text-slate-900">Aplicar alteração</div>
                <button type="button" data-close-recipient-scope class="text-slate-500 hover:text-slate-700">✕</button>
            </div>

            <div class="px-5 py-4">
                <div class="text-sm text-slate-700">
                    Deseja aplicar para:
                </div>

                <div class="mt-2 text-xs text-slate-600">
                    <div><span class="font-semibold" id="rec-scope-name"></span></div>
                    <div id="rec-scope-email"></div>
                </div>

                <div class="mt-4 grid gap-2">
                    <button id="btn-rec-order-only" type="button"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Apenas neste pedido
                    </button>

                    <button id="btn-rec-update-system" type="button"
                            class="w-full rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        Atualizar no sistema
                    </button>
                </div>

                <div id="rec-scope-hint" class="mt-3 hidden text-xs text-amber-700">
                    Este pedido não tem fornecedor vinculado (sem supplier_id). Só dá pra aplicar neste pedido.
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            #receive-modal::backdrop {
                background: rgba(2, 6, 23, .55);
            }
        </style>
    @endpush

    <dialog id="receive-modal" class="rounded-2xl p-0 w-[min(720px,94vw)]">
        <div class="rounded-2xl bg-white shadow-2xl max-h-[90vh] overflow-y-auto">
            <div class="sticky bg-white top-0 z-30 flex items-center gap-3 justify-between border-b border-slate-200 px-6 py-4">
                <div>
                    <h3 class="text-lg font-semibold">Entrada no estoque</h3>
                    <p class="text-sm text-slate-600">Selecione modo de entrada e preço.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="btn-receive-close"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancelar</button>
                    <button id="btnConfirm"
                            class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        Confirmar entrada
                    </button>
                </div>
            </div>

            <div class="p-6 space-y-4">
                <!-- ✅ TOGGLES (JS injeta aqui) -->
                <div id="receive-toggles"></div>

                <!-- ✅ KPIs (JS injeta aqui) -->
                <div id="receive-kpis"></div>

                <!-- ✅ ITENS (JS injeta aqui) -->
                <ul id="receive-items" class="divide-y divide-slate-100 rounded-2xl border border-slate-200"></ul>

                <!-- Lista principal: só pendentes -->
                <ul id="pendingList" class="divide-y divide-slate-100 rounded-2xl border border-slate-200 bg-white"></ul>

                <!-- Accordion: finalizados -->
                <details id="doneDetails" class="mt-4 rounded-2xl border border-slate-200 bg-white hidden">
                    <summary class="flex cursor-pointer items-center justify-between px-4 py-3">
                        <div class="text-sm font-semibold text-slate-700">
                            Itens com entrega finalizada (<span id="doneCount">0</span>)
                        </div>

                        <svg id="doneChevron" class="h-5 w-5 text-slate-500 transition-transform duration-200"
                             viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                  d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z"
                                  clip-rule="evenodd"/>
                        </svg>
                    </summary>

                    <div class="border-t border-slate-100 px-4 py-3">
                        <ul id="doneList" class="divide-y divide-slate-100 rounded-2xl border border-slate-200 bg-white"></ul>
                    </div>
                </details>
            </div>
        </div>
    </dialog>

    <!-- Modal: Confirmar exclusão -->
    <div id="modal-delete" class="fixed inset-0 z-[999] hidden">
        <div class="absolute inset-0 bg-slate-900/55"></div>

        <div class="relative mx-auto mt-24 w-[92%] max-w-md">
            <div class="rounded-2xl border border-slate-200 bg-white shadow-xl">
                <div class="flex items-start justify-between gap-3 px-5 pt-5">
                    <div>
                        <div class="text-base font-semibold text-slate-900">Excluir pedido</div>
                        <div id="del-sub" class="mt-1 text-sm text-slate-600"></div>
                    </div>

                    <button type="button" data-close-delete
                            class="grid h-9 w-9 place-items-center rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50">
                        ✕
                    </button>
                </div>

                <div class="px-5 pb-5 pt-3">
                    <p class="text-sm text-slate-700">
                        Você está prestes a remover este pedido. <span class="font-semibold">Não será possível recuperar depois.</span>
                    </p>

                    <!-- Aviso especial (só aparece no partial) -->
                    <div id="del-partial-warning"
                         class="mt-4 hidden rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                        <div class="font-semibold">Atenção</div>
                        <div class="mt-1">
                            Este pedido está com status <b>partial</b>. Ao excluir, <b>os itens que já entraram no
                                estoque permanecerão no estoque</b>.
                            A exclusão não desfaz movimentações já registradas.
                        </div>
                    </div>

                    <div class="mt-5 flex items-center justify-end gap-2">
                        <button type="button" data-close-delete
                                class="h-10 rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            Cancelar
                        </button>

                        <button id="btn-delete-confirm" type="button"
                                class="h-10 rounded-xl bg-rose-600 px-4 text-sm font-semibold text-white hover:bg-rose-700">
                            Excluir definitivamente
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('layouts.common.modal.modal_delete')
@endsection

@push('scripts')
    <script>
        (function () {
            const list = document.getElementById('ordersp-list');
            if (!list) return;

            function fit() {
                const rect = list.getBoundingClientRect();
                const bottomGap = 18; // ajuste fino se quiser
                const h = Math.max(240, window.innerHeight - rect.top - bottomGap);
                list.style.maxHeight = h + 'px';
            }

            window.addEventListener('resize', fit, {passive: true});
            window.addEventListener('load', fit);
            fit();
        })();
    </script>

    <script type="module" src="{{ asset('assets/js/template/views/part-orders/part-order-index.js') }}"></script>
@endpush
