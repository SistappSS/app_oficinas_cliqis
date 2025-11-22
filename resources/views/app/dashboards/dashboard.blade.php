@extends('layouts.templates.template')

@section('content')
    <section class="mx-auto max-w-7xl px-4 sm:px-6 pb-10 lg:pb-14">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 md:mb-5">
            @can('sales_invoice_view')
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:shadow transition">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-slate-500">MRR</p>
                        <span class="rounded-lg bg-blue-50 p-2 text-blue-700">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path
                            d="M3 3h18v4H3z"/><path d="M7 13h2v6H7zM11 9h2v10h-2zM15 11h2v8h-2z"/></svg>
                </span>
                    </div>
                    <p data-kpi="mrr" class="mt-3 text-2xl font-bold">{{brlPrice($subscriptions)}}</p>
                    <p class="text-xs text-emerald-600 mt-1">--</p>
                </div>
            @endcan

            @can('finance_receivable_view')
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:shadow transition">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-slate-500">Recebíveis</p>
                        <span class="rounded-lg bg-blue-50 p-2 text-blue-700">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path
                            d="M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"/><path
                            d="M14 2v6h6"/></svg>
                </span>
                    </div>
                    <p data-kpi="receive" class="mt-3 text-2xl font-bold">{{brlPrice($receivableWeek)}}</p>
                    <p class="text-xs text-amber-600 mt-1">próx. 7 dias</p>
                </div>
            @endcan

            @can('entitie_customer_view')
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:shadow transition">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-slate-500">Clientes ativos</p>
                        <span class="rounded-lg bg-blue-50 p-2 text-blue-700">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path
                            d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm-7 9a7 7 0 0 1 14 0Z"/></svg>
                </span>
                    </div>
                    <p data-kpi="customers" class="mt-3 text-2xl font-bold">{{ count($activeCustomers) }}</p>
                    <p class="text-xs text-slate-500 mt-1">base de clientes</p>
                </div>
            @endcan

            @can('sales_budget_view')
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:shadow transition">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-slate-500">Orçamentos aprovados</p>
                        <span class="rounded-lg bg-blue-50 p-2 text-blue-700">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path
                            d="M20 13V7a2 2 0 0 0-2-2h-5"/><rect x="3" y="7" width="14" height="13" rx="2"/></svg>
                </span>
                    </div>
                    <p class="mt-3 text-2xl font-bold">{{$approvedBudgets}}</p>
                    <p class="text-xs text-slate-500 mt-1">+{{$pendingBudgets}} pendentes</p>
                </div>
            @endcan
        </div>

        {{-- Linha com Donut + Performance --}}
        <div class="grid gap-6 lg:grid-cols-2">
            {{-- Donut de projetos --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold">Distribuição de Projetos</h3>
                        <p id="donut-title" class="text-sm text-slate-500">Total de 0 projetos</p>
                    </div>
                </div>

                <div class="mt-4 grid gap-6 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <div id="donut-projects"
                             class="relative mx-auto aspect-square max-w-xs"
                             data-segments=''
                             data-total=''>
                        </div>
                    </div>
                    <div class="flex flex-col justify-center gap-3 text-sm">
                        <div id="donut-legend" class="space-y-2"></div>
                    </div>
                </div>
            </div>

            {{-- Performance mensal (barras) --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold">Performance Mensal</h3>
                        <p class="text-sm text-slate-500">Principais indicadores</p>
                    </div>
                </div>

                <div id="perf-bars" class="mt-5 space-y-4"
                     data-bars=''>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl bg-blue-50 text-slate-800 px-4 py-3">
                        <div class="text-xs text-slate-600">Ticket Médio</div>
                        <div id="ticket-val" class="text-base font-semibold">R$ 0,00</div>
                    </div>
                    <div class="rounded-xl bg-fuchsia-50 text-slate-800 px-4 py-3">
                        <div class="text-xs text-slate-600">NPS Score</div>
                        <div id="nps-val" class="text-base font-semibold">—</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Atividades recentes (mantido) --}}
        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold">Atividades recentes</h3>
                <button class="text-sm text-blue-700 hover:underline">Ver tudo</button>
            </div>
            <ul data-activity class="mt-4 space-y-3 text-sm"></ul>
        </div>
    </section>

    @push('scripts')
        <script>
            (function () {
                // ---------- Helpers ----------
                const BRL = v => (Number(v) || 0).toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'});

                // ---------- DONUT ----------
                const donutEl = document.getElementById('donut-projects');
                const donutTitle = document.getElementById('donut-title');
                let segments;
                try {
                    segments = JSON.parse(donutEl.dataset.segments || '[]')
                } catch {
                    segments = []
                }

                // Fallback (cores Tailwind)
                if (!segments.length) {
                    segments = [
                        {label: 'Em andamento', value: 12, color: '#3b82f6'}, // blue-500
                        {label: 'Concluídos', value: 45, color: '#10b981'}, // emerald-500
                        {label: 'Pausados', value: 8, color: '#f59e0b'}, // amber-500
                        {label: 'Cancelados', value: 5, color: '#ef4444'}, // red-500
                    ];
                }
                let total = Number(donutEl.dataset.total || 0);
                if (!total) total = segments.reduce((s, a) => s + Number(a.value || 0), 0);
                if (donutTitle) donutTitle.textContent = `Total de ${total} projeto${total === 1 ? '' : 's'}`;

                function renderDonut(el, data) {
                    el.innerHTML = '';
                    const W = 220, H = 220, cx = W / 2, cy = H / 2;
                    const r = 80, circ = 2 * Math.PI * r;
                    const innerR = 54;

                    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                    svg.setAttribute('viewBox', `0 0 ${W} ${H}`);
                    svg.setAttribute('width', '100%');
                    svg.setAttribute('height', '100%');
                    el.appendChild(svg);

                    // Trilha cinza
                    const base = document.createElementNS(svg.namespaceURI, 'circle');
                    base.setAttribute('cx', cx);
                    base.setAttribute('cy', cy);
                    base.setAttribute('r', r);
                    base.setAttribute('fill', 'none');
                    base.setAttribute('stroke', '#e5e7eb');
                    base.setAttribute('stroke-width', '20');
                    svg.appendChild(base);

                    // Arcos
                    let offset = 0;
                    const g = document.createElementNS(svg.namespaceURI, 'g');
                    g.setAttribute('transform', `rotate(-90 ${cx} ${cy})`);
                    svg.appendChild(g);

                    data.forEach(seg => {
                        const len = (Number(seg.value || 0) / total) * circ;
                        const c = document.createElementNS(svg.namespaceURI, 'circle');
                        c.setAttribute('cx', cx);
                        c.setAttribute('cy', cy);
                        c.setAttribute('r', r);
                        c.setAttribute('fill', 'none');
                        c.setAttribute('stroke', seg.color || '#64748b');
                        c.setAttribute('stroke-width', '20');
                        c.setAttribute('stroke-linecap', 'butt');
                        c.setAttribute('stroke-dasharray', `${len} ${circ - len}`);
                        c.setAttribute('stroke-dashoffset', -offset);
                        g.appendChild(c);
                        offset += len;
                    });

                    // Furo (donut)
                    const hole = document.createElementNS(svg.namespaceURI, 'circle');
                    hole.setAttribute('cx', cx);
                    hole.setAttribute('cy', cy);
                    hole.setAttribute('r', innerR);
                    hole.setAttribute('fill', '#ffffff');
                    svg.appendChild(hole);

                    // Texto central
                    const t1 = document.createElementNS(svg.namespaceURI, 'text');
                    t1.setAttribute('x', cx);
                    t1.setAttribute('y', cy - 2);
                    t1.setAttribute('text-anchor', 'middle');
                    t1.setAttribute('class', 'fill-slate-900');
                    t1.setAttribute('font-size', '18');
                    t1.setAttribute('font-weight', '600');
                    t1.textContent = total;
                    const t2 = document.createElementNS(svg.namespaceURI, 'text');
                    t2.setAttribute('x', cx);
                    t2.setAttribute('y', cy + 16);
                    t2.setAttribute('text-anchor', 'middle');
                    t2.setAttribute('class', 'fill-slate-500');
                    t2.setAttribute('font-size', '10');
                    t2.textContent = 'projetos';
                    svg.appendChild(t1);
                    svg.appendChild(t2);

                    // Legenda
                    const legend = document.getElementById('donut-legend');
                    legend.innerHTML = '';
                    data.forEach(seg => {
                        const li = document.createElement('div');
                        li.className = 'flex items-center justify-between gap-4';
                        li.innerHTML = `
        <div class="flex items-center gap-2">
          <span class="h-2.5 w-2.5 rounded-full" style="background:${seg.color}"></span>
          <span class="text-slate-600">${seg.label}</span>
        </div>
        <span class="font-medium text-slate-900">${seg.value}</span>
      `;
                        legend.appendChild(li);
                    });
                }

                renderDonut(donutEl, segments);
                window.addEventListener('resize', () => renderDonut(donutEl, segments));

                // ---------- BARRAS (Performance) ----------
                const barsEl = document.getElementById('perf-bars');
                let bars;
                try {
                    bars = JSON.parse(barsEl.dataset.bars || '[]')
                } catch {
                    bars = []
                }
                if (!bars.length) {
                    bars = [
                        {label: 'Taxa de Conversão', value: 68, color: '#2563eb'},
                        {label: 'Satisfação do Cliente', value: 92, color: '#10b981'},
                        {label: 'Entregas no Prazo', value: 85, color: '#a855f7'},
                        {label: 'Retenção de Clientes', value: 78, color: '#f97316'},
                    ];
                }
                barsEl.innerHTML = bars.map(b => `
    <div>
      <div class="flex items-center justify-between text-sm mb-1">
        <span class="text-slate-700">${b.label}</span>
        <span class="font-medium text-slate-900">${b.value}%</span>
      </div>
      <div class="h-2.5 w-full rounded-full bg-slate-100">
        <div class="h-2.5 rounded-full" style="width:${Math.max(0, Math.min(100, b.value))}%; background:${b.color}"></div>
      </div>
    </div>
  `).join('');

                // Cards auxiliares (opcional, pode setar via backend com JS)
                document.getElementById('ticket-val').textContent = BRL(18500);
                document.getElementById('nps-val').textContent = '8,7/10';
            })();
        </script>
    @endpush
@endsection
