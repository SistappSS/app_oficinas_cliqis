@extends('layouts.templates.template')

@section('content')
    <main class="flex-1 mx-auto max-w-7xl w-full px-6 py-6">
        <section class="max-w-6xl mx-auto flex flex-col gap-6">
            <!-- Header centralizado -->
            <div class="mb-2 flex flex-col items-center gap-3">
                <div class="text-center">
                    <h1 class="text-3xl sm:text-4xl font-bold">Catálogo</h1>
                    <p class="mt-2 text-slate-600 text-sm">
                        Equipamentos cadastrados. Clique para ver o catálogo de peças vinculadas.
                    </p>
                </div>
            </div>

            <!-- Busca -->
            <div class="mx-auto w-full max-w-5xl">
                <div class="relative">
                    <input
                        id="search"
                        type="text"
                        placeholder="Buscar equipamentos (ex.: balança, impressora, CLP)…"
                        class="w-full rounded-2xl border border-slate-300 bg-white pl-11 pr-4 py-3 outline-none placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200"/>
                    <svg class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400"
                         xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="M21 21l-4.3-4.3"/>
                    </svg>
                </div>

                <!-- Chips info -->
                <div id="chips" class="mt-3 flex flex-wrap gap-2 text-xs"></div>
            </div>

            <!-- Botão alinhado à direita acima dos cards -->
            <div class="mt-2 flex justify-end">
                <button
                    type="button"
                    id="btn-add-equipment"
                    class="inline-flex items-center justify-center rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow hover:bg-blue-800">
                    Adicionar equipamento
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

            <!-- Grid de cards -->
            <div class="mt-4">
                <div id="cards" class="grid gap-3 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3"></div>
                <p id="empty-state" class="mt-6 hidden text-center text-sm text-slate-500">
                    Nenhum equipamento encontrado com essa busca.
                </p>
            </div>
        </section>
    </main>

    <!-- Modal (criar/editar) -->
    <div id="edit-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 backdrop-blur-sm">
        <div
            class="w-[calc(100vw-16px)] sm:w-full sm:max-w-lg h-[calc(100dvh-16px)] sm:h-auto sm:max-h-[90vh] flex flex-col rounded-2xl bg-white shadow-xl">
            <div
                class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-100 bg-white px-5 py-3.5">
                <h2 id="edit-modal-title" class="text-sm font-semibold text-slate-900">Editar equipamento</h2>
                <button type="button" id="close-modal"
                        class="rounded-full p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 6l12 12M18 6L6 18" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>

            <form id="edit-form" class="flex-1 overflow-y-auto px-5 pt-4 space-y-4">
                {{-- Preview imagem (apenas visual por enquanto) --}}
                <div class="grid grid-cols-[80px,1fr] gap-3 items-start">
                    <div class="relative h-16 w-16 overflow-hidden rounded-xl bg-slate-100">
                        <img id="edit-photo-preview" alt="Pré-visualização"
                             class="h-full w-full object-cover hidden">
                        <div id="edit-photo-placeholder"
                             class="absolute inset-0 flex items-center justify-center text-slate-400 text-xs">
                            Sem foto
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700">Foto do equipamento</label>
                        <input
                            id="edit-photo"
                            type="file"
                            accept="image/*"
                            class="mt-1 block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border file:border-slate-200 file:bg-slate-50 file:px-3 file:py-1.5 file:text-xs file:font-medium hover:file:bg-slate-100"/>
                        <p class="mt-1 text-[11px] text-slate-500">
                            Apenas visual. Upload real será implementado depois.
                        </p>
                    </div>
                </div>

                {{-- Campos do banco --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="edit-name" class="block text-xs font-medium text-slate-700">
                            Nome do equipamento
                        </label>
                        <input
                            id="edit-name"
                            type="text"
                            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-xs outline-none placeholder:text-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-100"/>
                    </div>

                    <div>
                        <label for="edit-code" class="block text-xs font-medium text-slate-700">
                            Código interno
                        </label>
                        <input
                            id="edit-code"
                            type="text"
                            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-xs outline-none placeholder:text-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-100"/>
                    </div>
                </div>

                <div>
                    <label for="edit-model" class="block text-xs font-medium text-slate-700">
                        Título / Modelo (descrição resumida)
                    </label>
                    <input
                        id="edit-model"
                        type="text"
                        class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-xs outline-none placeholder:text-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-100"/>
                </div>

                <div>
                    <label for="edit-serial" class="block text-xs font-medium text-slate-700">
                        Número de série
                    </label>
                    <input
                        id="edit-serial"
                        type="text"
                        class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-xs outline-none placeholder:text-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-100"/>
                </div>

                <div>
                    <label for="edit-notes" class="block text-xs font-medium text-slate-700">
                        Observações internas
                    </label>
                    <textarea
                        id="edit-notes"
                        rows="2"
                        class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-xs outline-none placeholder:text-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-100"></textarea>
                </div>

                {{-- PDF / extras (apenas visual, futuro = extraInfo) --}}
                <div>
                    <label class="block text-xs font-medium text-slate-700">Anexar ficha técnica (PDF)</label>
                    <input
                        id="edit-pdf"
                        type="file"
                        accept="application/pdf"
                        class="mt-1 block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border file:border-slate-200 file:bg-slate-50 file:px-3 file:py-1.5 file:text-xs file:font-medium hover:file:bg-slate-100"/>
                    <p class="mt-1 text-[11px] text-slate-500">
                        Apenas visual. Salvar arquivo via API depois (equipment_extra_infos).
                    </p>
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-medium text-slate-700">
                            Peças que compõem este equipamento
                        </label>
                        <button type="button" id="btn-manage-parts"
                                class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-medium text-slate-700 hover:bg-slate-50">
                            Gerenciar peças
                        </button>
                    </div>

                    <div id="edit-parts-list" class="mt-2 flex flex-wrap gap-2"></div>

                    <p class="mt-1 text-[11px] text-slate-500">
                        A lista é apenas para visualização. Os vínculos são feitos na tela de peças.
                    </p>
                </div>

                <div class="sticky bottom-0 bg-white pt-2 pb-3">
                    <div class="flex justify-end gap-2">
                        <button
                            type="button"
                            id="cancel-modal"
                            class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-xl bg-blue-700 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-800">
                            Salvar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Peças do equipamento -->
    <div id="parts-modal"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 backdrop-blur-sm">
        <div class="w-[calc(100vw-16px)] sm:w-full sm:max-w-lg h-[calc(100dvh-16px)] sm:h-auto sm:max-h-[80vh] flex flex-col rounded-2xl bg-white shadow-xl">
            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-100 bg-white px-5 py-3.5">
                <div>
                    <p class="text-[11px] font-medium text-slate-500">Peças que compõem o equipamento</p>
                    <h2 id="parts-modal-title" class="text-sm font-semibold text-slate-900"></h2>
                </div>
                <button type="button" id="parts-modal-x"
                        class="rounded-full p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2">
                        <path d="M6 6l12 12M18 6L6 18" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-5 py-4">
                <p id="parts-modal-empty" class="text-sm text-slate-500">
                    Nenhuma peça vinculada a este equipamento.
                </p>
                <ul id="parts-modal-list" class="mt-1 space-y-2 text-sm text-slate-700"></ul>
            </div>

            <div class="flex justify-end border-t border-slate-100 px-5 py-3">
                <button type="button" id="parts-modal-close"
                        class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                    Fechar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal: Catálogo (iframe) -->
    <div id="catalog-modal"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4">
        <div class="w-[calc(100vw-16px)] sm:w-full sm:max-w-6xl h-[calc(100dvh-16px)] sm:h-[90vh] flex flex-col rounded-2xl bg-white shadow-xl">
            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-100 bg-white px-5 py-3.5">
                <div>
                    <p class="text-[11px] font-medium text-slate-500">Catálogo técnico</p>
                    <h2 id="catalog-modal-title" class="text-sm font-semibold text-slate-900"></h2>
                </div>
                <button type="button" id="catalog-modal-x"
                        class="rounded-full p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 6l12 12M18 6L6 18" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>

            <div class="flex-1 min-h-0 flex flex-col">
                <!-- dentro do modal #catalog-modal, acima do iframe -->
                <div id="catalog-details" class="px-5 py-4 border-b border-slate-100">
                    <div class="grid grid-cols-1 lg:grid-cols-[1fr,360px] gap-4">
                        <!-- ESQUERDA: infos -->
                        <div class="flex items-start gap-3">
                            <div class="h-14 w-14 overflow-hidden rounded-xl bg-slate-100 flex items-center justify-center">
                                <img id="catalog-eq-image" class="h-full w-full object-cover hidden" alt="Foto do equipamento">
                                <div id="catalog-eq-image-empty" class="text-[11px] text-slate-400">Sem<br>imagem</div>
                            </div>

                            <div class="min-w-0">
                                <p class="text-[11px] font-medium text-slate-500">Equipamento</p>
                                <p id="catalog-eq-name" class="text-sm font-semibold text-slate-900"></p>
                                <p id="catalog-eq-desc" class="mt-0.5 text-[12px] text-slate-600"></p>

                                <div class="mt-2 flex flex-wrap gap-2">
                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] text-slate-700">
                  Código: <strong class="ml-1" id="catalog-eq-code"></strong>
                </span>
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] text-slate-700">
                  Serial: <strong class="ml-1" id="catalog-eq-serial"></strong>
                </span>
                                </div>

                                <div class="mt-3">
                                    <button id="catalog-open-edit" type="button"
                                            class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                        Editar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- DIREITA: peças vinculadas -->
                        <aside class="rounded-2xl border border-slate-200 bg-slate-50 p-3 max-h-[170px] overflow-y-auto">
                            <p class="text-xs font-semibold text-slate-800">Peças vinculadas</p>
                            <p id="catalog-eq-parts-empty" class="mt-1 text-[11px] text-slate-500 hidden">
                                Nenhuma peça vinculada.
                            </p>
                            <div id="catalog-eq-parts" class="mt-2 flex flex-wrap gap-2"></div>
                        </aside>
                    </div>
                </div>

                <!-- viewer -->
                <div class="flex-1 min-h-0 flex flex-col">
                    <iframe id="catalog-iframe"
                            class="block w-full flex-1 min-h-0 rounded-b-2xl"
                            src=""
                            loading="lazy"
                            referrerpolicy="no-referrer"
                            frameborder="0"></iframe>

                    <!-- ✅ ESTE BLOCO É O QUE ESTAVA FALTANDO (JS USA #catalog-empty) -->
                    <div id="catalog-empty"
                         class="hidden h-full w-full flex-col items-center justify-center gap-2 p-6 text-sm text-slate-500">
                        <p class="text-sm font-medium text-slate-700">Nenhum catálogo (PDF) cadastrado.</p>
                        <p class="text-[11px] text-slate-500">Use “Adicionar PDF” abaixo.</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between border-t border-slate-100 px-5 py-3">
                <div class="flex items-center gap-2">
                    <!-- ✅ TROQUEI hidden -> sr-only (melhor pra click programático) -->
                    <input id="catalog-upload" type="file" accept="application/pdf" class="sr-only">

                    <button id="catalog-upload-btn" type="button"
                            class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                        Adicionar PDF
                    </button>

                    <button id="catalog-save-btn" type="button" disabled
                            class="inline-flex items-center rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        Salvar
                    </button>

                    <p id="catalog-file-name" class="text-[11px] text-slate-500"></p>
                </div>

                <button type="button" id="catalog-modal-close"
                        class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                    Fechar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal: Gerenciar Peças (seleção) -->
    <div id="parts-picker-modal"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 backdrop-blur-sm">
        <div
            class="w-[calc(100vw-16px)] sm:w-full sm:max-w-3xl h-[calc(100dvh-16px)] sm:h-auto sm:max-h-[85vh] flex flex-col rounded-2xl bg-white shadow-xl">
            <div
                class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-100 bg-white px-5 py-3.5">
                <div>
                    <p class="text-[11px] font-medium text-slate-500">Selecionar peças do equipamento</p>
                    <h2 id="parts-picker-title" class="text-sm font-semibold text-slate-900"></h2>
                </div>
                <button type="button" id="parts-picker-x"
                        class="rounded-full p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2">
                        <path d="M6 6l12 12M18 6L6 18" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>

            <div class="sticky top-[56px] z-10 px-5 py-4 border-b border-slate-100 bg-white">
                <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
                    <div class="relative w-full sm:max-w-md">
                        <input id="parts-picker-search" type="text" placeholder="Buscar peça (nome, código, NCM)…"
                               class="w-full rounded-xl border border-slate-300 bg-white pl-10 pr-3 py-2 text-xs outline-none placeholder:text-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-100"/>
                        <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400"
                             xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2">
                            <circle cx="11" cy="11" r="7"/>
                            <path d="M21 21l-4.3-4.3"/>
                        </svg>
                    </div>

                    <label class="inline-flex items-center gap-2 text-xs text-slate-700 select-none">
                        <input id="parts-picker-only-selected" type="checkbox" class="rounded border-slate-300">
                        Mostrar só selecionadas
                    </label>
                </div>

                <div class="mt-3 flex justify-between flex-wrap gap-2 text-xs">
                   <div>
                        <span id="parts-picker-count"
                              class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1.5 font-medium text-slate-700"></span>
                       <span id="parts-picker-selected"
                             class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1.5 font-medium text-blue-700"></span>
                   </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <button id="parts-picker-select-page"
                                    type="button"
                                    class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-medium text-slate-700 hover:bg-slate-50">
                                Selecionar esta página
                            </button>

                            <button id="parts-picker-unselect-batch"
                                    type="button"
                                    class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-medium text-slate-700 hover:bg-slate-50">
                                Desmarcar último lote
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="parts-picker-list" class="flex-1 overflow-y-auto px-5 py-4">
                <div id="parts-picker-selected-wrap" class="space-y-2"></div>

                <div id="parts-picker-selected-sep" class="my-3 hidden border-t border-slate-100"></div>

                <div id="parts-picker-results-wrap" class="space-y-2"></div>

                <p id="parts-picker-only-empty" class="mt-2 hidden text-sm text-slate-500">
                    Nenhuma peça selecionada.
                </p>
            </div>

            <div class="sticky bottom-0 flex items-center justify-between border-t border-slate-100 bg-white px-5 py-3">
                <p id="parts-picker-loading" class="text-xs text-slate-500 invisible">Carregando…</p>
                <div class="flex gap-2">
                    <button type="button" id="parts-picker-close"
                            class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                        Fechar
                    </button>
                    <button type="button" id="parts-picker-save"
                            class="inline-flex items-center rounded-xl bg-blue-700 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-800">
                        Salvar peças
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('assets/js/template/views/catalogs/equipment.js') }}"></script>
@endpush
