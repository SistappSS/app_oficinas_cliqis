@extends('layouts.templates.template')

@section('content')
    <main class="mx-auto max-w-6xl px-4 sm:px-6 pb-32 pt-6">

        {{-- HERO --}}
        <section>
            <div
                class="relative overflow-hidden rounded-[28px] px-6 py-5 md:px-8 md:py-6 text-white shadow-[0_24px_70px_rgba(37,99,235,0.25)] bg-gradient-to-tr from-sky-400 via-blue-600 to-indigo-800"
            >
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <div class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-[11px] font-medium tracking-wide">
                            <span class="opacity-90">OS</span>
                            <span class="mx-1 opacity-60">•</span>
                            <span class="opacity-90">
                                #{{ $serviceOrder->code ?? '000001' }}
                            </span>
                        </div>
                        <h1 class="mt-3 text-2xl md:text-3xl font-extrabold tracking-tight">
                            {{ isset($serviceOrder) ? 'Editar ordem de serviço' : 'Nova ordem de serviço' }}
                        </h1>
                        <p class="mt-1 text-sm md:text-base text-sky-50/90">
                            Preencha e finalize. Mobile-first.
                        </p>
                    </div>

                    <div class="flex flex-col items-end gap-2">
                        <span class="inline-flex items-center rounded-full bg-white/20 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide">
                            {{ $serviceOrder->status_label ?? 'rascunho' }}
                        </span>
                    </div>
                </div>
            </div>
        </section>

        {{-- FORM OS --}}
        <form id="service-order-form" class="mt-6 space-y-6">
            {{-- ORDEM DE SERVIÇO --}}
            <section class="bg-white rounded-[24px] border border-slate-100 shadow-[0_18px_40px_rgba(15,23,42,0.04)] p-5 md:p-7">
                <h2 class="font-semibold text-slate-900 mb-5 text-lg">
                    Ordem de serviço
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Nº</label>
                        <input
                            id="so_number"
                            name="number"
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-sky-300 focus:ring-2 focus:ring-sky-100 focus:outline-none transition"
                            placeholder="000001"
                            value="{{ old('number', $serviceOrder->number ?? '') }}"
                            disabled
                        />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-slate-600 mb-1">Data</label>
                        <input
                            type="date"
                            id="so_date"
                            name="date"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-sky-300 focus:ring-2 focus:ring-sky-100 focus:outline-none transition"
                            value="{{ old('date', optional($serviceOrder->date ?? null)->format('Y-m-d')) }}"
                        />
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">
                            Solicitante
                        </label>
                        <input
                            id="requester_name"
                            name="requester_name"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-sky-300 focus:ring-2 focus:ring-sky-100 focus:outline-none transition"
                            placeholder="Nome do solicitante"
                            value="{{ old('requester_name', $serviceOrder->requester_name ?? '') }}"
                        />
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">
                            Responsável pelo serviço
                        </label>
                        <input
                            id="responsible_name"
                            name="responsible_name"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-sky-300 focus:ring-2 focus:ring-sky-100 focus:outline-none transition"
                            placeholder="Nome do responsável"
                            value="{{ old('responsible_name', $serviceOrder->responsible_name ?? '') }}"
                        />
                    </div>
                </div>
            </section>

            {{-- CLIENTE --}}
            <section class="bg-white rounded-[24px] border border-slate-100 shadow-[0_18px_40px_rgba(15,23,42,0.04)] p-5 md:p-7">
                <h2 class="font-semibold text-slate-900 mb-5 text-lg">Cliente</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm text-slate-600 mb-1">
                            Cliente / Razão Social *
                        </label>
                        <input
                            id="customer_name"
                            name="customer_name"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-sky-300 focus:ring-2 focus:ring-sky-100 focus:outline-none transition"
                            placeholder="Ex.: BAR DO SANTO"
                            value="{{ old('customer_name', $serviceOrder->customer_name ?? '') }}"
                        />
                    </div>

                    <div>
                        <label class="block text-sm text-slate-600 mb-1">
                            CNPJ / CPF
                        </label>
                        <input
                            id="customer_document"
                            name="customer_document"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-sky-300 focus:ring-2 focus:ring-sky-100 focus:outline-none transition"
                            placeholder="00.000.000/0000-00"
                            value="{{ old('customer_document', $serviceOrder->customer_document ?? '') }}"
                        />
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">
                            Contato
                        </label>
                        <input
                            id="customer_contact"
                            name="customer_contact"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-sky-300 focus:ring-2 focus:ring-sky-100 focus:outline-none transition"
                            placeholder="Nome do contato"
                            value="{{ old('customer_contact', $serviceOrder->customer_contact ?? '') }}"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">
                            Telefone
                        </label>
                        <input
                            id="customer_phone"
                            name="customer_phone"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-sky-300 focus:ring-2 focus:ring-sky-100 focus:outline-none transition"
                            placeholder="(11) 99999-9999"
                            value="{{ old('customer_phone', $serviceOrder->customer_phone ?? '') }}"
                        />
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">
                            Chamado / Ticket
                        </label>
                        <input
                            id="ticket_number"
                            name="ticket_number"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-sky-300 focus:ring-2 focus:ring-sky-100 focus:outline-none transition"
                            placeholder="Opcional"
                            value="{{ old('ticket_number', $serviceOrder->ticket_number ?? '') }}"
                        />
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm text-slate-600 mb-1">
                            Endereço
                        </label>
                        <input
                            id="address"
                            name="address"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-sky-300 focus:ring-2 focus:ring-sky-100 focus:outline-none transition"
                            placeholder="Rua / nº / complemento"
                            value="{{ old('address', $serviceOrder->address ?? '') }}"
                        />
                    </div>

                    <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm text-slate-600 mb-1">
                                Município
                            </label>
                            <input
                                id="city"
                                name="city"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-sky-300 focus:ring-2 focus:ring-sky-100 focus:outline-none transition"
                                placeholder="Cidade"
                                value="{{ old('city', $serviceOrder->city ?? '') }}"
                            />
                        </div>
                        <div>
                            <label class="block text-sm text-slate-600 mb-1">UF</label>
                            <input
                                id="state"
                                name="state"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-sky-300 focus:ring-2 focus:ring-sky-100 focus:outline-none transition"
                                placeholder="SP"
                                maxlength="2"
                                value="{{ old('state', $serviceOrder->state ?? '') }}"
                            />
                        </div>
                        <div>
                            <label class="block text-sm text-slate-600 mb-1">
                                CEP
                            </label>
                            <input
                                id="postal_code"
                                name="postal_code"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-sky-300 focus:ring-2 focus:ring-sky-100 focus:outline-none transition"
                                placeholder="00000-000"
                                value="{{ old('postal_code', $serviceOrder->postal_code ?? '') }}"
                            />
                        </div>
                    </div>
                </div>
            </section>

            {{-- EQUIPAMENTOS ATENDIDOS --}}
            <section
                id="so-equipments-block"
                class="bg-white rounded-[24px] border border-slate-100 shadow-[0_18px_40px_rgba(15,23,42,0.04)] p-5 md:p-7"
            >
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-slate-900 text-lg">
                        Equipamentos atendidos
                    </h2>
                    <button
                        type="button"
                        id="btn-add-equipment-row"
                        class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-700 hover:border-sky-300 hover:bg-sky-50/70 transition"
                    >
                        <span class="text-base leading-none">+</span>
                        <span>Adicionar equipamento</span>
                    </button>
                </div>

                <div id="equipment-rows" class="space-y-4">
                    {{-- linhas de equipamento serão geradas via JS --}}
                </div>
            </section>

            {{-- SERVIÇOS (itens) --}}
            <section
                id="so-services-block"
                class="bg-white rounded-[24px] border border-slate-100 shadow-[0_18px_40px_rgba(15,23,42,0.04)] p-5 md:p-7"
            >
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-slate-900 text-lg">Serviços</h2>
                    <button
                        type="button"
                        id="btn-add-service-row"
                        class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-700 hover:border-sky-300 hover:bg-sky-50/70 transition"
                    >
                        <span class="text-base leading-none">+</span>
                        <span>Adicionar serviço</span>
                    </button>
                </div>

                <div id="service-rows" class="space-y-3"></div>

                <div class="mt-5 flex items-center justify-end gap-4">
                    <div class="text-sm text-slate-600">Subtotal serviços</div>
                    <div id="subtotal-services" class="text-base font-semibold">
                        R$ 0,00
                    </div>
                </div>
            </section>

            {{-- PEÇAS --}}
            <section
                id="so-parts-block"
                class="bg-white rounded-[24px] border border-slate-100 shadow-[0_18px_40px_rgba(15,23,42,0.04)] p-5 md:p-7"
            >
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-slate-900 text-lg">Peças</h2>
                    <button
                        type="button"
                        id="btn-add-part-row"
                        class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-700 hover:border-sky-300 hover:bg-sky-50/70 transition"
                    >
                        <span class="text-base leading-none">+</span>
                        <span>Adicionar peça</span>
                    </button>
                </div>

                <div id="part-rows" class="space-y-3"></div>

                <div class="mt-5 flex items-center justify-end gap-4">
                    <div class="text-sm text-slate-600">Subtotal peças</div>
                    <div id="subtotal-parts" class="text-base font-semibold">
                        R$ 0,00
                    </div>
                </div>
            </section>

            {{-- ATENDIMENTO / PAGAMENTO (hora trabalhada + obs) --}}
            <section
                id="so-labor-block"
                class="bg-white rounded-[24px] border border-slate-100 shadow-[0_18px_40px_rgba(15,23,42,0.04)] p-5 md:p-7"
            >
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-slate-900 text-lg">
                        Atendimento / Pagamento
                    </h2>
                    <button
                        type="button"
                        id="btn-add-labor-row"
                        class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-700 hover:border-sky-300 hover:bg-sky-50/70 transition"
                    >
                        <span class="text-base leading-none">+</span>
                        <span>Registrar hora</span>
                    </button>
                </div>

                <div class="grid md:grid-cols-12 gap-3 items-end mb-5">
                    <div class="md:col-span-3">
                        <label class="block text-sm text-slate-600 mb-1">
                            Valor hora (R$)
                        </label>
                        <input
                            type="number"
                            step="0.01"
                            id="hourly_rate"
                            name="hourly_rate"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-right text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-sky-300 focus:ring-2 focus:ring-sky-100 focus:outline-none transition"
                            placeholder="0,00"
                            value="{{ old('hourly_rate', $serviceOrder->hourly_rate ?? '') }}"
                        />
                    </div>
                    <div class="md:col-span-9 flex flex-wrap gap-4 md:justify-end">
                        <div class="text-sm text-slate-600 self-center">
                            Total mão de obra:
                        </div>
                        <div id="subtotal-labor" class="text-base font-semibold self-center">
                            R$ 0,00
                        </div>
                    </div>
                </div>

                <div id="labor-rows" class="space-y-3"></div>

                <div class="border-t border-slate-100 my-5"></div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-1">
                        <label class="block text-sm text-slate-600 mb-1">
                            Condição de pagamento
                        </label>
                        <select
                            id="payment_terms"
                            name="payment_terms"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 focus:bg-white focus:border-sky-300 focus:ring-2 focus:ring-sky-100 focus:outline-none transition"
                        >
                            <option value="">Selecione...</option>
                            <option value="pix">PIX / Depósito</option>
                            <option value="boleto">Boleto</option>
                            <option value="dinheiro">Dinheiro</option>
                            <option value="avista">À vista</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-slate-600 mb-1">
                            Observações
                        </label>
                        <textarea
                            id="notes"
                            name="notes"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-sky-300 focus:ring-2 focus:ring-sky-100 focus:outline-none transition min-h-[80px] resize-none"
                            placeholder="Pagamento combinado, observações gerais de atendimento, etc."
                        >{{ old('notes', $serviceOrder->notes ?? '') }}</textarea>
                    </div>
                </div>
            </section>

            {{-- TOTAIS --}}
            <section
                id="so-totals-block"
                class="bg-white rounded-[24px] border border-slate-100 shadow-[0_18px_40px_rgba(15,23,42,0.04)] p-5 md:p-7"
            >
                <h2 class="font-semibold text-slate-900 mb-4 text-lg">Totais</h2>

                <div class="grid gap-3 md:grid-cols-3">
                    <div class="rounded-2xl bg-slate-50/80 border border-slate-100 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600 text-sm">Serviços</span>
                            <span id="total-services" class="font-semibold">R$ 0,00</span>
                        </div>
                    </div>
                    <div class="rounded-2xl bg-slate-50/80 border border-slate-100 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600 text-sm">Peças</span>
                            <span id="total-parts" class="font-semibold">R$ 0,00</span>
                        </div>
                    </div>
                    <div class="rounded-2xl bg-slate-50/80 border border-slate-100 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600 text-sm">Mão de obra</span>
                            <span id="total-labor" class="font-semibold">R$ 0,00</span>
                        </div>
                    </div>
                </div>

                <div class="mt-5 grid gap-3 md:grid-cols-3">
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">
                            Desconto (R$)
                        </label>
                        <input
                            type="number"
                            step="0.01"
                            id="discount"
                            name="discount"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-right text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-sky-300 focus:ring-2 focus:ring-sky-100 focus:outline-none transition"
                            placeholder="0,00"
                            value="{{ old('discount', $serviceOrder->discount ?? '') }}"
                        />
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">
                            Acréscimo (R$)
                        </label>
                        <input
                            type="number"
                            step="0.01"
                            id="addition"
                            name="addition"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-right text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-sky-300 focus:ring-2 focus:ring-sky-100 focus:outline-none transition"
                            placeholder="0,00"
                            value="{{ old('addition', $serviceOrder->addition ?? '') }}"
                        />
                    </div>
                    <div class="rounded-2xl bg-slate-900 text-white px-5 py-3 flex items-center justify-between">
                        <span class="text-sm font-medium text-sky-100">
                            Total geral
                        </span>
                        <span id="total-general" class="text-lg font-bold">
                            R$ 0,00
                        </span>
                    </div>
                </div>
            </section>
        </form>

        {{-- BARRA INFERIOR FIXA --}}
        <div
            class="fixed bottom-0 left-0 right-0 z-50 bg-white/95 backdrop-blur border-t border-slate-200 shadow-[0_-18px_40px_rgba(15,23,42,0.12)]"
        >
            <div class="max-w-6xl mx-auto px-4 py-3 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div class="flex-1 flex flex-wrap gap-6 text-xs text-slate-600">
                    <div class="flex flex-col leading-4">
                        <span>Serviços</span>
                        <span id="bar-services" class="font-semibold text-slate-900 text-sm">
                            R$ 0,00
                        </span>
                    </div>
                    <div class="flex flex-col leading-4">
                        <span>Peças</span>
                        <span id="bar-parts" class="font-semibold text-slate-900 text-sm">
                            R$ 0,00
                        </span>
                    </div>
                    <div class="flex flex-col leading-4">
                        <span>Mão de obra</span>
                        <span id="bar-labor" class="font-semibold text-slate-900 text-sm">
                            R$ 0,00
                        </span>
                    </div>
                    <div class="flex flex-col leading-4">
                        <span>Total</span>
                        <span id="bar-total" class="font-semibold text-indigo-600 text-sm">
                            R$ 0,00
                        </span>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button
                        type="button"
                        id="btn-save-draft"
                        class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-800 hover:bg-slate-100"
                    >
                        💾
                        <span>Salvar</span>
                    </button>
                    <button
                        type="button"
                        id="btn-finish"
                        class="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700"
                    >
                        ✉️
                        <span>Finalizar</span>
                    </button>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('assets/js/template/views/service-orders/service-order-form.js') }}"></script>
@endpush
