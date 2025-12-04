@extends('layouts.templates.template')

@section('content')

    <style>
        #tbody td:last-child {
            white-space: nowrap;
        }

        #tbody td:last-child > .flex {
            gap: .375rem;
        }

        #tbody td:last-child [data-nf],
        #tbody td:last-child [data-email],
        #tbody td:last-child [data-customer-area] {
            display: inline-grid;
            place-items: center;
            width: 36px;
            height: 36px;
            border-radius: 12px;
            border: 1px solid rgb(226 232 240);
            background: #fff;
            color: rgb(71 85 105);
            padding: 0;
            transition: background .15s, border-color .15s, color .15s, box-shadow .15s, transform .12s;
        }

        #tbody td:last-child [data-nf]:hover {
            background: rgb(239 246 255);
            border-color: rgb(191 219 254);
            color: rgb(29 78 216);
            box-shadow: 0 1px 2px rgba(2, 6, 23, .08);
            transform: translateY(-1px);
        }

        #tbody td:last-child [data-email]:hover {
            background: rgb(240 253 250);
            border-color: rgb(167 243 208);
            color: rgb(4 120 87);
            box-shadow: 0 1px 2px rgba(2, 6, 23, .08);
            transform: translateY(-1px);
        }

        #tbody td:last-child [data-customer-area]:hover {
            background: rgb(248 250 252);
            border-color: rgb(203 213 225);
            color: rgb(15 23 42);
            box-shadow: 0 1px 2px rgba(2, 6, 23, .08);
            transform: translateY(-1px);
        }

        #tbody td:last-child svg {
            width: 16px;
            height: 16px;
        }
    </style>

    <main class="mx-auto max-w-7xl px-4 sm:px-6 pb-10 lg:pb-14">

        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold">Cobranças de Ordens de Serviço</h1>
                    <p class="text-sm text-slate-600">
                        OS aprovadas aguardando faturamento / NF.
                    </p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    {{-- Espaço pra futuros botões (ex.: exportar) --}}
                </div>
            </div>

            <!-- KPIs simples -->
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Total aprovado (OS)</p>
                    <p id="kpi-approved" class="mt-3 text-3xl font-bold">R$ 0,00</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Qtd. OS aprovadas</p>
                    <p id="kpi-count" class="mt-3 text-3xl font-bold">0</p>
                </div>
            </div>

            <!-- busca -->
            <div class="relative hidden sm:block">
                <input id="search" placeholder="Buscar cliente, OS, ticket..."
                       class="w-[22rem] rounded-xl border border-slate-300 bg-white pl-10 pr-3 py-2.5 text-sm placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none"/>
                <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="M21 21l-4.3-4.3"/>
                </svg>
            </div>
        </div>

        <!-- Tabela -->
        <div class="mt-5 mb-8 rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto ">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-slate-600 bg-blue-50">
                    <tr>
                        <th class="px-6 py-4 first:rounded-tl-2xl">Cliente</th>
                        <th class="px-3 py-4">Nº OS</th>
                        <th class="px-3 py-4">Data OS</th>
                        <th class="px-3 py-4">Ticket</th>
                        <th class="px-3 py-4 text-right">Valor total</th>
                        <th class="px-3 py-4 text-center">Status OS</th>
                        <th class="px-6 py-4 text-right last:rounded-tr-2xl">Ações</th>
                    </tr>
                    </thead>
                    <tbody id="tbody" class="divide-y divide-slate-100"></tbody>
                </table>
            </div>
        </div>
    </main>

    {{-- MODAL GERAR NF / LANÇAMENTO RECEBÍVEL --}}
    <div id="modal-nf" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm">
        <div
            class="absolute left-1/2 top-1/2 w-[min(520px,92vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Gerar NF / Cobrança</h2>
                    <p id="nf-os-label" class="text-xs text-slate-500 mt-1">
                        OS -
                    </p>
                </div>
                <button id="nf-close" class="rounded-lg p-2 hover:bg-slate-100">
                    <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form id="billing-form" class="mt-4 space-y-3">
                @csrf

                <input type="hidden" name="service_order_id" id="billing-os-id">

                <div class="grid sm:grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm font-medium">Primeiro vencimento</label>
                        <input type="date" name="first_due_date" required
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5">
                    </div>
                    <div>
                        <label class="text-sm font-medium">Forma de pagamento</label>
                        <select id="nf-payment-method" required
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                            <option value="">Selecione...</option>
                            <option value="pix">Pix</option>
                            <option value="boleto">Boleto</option>
                            <option value="cartao_credito">Cartão de crédito</option>
                            <option value="cartao_debito">Cartão de débito</option>
                            <option value="transferencia">Transferência</option>
                            <option value="dinheiro">Dinheiro</option>
                        </select>
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm font-medium">Valor total (R$)</label>
                        <input id="nf-amount" type="number" min="0" step="0.01" readonly
                               class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"/>
                    </div>
                </div>

                <div class="mt-3">
                    <label class="flex items-center gap-2 text-sm font-medium">
                        <input type="checkbox" name="use_down_payment" id="use_down_payment"
                               class="h-4 w-4 rounded border-slate-300 text-blue-600">
                        Usar sinal (% do total)
                    </label>
                </div>

                <div id="down-payment-wrap" class="mt-3 hidden">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-sm font-medium">% de sinal</label>
                            <input type="number" name="down_payment_percent" min="1" max="99"
                                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5">
                        </div>
                        <div>
                            <label class="text-sm font-medium">Parcelas do restante</label>
                            <input type="number" name="remaining_installments" min="1"
                                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5">
                        </div>
                    </div>
                </div>

                <div id="no-down-payment-wrap" class="mt-3">
                    <label class="text-sm font-medium">Parcelas</label>
                    <input type="number" name="installments" min="1" value="1"
                           class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5">
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" id="nf-cancel"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                        Gerar NF (simulado)
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
{{--        <script>--}}
{{--            const form = document.getElementById('billing-form');--}}
{{--            const useDown = document.getElementById('use_down_payment');--}}
{{--            const wrapDown = document.getElementById('down-payment-wrap');--}}
{{--            const wrapNo   = document.getElementById('no-down-payment-wrap');--}}

{{--            useDown.addEventListener('change', () => {--}}
{{--                const on = useDown.checked;--}}
{{--                wrapDown.classList.toggle('hidden', !on);--}}
{{--                wrapNo.classList.toggle('hidden', on);--}}
{{--            });--}}

{{--            form.addEventListener('submit', async (e) => {--}}
{{--                e.preventDefault();--}}

{{--                const osId = document.getElementById('billing-os-id').value;--}}
{{--                const fd = new FormData(form);--}}
{{--                fd.set('use_down_payment', useDown.checked ? 1 : 0);--}}

{{--                const res = await fetch(`/service-orders/${osId}/billing/generate`, {--}}
{{--                    method: 'POST',--}}
{{--                    headers: {--}}
{{--                        'Accept': 'application/json',--}}
{{--                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''--}}
{{--                    },--}}
{{--                    body: fd--}}
{{--                });--}}

{{--                if (!res.ok) {--}}
{{--                    console.error(await res.text());--}}
{{--                    alert('Erro ao gerar cobrança.');--}}
{{--                    return;--}}
{{--                }--}}

{{--                const j = await res.json();--}}
{{--                if (!j.ok) {--}}
{{--                    alert(j.error || 'Erro ao gerar cobrança.');--}}
{{--                    return;--}}
{{--                }--}}

{{--                // sucesso → fecha modal, recarrega lista de invoices/OS--}}
{{--            });--}}
{{--        </script>--}}
        <script src="{{ asset('assets/js/template/views/service-orders/service-order-billing.js') }}"></script>
    @endpush

@endsection
