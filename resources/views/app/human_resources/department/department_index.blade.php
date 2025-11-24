@extends('layouts.templates.template')

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-xl font-semibold">Departamentos</h1>
        </div>

        <!-- busca + ações na mesma linha -->
        <div class="mt-4 flex items-center gap-4">
            <div class="relative w-full max-w-xl">
                <input id="search" placeholder="Buscar por nome ou descrição..."
                       class="w-full rounded-xl border border-slate-300 bg-white pl-10 pr-3 py-2.5 placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none"/>
                <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="M21 21l-4.3-4.3"/>
                </svg>
            </div>

            <!-- botões de ação -->
            <div class="ml-auto flex items-center gap-2 shrink-0">
                <button id="btn-add"
                        class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-800">
                    Adicionar departamento
                </button>
                <button
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow hover:bg-slate-50">
                    Exportar
                </button>
                <button id="toggle-header"
                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow hover:bg-slate-50"
                        aria-expanded="true" aria-controls="header-collapsible" type="button"
                        title="Expandir/contrair cabeçalho">
                    <i id="toggle-icon" class="fa-solid fa-up-right-and-down-left-from-center"></i>
                </button>
            </div>
        </div>

        <!-- Tabela -->
        <div class="mt-5 mb-8 rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="">
                <table class="min-w-full text-sm">
                    <thead class="text-center text-slate-600 bg-blue-50">
                    <tr>
                        <th class="px-6 py-4 first:rounded-tl-2xl">Departamento</th>
                        <th class="px-3 py-4">Descrição</th>
                        <th class="px-6 py-4 text-right last:rounded-tr-2xl">Ações</th>
                    </tr>
                    </thead>
                    <tbody id="tbody" class="divide-y divide-slate-100"></tbody>
                </table>
            </div>
        </div>
    </div>

    @include('app.human_resources.department.department_modal')
    @include('layouts.common.modal.modal_delete')
@endsection

@push('scripts')
    <script type="module" src="{{ asset('assets/js/template/views/human-resources/department.js') }}"></script>
@endpush
