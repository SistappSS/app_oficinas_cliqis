@extends('layouts.templates.template')

@section('content')
    @push('styles')
        <style>
            .month-head {
                position: sticky;
                top: 0;
                z-index: 5;
            }
        </style>
    @endpush

    <main class="mx-auto max-w-7xl px-4 sm:px-6 pb-10 lg:pb-14">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold">Contas a Receber — Ordens de Serviço</h1>
                <p class="text-sm text-slate-600">Recebimentos gerados a partir das OS faturadas.</p>
            </div>
        </div>

        {{-- KPIs --}}
        <section class="mt-5 grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Total pendente (período)</p>
                <p id="kpi-pendente" class="mt-2 text-3xl font-bold">R$ 0,00</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Total pago (período)</p>
                <p id="kpi-pago" class="mt-2 text-3xl font-bold">R$ 0,00</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Total atrasado (período)</p>
                <p id="kpi-atrasado" class="mt-2 text-3xl font-bold">R$ 0,00</p>
            </div>
        </section>

        {{-- Tabs + período --}}
        <div class="flex items-center justify-between gap-3 mt-3">
            <div class="mt-3 inline-flex rounded-full p-1 bg-slate-100" id="status-tabs">
                <button data-status="all"
                        class="tab-btn rounded-full px-3 py-1.5 text-sm bg-blue-600 text-white">
                    Todos
                </button>
                <button data-status="pending"
                        class="tab-btn rounded-full px-3 py-1.5 text-sm bg-slate-100">
                    Pendentes
                </button>
                <button data-status="overdue"
                        class="tab-btn rounded-full px-3 py-1.5 text-sm bg-slate-100">
                    Atrasados
                </button>
                <button data-status="paid"
                        class="tab-btn rounded-full px-3 py-1.5 text-sm bg-slate-100">
                    Pagos
                </button>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                <div class="flex items-center gap-2">
                    <div class="text-sm text-slate-600">De</div>
                    <input id="filter-start" type="date"
                           class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm leading-none focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none">
                </div>

                <div class="flex items-center gap-2">
                    <div class="text-sm text-slate-600">até</div>
                    <input id="filter-end" type="date"
                           class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm leading-none focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none">
                </div>

                <button id="filter-apply"
                        class="rounded-xl bg-indigo-900 px-3 py-2 text-xs font-semibold text-white shadow hover:bg-slate-800">
                    Aplicar
                </button>
            </div>
        </div>

        {{-- Busca + toggle agrupar --}}
        <div class="px-2 pt-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="relative w-full sm:w-auto">
                <input id="q" placeholder="Buscar OS, cliente ou fatura..."
                       class="w-full sm:w-[22rem] rounded-xl border border-slate-300 bg-white pl-10 pr-3 py-2.5 text-sm placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none">
                <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="M21 21l-4.3-4.3"/>
                </svg>
            </div>

            <div class="flex items-center gap-3">
                <span class="text-sm text-slate-600">Agrupar por OS</span>
                <button id="grp-toggle" aria-pressed="true"
                        class="relative h-6 w-11 rounded-full bg-blue-600 transition ring-1 ring-blue-200">
                    <span class="absolute left-5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition"></span>
                </button>
            </div>
        </div>

        {{-- Tabela --}}
        <section class="mt-5 mb-8 rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto ">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-slate-600 bg-blue-50">
                    <tr class="whitespace-nowrap">
                        <th class="px-6 py-4 first:rounded-tl-2xl">Cliente</th>
                        <th class="px-3 py-4">OS</th>
                        <th class="px-3 py-4">Fatura</th>
                        <th class="px-3 py-4">Vencimento</th>
                        <th class="px-3 py-4 text-right">Valor</th>
                        <th class="px-3 py-4">Parcela</th>
                        <th class="px-3 py-4">Status</th>
                        <th class="px-6 py-4 text-right last:rounded-tr-2xl">Ações</th>
                    </tr>
                    </thead>
                    <tbody id="tbody" class="divide-y divide-slate-100"></tbody>
                </table>
            </div>
        </section>
    </main>

    {{-- Modal registrar recebimento --}}
    <div id="recv-pay" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm">
        <div
            class="absolute left-1/2 top-1/2 w-[min(520px,92vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">Registrar recebimento</h2>
                <button data-close class="rounded-lg p-2 hover:bg-slate-100">
                    <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form id="recv-pay-form" class="mt-4 grid gap-3">
                <input type="hidden" name="invoice_id"/>

                <div class="grid sm:grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm font-medium">Data</label>
                        <input name="paid_at" type="date"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" required>
                    </div>
                    <div>
                        <label class="text-sm font-medium">Valor recebido</label>
                        <input name="amount" type="number" step="0.01" min="0.01"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" required>
                    </div>
                </div>

                <div class="grid sm:grid-cols-3 gap-3">
                    <div>
                        <label class="text-sm font-medium">Juros</label>
                        <input name="interest" type="number" step="0.01" min="0"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5">
                    </div>
                    <div>
                        <label class="text-sm font-medium">Multa</label>
                        <input name="fine" type="number" step="0.01" min="0"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5">
                    </div>
                    <div>
                        <label class="text-sm font-medium">Desconto</label>
                        <input name="discount" type="number" step="0.01" min="0"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5">
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium">Ref.</label>
                    <input name="reference"
                           class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5">
                </div>

                <div>
                    <label class="text-sm font-medium">Observações</label>
                    <textarea name="notes"
                              class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" data-close
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm">
                        Cancelar
                    </button>
                    <button
                        class="rounded-xl bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">
                        Salvar baixa
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            const $  = s => document.querySelector(s);
            const $$ = s => [...document.querySelectorAll(s)];

            const fmtBR = n =>
                'R$ ' + Number(n || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 });

            function formatYMDToBR(ymd) {
                if (!ymd) return '—';
                const parts = String(ymd).split('-');
                if (parts.length !== 3) return '—';
                const [yyyy, mm, dd] = parts;
                return dd + '/' + mm + '/' + yyyy;
            }

            function statusChip(st) {
                const map = {
                    pending:  ['Pendente',  'bg-amber-50 text-amber-700'],
                    overdue:  ['Atrasado',  'bg-rose-50 text-rose-700'],
                    paid:     ['Pago',      'bg-emerald-50 text-emerald-700'],
                    canceled: ['Cancelado', 'bg-slate-100 text-slate-600'],
                };
                const cfg = map[st] || ['—', 'bg-slate-100 text-slate-600'];
                return `<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${cfg[1]}">${cfg[0]}</span>`;
            }

            function parcelLabelForRow(item) {
                if (item.type === 'signal') {
                    return 'Sinal';
                }
                if (item.type === 'parcel') {
                    const n = item.installment;
                    const t = item.installments_total;
                    if (n && t) return `Parcela ${n}/${t}`;
                    if (n) return `Parcela ${n}`;
                    return 'Parcela';
                }
                if (item.type === 'single') {
                    return 'Único';
                }
                return '—';
            }

            function parcelChip(label) {
                if (!label || label === '—') {
                    return `<span class="inline-flex rounded-full bg-slate-100 text-slate-600 px-2.5 py-1 text-xs font-medium">—</span>`;
                }
                const low = label.toLowerCase();
                if (low.includes('sinal'))   return `<span class="inline-flex rounded-full bg-sky-50 text-sky-700 px-2.5 py-1 text-xs font-medium">${label}</span>`;
                if (low.includes('parcela')) return `<span class="inline-flex rounded-full bg-rose-50 text-rose-700 px-2.5 py-1 text-xs font-medium">${label}</span>`;
                if (low.includes('único'))   return `<span class="inline-flex rounded-full bg-emerald-50 text-emerald-700 px-2.5 py-1 text-xs font-medium">${label}</span>`;
                return `<span class="inline-flex rounded-full bg-slate-100 text-slate-700 px-2.5 py-1 text-xs font-medium">${label}</span>`;
            }

            function firstLetter(n) {
                if (!n) return '?';
                return n.trim().charAt(0).toUpperCase() || '?';
            }

            function rowFlatHTML(i) {
                const so = i.service_order || {};
                const clientName = so.client_name || '-';
                const osNumber   = so.order_number ? `OS ${so.order_number}` : '—';

                const venc   = formatYMDToBR(i.date);
                const amount = fmtBR(i.price);
                const parcelaLabel = parcelLabelForRow(i);
                const parcelaHTML  = parcelChip(parcelaLabel);

                const canPay = (i.status === 'pending' || i.status === 'overdue');

                const payBtn = canPay
                    ? `<button class="inline-flex rounded-lg p-2 text-slate-600 hover:text-emerald-700 hover:bg-emerald-50"
                               data-paid="${i.id}" title="Dar baixa">
                           <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                             <rect x="3" y="7" width="18" height="10" rx="2"/>
                             <circle cx="12" cy="12" r="3"/>
                             <path d="M10.8 12.2l1 1 2-2" stroke-linecap="round" stroke-linejoin="round"/>
                           </svg>
                       </button>`
                    : '';

                return `
<tr class="hover:bg-slate-50">
  <td class="px-6 py-3">
    <div class="flex items-center gap-3">
      <span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-50 text-blue-700 font-semibold ring-1 ring-blue-200 text-xs">
        ${firstLetter(clientName)}
      </span>
      <div class="space-y-0.5">
        <div class="text-sm font-medium text-slate-900">${clientName}</div>
        <div class="text-xs text-slate-500">${osNumber}</div>
      </div>
    </div>
  </td>
  <td class="px-3 py-3 text-sm text-slate-700">${osNumber}</td>
  <td class="px-3 py-3 text-sm text-slate-700">${i.number || '—'}</td>
  <td class="px-3 py-3 text-sm text-slate-700">${venc}</td>
  <td class="px-3 py-3 text-sm font-medium text-slate-900 text-right">${amount}</td>
  <td class="px-3 py-3 text-sm text-slate-700">${parcelaHTML}</td>
  <td class="px-3 py-3">${statusChip(i.status)}</td>
  <td class="px-6 py-3 text-right">${payBtn}</td>
</tr>`;
            }

            function groupByServiceOrder(rows) {
                const map = new Map();

                for (const r of rows) {
                    const so = r.service_order || {};
                    const key = so.id ? `SO_${so.id}` : `SO_0|${so.client_name || '-'}`;

                    if (!map.has(key)) {
                        map.set(key, {
                            id: key,
                            title: `${so.client_name || '-'} — ${so.order_number ? 'OS ' + so.order_number : 'Sem OS'}`,
                            firstDate: null,
                            total: 0,
                            statuses: { pending:0, overdue:0, paid:0, canceled:0 },
                            items: [],
                        });
                    }

                    const g = map.get(key);
                    g.items.push(r);
                    g.total += Number(r.price || 0);

                    const st = r.status || 'pending';
                    g.statuses[st] = (g.statuses[st] || 0) + 1;

                    const d = r.date ? new Date(r.date) : null;
                    if (d && (!g.firstDate || d < g.firstDate)) g.firstDate = d;
                }

                const groups = [...map.values()];
                groups.forEach(g => g.items.sort((a,b) => new Date(a.date) - new Date(b.date)));
                groups.sort((a,b) => {
                    const ax = a.firstDate ? a.firstDate.getTime() : Number.MAX_SAFE_INTEGER;
                    const bx = b.firstDate ? b.firstDate.getTime() : Number.MAX_SAFE_INTEGER;
                    return ax - bx;
                });

                return groups;
            }

            function drawGrouped() {
                const body = $('#tbody');
                const groups = groupByServiceOrder(__ROWS);

                if (!groups.length) {
                    body.innerHTML = '<tr><td colspan="8" class="px-6 py-3 text-slate-500">Nenhum registro.</td></tr>';
                    return;
                }

                const rows = [];

                for (const g of groups) {
                    const badge =
                        g.statuses.overdue ? statusChip('overdue') :
                            g.statuses.pending ? statusChip('pending') :
                                (g.statuses.paid === g.items.length)
                                    ? statusChip('paid')
                                    : statusChip('canceled');

                    const boxId = `grp_${g.id}`;

                    rows.push(`
<tr class="bg-slate-50/60">
  <td colspan="8" class="px-4 py-2">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2">
        <button type="button"
                class="rounded-lg p-1.5 hover:bg-white"
                data-toggle="${boxId}"
                aria-expanded="false"
                title="Expandir/Recolher">
          <svg class="h-4 w-4 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="m6 9 6 6 6-6"/>
          </svg>
        </button>
        <div>
          <div class="font-semibold text-sm text-slate-900">${g.title}</div>
          <div class="text-xs text-slate-600">
            Primeiro venc.: ${g.firstDate ? formatYMDToBR(g.firstDate.toISOString().slice(0,10)) : '—'}
            • ${g.items.length} documento(s)
          </div>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <div class="text-sm font-semibold">${fmtBR(g.total)}</div>
        ${badge}
      </div>
    </div>

    <div class="mt-2 rounded-xl border border-slate-200 bg-white hidden" data-box="${boxId}">
      <table class="min-w-full text-sm">
        <tbody>
          ${g.items.map(rowFlatHTML).join('')}
        </tbody>
      </table>
    </div>
  </td>
</tr>`);
                }

                body.innerHTML = rows.join('');

                // abre/fecha grupo
                body.querySelectorAll('[data-toggle]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const id  = btn.getAttribute('data-toggle');
                        const box = body.querySelector(`[data-box="${id}"]`);
                        const svg = btn.querySelector('svg');
                        const expanded = btn.getAttribute('aria-expanded') === 'true';
                        btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                        box?.classList.toggle('hidden');
                        svg?.classList.toggle('rotate-180');
                    });
                });

                bindPayButtons();
            }

            function monthKey(ymd) {
                if (!ymd) return '9999-12';
                const only = String(ymd).split(' ')[0];
                const [y,m] = only.split('-');
                return (y && m) ? `${y}-${m}` : '9999-12';
            }

            function monthLabel(key) {
                if (key === '9999-12') return 'Sem vencimento';
                const d = new Date(`${key}-01T00:00:00`);
                let lab = d.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
                lab = lab.replace(' de ', ' / ');
                return lab.charAt(0).toUpperCase() + lab.slice(1);
            }

            function buildMonthStats(rows) {
                const map = new Map();

                for (const r of rows) {
                    const k = monthKey(r.date);
                    if (!map.has(k)) {
                        map.set(k, {
                            total: 0,
                            totalPaid: 0,
                            count: 0,
                            paidCount: 0,
                        });
                    }

                    const g   = map.get(k);
                    const val = Number(r.price || 0);

                    g.total += val;
                    g.count += 1;

                    if (r.status === 'paid') {
                        g.paidCount += 1;
                        g.totalPaid += val;
                    }
                }

                return map;
            }

            function drawFlat() {
                const body = $('#tbody');

                if (!__ROWS.length) {
                    body.innerHTML =
                        '<tr><td colspan="8" class="px-6 py-3 text-slate-500">Nenhum registro.</td></tr>';
                    return;
                }

                const stats = buildMonthStats(__ROWS);
                const keys  = [...stats.keys()].sort();

                const out = [];

                for (const k of keys) {
                    const g = stats.get(k);

                    out.push(`
<tr class="month-head bg-blue-50/60">
  <td colspan="8" class="px-4 py-2">
    <div class="flex items-center justify-between">
      <div class="font-semibold text-sm capitalize">${monthLabel(k)}</div>
      <div class="mt-0.5 text-xs text-slate-600">
        ${g.paidCount}/${g.count} recebidas
      </div>
    </div>
  </td>
</tr>`);

                    for (const r of __ROWS) {
                        if (monthKey(r.date) === k) {
                            out.push(rowFlatHTML(r));
                        }
                    }
                }

                body.innerHTML = out.join('');
                bindPayButtons();
            }

            function draw() {
                if (groupByOrder) {
                    drawGrouped();
                } else {
                    drawFlat();
                }
            }

            let __ROWS = [];
            let currentTab = 'all';
            let groupByOrder = (localStorage.getItem('so_recv_group_by_order') ?? '1') === '1';
            let filterStart = '';
            let filterEnd   = '';

            function paintGroupToggle() {
                const b = $('#grp-toggle');
                if (!b) return;
                b.setAttribute('aria-pressed', groupByOrder ? 'true' : 'false');
                b.classList.toggle('bg-blue-600', groupByOrder);
                b.classList.toggle('bg-slate-300', !groupByOrder);
                const knob = b.querySelector('span');
                if (knob) {
                    knob.style.left = groupByOrder ? '1.25rem' : '0.25rem';
                }
            }

            $('#grp-toggle')?.addEventListener('click', () => {
                groupByOrder = !groupByOrder;
                localStorage.setItem('so_recv_group_by_order', groupByOrder ? '1' : '0');
                paintGroupToggle();
                draw();
            });

            async function load() {
                const url = new URL('/finances/receivables/service-orders/api', window.location.origin);

                const q = $('#q')?.value?.trim() || '';
                if (q) url.searchParams.set('q', q);

                if (currentTab !== 'all') {
                    url.searchParams.set('tab', currentTab);
                }

                if (filterStart) url.searchParams.set('start_date', filterStart);
                if (filterEnd)   url.searchParams.set('end_date',   filterEnd);

                const r = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (!r.ok) {
                    console.error('Falha no fetch', r.status, await r.text());
                    $('#tbody').innerHTML =
                        '<tr><td colspan="8" class="px-6 py-3 text-slate-500">Erro ao carregar.</td></tr>';

                    $('#kpi-pendente').textContent = fmtBR(0);
                    $('#kpi-pago').textContent     = fmtBR(0);
                    $('#kpi-atrasado').textContent = fmtBR(0);
                    return;
                }

                const payload = await r.json();
                __ROWS = payload.data || [];

                if (payload.kpis) {
                    $('#kpi-pendente').textContent = fmtBR(payload.kpis.pendente   || 0);
                    $('#kpi-pago').textContent     = fmtBR(payload.kpis.pago       || 0);
                    $('#kpi-atrasado').textContent = fmtBR(payload.kpis.atrasado   || 0);
                } else {
                    $('#kpi-pendente').textContent = fmtBR(0);
                    $('#kpi-pago').textContent     = fmtBR(0);
                    $('#kpi-atrasado').textContent = fmtBR(0);
                }

                __ROWS.sort((a, b) => new Date(a.date) - new Date(b.date));

                draw();
            }

            $$('#status-tabs .tab-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    $$('#status-tabs .tab-btn').forEach(x =>
                        x.className = 'tab-btn rounded-full px-3 py-1.5 text-sm bg-slate-100'
                    );
                    btn.className = 'tab-btn rounded-full px-3 py-1.5 text-sm bg-blue-600 text-white';

                    currentTab = btn.dataset.status || 'all';
                    load();
                });
            });

            $('#q')?.addEventListener('input', () => {
                load();
            });

            $('#filter-apply')?.addEventListener('click', () => {
                filterStart = $('#filter-start')?.value || '';
                filterEnd   = $('#filter-end')?.value || '';
                load();
            });

            let payUI;

            function bindRecvPayModal() {
                const modalEl = document.querySelector('#recv-pay');

                const E = (form, name) => form.elements.namedItem(name);

                function close() { modalEl.classList.add('hidden'); }

                modalEl.addEventListener('click', (e) => {
                    if (e.target.closest('[data-close]')) close();
                });

                function rebindForm() {
                    const oldForm = document.querySelector('#recv-pay-form');
                    const newForm = oldForm.cloneNode(true);
                    oldForm.parentNode.replaceChild(newForm, oldForm);
                    return newForm;
                }

                function fillDefaults(f, row) {
                    E(f,'invoice_id').value = row.id;
                    E(f,'paid_at').value    = new Date().toISOString().slice(0,10);

                    const val = Number(row.price || 0);
                    E(f,'amount').value     = Number.isFinite(val) ? String(val) : '';

                    E(f,'interest').value   = '';
                    E(f,'fine').value       = '';
                    E(f,'discount').value   = '';
                    E(f,'reference').value  = '';
                    E(f,'notes').value      = '';
                }

                function buildPayload(f, row) {
                    const rawAmount = E(f,'amount').value === ''
                        ? (row.price ?? '')
                        : E(f,'amount').value;

                    const parseNum = (v) => {
                        if (v === '' || v === null || v === undefined) return 0;
                        return parseFloat(String(v).replace(',', '.')) || 0;
                    };

                    return {
                        paid_at:   E(f,'paid_at').value,
                        amount:    rawAmount === '' ? null : parseFloat(String(rawAmount).replace(',', '.')),
                        interest:  parseNum(E(f,'interest').value),
                        fine:      parseNum(E(f,'fine').value),
                        discount:  parseNum(E(f,'discount').value),
                        reference: E(f,'reference').value || null,
                        notes:     E(f,'notes').value     || null,
                    };
                }

                return {
                    open(row) {
                        const f = rebindForm();
                        fillDefaults(f, row);

                        f.addEventListener('submit', async (e) => {
                            e.preventDefault();

                            const id = E(f,'invoice_id').value;
                            const payload = buildPayload(f, row);

                            if (!payload.paid_at) {
                                alert('Informe a data');
                                return;
                            }
                            if (payload.amount == null || isNaN(payload.amount) || payload.amount <= 0) {
                                alert('Informe o valor');
                                return;
                            }

                            const res = await fetch(`/finances/receivables/invoices/${id}/pay`, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                                },
                                body: JSON.stringify(payload)
                            });

                            if (res.ok) {
                                close();
                                await load();
                            } else {
                                console.error(await res.text());
                                alert('Erro ao registrar recebimento');
                            }
                        });

                        modalEl.classList.remove('hidden');
                    }
                };
            }

            function bindPayButtons() {
                if (!payUI) payUI = bindRecvPayModal();

                document.querySelectorAll('#tbody [data-paid]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const id = btn.dataset.paid;
                        const row = __ROWS.find(r => String(r.id) === String(id));
                        if (!row) {
                            console.warn('Row não encontrada', {id});
                            return;
                        }
                        payUI.open(row);
                    });
                });
            }

            (async function init() {
                paintGroupToggle();
                await load();
            })();
        </script>
    @endpush
@endsection
