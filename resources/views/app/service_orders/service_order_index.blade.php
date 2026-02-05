@extends('layouts.templates.template')

@section('content')
    @push('styles')
        <style>
            #signature-actions::backdrop {
                background: rgba(2, 6, 23, .55);
            }

            body { overflow: hidden; }

        </style>
    @endpush

    <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mt-4">
            <h1 class="text-xl font-semibold">Ordens de serviço</h1>

            <div class="ml-auto flex items-center gap-2 shrink-0">
                <a href="{{ route('service-order.create') }}"
                   id="btn-add"
                   class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-800">
                    Nova OS
                </a>

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

        {{-- busca --}}
        <div class="mt-4">
            <div class="relative w-full max-w-xl">
                <input id="search"
                       placeholder="Buscar por nº, cliente ..."
                       class="w-full rounded-xl border border-slate-300 bg-white pl-10 pr-3 py-2.5 text-sm placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none"/>
                <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="M21 21l-4.3-4.3"/>
                </svg>
            </div>

            {{-- filtros de status (aba) --}}
            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                <button data-status-filter=""
                        class="px-3 py-1.5 rounded-full border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 active-status">
                    Todas
                </button>
                <button data-status-filter="draft"
                        class="px-3 py-1.5 rounded-full border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100">
                    Rascunho
                </button>
                <button data-status-filter="pending"
                        class="px-3 py-1.5 rounded-full border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100">
                    Pendente
                </button>
                <button data-status-filter="approved"
                        class="px-3 py-1.5 rounded-full border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100">
                    Aprovada
                </button>
                <button data-status-filter="nf_emitida"
                        class="px-3 py-1.5 rounded-full border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100">
                    NF EMITIDA
                </button>
                <button data-status-filter="rejected"
                        class="px-3 py-1.5 rounded-full border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100">
                    Rejeitada
                </button>
            </div>
        </div>

        {{-- tabela --}}
        <div id="table-scroll"
             class="mt-5 mb-8 rounded-2xl border border-slate-200 bg-white shadow-sm overflow-auto">
            <table class="min-w-full text-sm">
                <thead class="sticky top-0 z-10 text-center text-slate-600 bg-blue-50">
                <tr>
                    <th class="px-6 py-4 first:rounded-tl-2xl text-left">OS</th>
                    <th class="px-3 py-4 text-left">Cliente</th>
                    <th class="px-3 py-4 text-center">Total (R$)</th>
                    <th class="px-3 py-4 text-center">Status</th>
                    <th class="px-3 py-4 text-center">Data</th>
                    <th class="px-6 py-4 text-center last:rounded-tr-2xl">Ações</th>
                </tr>
                </thead>
                <tbody id="tbody" class="divide-y divide-slate-100"></tbody>
            </table>
        </div>
    </div>

    <dialog id="send-email"
            class="fixed inset-0 size-auto max-h-none max-w-none bg-transparent backdrop:bg-transparent">
        <div class="fixed inset-0 bg-slate-900/50"></div>

        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div
                class="relative w-full sm:max-w-lg transform overflow-hidden rounded-2xl bg-white text-left shadow-xl border border-slate-200">

                <div class="px-5 pb-4 sm:p-6 sm:pb-4">

                    <div id="email-feedback"
                         style="display:none; margin-bottom: 15px; padding:10px 12px; border-radius:12px; font-size:12px;">
                        <strong id="email-feedback-title"></strong>
                        <div id="email-feedback-msg" style="margin-top:4px;"></div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="flex size-10 items-center justify-center rounded-full bg-blue-50 text-blue-700">
                            ✉️
                        </div>


                        <div class="flex-1">
                            <h3 class="text-base font-semibold text-slate-900">Enviar OS por e-mail</h3>
                            <p class="mt-1 text-sm text-slate-500">PDF será anexado automaticamente.</p>

                            <div class="mt-4 grid gap-3">
                                <div>
                                    <label class="block text-sm text-slate-600 mb-1">Para</label>
                                    <input id="mail_to" type="email"
                                           class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 focus:bg-white focus:border-sky-300 focus:ring-2 focus:ring-sky-100 outline-none"
                                           placeholder="cliente@empresa.com"/>
                                    <p class="mt-1 text-xs text-slate-400">Se vazio, usa o e-mail cadastrado do
                                        cliente.</p>
                                </div>

                                <div>
                                    <label class="block text-sm text-slate-600 mb-1">Assunto</label>
                                    <input id="mail_subject" type="text"
                                           class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 focus:bg-white focus:border-sky-300 focus:ring-2 focus:ring-sky-100 outline-none"/>
                                </div>

                                <div>
                                    <label class="block text-sm text-slate-600 mb-1">Mensagem</label>
                                    <textarea id="mail_message"
                                              class="w-full min-h-[90px] resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 focus:bg-white focus:border-sky-300 focus:ring-2 focus:ring-sky-100 outline-none"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="bg-slate-50/60 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 gap-2">
                    <button type="button" id="send-email-yes"
                            class="inline-flex w-full justify-center rounded-2xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 sm:ml-3 sm:w-auto">
                        Enviar
                    </button>

                    <button type="button" id="send-email-no"
                            class="mt-2 inline-flex w-full justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 sm:mt-0 sm:w-auto">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </dialog>

    <dialog id="signature-actions"
            class="w-full max-w-xl rounded-3xl p-0 overflow-hidden shadow-2xl">
        <div class="p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 id="sig-title" class="text-base font-semibold text-slate-900">Assinatura Digital</h3>
                    <p id="sig-subtitle" class="mt-1 text-sm text-slate-600">
                        Escolha como deseja coletar a assinatura.
                    </p>
                </div>

                <button type="button" id="sig-close"
                        class="rounded-xl p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                    ✕
                </button>
            </div>

            <div id="sig-feedback"
                 class="mt-4 hidden rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700"></div>

            <div class="mt-5 grid gap-3 sm:grid-cols-1">
                <button type="button" id="sig-email"
                        class="flex flex-col items-start gap-1 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-left text-xs hover:border-brand-300 hover:bg-brand-50/60 disabled:opacity-60 disabled:cursor-not-allowed">
                    <span class="font-semibold text-slate-800">Enviar para e-mail</span>
                    <span class="text-[11px] text-slate-500">Link de assinatura digital por e-mail do cliente.</span>
                </button>

                <button type="button" id="sig-tablet"
                        class="flex flex-col items-start gap-1 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-left text-xs hover:border-brand-300 hover:bg-brand-50/60">
                    <span class="font-semibold text-slate-800">Assinar no tablet</span>
                    <span class="text-[11px] text-slate-500">Abrir área de assinatura na tela.</span>
                </button>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="button" id="sig-cancel"
                        class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                    Cancelar
                </button>
            </div>
        </div>
    </dialog>

    <!-- Modal: editar OS aprovada -->
    <dialog id="confirm-approved-edit" class="rounded-2xl p-0 backdrop:bg-black/40">
        <div class="w-[520px] max-w-[90vw] bg-white rounded-2xl overflow-hidden">
            <div class="p-5 border-b border-slate-200">
                <div class="text-base font-semibold text-slate-900">Atenção</div>
                <div class="text-sm text-slate-600 mt-1">
                    Se você alterar algo, esta OS volta para <b>PENDENTE</b>. Continuar?
                </div>
            </div>
            <div class="p-4 flex justify-end gap-2">
                <button id="approved-edit-no" class="rounded-xl border border-slate-200 px-4 py-2 text-sm">Cancelar</button>
                <button id="approved-edit-yes" class="rounded-xl bg-slate-900 px-4 py-2 text-sm text-white">Continuar</button>
            </div>
        </div>
    </dialog>

    <!-- Modal: recusar OS -->
    <dialog id="confirm-reject" class="rounded-2xl p-0 backdrop:bg-black/40">
        <div class="w-[520px] max-w-[90vw] bg-white rounded-2xl overflow-hidden">
            <div class="p-5 border-b border-slate-200">
                <div class="text-base font-semibold text-slate-900">Recusar OS</div>
                <div class="text-sm text-slate-600 mt-1">
                    Marcar esta OS como <b>RECUSADA</b>?
                </div>
                <div id="reject-meta" class="mt-2 text-xs text-slate-500"></div>
            </div>
            <div class="p-4 flex justify-end gap-2">
                <button id="reject-no" class="rounded-xl border border-slate-200 px-4 py-2 text-sm">Cancelar</button>
                <button id="reject-yes" class="rounded-xl bg-rose-600 px-4 py-2 text-sm text-white">Recusar</button>
            </div>
        </div>
    </dialog>


    @include('layouts.common.modal.modal_delete')
@endsection

@push('scripts')
    <script type="module" src="{{ asset('assets/js/template/views/service-orders/service-order-index.js') }}"></script>
@endpush
