@extends('layouts.templates.template')

@section('content')
    @push('styles')
        <style>
            .no-scrollbar{scrollbar-width:none}.no-scrollbar::-webkit-scrollbar{display:none}
            .parts-grid{grid-template-columns:.8fr 2fr .9fr 1fr .8fr .7fr 1.1fr .8fr 1.2fr .7fr}
            .parts-head{grid-template-columns:.8fr 2fr .9fr 1fr .8fr .7fr 1.1fr .8fr 1.2fr .7fr}
            @media (max-width:1280px){
                .parts-grid{grid-template-columns:.7fr 1.6fr .8fr .9fr .7fr .6fr .9fr .7fr 1fr .6fr}
                .parts-head{grid-template-columns:.7fr 1.6fr .8fr .9fr .7fr .6fr .9fr .7fr 1fr .6fr}
            }
            @media (max-width:1100px){.parts-grid input{font-size:11px}.parts-head{font-size:10px}}
            @media print{
                #modal-view .no-print{display:none}
                #modal-view .print-area{padding:0!important;box-shadow:none!important}
                body{background:white}
            }
        </style>
    @endpush

    <main id="orders-parts-fragment" class="mx-auto max-w-7xl px-4 sm:px-6 pb-10 lg:pb-14" data-fragment>
        {{-- Banner de rascunho --}}
        <div id="draft-banner" class="hidden mb-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <p><strong>Atenção:</strong> você salvou um pedido como <strong>rascunho</strong> e ele ainda <strong>não foi enviado</strong>.</p>
                <div class="flex gap-2">
                    <button id="btn-draft-view" class="rounded-lg border border-rose-300 bg-white px-3 py-1.5 font-medium text-rose-700 hover:bg-rose-100">Visualizar</button>
                    <button id="btn-draft-send" class="rounded-lg bg-rose-600 px-3 py-1.5 font-semibold text-white hover:bg-rose-700">Enviar agora</button>
                    <button id="btn-draft-dismiss" class="rounded-lg border border-rose-300 bg-rose-50 px-3 py-1.5 text-rose-700 hover:bg-rose-100">Dispensar</button>
                </div>
            </div>
        </div>

        {{-- Título + ações --}}
        <section class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Pedidos de Peças</h1>
                <p class="text-sm text-slate-600">Crie, visualize e envie propostas formais de peças.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button id="btn-new-parts" type="button" data-action="newParts"
                        class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                    Criar pedido de peças
                </button>
                <button data-action="regPart"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Cadastrar peça
                </button>
                <button data-action="importParts"
                        class="rounded-xl border border-blue-300 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-100">
                    Cadastro em massa (CSV)
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
                <button data-status="all" class="flt rounded-full bg-blue-50 px-3.5 py-1.5 text-sm font-medium text-blue-700 ring-1 ring-inset ring-blue-200">Todos</button>
                <button data-status="aberto" class="flt rounded-full bg-white px-3.5 py-1.5 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-200 hover:bg-slate-50">Em aberto</button>
                <button data-status="pendente" class="flt rounded-full bg-white px-3.5 py-1.5 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-200 hover:bg-slate-50">Pendente</button>
                <button data-status="atraso" class="flt rounded-full bg-white px-3.5 py-1.5 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-200 hover:bg-slate-50">Em atraso</button>
                <button data-status="concluido" class="flt rounded-full bg-white px-3.5 py-1.5 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-200 hover:bg-slate-50">Concluído</button>
            </div>
            <div class="flex items-center gap-2">
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="currentColor"><path d="M10 2a8 8 0 1 1 5.29 14.29l4.21 4.2-1.42 1.42-4.2-4.21A8 8 0 0 1 10 2Zm0 2a6 6 0 1 0 0 12 6 6 0 0 0 0-12Z"/></svg>
                    <input id="search" class="w-[min(320px,70vw)] rounded-xl border border-slate-300 bg-white pl-9 pr-3 py-2.5 text-sm outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-200"
                           placeholder="Buscar por nº, CNPJ, título..." />
                </div>
            </div>
        </section>

        {{-- Lista --}}
        <section class="mt-2 overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
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
            <div id="empty-state" class="hidden p-10 text-center text-slate-500">Nenhum pedido encontrado.</div>
        </section>
    </main>

    {{-- Modal: Pedido de Peças --}}
    <div id="modal-parts" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm">
        <div class="absolute left-1/2 top-1/2 w-[min(1400px,98vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h2 id="parts-modal-title" class="text-lg font-semibold">Novo pedido de peças</h2>
                <button data-close-parts class="rounded-lg p-2 hover:bg-slate-100" aria-label="Fechar">
                    <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="grid md:grid-cols-[1fr_320px]">
                <form id="form-parts" class="p-6 space-y-5 max-h-[78vh] overflow-y-auto">
                    <div class="grid gap-4 md:grid-cols-4">
                        <div class="md:col-span-2">
                            <label class="text-sm font-medium">Nome do pedido</label>
                            <input id="pp-title" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="Ex.: Peças balança industrial"/>
                        </div>
                        <div>
                            <label class="text-sm font-medium">CNPJ de faturamento</label>
                            <input id="pp-cnpj" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="00.000.000/0000-00" inputmode="numeric" maxlength="18"/>
                        </div>
                        <div>
                            <label class="text-sm font-medium">Data</label>
                            <input id="pp-date" type="date" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"/>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-sm font-medium">UF de faturamento</label>
                            <select id="pp-uf" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                                <option value="">Selecione a UF…</option>
                                <option>AC</option><option>AL</option><option>AM</option><option>AP</option><option>BA</option><option>CE</option><option>DF</option><option>ES</option><option>GO</option><option>MA</option><option>MG</option><option>MS</option><option>MT</option><option>PA</option><option>PB</option><option>PE</option><option>PI</option><option>PR</option><option>RJ</option><option>RN</option><option>RO</option><option>RR</option><option>RS</option><option>SC</option><option>SE</option><option>SP</option><option>TO</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-xs text-slate-500">Obs.: ICMS é aplicado sobre o Subtotal (ajustável futuramente, se desejar outra base de cálculo).</label>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-4 py-3">
                            <h3 class="text-sm font-semibold">Itens do pedido</h3>
                            <div class="flex items-center gap-2">
                                <button type="button" id="btn-add-item" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100">Adicionar item</button>
                                <button type="button" id="btn-open-reg" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Cadastrar peça</button>
                            </div>
                        </div>

                        <div class="parts-head grid gap-3 bg-slate-50 px-4 py-2 text-[11px] font-medium text-slate-600">
                            <div>Código</div><div>Descrição</div><div>NCM</div>
                            <div class="text-right">Valor item</div><div class="text-right">IPI %</div>
                            <div class="text-right">Qtd</div><div class="text-right">Valor c/ IPI</div>
                            <div class="text-right">Desc. %</div><div class="text-right">Valor c/ desc.</div>
                            <div class="text-right">—</div>
                        </div>

                        <div id="items-body" class="divide-y divide-slate-100 px-4"></div>
                        <datalist id="parts-codes"></datalist>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" data-close-parts class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancelar</button>
                        <button id="btn-save-draft" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:opacity-90">Salvar rascunho</button>
                    </div>
                </form>

                <aside class="border-t md:border-t-0 md:border-l border-slate-200 p-6 space-y-4">
                    <h3 class="text-sm font-semibold">Resumo do pedido</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span>Itens</span><span id="sum-items" class="font-medium">0</span></div>
                        <div class="flex justify-between"><span>Subtotal</span><span id="sum-sub" class="font-medium">R$ 0,00</span></div>
                        <div class="flex justify-between"><span>IPI</span><span id="sum-ipi" class="font-medium">R$ 0,00</span></div>
                        <div class="flex justify-between"><span>ICMS <span id="sum-icms-tag" class="text-slate-500">(—)</span></span><span id="sum-icms" class="font-medium">R$ 0,00</span></div>
                        <div class="flex justify-between"><span>Descontos</span><span id="sum-disc" class="font-medium text-emerald-700">- R$ 0,00</span></div>
                        <div class="flex justify-between border-t pt-2 text-base font-semibold"><span>Total</span><span id="sum-total">R$ 0,00</span></div>
                    </div>
                    <button id="btn-send" class="w-full rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Enviar pedido</button>
                </aside>
            </div>
        </div>
    </div>

    {{-- Modal: Confirmar Envio --}}
    <div id="modal-confirm" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm">
        <div class="absolute left-1/2 top-1/2 w-[min(520px,94vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-start gap-4">
                <div class="grid size-12 place-items-center rounded-full bg-blue-600 text-white shadow">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16M12 4v16"/></svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold">Enviar este pedido agora?</h3>
                    <p class="mt-1 text-sm text-slate-600">Você poderá visualizar a proposta após o envio.</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button id="btn-confirm-send" class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Confirmar envio</button>
                        <button id="btn-return-edit" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Voltar a editar</button>
                    </div>
                </div>
                <button id="btn-confirm-x" class="rounded-lg p-2 hover:bg-slate-100" aria-label="Fechar">
                    <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Modal: Sucesso --}}
    <div id="modal-success" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm">
        <div class="absolute left-1/2 top-1/2 w-[min(520px,94vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-start gap-4">
                <div class="grid size-12 place-items-center rounded-full bg-blue-600 text-white shadow">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m20 6-11 11-5-5"/></svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold">Pedido enviado com sucesso</h3>
                    <p class="mt-1 text-sm text-slate-600">Sua proposta foi gerada. <span class="text-slate-500">Em breve: envio automático em PDF por e-mail.</span></p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button id="btn-success-view" class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Ver pedido</button>
                        <button id="btn-success-close" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Fechar</button>
                    </div>
                </div>
                <button id="btn-success-x" class="rounded-lg p-2 hover:bg-slate-100" aria-label="Fechar">
                    <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Modal: Visualização / Proposta --}}
    <div id="modal-view" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm">
        <div class="absolute left-1/2 top-1/2 w-[min(1200px,98vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white shadow-2xl">
            <div class="no-print flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <div class="flex items-center gap-3">
                    <h2 class="text-lg font-semibold">Visualizar proposta</h2>
                    <span id="badge-draft-view" class="hidden rounded-md bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-700">Rascunho</span>
                </div>
                <div class="flex gap-2">
                    <button id="btn-edit-draft" class="hidden rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-700 hover:bg-blue-100">Editar rascunho</button>
                    <button id="btn-print" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Imprimir</button>
                    <button data-close-view class="rounded-lg p-2 hover:bg-slate-100" aria-label="Fechar">
                        <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
            <div class="print-area p-8 overflow-y-auto max-h-[80vh]">
                <div id="view-content" class="prose max-w-none"></div>
            </div>
        </div>
    </div>

    {{-- Modal: Cadastrar peça --}}
    <div id="modal-reg-part" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm">
        <div class="absolute left-1/2 top-1/2 w-[min(560px,94vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">Cadastrar peça</h2>
                <button data-close-regpart class="rounded-lg p-2 hover:bg-slate-100" aria-label="Fechar">
                    <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <form id="form-reg-part" class="mt-4 grid gap-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="text-sm font-medium">Código</label><input id="rp-code" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="Ex.: 6206088" required></div>
                    <div><label class="text-sm font-medium">NCM</label><input id="rp-ncm" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="Ex.: 8443.99.41"></div>
                </div>
                <div><label class="text-sm font-medium">Descrição</label><input id="rp-desc" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="Ex.: Mecanismo Fujitsu c/proteção"></div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div><label class="text-sm font-medium">Valor item</label><input id="rp-price" inputmode="decimal" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="Ex.: 670,65"></div>
                    <div><label class="text-sm font-medium">IPI %</label><input id="rp-ipi" inputmode="decimal" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="Ex.: 6,5"></div>
                    <div class="flex items-end"><button class="w-full rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Salvar peça</button></div>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Import CSV --}}
    <div id="modal-import-parts" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm">
        <div class="absolute left-1/2 top-1/2 w-[min(720px,96vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">Cadastro em massa (CSV)</h2>
                <button data-close-import class="rounded-lg p-2 hover:bg-slate-100" aria-label="Fechar">
                    <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="mt-3 text-sm text-slate-600 space-y-2">
                <p>Importe um <strong>.csv</strong> com colunas: <code>codigo, descricao, ncm, valor, ipi</code>.</p>
                <div class="flex gap-2">
                    <button type="button" id="btn-dl-template" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 font-medium text-slate-700 hover:bg-slate-50">Baixar modelo CSV</button>
                </div>
            </div>

            <div class="mt-4">
                <input id="csv-file" type="file" accept=".csv,text/csv" class="block w-full text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-blue-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-blue-700">
            </div>

            <div id="csv-summary" class="mt-4 hidden rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm">
                <div class="grid gap-2 sm:grid-cols-3">
                    <div><span class="text-slate-500">Linhas válidas:</span> <span id="sum-valid" class="font-semibold">0</span></div>
                    <div><span class="text-slate-500">Novos:</span> <span id="sum-new" class="font-semibold">0</span></div>
                    <div><span class="text-slate-500">Atualizados:</span> <span id="sum-upd" class="font-semibold">0</span></div>
                </div>
                <div class="mt-2 text-rose-600" id="sum-errors"></div>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button data-close-import class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancelar</button>
                <button id="btn-import-confirm" disabled class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50">Concluir importação</button>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div id="toast" class="pointer-events-none fixed bottom-5 right-5 z-50 hidden">
        <div class="rounded-xl bg-slate-900/90 px-4 py-3 text-sm font-medium text-white shadow-lg">Mensagem</div>
    </div>

    @include('layouts.common.modal.modal_delete')
@endsection

@push('scripts')
    <script type="module" src="{{ asset('assets/js/template/views/part-orders/part-order-index.js') }}"></script>
@endpush
