<div id="{{$modalId}}" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm">
    <div class="absolute left-1/2 top-1/2 w-[min(720px,92vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-2xl overflow-y-auto max-h-[70vh]">
        <div class="flex items-center justify-between">
            <h2 id="m-title" class="text-lg font-semibold">{{$modalTitle}}</h2>
            <button id="m-close" class="rounded-lg p-2 hover:bg-slate-100" type="button" aria-label="Fechar">
                <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div id="modal-errors" class="hidden mx-4 mt-2 mb-0 rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-700"></div>

        <form id="{{$formId}}" class="mt-4 space-y-5" enctype="multipart/form-data" novalidate>
            @csrf

            @if ($input)
                <input type="hidden" name="{{ $input['name'] }}" id="{{ $input['name'] }}" value="" />
            @endif

            {{ $slot }}

            <div class="flex justify-between gap-2 pt-1">
                <button type="button" id="btn-delete" class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-100">
                    Excluir registro
                </button>
                <div class="ml-auto flex gap-2">
                    <button type="button" id="m-cancel"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button id="m-submit" type="button"
                            class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                        Salvar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
