@extends('layouts.templates.template')

@section('content')
    <main class="mx-auto max-w-6xl px-4 sm:px-6 pb-32 pt-6">
        @php
            $technician     = $serviceOrder->technician ?? $defaultTechnician ?? null;
            $technicianName = $technician->full_name ?? auth()->user()->name;
            $technicianId   = $technician->id ?? null;

            $laborHourDefault = old(
                'hourly_rate',
                $serviceOrder->labor_hour_value
                    ?? ($technician->hourly_rate ?? null)
            );
        @endphp

        {{-- HERO --}}
        <section>
            <div
                class="relative overflow-hidden rounded-[28px] px-6 py-5 md:px-8 md:py-6 text-white shadow-[0_24px_70px_rgba(37,99,235,0.25)] bg-gradient-to-tr from-sky-400 via-blue-600 to-indigo-800">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <div class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-[11px] font-medium tracking-wide">
                            <span class="opacity-90">OS</span>
                            <span class="mx-1 opacity-60">•</span>
                            <span class="opacity-90">
                                #{{ $serviceOrder->code ?? ($nextOrderNumber ?? '000001') }}
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
                        <span
                            class="inline-flex items-center rounded-full bg-white/20 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide">
                            {{ $serviceOrder->status_label ?? 'rascunho' }}
                        </span>
                    </div>
                </div>
            </div>
        </section>

        {{-- FORM OS --}}
        <form id="service-order-form" class="mt-6 space-y-6">
            {{-- ORDEM DE SERVIÇO --}}
            <section
                class="bg-white rounded-[24px] border border-slate-100 shadow-[0_18px_40px_rgba(15,23,42,0.04)] p-5 md:p-7">
                <h2 class="font-semibold text-slate-900 mb-5 text-lg">
                    Ordem de serviço
                </h2>

                <!-- hidden com ID da OS (edição) -->
                <input type="hidden" id="service_order_id" value="{{ $serviceOrder->id ?? '' }}">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Nº</label>
                        <input
                            id="order_number_display"
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900"
                            value="{{ $serviceOrder->order_number ?? $displayOrderNumber ?? '' }}"
                            disabled
                        />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-slate-600 mb-1">Data</label>
                        <input
                            id="order_date"
                            type="date"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900"
                            value="{{ now()->format('Y-m-d') }}"
                        />
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Solicitante</label>
                        <input
                            id="requester_name"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm"
                            placeholder="Nome do solicitante"
                        />
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Responsável pelo serviço</label>
                        <input
                            id="service_responsible"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm"
                            value="{{ $technicianName }}"
                            disabled
                        />
                        <input type="hidden" id="technician_id" value="{{ $technicianId }}">
                    </div>
                </div>
            </section>

            {{-- CLIENTE --}}
            <section
                class="bg-white rounded-[24px] border border-slate-100 shadow-[0_18px_40px_rgba(15,23,42,0.04)] p-5 md:p-7">
                <h2 class="font-semibold text-slate-900 mb-5 text-lg">Cliente</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm text-slate-600 mb-1">
                            Cliente / Razão Social *
                        </label>
                        <input
                            id="os_client_name"
                            name="customer_name"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-sky-300 focus:ring-2 focus:ring-sky-100 focus:outline-none transition"
                            placeholder="Ex.: BAR DO SANTO"
                            value="{{ old('customer_name', $serviceOrder->customer_name ?? '') }}"
                        />
                        <!-- dropdown de resultados -->
                        <div id="os_client_results"
                             class="absolute z-30 mt-1 w-full rounded-2xl border border-slate-200 bg-white shadow-lg hidden max-h-64 overflow-auto text-xs">
                            <!-- preenchido via JS -->
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-600 mb-1">
                            CNPJ / CPF
                        </label>
                        <input
                            id="os_client_document"
                            name="customer_document"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-sky-300 focus:ring-2 focus:ring-sky-100 focus:outline-none transition"
                            placeholder="00.000.000/0000-00"
                            value="{{ old('customer_document', $serviceOrder->customer_document ?? '') }}"
                        />
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">
                            E-mail
                        </label>
                        <input
                            id="os_client_email"
                            name="email"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-sky-300 focus:ring-2 focus:ring-sky-100 focus:outline-none transition"
                            placeholder="Endereço de e-mail"
                            value="{{ old('email', $serviceOrder->email ?? '') }}"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">
                            Telefone
                        </label>
                        <input
                            id="os_client_phone"
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
                            id="os_client_address"
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
                                id="os_client_city"
                                name="city"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-sky-300 focus:ring-2 focus:ring-sky-100 focus:outline-none transition"
                                placeholder="Cidade"
                                value="{{ old('city', $serviceOrder->city ?? '') }}"
                            />
                        </div>
                        <div>
                            <label class="block text-sm text-slate-600 mb-1">UF</label>
                            <input
                                id="os_client_state"
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
                                id="os_client_zip"
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
                    <h2 class="font-semibold text-slate-900 text-lg">Equipamentos atendidos</h2>
                    <button
                        type="button"
                        id="btn-add-equipment"
                        class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-700 hover:border-sky-300 hover:bg-sky-50/70 transition"
                    >
                        <span class="text-base leading-none">+</span>
                        <span>Adicionar equipamento</span>
                    </button>
                </div>

                <div id="equipment-list" class="space-y-4"></div>
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
                        id="btn-add-service"
                        class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-700 hover:border-sky-300 hover:bg-sky-50/70 transition"
                    >
                        <span class="text-base leading-none">+</span>
                        <span>Adicionar serviço</span>
                    </button>
                </div>

                <div id="service-list" class="space-y-3"></div>

                <div class="mt-5 flex items-center justify-end gap-4">
                    <div class="text-sm text-slate-600">Subtotal serviços</div>
                    <div id="services-subtotal-display" class="text-base font-semibold">
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
                        id="btn-add-part"
                        class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-700 hover:border-sky-300 hover:bg-sky-50/70 transition"
                    >
                        <span class="text-base leading-none">+</span>
                        <span>Adicionar peça</span>
                    </button>
                </div>

                <div id="part-list" class="space-y-3"></div>

                <div class="mt-5 flex items-center justify-end gap-4">
                    <div class="text-sm text-slate-600">Subtotal peças</div>
                    <div id="parts-subtotal-display" class="text-base font-semibold">
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
                    <h2 class="font-semibold text-slate-900 text-lg">Atendimento / Pagamento</h2>
                    <button
                        type="button"
                        id="btn-add-labor"
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
                            id="labor_hour_value"
                            name="hourly_rate"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-right text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-sky-300 focus:ring-2 focus:ring-sky-100 focus:outline-none transition"
                            placeholder="0,00"
                            value="{{ $laborHourDefault }}"
                        />
                    </div>
                    <div class="md:col-span-9 flex flex-wrap gap-4 md:justify-end">
                        <div class="text-sm text-slate-600 self-center">Total mão de obra:</div>
                        <div id="labor-total-amount-display" class="text-base font-semibold self-center">
                            R$ 0,00
                        </div>
                    </div>
                </div>

                <div id="labor-list" class="space-y-3"></div>

                <div class="border-t border-slate-100 my-5"></div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-1">
                        <label class="block text-sm text-slate-600 mb-1">
                            Condição de pagamento
                        </label>
                        <select
                            id="payment_condition"
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
                            id="payment_notes"
                            name="notes"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-sky-300 focus:ring-2 focus:ring-sky-100 focus:outline-none transition min-h-[80px] resize-none"
                            placeholder="Pagamento combinado, observações gerais de atendimento, etc."
                        >{{ old('notes', $serviceOrder->notes ?? '') }}</textarea>
                    </div>
                </div>
            </section>

            {{-- TOTAIS --}}
            <section id="so-totals-block"
                     class="bg-white rounded-[24px] border border-slate-100 shadow-[0_18px_40px_rgba(15,23,42,0.04)] p-5 md:p-7">
                <h2 class="font-semibold text-slate-900 mb-4 text-lg">Totais</h2>

                <div class="grid gap-3 md:grid-cols-3">
                    <div class="rounded-2xl bg-slate-50/80 border border-slate-100 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600 text-sm">Serviços</span>
                            <span id="box-services-value" class="font-semibold">R$ 0,00</span>
                        </div>
                    </div>
                    <div class="rounded-2xl bg-slate-50/80 border border-slate-100 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600 text-sm">Peças</span>
                            <span id="box-parts-value" class="font-semibold">R$ 0,00</span>
                        </div>
                    </div>
                    <div class="rounded-2xl bg-slate-50/80 border border-slate-100 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600 text-sm">Mão de obra</span>
                            <span id="box-labor-value" class="font-semibold">R$ 0,00</span>
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
                        <span id="grand_total_display" class="text-lg font-bold">
                            R$ 0,00
                        </span>
                    </div>
                </div>
            </section>
        </form>

        {{-- BARRA INFERIOR FIXA --}}
        <div
            class="fixed bottom-0 left-0 right-0 z-50 bg-white/95 backdrop-blur border-t border-slate-200 shadow-[0_-18px_40px_rgba(15,23,42,0.12)]">
            <div class="max-w-6xl mx-auto px-4 py-3 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div class="flex-1 flex flex-wrap gap-6 text-xs text-slate-600">
                    <div class="flex flex-col leading-4">
                        <span>Serviços</span>
                        <span id="footer-services-value" class="font-semibold text-slate-900 text-sm">
                            R$ 0,00
                        </span>
                    </div>
                    <div class="flex flex-col leading-4">
                        <span>Peças</span>
                        <span id="footer-parts-value" class="font-semibold text-slate-900 text-sm">
                            R$ 0,00
                        </span>
                    </div>
                    <div class="flex flex-col leading-4">
                        <span>Mão de obra</span>
                        <span id="footer-labor-value" class="font-semibold text-slate-900 text-sm">
                            R$ 0,00
                        </span>
                    </div>
                    <div class="flex flex-col leading-4">
                        <span>Total</span>
                        <span id="footer-grand-value" class="font-semibold text-indigo-600 text-sm">
                            R$ 0,00
                        </span>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button
                        type="button"
                        id="btn-save-os"
                        class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-800 hover:bg-slate-100"
                    >
                        💾
                        <span>Salvar</span>
                    </button>
                    <button
                        type="button"
                        id="btn-finish-os"
                        class="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700"
                    >
                        ✉️
                        <span>Finalizar</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Modal salvar cadastros --}}
        <div id="os-save-modal"
             class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5">
                    <h2 class="text-sm font-semibold text-slate-900">
                        Salvar dados nos cadastros?
                    </h2>
                    <button type="button" data-os-save-cancel
                            class="rounded-full p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                        ✕
                    </button>
                </div>

                <div class="px-5 py-4 space-y-4 text-sm">
                    <p class="text-slate-600">
                        Além de salvar a OS, você deseja aproveitar as informações digitadas
                        para criar/atualizar cadastros?
                    </p>

                    <div class="space-y-2">
                        <label class="flex items-start gap-2 text-sm text-slate-700">
                            <input type="checkbox" class="mt-1" id="save_customer">
                            <span>Cliente (cliente / razão social, contato, endereço)</span>
                        </label>

                        <label class="flex items-start gap-2 text-sm text-slate-700">
                            <input type="checkbox" class="mt-1" id="save_technician">
                            <span>Técnico (funcionário responsável)</span>
                        </label>

                        <label class="flex items-start gap-2 text-sm text-slate-700">
                            <input type="checkbox" class="mt-1" id="save_services">
                            <span>Serviços (descrição, valor unitário)</span>
                        </label>

                        <label class="flex items-start gap-2 text-sm text-slate-700">
                            <input type="checkbox" class="mt-1" id="save_parts">
                            <span>Peças (código, descrição, valor unitário)</span>
                        </label>

                        <label class="flex items-start gap-2 text-sm text-slate-700">
                            <input type="checkbox" class="mt-1" id="save_equipments">
                            <span>Equipamentos atendidos</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-100 px-5 py-3">
                    <button type="button" data-os-save-cancel
                            class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button type="button" id="os-save-confirm"
                            class="inline-flex items-center rounded-xl bg-brand-600 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-700">
                        Salvar OS
                    </button>
                </div>
            </div>
        </div>

        {{-- Modal finalizar OS --}}
        <div id="os-finalize-modal"
             class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 backdrop-blur-sm">
            <div class="w-full max-w-lg rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5">
                    <h2 class="text-sm font-semibold text-slate-900">
                        Finalizar OS
                    </h2>
                    <button type="button" data-os-finalize-cancel
                            class="rounded-full p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                        ✕
                    </button>
                </div>

                <div class="px-5 py-4 space-y-4 text-sm">
                    <p class="text-slate-600">
                        Escolha como deseja finalizar esta ordem de serviço.
                    </p>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <button type="button" id="os-finalize-email"
                                class="flex flex-col items-start gap-1 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-left text-xs hover:border-brand-300 hover:bg-brand-50/60 disabled:opacity-60 disabled:cursor-not-allowed">
                            <span class="font-semibold text-slate-800">Enviar para e-mail</span>
                            <span class="text-[11px] text-slate-500">Link de assinatura por e-mail do cliente.</span>
                        </button>

                        <button type="button" id="os-finalize-tablet"
                                class="flex flex-col items-start gap-1 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-left text-xs hover:border-brand-300 hover:bg-brand-50/60">
                            <span class="font-semibold text-slate-800">Assinar no tablet</span>
                            <span class="text-[11px] text-slate-500">Abrir área de assinatura na tela.</span>
                        </button>

                        <button type="button" id="os-finalize-new"
                                class="flex flex-col items-start gap-1 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-left text-xs hover:border-brand-300 hover:bg-brand-50/60">
                            <span class="font-semibold text-slate-800">Gerar nova OS</span>
                            <span class="text-[11px] text-slate-500">Após finalizar, ir para nova OS em branco.</span>
                        </button>
                    </div>

                    <div class="border-t border-slate-100 pt-3 mt-3">
                        <p class="text-xs font-semibold text-slate-700 mb-2">
                            Aproveitar dados desta OS para cadastros:
                        </p>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <label class="flex items-start gap-2 text-sm text-slate-700">
                                <input type="checkbox" class="mt-1" id="final_save_customer">
                                <span>Cliente</span>
                            </label>
                            <label class="flex items-start gap-2 text-sm text-slate-700">
                                <input type="checkbox" class="mt-1" id="final_save_technician">
                                <span>Técnico</span>
                            </label>
                            <label class="flex items-start gap-2 text-sm text-slate-700">
                                <input type="checkbox" class="mt-1" id="final_save_services">
                                <span>Serviços</span>
                            </label>
                            <label class="flex items-start gap-2 text-sm text-slate-700">
                                <input type="checkbox" class="mt-1" id="final_save_parts">
                                <span>Peças</span>
                            </label>
                            <label class="flex items-start gap-2 text-sm text-slate-700">
                                <input type="checkbox" class="mt-1" id="final_save_equipments">
                                <span>Equipamentos</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-100 px-5 py-3">
                    <button type="button" data-os-finalize-cancel
                            class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>

        {{-- Modal assinatura tablet --}}
        <div id="os-signature-modal"
             class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-sm">
            <div class="w-full max-w-xl rounded-2xl bg-white shadow-xl flex flex-col">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5">
                    <h2 class="text-sm font-semibold text-slate-900">
                        Assinatura do cliente
                    </h2>
                    <button type="button" id="signature-close"
                            class="rounded-full p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                        ✕
                    </button>
                </div>

                <div class="px-5 py-4">
                    <p class="text-xs text-slate-500 mb-2">
                        Peça para o cliente assinar com o dedo ou caneta no tablet / celular.
                    </p>
                    <div class="border border-slate-300 rounded-2xl overflow-hidden bg-slate-50">
                        <canvas id="signature-pad" class="w-full h-56 touch-none"></canvas>
                    </div>
                    <div class="mt-3 flex justify-between">
                        <button type="button" id="signature-clear"
                                class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                            Limpar
                        </button>
                        <button type="button" id="signature-save"
                                class="inline-flex items-center rounded-xl bg-brand-600 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-700">
                            Salvar assinatura
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('assets/js/template/views/service-orders/service-order-form.js') }}"></script>
@endpush
