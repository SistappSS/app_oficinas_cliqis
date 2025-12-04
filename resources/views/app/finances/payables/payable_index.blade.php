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
                    Mês
                    <input id="month-filter" type="month"
                           class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none">
                </label>
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
                    <!-- (Antes do Novo) Toggle agrupar -->
                    <div class="flex items-center">
                        <span class="text-sm text-slate-600 mr-3">Agrupar por orçamento</span>
                        <button id="grp-toggle" aria-pressed="true"
                                class="relative h-6 w-11 rounded-full bg-blue-600 transition ring-1 ring-blue-200">
                            <span
                                class="absolute left-5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition"></span>
                        </button>
                    </div>


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
                <table class="min-w-full text-sm text-center">
                    <thead class="text-slate-600 bg-blue-50">
                    <tr>
                        <th class="px-6 py-4 first:rounded-tl-2xl">Data</th>
                        <th class="px-3 py-4">Descrição</th>
                        <th class="px-3 py-4">Valor</th>
                        <th class="px-3 py-4">Status</th>
                        <th class="px-6 py-4 last:rounded-tr-2xl">Ações</th>
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
            class="absolute left-1/2 top-1/2 w-[min(520px,92vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-2xl">
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
                        <label class="text-sm font-medium">Valor pago</label>
                        <input name="amount" type="number" step="0.01" min="0.01"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"
                               required>
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

    @push('scripts')
        <script>
            const fmt = n => 'R$ ' + Number(n || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2});
            const $ = s => document.querySelector(s);

            // novo helper para data
            const formatDateBr = (str) => {
                if (!str) return '-';
                const [y, m, d] = str.split('-');
                return `${d.padStart(2, '0')}/${m.padStart(2, '0')}/${y}`;
            };

            // inicializa filtro de mês com o mês atual
            const monthInput = $('#month-filter');
            if (monthInput && !monthInput.value) {
                const now = new Date();
                const y = now.getFullYear();
                const m = String(now.getMonth() + 1).padStart(2, '0');
                monthInput.value = `${y}-${m}`;
            }
            monthInput?.addEventListener('change', () => {
                load();
            });


            // --------- Tabs ----------
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

            // --------- Toggle agrupar (só visual) ----------
            (function paintGroupToggle() {
                const b = document.getElementById('grp-toggle');
                const on = (localStorage.getItem('payables_group_by_budget') ?? '1') === '1';
                b.setAttribute('aria-pressed', on ? 'true' : 'false');
                b.classList.toggle('bg-blue-600', on);
                b.classList.toggle('bg-slate-300', !on);
                b.querySelector('span').style.left = on ? '1.25rem' : '0.25rem';
            })();
            document.getElementById('grp-toggle')?.addEventListener('click', () => {
                const b = document.getElementById('grp-toggle');
                const on = b.getAttribute('aria-pressed') !== 'true';
                b.setAttribute('aria-pressed', on ? 'true' : 'false');
                b.classList.toggle('bg-blue-600', on);
                b.classList.toggle('bg-slate-300', !on);
                b.querySelector('span').style.left = on ? '1.25rem' : '0.25rem';
                localStorage.setItem('payables_group_by_budget', on ? '1' : '0');
            });

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

            // --------- Carregar Tabela ----------
            async function load() {
                const url = new URL('/finances/payable-api', location.origin);
                const q = $('#q')?.value?.trim();
                if (q) url.searchParams.set('q', q);
                if (statusFilter && statusFilter !== 'all') url.searchParams.set('status', statusFilter);

                const month = $('#month-filter')?.value;
                if (month) {
                    const [yStr, mStr] = month.split('-');
                    const y = Number(yStr);
                    const m = Number(mStr);
                    const lastDay = new Date(y, m, 0).getDate(); // último dia do mês (local)

                    const start = `${yStr}-${mStr}-${'01'}`;
                    const end   = `${yStr}-${mStr}-${String(lastDay).padStart(2, '0')}`;

                    url.searchParams.set('start', start);
                    url.searchParams.set('end', end);
                }

                const r = await fetch(url, {headers: {'Accept': 'application/json'}});
                const data = await r.json();

                const rows = (data.data || []).map(i => {
                    let chip;
                    if (i.status === 'paid') {
                        chip = '<span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">Pago</span>';
                    } else if (i.status === 'canceled') {
                        chip = '<span class="inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700">Cancelado</span>';
                    } else if (i.overdue) {
                        chip = '<span class="inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700">Atrasado</span>';
                    } else {
                        chip = '<span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">Pendente</span>';
                    }


                    const parcial = (i.amount_paid > 0 && i.status === 'pending')
                        ? '<span class="ml-2 text-[11px] text-slate-500">(parcial)</span>'
                        : '';

                    const isParcelado = i.origin.type === 'variable' && i.origin.total_recurrences > 1;
                    const detParcela = isParcelado
                        ? `Parcela ${i.origin.recurrence}/${i.origin.total_recurrences}`
                        : (i.origin.total_recurrences > 1 ? `Parcela ${i.origin.recurrence}` : '');

                    const linhaPago = (i.paid_total > 0)
                        ? `<div class="text-xs text-emerald-700 mt-0.5">Pago ${fmt(i.paid_total)}${i.last_paid_at ? ' em ' + new Date(i.last_paid_at).toLocaleDateString('pt-BR') : ''}</div>`
                        : '';

                    return `
<tr class="hover:bg-slate-50 text-center">
<td class="px-6 py-3">${formatDateBr(i.date)}</td>
  <td class="px-3 py-3">
    <div class="font-medium">${i.origin.description || '-'}</div>
    <div class="text-xs text-slate-500">${detParcela}</div>
    ${linhaPago}
  </td>
  <td class="px-3 py-3">${fmt(i.price)}</td>
  <td class="px-3 py-3">${chip}${parcial}</td>
  <td class="px-6 py-3">
  ${i.status === 'pending'
                        ? `<button class="rounded-lg p-2 text-slate-600 hover:text-emerald-700 hover:bg-emerald-50"
               data-open-pay="${i.id}"
               data-amount="${i.price}"
               title="Dar baixa">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                <path d="M4 4h16v12H5.17L4 17.17V4Zm3 14h13v2H7v-2Z"/>
            </svg>
       </button>
       <button class="rounded-lg p-2 text-slate-600 hover:text-rose-700 hover:bg-rose-50"
               data-cancel="${i.id}" title="Cancelar parcela">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                <path d="M6 6l12 12M6 18L18 6"/>
            </svg>
       </button>`
                        : ''
                    }
</td>
</tr>`;
                }).join('');

                document.getElementById('tbody').innerHTML = rows;

                // KPIs
                $('#kpi-pend').textContent = fmt(data.kpis?.pending_sum || 0);
                $('#kpi-paid').textContent = fmt(data.kpis?.paid_sum || 0);
                $('#kpi-net').textContent = fmt(data.kpis?.net_outflow || 0);

                // bind ações
                document.querySelectorAll('[data-open-pay]').forEach(b => {
                    b.addEventListener('click', () => {
                        const id  = b.dataset.openPay;
                        const amt = Number(b.dataset.amount || 0);
                        payModal.open(id, amt || undefined);
                    });
                });


                document.querySelectorAll('[data-cancel]').forEach(b => {
                    b.addEventListener('click', async () => {
                        if (!confirm('Cancelar esta parcela?')) return;
                        const id = b.dataset.cancel;
                        const res = await fetch(`/finances/payable-api/${id}/cancel`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]')?.content || ''
                            }
                        });
                        if (res.ok) load(); else alert('Falha ao cancelar');
                    });
                });

                payModal = bindPayModal();
            }

            // Modal de baixa
            function currency(n) {
                return 'R$ ' + Number(n || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2});
            }

            function bindPayModal() {
                const modal = document.getElementById('modal-pay');
                const form  = document.getElementById('form-pay');

                // Fecha modal
                modal.querySelectorAll('[data-close]').forEach(b =>
                    b.addEventListener('click', () => modal.classList.add('hidden'))
                );

                // Submit da baixa
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const id = form.id.value;
                    const fd = new FormData(form);
                    const payload = Object.fromEntries(fd.entries());

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
                        load();
                    } else {
                        alert('Erro ao baixar');
                    }
                });

                return {
                    open: (id, amount) => {
                        form.id.value       = id;
                        form.paid_at.value  = new Date().toISOString().slice(0, 10);
                        form.amount.value   = amount ?? '';
                        form.notes.value    = '';
                        modal.classList.remove('hidden');
                    }
                };
            }

            ['#q'].forEach(s => $(s)?.addEventListener('input', load));

            let payModal;
            bindNewModal();
            load();
        </script>
    @endpush
@endsection

