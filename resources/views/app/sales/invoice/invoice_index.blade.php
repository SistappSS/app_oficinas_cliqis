@extends('layouts.templates.template')
@section('content')

    <style>
        #tbody td:last-child {
            white-space: nowrap;
        }

        #tbody td:last-child > .flex {
            gap: .375rem;
        }

        #tbody td:last-child [data-edit],
        #tbody td:last-child [data-del],
        #tbody td:last-child [data-manage-sub] {
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

        #tbody td:last-child [data-manage-sub]:hover {
            background: rgb(238 242 255); /* indigo-50 */
            border-color: rgb(199 210 254); /* indigo-200 */
            color: rgb(79 70 229); /* indigo-600 */
            box-shadow: 0 1px 2px rgba(2, 6, 23, .08);
            transform: translateY(-1px);
        }

        #tbody td:last-child [data-edit]:hover {
            background: rgb(239 246 255);
            border-color: rgb(191 219 254);
            color: rgb(29 78 216);
            box-shadow: 0 1px 2px rgba(2, 6, 23, .08);
            transform: translateY(-1px);
        }

        #tbody td:last-child [data-del]:hover {
            background: rgb(254 242 242);
            border-color: rgb(254 202 202);
            color: rgb(185 28 28);
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
                    <h1 class="text-xl font-semibold">Cobranças</h1>
                    <p class="text-sm text-slate-600">Pagamentos, pendências e atrasos.</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">


                    <button id="btn-add"
                            class="ml-3 rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                        Adicionar cobrança
                    </button>

                    <div class="flex items-center gap-2 border-slate-200">
                        <button id="btn-export"
                                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow hover:bg-slate-50 flex items-center gap-2">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.7">
                                <path d="M12 3v12m0 0-4-4m4 4 4-4"/>
                                <path d="M5 21h14"/>
                            </svg>
                            Exportar CSV
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
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">A receber</p>
                    <p id="kpi-pending" class="mt-3 text-3xl font-bold">R$ 0</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Em atraso</p>
                    <p id="kpi-overdue" class="mt-3 text-3xl font-bold">R$ 0</p>
                </div>
            </div>

            <!-- busca -->
            <div class="relative hidden sm:block">
                <input id="search" placeholder="Buscar cliente, fatura..."
                       class="w-[22rem] rounded-xl border border-slate-300 bg-white pl-10 pr-3 py-2.5 text-sm placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none"/>
                <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="M21 21l-4.3-4.3"/>
                </svg>
            </div>

            <div class="flex items-center justify-between gap-3">
                <!-- Tabs -->
                <div id="status-tabs" class="mt-3 inline-flex items-center gap-2 md:gap-3 rounded-full p-2 pl-3 pr-3">
                    <button data-status="all" class="tab-btn rounded-full px-3 py-1.5 text-sm bg-blue-600 text-white">
                        Todos
                    </button>
                    <button data-status="single" class="tab-btn rounded-full px-3 py-1.5 text-sm bg-slate-100">Cobranças
                        únicas
                    </button>
                    <button data-status="recurring" class="tab-btn rounded-full px-3 py-1.5 text-sm bg-slate-100">
                        Recorrentes
                    </button>
                    <button data-status="pending" class="tab-btn rounded-full px-3 py-1.5 text-sm bg-slate-100">
                        Pendentes
                    </button>
                    <button data-status="overdue" class="tab-btn rounded-full px-3 py-1.5 text-sm bg-slate-100">
                        Atrasados
                    </button>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <div class="flex items-center gap-2 mr-2">
                        <input id="month-filter" type="month"
                               class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none"/>
                    </div>

                    <span class="text-sm text-slate-600 mx-3">Agrupar por orçamento</span>
                    <button id="grp-toggle" aria-pressed="true"
                            class="relative h-6 w-11 rounded-full bg-blue-600 transition ring-1 ring-blue-200">
                        <span class="absolute left-5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabela -->
        <div class="mt-5 mb-8 rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-slate-600 bg-blue-50">
                    <tr>
                        <th class="px-6 py-4 first:rounded-tl-2xl">Cliente</th>
                        <th class="px-3 py-4">Fatura</th>
                        <th class="px-3 py-4">Vencimento</th>
                        <th class="px-3 py-4 text-right">Valor</th>
                        <th class="px-3 py-4 text-right">Parcelas</th>
                        <th class="px-3 py-4 text-right">Status</th>
                        <th class="px-3 py-4 text-center">Lembrete</th>
                        <th class="px-6 py-4 text-right last:rounded-tr-2xl">Ações</th>
                    </tr>
                    </thead>
                    <tbody id="tbody" class="divide-y divide-slate-100"></tbody>
                </table>
            </div>
        </div>
    </main>

    {{-- modais e toast permanecem iguais --}}

    <div id="modal-new" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm">
        <div
            class="absolute left-1/2 top-1/2 w-[min(560px,92vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">Nova cobrança</h2>
                <button data-close-new class="rounded-lg p-2 hover:bg-slate-100">
                    <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form id="form-new" class="mt-4 space-y-3">
                @csrf

                <div class="grid sm:grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm font-medium">Cliente</label>
                        <select id="n-client"
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"></select>
                    </div>
                    <div>
                        <label class="text-sm font-medium">Serviço</label>
                        <select id="n-service"
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"></select>
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm font-medium">Vencimento</label>
                        <input id="n-due" type="date" required
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"/>
                    </div>
                    <div>
                        <label class="text-sm font-medium">Valor (R$)</label>
                        <input id="n-amount" type="number" min="1" step="0.01" required
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"/>
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm font-medium">Parcelas</label>
                        <input id="n-install" type="number" min="1" step="1"
                               placeholder="1 = sem parcelar"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"/>
                    </div>
                    <div>
                        <label class="text-sm font-medium">Recorrente</label>
                        <select id="n-recurring"
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5">
                            <option value="0">Não</option>
                            <option value="monthly">Mensal</option>
                            <option value="yearly">Anual</option>
                        </select>
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input id="n-auto" type="checkbox" checked
                           class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    Enviar lembrete 3 dias antes
                </label>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" data-close-new
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="modal-edit" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm">
        <div
            class="absolute left-1/2 top-1/2 w-[min(560px,92vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">Editar cobrança</h2>
                <button data-close-edit class="rounded-lg p-2 hover:bg-slate-100">
                    <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form id="form-edit" class="mt-4 space-y-3">
                <input type="hidden" id="e-id">

                <div class="grid sm:grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm font-medium">Vencimento</label>
                        <input id="e-due" type="date" required
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"/>
                    </div>
                    <div>
                        <label class="text-sm font-medium">Valor (R$)</label>
                        <input id="e-amount" type="number" step="0.01" min="0" required
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"/>
                    </div>
                </div>

                <div class="flex justify-between gap-2 pt-2">
                    <button type="button" id="btn-del"
                            class="rounded-xl border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-100">
                        Excluir
                    </button>

                    <div class="ml-auto flex gap-2">
                        <button type="button" data-close-edit
                                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                            Salvar alterações
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="modal-reminder" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm">
        <div
            class="absolute left-1/2 top-1/2 w-[min(560px,92vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">Enviar lembrete por e-mail</h2>
                <button data-close-reminder class="rounded-lg p-2 hover:bg-slate-100">
                    <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="mt-4 space-y-3 text-sm">
                <div>
                    <p class="text-xs font-medium text-slate-500">Será enviado para</p>
                    <p id="rem-email" class="mt-0.5 text-sm font-semibold text-slate-800">-</p>
                </div>

                <div>
                    <p class="text-xs font-medium text-slate-500">Assunto</p>
                    <p id="rem-subject" class="mt-0.5 text-sm text-slate-800 break-words">-</p>
                </div>

                <div>
                    <p class="text-xs font-medium text-slate-500 mb-1">Pré-visualização do e-mail</p>
                    <div
                        class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 max-h-64 overflow-auto text-sm text-slate-700">
                        <p class="font-semibold mb-2">Lembrete de vencimento</p>
                        <p id="rem-body" class="whitespace-pre-line text-sm text-slate-700"></p>
                    </div>
                </div>

                <p id="rem-error" class="text-xs text-red-600 hidden"></p>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" data-close-reminder
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Cancelar
                </button>
                <button type="button" id="btn-rem-send"
                        class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                    Enviar
                </button>
            </div>
        </div>
    </div>

    <div id="toast" class="pointer-events-none fixed bottom-5 right-5 z-50 hidden">
        <div class="rounded-xl bg-slate-900/90 px-4 py-3 text-sm font-medium text-white shadow-lg">Mensagem</div>
    </div>

    @push('scripts')
        <script>
            const CUSTOMER_AREA_TEMPLATE = @json(
                route('customer-area', ['customer' => '__CUSTOMER__'])
            );
        </script>

        <script>
            const fmtBR = n => 'R$ ' + Number(n || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2});
            const $ = s => document.querySelector(s);
            const $$ = s => [...document.querySelectorAll(s)];

            const byDueAsc = (a, b) => {
                const da = a.due_date || '';
                const db = b.due_date || '';
                return da.localeCompare(db);
            };

            function formatDateBR(dateStr) {
                if (!dateStr) return '-';
                const [y, m, rest] = dateStr.split('-');
                if (!y || !m || !rest) return '-';
                const d = rest.split('T')[0].split(' ')[0];
                return `${d}/${m}/${y}`;
            }

            // helpers de mês
            function getMonthKey(dateStr) {
                if (!dateStr) return 'no-date';
                const [y, m] = dateStr.split('-');
                if (!y || !m) return 'no-date';
                return `${y}-${m}`;
            }

            const MONTH_NAMES = [
                'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
            ];

            function formatMonthLabel(key) {
                if (key === 'no-date') return 'Sem vencimento definido';
                const [y, m] = key.split('-');
                const idx = parseInt(m, 10) - 1;
                const name = MONTH_NAMES[idx] || '';
                return `${name} ${y}`;
            }

            const tbody = $('#tbody');
            const search = $('#search');
            const monthFilterInput = $('#month-filter');
            const toastBox = $('#toast');

            const todayMid = () => new Date(new Date().toDateString());
            const pad6 = v => ('#' + String(v ?? '').padStart(6, '0'));

            function effectiveStatus(i) {
                return i.status || 'pending';
            }

            async function loadCustomers() {
                const r = await fetch('/entities/customer-api');
                const j = await r.json();
                const sel = $('#n-client');
                sel.innerHTML = '<option value="">Selecione...</option>';
                (j.data || []).forEach(c => {
                    sel.innerHTML += `<option value="${c.id}">${c.name}</option>`;
                });
            }

            async function loadServices() {
                const s = await fetch('/sales/service-api');
                const j = await s.json();
                const serv = $('#n-service');
                serv.innerHTML = '<option value="">Selecione...</option>';
                (j.data || []).forEach(s => {
                    serv.innerHTML += `<option value="${s.id}">${s.name}</option>`;
                });
            }

            function toast(msg) {
                const box = toastBox.firstElementChild;
                box.textContent = msg;
                toastBox.classList.remove('hidden');
                setTimeout(() => toastBox.classList.add('hidden'), 1600);
            }

            let currentReminderId = null;

            // Analisa a descrição do item pra descobrir rótulo de parcela
            function parseInstallment(inv) {
                const d = inv?.items?.[0]?.description || '';

                const m = d.match(/Parcela\s+(\d+)(?:\s*\/\s*(\d+))?/i);
                if (m) return {kind: 'parcel', i: Number(m[1]), n: m[2] ? Number(m[2]) : null};

                if (/Finaliza(?:ção)?/i.test(d)) return {kind: 'final'};
                if (/Sinal/i.test(d)) return {kind: 'signal'};

                return null;
            }

            function parcelBadge(inv) {
                if (inv.kind === 'subscription') {
                    if (inv.period === 'monthly') {
                        return '<span class="inline-flex rounded-full bg-green-50 text-green-700 px-2.5 py-1 text-xs font-medium">Mensal</span>';
                    }
                    if (inv.period === 'yearly') {
                        return '<span class="inline-flex rounded-full bg-indigo-50 text-indigo-700 px-2.5 py-1 text-xs font-medium">Anual</span>';
                    }
                    return '<span class="inline-flex rounded-full bg-indigo-50 text-indigo-700 px-2.5 py-1 text-xs font-medium">Recorrente</span>';
                }

                const meta = parseInstallment(inv);

                if (meta?.kind === 'signal') {
                    return '<span class="inline-flex rounded-full bg-blue-50 text-blue-700 px-2.5 py-1 text-xs font-medium">Sinal</span>';
                }
                if (meta?.kind === 'final') {
                    return '<span class="inline-flex rounded-full bg-violet-50 text-violet-700 px-2.5 py-1 text-xs font-medium">Finalização</span>';
                }
                if (meta?.kind === 'parcel') {
                    const txt = meta.n ? `Parcela ${meta.i}/${meta.n}` : `Parcela ${meta.i}`;
                    return `<span class="inline-flex rounded-full bg-rose-50 text-rose-400 px-2.5 py-1 text-xs font-medium">${txt}</span>`;
                }

                const n = Number(inv.installments || 1);
                const txt = n === 1 ? 'Parcela 1x' : `Parcela ${n}`;
                return `<span class="inline-flex rounded-full bg-rose-50 text-rose-400 px-2.5 py-1 text-xs font-medium">${txt}</span>`;
            }

            function actionButtons(i) {
                if (i.kind === 'subscription') {
                    return `<div class="flex justify-end gap-1.5">
    <button class="rounded-lg p-2 text-slate-600 hover:text-emerald-700 hover:bg-emerald-50"
            title="Enviar lembrete por e-mail" data-reminder="${i.id}">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
        <path d="M3 4l18 8-18 8 4-8-4-8z"/>
      </svg>
    </button>
    <button class="rounded-lg p-2 text-slate-600 hover:text-indigo-700 hover:bg-indigo-50"
            title="Gerenciar recorrência"
            data-manage-sub="${i.id}"
            data-customer="${i.customer?.id ?? ''}">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <path d="M4.5 9A7.5 7.5 0 0 1 12 3.5c2.1 0 4.1.9 5.5 2.5"/>
        <path d="M19.5 15A7.5 7.5 0 0 1 12 20.5 7.5 7.5 0 0 1 6 18"/>
        <path d="M16 5h2.5V2.5"/>
        <path d="M8 19H5.5V21.5"/>
      </svg>
    </button>
  </div>`;
                }

                return `<div class="flex justify-end gap-1.5">
    <button class="rounded-lg p-2 text-slate-600 hover:text-emerald-700 hover:bg-emerald-50"
            title="Enviar lembrete por e-mail" data-reminder="${i.id}">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
        <path d="M3 4l18 8-18 8 4-8-4-8z"/>
      </svg>
    </button>
    <button class="rounded-lg p-2 text-slate-600 hover:text-blue-700 hover:bg-blue-50"
            title="Editar" data-edit="${i.id}">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
        <path d="M3 17.25V21h3.75l11-11L14 6.25l-11 11Z"/>
        <path d="m14 6.25 2.75-2.75L19.5 6.25 16.75 9 14 6.25Z"/>
      </svg>
    </button>
    <button class="rounded-lg p-2 text-slate-600 hover:text-red-700 hover:bg-red-50"
            data-del="${i.id}" title="Excluir">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
        <path d="M6 7h12v13a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V7Zm2-3h8l1-3H7l1 3Z"/>
      </svg>
    </button>
  </div>`;
            }

            function rowHTML(i, {showReminder = !groupByBudget} = {}) {
                const isOverdue = (effectiveStatus(i) === 'overdue');
                const chip = isOverdue
                    ? '<span class="inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700">Atrasado</span>'
                    : '<span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">Pendente</span>';

                const reminder = !showReminder
                    ? ''
                    : `<label class="relative inline-flex cursor-pointer select-none items-center">
                     <input type="checkbox" class="sr-only peer"
                            data-auto="${i.id}" ${i.auto_reminder ? 'checked' : ''}>
                     <span class="w-10 h-5 rounded-full bg-slate-300 transition-colors peer-checked:bg-blue-600"></span>
                     <span class="absolute left-0.5 top-1/2 -translate-y-1/2 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                   </label>`;

                return `<tr class="hover:bg-slate-50 text-center">
  <td class="px-6 py-3">
    <div class="flex items-center gap-3">
      <span class="grid h-8 w-8 place-items-center rounded-full bg-blue-100 text-blue-700 font-semibold">
        ${(i.customer?.name || '?').slice(0, 1)}
      </span>
      <p class="font-medium truncate max-w-[220px]">${i.customer?.name || '-'}</p>
    </div>
  </td>

  <td class="px-3 py-3">
    <div class="font-medium">${i.number ? i.number : '—'}</div>
  </td>

  <td class="px-3 py-3">${i.due_date ? formatDateBR(i.due_date) : '-'}</td>

  <td class="px-3 py-3 text-right">${fmtBR(i.amount)}</td>
  <td class="px-3 py-3 text-right">${parcelBadge(i)}</td>
  <td class="px-3 py-3 text-right">${chip}</td>
  <td class="px-3 py-3 text-center">${reminder}</td>
  <td class="px-6 py-3 text-right">${actionButtons(i)}</td>
</tr>`;
            }

            function buildGroups(arr) {
                const map = new Map();

                arr.forEach(inv => {
                    const gid = (inv.budget?.id != null)
                        ? `b_${inv.budget.id}`
                        : (inv.customer?.id != null)
                            ? `c_${inv.customer.id}`
                            : 'c_0';

                    if (!map.has(gid)) {
                        map.set(gid, {
                            id: gid,
                            budget_code: inv.budget?.budget_code ?? null,
                            customer_name: inv.customer?.name ?? '-',
                            items: [],
                            total: 0,
                            firstDue: null
                        });
                    }

                    const g = map.get(gid);
                    g.items.push(inv);
                    g.total += Number(inv.amount || 0);

                    if (!g.firstDue || (inv.due_date && inv.due_date < g.firstDue)) {
                        g.firstDue = inv.due_date || g.firstDue;
                    }
                });

                const groups = [...map.values()];

                // ordena itens internos por vencimento
                groups.forEach(g => {
                    g.items.sort((a, b) => (a.due_date || '').localeCompare(b.due_date || ''));
                });

                // ordena grupos por budget_code desc
                groups.sort((a, b) => {
                    const aNum = a.budget_code
                        ? parseInt(String(a.budget_code).replace(/\D/g, ''), 10) || 0
                        : 0;
                    const bNum = b.budget_code
                        ? parseInt(String(b.budget_code).replace(/\D/g, ''), 10) || 0
                        : 0;

                    if (aNum !== bNum) return bNum - aNum; // desc
                    return (a.customer_name || '').localeCompare(b.customer_name || '');
                });

                return groups;
            }

            function drawInvoices() {
                // modo sem agrupamento: separa por mês
                if (!groupByBudget) {
                    const items = [...__INVS];
                    items.sort(byDueAsc);

                    const rows = [];
                    let currentMonthKey = null;

                    for (const inv of items) {
                        const key = getMonthKey(inv.due_date || '');
                        if (key !== currentMonthKey) {
                            currentMonthKey = key;
                            rows.push(`
<tr class="bg-blue-50/60">
  <td colspan="8" class="px-6 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600">
    ${formatMonthLabel(key)}
  </td>
</tr>`);
                        }
                        rows.push(rowHTML(inv));
                    }

                    tbody.innerHTML = rows.join('');
                    return;
                }

                // modo agrupado
                const groups = buildGroups(__INVS);
                const rows = [];

                for (const g of groups) {
                    const boxId = `grp_${g.id}`;

                    rows.push(`
      <tr class="bg-slate-50/60">
        <td colspan="8" class="px-3 py-2">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <button type="button" class="rounded-lg p-1.5 hover:bg-white"
                      data-toggle="${boxId}" aria-expanded="false">
                <svg class="h-4 w-4 transition-transform" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
              </button>
              <div>
                <div class="font-semibold">
                  Orçamento ${g.budget_code ? '#' + g.budget_code : '(sem orçamento)'} — ${g.customer_name}
                </div>
                <div class="text-xs text-slate-600">
                  Próx. venc.: ${g.firstDue ? formatDateBR(g.firstDue) : '-'} • ${g.items.length} documento(s)
                </div>
              </div>
            </div>

            <div class="flex items-center gap-4">
              <div class="text-sm font-semibold">${fmtBR(g.total)}</div>
              <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">
                Pendente
              </span>
            </div>
          </div>

          <div class="mt-2 rounded-xl border border-slate-200 bg-white hidden" data-box="${boxId}">
            <table class="min-w-full text-sm">
              <tbody>
                ${g.items.map(rowHTML).join('')}
              </tbody>
            </table>
          </div>
        </td>
      </tr>`);
                }

                tbody.innerHTML = rows.join('');

                tbody.querySelectorAll('[data-toggle]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const id = btn.getAttribute('data-toggle');
                        const box = tbody.querySelector(`[data-box="${id}"]`);
                        const svg = btn.querySelector('svg');
                        const exp = btn.getAttribute('aria-expanded') === 'true';
                        btn.setAttribute('aria-expanded', exp ? 'false' : 'true');
                        box?.classList.toggle('hidden');
                        svg?.classList.toggle('rotate-180');
                    });
                });
            }

            async function exportCsv() {
                const r = await fetch('/invoices', {headers: {'Accept': 'application/json'}});
                const j = await r.json();

                const head = ['Cliente', 'Fatura', 'Vencimento', 'Valor', 'Parcelas', 'Status'];

                const rows = (j.data || []).map(i => [
                    i.customer?.name || '',
                    i.number,
                    i.due_date || '',
                    String(i.amount).replace('.', ','),
                    i.installments || 1,
                    i.status
                ]);

                const csv = [head, ...rows]
                    .map(r => r.map(v => `"${String(v).replace(/"/g, '""')}"`).join(';'))
                    .join('\n');

                const blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
                const url2 = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url2;
                a.download = 'cobrancas.csv';
                document.body.appendChild(a);
                a.click();
                a.remove();
                URL.revokeObjectURL(url2);
            }

            async function openReminderModal(id) {
                const modal = $('#modal-reminder');
                const emailEl = $('#rem-email');
                const subjEl = $('#rem-subject');
                const bodyEl = $('#rem-body');
                const errEl = $('#rem-error');
                const btnSend = $('#btn-rem-send');

                currentReminderId = id;

                // estado inicial / carregando
                errEl.classList.add('hidden');
                errEl.textContent = '';
                emailEl.textContent = 'Carregando...';
                subjEl.textContent = 'Carregando...';
                bodyEl.textContent = 'Carregando pré-visualização...';

                btnSend.disabled = true;
                btnSend.classList.add('opacity-70', 'cursor-not-allowed');

                modal.classList.remove('hidden');

                try {
                    const r = await fetch(`/invoices/${id}/send-reminder-preview`, {
                        headers: {'Accept': 'application/json'}
                    });
                    const j = await r.json().catch(() => null);

                    if (!r.ok || !j?.ok) {
                        throw new Error(j?.message || 'Erro ao gerar pré-visualização.');
                    }

                    emailEl.textContent = j.email || '-';
                    subjEl.textContent = j.subject || '-';
                    bodyEl.textContent = j.body || '';

                    btnSend.disabled = false;
                    btnSend.classList.remove('opacity-70', 'cursor-not-allowed');
                } catch (e) {
                    emailEl.textContent = '-';
                    subjEl.textContent = '-';
                    bodyEl.textContent = '';

                    errEl.textContent = e.message || 'Erro ao carregar pré-visualização.';
                    errEl.classList.remove('hidden');
                }
            }

            function bindTableActions() {
                tbody.addEventListener('click', async (e) => {
                    const reminder = e.target.closest('[data-reminder]');

                    if (reminder) {
                        const id = reminder.dataset.reminder;
                        openReminderModal(id);
                        return;
                    }

                    const manage = e.target.closest('[data-manage-sub]');
                    if (manage) {
                        const customerId = manage.dataset.customer;
                        const subscriptionId = manage.dataset.manageSub;

                        if (!customerId) {
                            alert('Cliente não encontrado para esta recorrência.');
                            return;
                        }

                        // monta URL a partir do template gerado no Blade
                        const url = CUSTOMER_AREA_TEMPLATE.replace('__CUSTOMER__', customerId)
                            + `?sub=${encodeURIComponent(subscriptionId)}`;

                        window.location.href = url;
                        return;
                    }

                    const edit = e.target.closest('[data-edit]');
                    const del = e.target.closest('[data-del]');

                    if (edit) {
                        const id = edit.dataset.edit;
                        const r = await fetch(`/invoices?ids[]=${id}`, {
                            headers: {'Accept': 'application/json'}
                        });
                        const j = await r.json();
                        const it = (j.data || [])[0];
                        if (!it) return;

                        $('#e-id').value = it.id;
                        $('#e-due').value = it.due_date || '';
                        $('#e-amount').value = it.amount || 0;

                        $('#modal-edit').classList.remove('hidden');

                        // excluir dentro do modal
                        $('#btn-del').onclick = async () => {
                            if (confirm('Excluir cobrança?')) {
                                const r = await fetch(`/invoices/${it.id}`, {
                                    method: 'DELETE',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    }
                                });
                                if (r.ok) {
                                    $('#modal-edit').classList.add('hidden');
                                    toast('Excluída');
                                    loadInvoices();
                                } else {
                                    alert('Erro ao excluir');
                                }
                            }
                        };

                        return;
                    }

                    if (del) {
                        const id = del.dataset.del;
                        if (confirm('Excluir cobrança?')) {
                            const r = await fetch(`/invoices/${id}`, {
                                method: 'DELETE',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                }
                            });
                            if (r.ok) {
                                toast('Excluída');
                                loadInvoices();
                            } else {
                                alert('Erro ao excluir');
                            }
                        }
                    }
                });

                tbody.addEventListener('change', async (e) => {
                    const ck = e.target.closest('input[data-auto]');
                    if (!ck) return;

                    const id = ck.dataset.auto;
                    await fetch(`/invoices/${id}`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            auto_reminder: ck.checked ? 1 : 0
                        })
                    });

                    toast(ck.checked ? 'Lembrete ativado' : 'Lembrete desativado');
                });
            }

            // modais (novo / editar)
            function bindModals() {
                const modalNew = $('#modal-new');
                const modalEdit = $('#modal-edit');

                // abrir modal nova cobrança
                $('#btn-add')?.addEventListener('click', () => {
                    modalNew.classList.remove('hidden');
                    loadCustomers();
                    loadServices();
                });

                // fechar modal nova cobrança
                $$('[data-close-new]').forEach(b =>
                    b.addEventListener('click', () => modalNew.classList.add('hidden'))
                );

                // submit nova cobrança
                $('#form-new')?.addEventListener('submit', async (e) => {
                    e.preventDefault();

                    const cid = $('#n-client').value;
                    const sid = $('#n-service').value;
                    if (!cid) return alert('Selecione um cliente');

                    const body = {
                        customer_id: Number(cid),
                        service_id: Number(sid),
                        due_date: $('#n-due').value,
                        amount: Number($('#n-amount').value || 0),
                        installments: Number($('#n-install').value || 1),
                        is_recurring: $('#n-recurring').value !== '0',
                        recurring_period: $('#n-recurring').value === '0' ? null : $('#n-recurring').value,
                        auto_reminder: $('#n-auto').checked
                    };

                    const r = await fetch('/invoices', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(body)
                    });

                    if (r.ok) {
                        modalNew.classList.add('hidden');
                        e.target.reset();
                        toast('Criada');
                        loadInvoices();
                    } else {
                        alert('Erro ao criar');
                    }
                });

                // fechar modal editar
                $$('[data-close-edit]').forEach(b =>
                    b.addEventListener('click', () => modalEdit.classList.add('hidden'))
                );

                // salvar edição
                $('#form-edit')?.addEventListener('submit', async (e) => {
                    e.preventDefault();

                    const id = $('#e-id').value;
                    const body = {
                        due_date: $('#e-due').value,
                        amount: Number($('#e-amount').value || 0)
                    };

                    const r = await fetch(`/invoices/${id}`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(body)
                    });

                    if (r.ok) {
                        $('#modal-edit').classList.add('hidden');
                        toast('Atualizada');
                        loadInvoices();
                    } else {
                        alert('Erro ao salvar');
                    }
                });
            }

            function bindReminderModal() {
                const modal = $('#modal-reminder');
                if (!modal) return;

                const btnSend = $('#btn-rem-send');
                const errEl = $('#rem-error');

                // enviar
                btnSend.addEventListener('click', async () => {
                    if (!currentReminderId) return;

                    errEl.classList.add('hidden');
                    errEl.textContent = '';

                    const originalText = btnSend.textContent;
                    btnSend.textContent = 'Enviando...';
                    btnSend.disabled = true;
                    btnSend.classList.add('opacity-70', 'cursor-not-allowed');

                    try {
                        const r = await fetch(`/invoices/${currentReminderId}/send-reminder`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });

                        const j = await r.json().catch(() => null);

                        if (!r.ok || !j?.ok) {
                            throw new Error(j?.message || 'Erro ao enviar lembrete.');
                        }

                        modal.classList.add('hidden');
                        currentReminderId = null;
                        toast(j.message || 'Lembrete enviado');
                    } catch (e) {
                        errEl.textContent = e.message || 'Erro ao enviar lembrete.';
                        errEl.classList.remove('hidden');
                    } finally {
                        btnSend.textContent = originalText;
                        btnSend.disabled = false;
                        btnSend.classList.remove('opacity-70', 'cursor-not-allowed');
                    }
                });

                // fechar (X e botão cancelar)
                $$('[data-close-reminder]').forEach(b => {
                    b.addEventListener('click', () => {
                        modal.classList.add('hidden');
                        currentReminderId = null;
                    });
                });
            }

            function bindFilters() {
                search?.addEventListener('input', loadInvoices);

                if (monthFilterInput) {
                    monthFilterInput.addEventListener('change', () => {
                        monthFilter = monthFilterInput.value || '';
                        loadInvoices();
                    });

                    monthFilterInput.addEventListener('input', () => {
                        if (!monthFilterInput.value) {
                            monthFilter = '';
                            loadInvoices();
                        }
                    });
                }

                $('#btn-export')?.addEventListener('click', exportCsv);
            }

            let __INVS = [];
            let tabFilter = 'all';

            let groupByBudget = (localStorage.getItem('billing_group_by_budget') ?? '1') === '1';
            let monthFilter = '';

            (function paintGroupToggle() {
                const b = document.getElementById('grp-toggle');
                b.setAttribute('aria-pressed', groupByBudget ? 'true' : 'false');
                b.classList.toggle('bg-blue-600', groupByBudget);
                b.classList.toggle('bg-slate-300', !groupByBudget);
                b.querySelector('span').style.left = groupByBudget ? '1.25rem' : '0.25rem';
            })();

            document.getElementById('grp-toggle').addEventListener('click', () => {
                groupByBudget = !groupByBudget;
                localStorage.setItem('billing_group_by_budget', groupByBudget ? '1' : '0');

                const b = document.getElementById('grp-toggle');
                b.setAttribute('aria-pressed', groupByBudget ? 'true' : 'false');
                b.classList.toggle('bg-blue-600', groupByBudget);
                b.classList.toggle('bg-slate-300', !groupByBudget);
                b.querySelector('span').style.left = groupByBudget ? '1.25rem' : '0.25rem';

                if (!groupByBudget) {
                    __INVS.sort(byDueAsc);
                }
                drawInvoices();
            });

            let statusFilter = 'all';

            document.querySelectorAll('#status-tabs .tab-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('#status-tabs .tab-btn').forEach(x => {
                        x.className = 'tab-btn rounded-full px-3 py-1.5 text-sm bg-slate-100';
                    });
                    btn.className = 'tab-btn rounded-full px-3 py-1.5 text-sm bg-blue-600 text-white';

                    statusFilter = btn.dataset.status || 'all';

                    loadInvoices();
                });
            });

            async function loadInvoices() {
                const params = new URLSearchParams();

                params.set('tab', statusFilter);

                const q = (search?.value || '').trim();
                if (q) params.set('q', q);

                const r = await fetch('/invoices?' + params.toString(), {
                    headers: {'Accept': 'application/json'}
                });

                const j = await r.json();

                let data = j.data || [];

                // filtro por mês (client side)
                if (monthFilter) {
                    data = data.filter(i => {
                        const d = i.due_date || '';
                        if (!d) return false;
                        return d.startsWith(monthFilter);
                    });
                }

                __INVS = data;

                if (!groupByBudget) {
                    __INVS.sort(byDueAsc);
                }

                if (monthFilter) {
                    let kPending = 0;
                    let kOverdue = 0;

                    __INVS.forEach(i => {
                        const amt = Number(i.amount || 0);
                        const st = effectiveStatus(i);
                        if (st === 'pending') kPending += amt;
                        if (st === 'overdue') kOverdue += amt;
                    });

                    $('#kpi-pending').textContent = fmtBR(kPending);
                    $('#kpi-overdue').textContent = fmtBR(kOverdue);
                } else {
                    $('#kpi-pending').textContent = fmtBR(j.kpi_pending || 0);
                    $('#kpi-overdue').textContent = fmtBR(j.kpi_overdue || 0);
                }

                drawInvoices();
            }

            bindTableActions();
            bindModals();
            bindReminderModal();
            bindFilters();
            loadInvoices();
        </script>
    @endpush

@endsection
