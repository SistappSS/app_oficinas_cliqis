@extends('layouts.templates.template')

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6 py-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-xl font-semibold">Módulos</h1>
            <div class="">
                <button id="btn-open-module-modal"
                        class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-800">
                    Novo Módulo
                </button>
                <button id="toggle-header"
                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow hover:bg-slate-50"
                        aria-expanded="true" aria-controls="header-collapsible" type="button"
                        title="Expandir/contrair cabeçalho">
                    <i id="toggle-icon" class="fa-solid fa-up-right-and-down-left-from-center"></i>
                </button>
            </div>
        </div>

        <div class="mt-3 rounded-2xl border border-slate-200 bg-white shadow-sm overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-slate-500">
                <tr>
                    <th class="px-4 py-3">Módulo</th>
                    <th class="px-4 py-3">Valor</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Cadastrado em</th>
                    <th class="px-4 py-3 text-center">Ações</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100" id="module-table-body">
                @foreach($modules as $module)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium">{{ $module->name }}</td>
                        <td class="px-4 py-3">{{ number_format($module->price, 2, ',', '.') }}</td>
                        <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs {{ $module->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $module->is_active ? 'Ativo' : 'Inativo' }}
                        </span>
                        </td>
                        <td class="px-4 py-3">{{ $module->created_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 space-x-2">
                            <button class="text-blue-600 hover:underline edit-module" data-id="{{ $module->id }}">Editar</button>
                            <button class="text-red-600 hover:underline delete-module" data-id="{{ $module->id }}">Excluir</button>
                            <button class="text-green-600 hover:underline add-feature" data-id="{{ $module->id }}">+ Feature</button>
                        </td>
                    </tr>
                    @if($module->features->count())
                        <tr>
                            <td colspan="5" class="bg-slate-50 px-4 py-2">
                                <ul class="text-sm space-y-1">
                                    @foreach($module->features as $f)
                                        <li class="flex justify-between">
                                            <span>{{ $f->name }} — R$ {{ number_format($f->price, 2, ',', '.') }}</span>
                                            <span class="text-slate-500">{{ implode(', ', $f->roles ?? []) }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal de Novo Módulo -->
    <div id="module-modal" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm">
        <div class="absolute left-1/2 top-1/2 w-[min(700px,95vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold">Novo Módulo</h2>
                <button id="btn-close-module-modal" class="rounded-lg p-2 hover:bg-slate-100">
                    <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form id="formModule" method="POST" action="{{ route('module.store') }}" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Módulo</label>
                        <input type="text" id="name" name="name" placeholder="Financeiro" required
                               class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-blue-600 focus:ring-2 focus:ring-blue-200">
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-slate-700 mb-1">Descrição</label>
                        <textarea id="description" name="description" rows="4" placeholder="Esse módulo irá habilitar as opções .."
                                  class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-blue-600 focus:ring-2 focus:ring-blue-200"></textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="price" class="block text-sm font-medium text-slate-700 mb-1">Valor</label>
                            <input type="number" step="0.01" id="price" name="price" placeholder="0.00"
                                   class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-blue-600 focus:ring-2 focus:ring-blue-200">
                        </div>

                        <div>
                            <label for="icon" class="block text-sm font-medium text-slate-700 mb-1">Ícone</label>
                            <input type="text" id="icon" name="icon" placeholder="ex: lucide-wallet"
                                   class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-blue-600 focus:ring-2 focus:ring-blue-200">
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="is_active" name="is_active" value="1"
                               class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500">
                        <label for="is_active" class="text-sm text-slate-700">Módulo ativo?</label>
                    </div>

                    @php($segments = $segments ?? config('segments'))
                    @php($checked = collect($checkedSegments ?? []))

                    <fieldset class="mt-6">
                        <legend class="text-sm font-medium text-slate-700">Obrigatório por segmento</legend>
                        <p class="text-xs text-slate-500 mb-2">Se marcado, este módulo aparecerá pré-selecionado e travado para o segmento.</p>

                        <div class="grid sm:grid-cols-3 gap-3">
                            @foreach($segments as $seg)
                                @php($id = 'seg-'.$seg)
                                <label for="{{ $id }}" class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-2">
                                    <span class="text-sm capitalize">{{ $seg }}</span>
                                    <input
                                        id="{{ $id }}"
                                        type="checkbox"
                                        name="required_segments[]"
                                        value="{{ $seg }}"
                                        class="h-4 w-4 accent-blue-600"
                                        @checked($checked->contains($seg))
                                    >
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" id="btn-cancel-module"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                        Salvar Módulo
                    </button>
                </div>
            </form>
        </div>
    </div>

    @include('app.modules.feature.feature_modal')

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Modal de módulo
            const moduleModal = document.getElementById('module-modal');
            document.getElementById('btn-open-module-modal')?.addEventListener('click', () => moduleModal.classList.remove('hidden'));
            document.getElementById('btn-close-module-modal')?.addEventListener('click', () => moduleModal.classList.add('hidden'));
            document.getElementById('btn-cancel-module')?.addEventListener('click', () => moduleModal.classList.add('hidden'));

            // Modal de feature
            const featureModal = document.getElementById('feature-modal');
            const moduleSelectInFeature = featureModal?.querySelector('select[name="module_id"]');
            document.querySelectorAll('.add-feature').forEach(btn => {
                btn.addEventListener('click', () => {
                    if (moduleSelectInFeature) moduleSelectInFeature.value = btn.dataset.id;
                    featureModal.classList.remove('hidden');
                });
            });

            document.getElementById('btn-close-feature-modal')?.addEventListener('click', () => featureModal.classList.add('hidden'));
            document.getElementById('btn-cancel-feature')?.addEventListener('click', () => featureModal.classList.add('hidden'));
        });
    </script>
@endpush
