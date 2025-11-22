@extends('layouts.templates.template')

@section('content')
    <main class="mx-auto max-w-7xl px-4 sm:px-6 pb-10 lg:pb-14">
        <div class="flex items-center gap-4 mb-6">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">
                        {{ $customer->name }}
                    </h1>
                    <p class="text-sm text-slate-600">
                        Gerencie as recorrências e visualize as últimas cobranças deste cliente.
                    </p>
                </div>

                <div class="ml-auto flex items-center gap-2 shrink-0">
                    <button id="btn-add" class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-800">
                        Nova recorrência
                    </button>
                    <a href="{{ route('invoice.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        Cobranças
                    </a>
                    <button id="toggle-header"
                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow hover:bg-slate-50"
                            aria-expanded="true" aria-controls="header-collapsible" type="button"
                            title="Expandir/contrair cabeçalho">
                        <i id="toggle-icon" class="fa-solid fa-up-right-and-down-left-from-center"></i>
                    </button>
                </div>
            </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-800">Recorrências ativas</p>
                        <p class="text-xs text-slate-500">
                            Cobranças recorrentes configuradas para este cliente.
                        </p>
                    </div>
                    <p class="text-xs text-slate-500">
                        {{ $subscriptions->count() }} ativa(s)
                    </p>
                </div>

                @if ($subscriptions->isEmpty())
                    <p class="text-sm text-slate-500">
                        Nenhuma recorrência configurada para este cliente.
                    </p>
                @else
                    <div class="space-y-3">
                        @foreach ($subscriptions as $sub)
                            <div
                                class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-3"
                                data-sub-card
                                data-sub-id="{{ $sub->id }}"
                                data-sub-key="sub_{{ $sub->id }}"
                                data-sub-name="{{ e($sub->name) }}"
                                data-sub-amount="{{ $sub->amount }}"
                                data-sub-period="{{ $sub->period }}"
                                data-sub-day="{{ $sub->day_of_month }}"
                                data-sub-month="{{ $sub->month_of_year }}"
                                data-sub-auto="{{ $sub->auto_reminder ? '1' : '0' }}"
                            >
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">
                                        {{ $sub->name }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        {{ $sub->period === 'yearly' ? 'Anual' : 'Mensal' }}
                                        • Próx. vencimento:
                                        @if($sub->next_due_date)
                                            {{ \Carbon\Carbon::parse($sub->next_due_date)->format('d/m/Y') }}
                                        @else
                                            —
                                        @endif
                                    </p>
                                </div>

                                <div class="flex items-center gap-4">
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-slate-900">
                                            R$ {{ number_format($sub->amount, 2, ',', '.') }}
                                        </p>
                                    </div>

                                    <div class="flex flex-col sm:flex-row gap-2">
                                        <button type="button"
                                                class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                                data-edit-sub="{{ $sub->id }}">
                                            Editar recorrência
                                        </button>
                                        <button type="button"
                                                class="rounded-full border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100"
                                                data-cancel-sub="{{ $sub->id }}">
                                            Cancelar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-800">Últimas cobranças</p>
                        <p class="text-xs text-slate-500">
                            Histórico de pagamentos e cobranças únicas deste cliente.
                        </p>
                    </div>
                </div>

                @if ($charges->isEmpty())
                    <p class="text-sm text-slate-500">
                        Nenhuma cobrança encontrada para este cliente.
                    </p>
                @else
                    <ul class="divide-y divide-slate-100 text-sm">
                        @foreach($charges as $c)
                            @php
                                $date = $c['date']
                                    ? \Carbon\Carbon::parse($c['date'])->format('d/m/Y')
                                    : '-';

                                // payment OU invoice com is_paid = true
                                $isPaid = ($c['type'] === 'payment') || (!empty($c['is_paid']));

                                // status + cor do chip
                                $labelStatus = 'Pago';
                                $statusClass = 'bg-emerald-50 text-emerald-700';

                                if ($c['type'] === 'invoice') {
                                    $st = $c['status'] ?? '';

                                    switch (mb_strtolower($st)) {
                                        case 'vencido':
                                            $labelStatus = 'Vencido';
                                            $statusClass = 'bg-rose-50 text-rose-700';
                                            break;
                                        case 'pendente':
                                            $labelStatus = 'Pendente';
                                            $statusClass = 'bg-amber-50 text-amber-700';
                                            break;
                                        case 'cancelado':
                                            $labelStatus = 'Cancelado';
                                            $statusClass = 'bg-slate-100 text-slate-500';
                                            break;
                                        default:
                                            $labelStatus = $st ?: 'Pago';
                                    }
                                }
                            @endphp

                            <li class="flex items-center justify-between py-2">
                                <div>
                                    <p class="font-medium text-slate-900">
                                        {{ $c['title'] }}
                                    </p>

                                    @if($isPaid)
                                        <p class="text-xs text-slate-500">
                                            Pago em {{ $date }}
                                        </p>
                                    @else
                                        <p class="text-xs text-slate-500">
                                            Vencimento em {{ $date }}
                                        </p>
                                    @endif
                                </div>

                                <div class="text-right flex items-center">
                                    <p class="text-sm font-semibold text-slate-900">
                                        R$ {{ number_format($c['amount'], 2, ',', '.') }}
                                    </p>

                                    <div class="mt-0.5 flex items-center justify-end gap-1">
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-medium {{ $statusClass }}">
                                            {{ $labelStatus }}
                                        </span>

                                        @if($c['type'] === 'payment')
                                            @php
                                                $tags = [];
                                                if ($c['discount'] > 0) $tags[] = 'Desc. R$ '.number_format($c['discount'], 2, ',', '.');
                                                if ($c['interest'] > 0) $tags[] = 'Juros R$ '.number_format($c['interest'], 2, ',', '.');
                                                if ($c['fine'] > 0) $tags[] = 'Multa R$ '.number_format($c['fine'], 2, ',', '.');
                                            @endphp
                                            @if($tags)
                                                <p class="text-[11px] text-slate-500">
                                                    {{ implode(' · ', $tags) }}
                                                </p>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <div id="modal-edit-sub"
             class="hidden fixed inset-0 z-50 bg-black/40 backdrop-blur-sm">
            <div
                class="absolute left-1/2 top-1/2 w-[min(480px,92vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-5 shadow-2xl">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Editar recorrência</p>
                        <p id="sub-title" class="text-xs text-slate-500"></p>
                    </div>
                    <button type="button" class="rounded-lg p-2 hover:bg-slate-100" data-close-sub>
                        <svg class="h-4 w-4 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2">
                            <path d="M18 6 6 18M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form id="form-edit-sub" class="space-y-3">
                    <input type="hidden" id="sub-id">

                    <div>
                        <label class="text-xs font-medium text-slate-700">Valor (R$)</label>
                        <input type="number" step="0.01" min="0.01" id="sub-amount"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    </div>

                    <div class="grid sm:grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-medium text-slate-700">Período</label>
                            <select id="sub-period"
                                    class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                                <option value="monthly">Mensal</option>
                                <option value="yearly">Anual</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-700">Dia de vencimento</label>
                            <input type="number" id="sub-day" min="1" max="31"
                                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                        </div>
                    </div>

                    <div id="row-month" class="hidden">
                        <label class="text-xs font-medium text-slate-700">Mês do vencimento (anual)</label>
                        <input type="number" id="sub-month" min="1" max="12"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    </div>

                    <label class="flex items-center gap-2 text-xs text-slate-700">
                        <input type="checkbox" id="sub-auto"
                               class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        Enviar lembrete automático antes do vencimento
                    </label>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" data-close-sub
                                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="rounded-xl bg-blue-700 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-800">
                            Salvar alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    @push('scripts')
        <script>
            const SELECTED_SUB = @json($selectedSub);
            const SUB_UPDATE_TEMPLATE = @json(route('subscriptions.update', ['subscription' => '__SUB__']));
            const SUB_CANCEL_TEMPLATE = @json(route('subscriptions.cancel', ['subscription' => '__SUB__']));

            // destaca card vindo da tela de cobranças
            document.addEventListener('DOMContentLoaded', () => {
                if (SELECTED_SUB) {
                    const el = document.querySelector(`[data-sub-key="${SELECTED_SUB}"]`);
                    if (el) {
                        el.classList.add('ring-2', 'ring-blue-500', 'ring-offset-2', 'ring-offset-slate-50');
                        el.scrollIntoView({behavior: 'smooth', block: 'center'});
                    }
                }
            });

            const modal = document.getElementById('modal-edit-sub');
            const form = document.getElementById('form-edit-sub');
            const subIdInput = document.getElementById('sub-id');
            const subTitle = document.getElementById('sub-title');
            const amountInput = document.getElementById('sub-amount');
            const periodSelect = document.getElementById('sub-period');
            const dayInput = document.getElementById('sub-day');
            const monthRow = document.getElementById('row-month');
            const monthInput = document.getElementById('sub-month');
            const autoInput = document.getElementById('sub-auto');

            function openEditModal(id) {
                const card = document.querySelector(`[data-sub-card][data-sub-id="${id}"]`);
                if (!card) return;

                subIdInput.value = id;
                subTitle.textContent = card.dataset.subName || '';
                amountInput.value = card.dataset.subAmount || '';
                periodSelect.value = card.dataset.subPeriod || 'monthly';
                dayInput.value = card.dataset.subDay || '';
                monthInput.value = card.dataset.subMonth || '';
                autoInput.checked = card.dataset.subAuto === '1';

                if (periodSelect.value === 'yearly') {
                    monthRow.classList.remove('hidden');
                } else {
                    monthRow.classList.add('hidden');
                }

                modal.classList.remove('hidden');
            }

            // abrir modal editar
            document.querySelectorAll('[data-edit-sub]').forEach(btn => {
                btn.addEventListener('click', () => {
                    openEditModal(btn.dataset.editSub);
                });
            });

            // toggle mês anual
            periodSelect.addEventListener('change', () => {
                if (periodSelect.value === 'yearly') {
                    monthRow.classList.remove('hidden');
                } else {
                    monthRow.classList.add('hidden');
                }
            });

            // fechar modal
            document.querySelectorAll('[data-close-sub]').forEach(btn => {
                btn.addEventListener('click', () => {
                    modal.classList.add('hidden');
                });
            });

            // submit edição
            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                const id = subIdInput.value;
                if (!id) return;

                const url = SUB_UPDATE_TEMPLATE.replace('__SUB__', id);

                const body = {
                    amount: parseFloat(amountInput.value || 0),
                    period: periodSelect.value,
                    day_of_month: parseInt(dayInput.value || '1', 10),
                    month_of_year: periodSelect.value === 'yearly'
                        ? parseInt(monthInput.value || '1', 10)
                        : null,
                    auto_reminder: autoInput.checked ? 1 : 0,
                };

                const r = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify(body),
                });

                if (!r.ok) {
                    alert('Erro ao salvar recorrência.');
                    return;
                }

                window.location.reload();
            });

            // cancelar recorrência
            document.querySelectorAll('[data-cancel-sub]').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const id = btn.dataset.cancelSub;
                    if (!id) return;

                    if (!confirm('Cancelar esta recorrência? Nenhuma nova cobrança será gerada.')) {
                        return;
                    }

                    const url = SUB_CANCEL_TEMPLATE.replace('__SUB__', id);

                    const r = await fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                    });

                    if (!r.ok) {
                        alert('Erro ao cancelar recorrência.');
                        return;
                    }

                    window.location.reload();
                });
            });
        </script>
    @endpush
@endsection
