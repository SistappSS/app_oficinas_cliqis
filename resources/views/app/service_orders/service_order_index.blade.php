@extends('layouts.templates.template')

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mt-4">
            <h1 class="text-xl font-semibold">Ordens de serviço</h1>

            <div class="ml-auto flex items-center gap-2 shrink-0">
                {{-- Nova OS --}}
                <a href="{{ route('service-order.create') }}"
                   id="btn-add"
                   class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-800">
                    Nova OS
                </a>

                {{-- Exportar (depois ligamos na rota/CSV) --}}
                <button
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow hover:bg-slate-50">
                    Exportar
                </button>

                {{-- Toggle header (segue padrão das outras telas, se já tiver script global) --}}
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
                       placeholder="Buscar por nº, cliente, ticket..."
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
                <button data-status-filter="completed"
                        class="px-3 py-1.5 rounded-full border border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100">
                    Concluída
                </button>
                <button data-status-filter="rejected"
                        class="px-3 py-1.5 rounded-full border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100">
                    Rejeitada
                </button>
            </div>
        </div>

        {{-- tabela --}}
        <div class="mt-5 mb-8 rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="">
                <table class="min-w-full text-sm">
                    <thead class="text-center text-slate-600 bg-blue-50">
                    <tr>
                        <th class="px-6 py-4 first:rounded-tl-2xl text-left">OS</th>
                        <th class="px-3 py-4 text-left">Cliente</th>
                        <th class="px-3 py-4 text-left">Data</th>
                        <th class="px-3 py-4 text-left">Ticket</th>
                        <th class="px-3 py-4 text-right">Total (R$)</th>
                        <th class="px-3 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right last:rounded-tr-2xl">Ações</th>
                    </tr>
                    </thead>
                    <tbody id="tbody" class="divide-y divide-slate-100"></tbody>
                </table>
            </div>
        </div>
    </div>

    @include('layouts.common.modal.modal_delete')
@endsection

@push('scripts')
    <script type="module" src="{{ asset('assets/js/template/views/service-orders/service-order-index.js') }}"></script>
@endpush
