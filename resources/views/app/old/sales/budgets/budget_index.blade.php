@extends('layouts.templates.template')

@section('content')
    <main class="mx-auto max-w-7xl px-4 sm:px-6 pb-10 lg:pb-14">
        <div class="mb-3">
            <h1 class="text-2xl font-semibold">Orçamentos</h1>
            <p class="text-sm text-slate-600">Últimos orçamentos gerados.</p>
        </div>

        <!-- Busca -->
        <div class="mt-2 flex items-center gap-2">
            <div class="relative w-full max-w-xl">
                <input id="b-search"
                       placeholder="Buscar orçamento .."
                       class="w-full rounded-xl border border-slate-300 bg-white pl-10 pr-3 py-2.5 text-sm placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none"/>
                <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="M21 21l-4.3-4.3"/>
                </svg>
            </div>
        </div>

        <!-- Filtros + ações (botões à direita) -->
        <div class="mt-3 flex items-center gap-2 flex-wrap">
            <div class="flex flex-wrap items-center gap-2">
                <button data-status="all" class="status-filter rounded-full px-3 py-1.5 text-sm bg-blue-600 text-white">
                    Todos
                </button>
                <button data-status="pending"
                        class="status-filter rounded-full px-3 py-1.5 text-sm bg-slate-100 text-slate-800">
                    Abertos
                </button>
                <button data-status="approved"
                        class="status-filter rounded-full px-3 py-1.5 text-sm bg-slate-100 text-slate-800">
                    Aprovados
                </button>
                <button data-status="rejected"
                        class="status-filter rounded-full px-3 py-1.5 text-sm bg-slate-100 text-slate-800">
                    Rejeitados
                </button>
            </div>

            <div class="ml-auto flex items-center gap-2 shrink-0">
                <a href="{{ route('budget.create') }}"
                   class="inline-flex items-center rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow hover:bg-blue-800">
                    Novo orçamento
                </a>

                <a href="{{ route('budget-config.index') }}"
                   class="inline-flex items-center rounded-xl bg-gray-700 px-4 py-2.5 text-sm font-semibold text-white shadow hover:bg-gray-800">
                    Configurar PDF
                </a>

                <button id="toggle-header"
                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow hover:bg-slate-50"
                        aria-expanded="true" aria-controls="header-collapsible" type="button"
                        title="Expandir/contrair cabeçalho">
                    <i id="toggle-icon" class="fa-solid fa-up-right-and-down-left-from-center"></i>
                </button>
            </div>
        </div>

        <!-- Tabela — mesmo design da invoice -->
        <div class="mt-5 mb-8 rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="">
                <table class="min-w-full text-sm">
                    <thead class="text-center text-slate-600 bg-blue-50">
                    <tr>
                        <th class="px-6 py-4 first:rounded-tl-2xl">Código</th>
                        <th class="px-3 py-4">Cliente</th>
                        <th class="px-3 py-4">Criado</th>
                        <th class="px-3 py-4 text-center">Total</th>
                        <th class="px-3 py-4">Escopo</th>
                        <th class="px-3 py-4">Status</th>
                        <th class="px-6 py-4 text-center last:rounded-tr-2xl">Ações</th>
                    </tr>
                    </thead>
                    <tbody id="tbody" class="divide-y divide-slate-100"></tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="toast" class="pointer-events-none fixed bottom-5 right-5 z-50 hidden">
        <div class="rounded-xl bg-slate-900/90 px-4 py-3 text-sm font-medium text-white shadow-lg">Mensagem</div>
    </div>

    @push('scripts')
        <script>
            window.showToast = function (msg) {
                const t = document.getElementById('toast'); if (!t) return;
                const box = t.querySelector('div'); if (box) box.textContent = msg;
                t.classList.remove('hidden');
                clearTimeout(t._t); t._t = setTimeout(()=>t.classList.add('hidden'), 3000);
            };
            // se veio da geração sem escolher opção no modal
            if (new URLSearchParams(location.search).get('created') === '1') {
                window.addEventListener('DOMContentLoaded', () => showToast('Orçamento gerado!'));
            }
        </script>

        <script>
            // ================= Helpers básicos =================
            const $ = s => document.querySelector(s);
            const $$ = s => document.querySelectorAll(s);
            const BRLfmt = v => (Number(v) || 0).toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'});

            // estado de filtro
            let bStatus = 'all'; // pending | approved | rejected | all
            let bTerm = '';

            const bTbody = $('#tbody');

            function normalizeTotals(t) {
                const r = t?.recorr || {};
                const sinal = Number(t?.sinal ?? 0);
                const somaParcelas = Number(t?.parcelas?.soma ?? 0);
                const unico = Number(t?.unico ?? (sinal + somaParcelas));
                return {
                    unico,
                    mensal: Number(t?.mensal ?? r.mensal ?? 0),
                    anual: Number(t?.anual ?? r.anual ?? 0),
                    contrato: Number(t?.contrato ?? (unico + (r.mensal || 0) + (r.anual || 0)))
                };
            }

            function formatTotalCell(tot) {
                const {unico, mensal, anual} = normalizeTotals(tot);
                const hasUnico = unico > 0;
                const hasM = mensal > 0;
                const hasY = anual > 0;

                let main = '';
                let sub = '';

                if (hasUnico && (hasM || hasY)) {
                    const parts = [];
                    if (hasM) parts.push(`${BRLfmt(mensal)} /mês`);
                    if (hasY) parts.push(`${BRLfmt(anual)} /ano`);
                    main = parts.join(' + ');
                    sub = `inclui ${BRLfmt(unico)} em únicos`;
                } else if (hasM || hasY) {
                    const parts = [];
                    if (hasM) parts.push(`${BRLfmt(mensal)} /mês`);
                    if (hasY) parts.push(`${BRLfmt(anual)} /ano`);
                    main = parts.join(' + ');
                    sub = 'recorrente';
                } else {
                    main = BRLfmt(unico);
                    sub = 'único';
                }

                return {main, sub};
            }

            function scopeFromTotals(tot) {
                const {unico, mensal, anual} = normalizeTotals(tot);
                const hasUnico = unico > 0;
                const hasRec = (mensal > 0 || anual > 0);
                if (hasUnico && hasRec) return 'misto';
                if (hasRec) return 'recorrente';
                return 'único';
            }

            async function loadBudgets() {
                const r = await fetch('{{ route('budget.index') }}');
                const j = await r.json();
                window.__BUDGETS = j.data || [];
                drawBudgets();
            }

            function rowHTML(b) {
                const t = b.totais || {};
                const f = formatTotalCell(t);
                const escopo = b.escopo || scopeFromTotals(t);

                const norm = normalizeTotals(t);
                const bits = [];
                if (norm.unico > 0) bits.push(`Único: ${BRLfmt(norm.unico)}`);
                if (norm.mensal > 0) bits.push(`Mensal: ${BRLfmt(norm.mensal)}`);
                if (norm.anual > 0) bits.push(`Anual: ${BRLfmt(norm.anual)}`);
                const resumo = bits.length
                    ? `<div class="text-[11px] text-slate-500">${bits.join(' · ')}</div>`
                    : `<div class="text-[11px] text-slate-500">Sem valores</div>`;

                return `
<tr class="hover:bg-slate-50 text-center">
    <td class="px-6 py-3 whitespace-nowrap font-medium text-slate-900">#${b.code}</td>

    <td class="px-3 py-3">
        <div class="text-slate-900">${b.cliente}</div>
        ${resumo}
    </td>

    <td class="px-3 py-3 whitespace-nowrap">${b.criado_em}</td>

     <td class="px-3 py-3 text-center">
        <div class="font-medium text-slate-900">${f.main}</div>
        <div class="text-[11px] text-slate-500">${f.sub}</div>
    </td>

    <td class="px-3 py-3 whitespace-nowrap capitalize">${escopo}</td>

    <td class="px-3 py-3 whitespace-nowrap">
        ${statusToggleHTML(b.id, b.status)}
    </td>

    <td class="px-6 py-3 text-center whitespace-nowrap">
        ${actionsMenuHTML(b.id)}
    </td>
</tr>`;
            }

            function drawBudgets() {
                const data = window.__BUDGETS || [];

                const filtered = data
                    .filter(b => (bStatus === 'all' ? true : b.status === bStatus))
                    .filter(b => {
                        if (!bTerm) return true;
                        const term = bTerm.toLowerCase();
                        return (b.cliente || '').toLowerCase().includes(term)
                            || String(b.code).includes(term);
                    });

                bTbody.innerHTML = filtered.map(b => rowHTML(b)).join('');
            }

            function statusToggleHTML(id, status) {
                let color, label;
                if (status === 'approved') {
                    color = 'bg-emerald-100';
                    label = 'Aprovado';
                } else if (status === 'rejected') {
                    color = 'bg-rose-100';
                    label = 'Rejeitado';
                } else {
                    color = 'bg-amber-100';
                    label = 'Aberto';
                }

                return `
<div class="inline-flex items-center rounded-full ${color} px-2 py-1 gap-2 text-slate-700 text-[11px]">
    <button class="st-reject rounded-full h-6 w-6 grid place-items-center bg-rose-200/60 text-[12px] text-slate-700"
            data-id="${id}" title="Rejeitar">✕</button>
    <span class="font-medium">${label}</span>
    <button class="st-approve rounded-full h-6 w-6 grid place-items-center bg-emerald-200/60 text-[12px] text-slate-700"
            data-id="${id}" title="Aprovar">✓</button>
</div>`;
            }

            function actionsMenuHTML(id) {
                return `
                    <div class="relative inline-block text-left">
                        <button class="act-btn rounded-lg p-2 hover:bg-slate-100 focus:outline-none"
                                data-id="${id}" aria-haspopup="true" aria-expanded="false">⋮</button>

                        <div class="act-menu hidden absolute right-0 top-full z-50 mt-2 w-48 rounded-xl border border-slate-200 bg-white shadow-xl ring-1 ring-black/5"
                             data-for="${id}">
                            <ul class="py-1 text-sm text-slate-700">
                                <li><button class="block w-full px-3 py-2 text-left hover:bg-slate-50 act-view-pdf" data-id="${id}">Visualizar orçamento</button></li>
                                <li><button class="block w-full px-3 py-2 text-left hover:bg-slate-50 act-duplicate" data-id="${id}">Duplicar orçamento</button></li>
                                <li><button class="block w-full px-3 py-2 text-left hover:bg-slate-50 act-send-mail" data-id="${id}">Enviar por email</button></li>
                                <li><button class="block w-full px-3 py-2 text-left hover:bg-slate-50 act-download" data-id="${id}">Baixar orçamento</button></li>
                                <li class="border-t border-slate-200 mt-1 pt-1">
                                    <button class="block w-full px-3 py-2 text-left text-rose-600 hover:bg-rose-50 act-delete" data-id="${id}">Excluir orçamento</button>
                                </li>
                            </ul>
                        </div>
                    </div>`;
            }

            // Ações
            document.addEventListener('click', async e => {
                const approveBtn = e.target.closest('.st-approve');
                if (approveBtn) {
                    const id = approveBtn.dataset.id;
                    await fetch(`/budgets/${id}/approve`, {
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}
                    });
                    await loadBudgets();
                    return;
                }

                const rejectBtn = e.target.closest('.st-reject');
                if (rejectBtn) {
                    const id = rejectBtn.dataset.id;
                    await fetch(`/budgets/${id}/reject`, {
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}
                    });
                    await loadBudgets();
                    return;
                }

                const trigger = e.target.closest('.act-btn');
                $$('.act-menu').forEach(m => m.classList.add('hidden'));
                if (trigger) {
                    const id = trigger.dataset.id;
                    const menu = document.querySelector(`.act-menu[data-for="${id}"]`);
                    menu?.classList.toggle('hidden');
                }

                const idView = e.target.closest('.act-view-pdf')?.dataset.id;
                const idDup = e.target.closest('.act-duplicate')?.dataset.id;
                const idSend = e.target.closest('.act-send-mail')?.dataset.id;
                const idDown = e.target.closest('.act-download')?.dataset.id;
                const idDel = e.target.closest('.act-delete')?.dataset.id;

                if (idView) {
                    viewPdf(idView);
                }
                if (idDup) {
                    duplicateBudget(idDup);
                }
                if (idSend) {
                    sendEmail(idSend);
                }
                if (idDown) {
                    downloadPdf(idDown);
                }
                if (idDel) {
                    deleteBudget(idDel);
                }
            });

            document.addEventListener('keydown', e => {
                if (e.key === 'Escape') $$('.act-menu').forEach(m => m.classList.add('hidden'));
            });

            document.addEventListener('click', e => {
                const insideMenu = e.target.closest('.act-menu');
                const btnMenu = e.target.closest('.act-btn');
                if (!insideMenu && !btnMenu) $$('.act-menu').forEach(m => m.classList.add('hidden'));
            }, true);



            // -------- funções utilitárias de ação do menu --------

            // Visualizar orçamento (abre PDF em nova aba)
            async function viewPdf(id) {
                try {
                    const resp = await fetch(`/sales/budget/${id}/view-budget`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/pdf'
                        }
                    });

                    if (!resp.ok) {
                        console.error('Erro PDF view', await resp.text());
                        alert('Falha ao gerar o PDF.');
                        return;
                    }

                    const blob = await resp.blob();
                    const url = URL.createObjectURL(blob);
                    window.open(url, '_blank');
                } catch (err) {
                    console.error(err);
                    alert('Erro de rede ao gerar o PDF.');
                }
            }

            // Baixar orçamento (força download do mesmo PDF)
            async function downloadPdf(id) {
                try {
                    const resp = await fetch(`/sales/budget/${id}/view-budget`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/pdf'
                        }
                    });

                    if (!resp.ok) {
                        console.error('Erro PDF download', await resp.text());
                        alert('Falha ao gerar o PDF.');
                        return;
                    }

                    const blob = await resp.blob();
                    const url = URL.createObjectURL(blob);

                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `orcamento-${id}.pdf`;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    URL.revokeObjectURL(url);

                } catch (err) {
                    console.error(err);
                    alert('Erro de rede ao baixar o PDF.');
                }
            }

            // Enviar por email
            async function sendEmail(id) {
                try {
                    const resp = await fetch(`/sales/budget/${id}/send-email`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({})
                    });

                    if (!resp.ok) {
                        const txt = await resp.text();
                        console.error('send-email erro', txt);
                        alert('Falha ao enviar e-mail.');
                        return;
                    }

                    alert('E-mail enviado.');
                } catch (err) {
                    console.error(err);
                    alert('Erro de rede ao enviar e-mail.');
                }
            }

            // Duplicar orçamento
            function duplicateBudget(id) {
                // ideia: abrir a tela de criação já com os dados desse orçamento
                // você depois, no budget_create, lê esse query param e faz um fetch desse orçamento pra preencher
                window.location.href = "{{ route('budget.create') }}" + `?duplicate=${id}`;
            }

            // Excluir orçamento
            async function deleteBudget(id) {
                if (!confirm('Tem certeza que deseja excluir este orçamento?')) return;

                try {
                    const resp = await fetch(`/budgets/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    });

                    if (!resp.ok) {
                        console.error('Erro delete', await resp.text());
                        alert('Falha ao excluir.');
                        return;
                    }

                    // recarrega lista
                    await loadBudgets();
                } catch (err) {
                    console.error(err);
                    alert('Erro de rede ao excluir.');
                }
            }

            $$('.status-filter').forEach(btn => {
                btn.addEventListener('click', () => {
                    $$('.status-filter').forEach(x => {
                        x.classList.remove('bg-blue-600', 'text-white');
                        x.classList.add('bg-slate-100', 'text-slate-800');
                    });
                    btn.classList.remove('bg-slate-100', 'text-slate-800');
                    btn.classList.add('bg-blue-600', 'text-white');
                    bStatus = btn.dataset.status;
                    drawBudgets();
                });
            });

            $('#b-search').addEventListener('input', e => {
                bTerm = e.target.value.trim();
                drawBudgets();
            });

            (async function init() {
                await loadBudgets();
            })();
        </script>
    @endpush
@endsection
