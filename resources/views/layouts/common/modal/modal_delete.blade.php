<dialog id="confirm-delete" class="fixed inset-0 size-auto max-h-none max-w-none bg-transparent p-0 backdrop:bg-transparent">
    <!-- backdrop -->
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-[2px]"></div>

    <!-- container -->
    <div class="fixed inset-0 flex items-end justify-center p-4 sm:items-center">
        <div
            class="relative w-full max-w-md overflow-hidden rounded-[24px] bg-white border border-slate-200 shadow-[0_18px_40px_rgba(15,23,42,0.16)]"
            role="dialog"
            aria-modal="true"
        >
            <!-- header -->
            <div class="flex items-start gap-3 px-5 pt-5">
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-50 text-blue-700 border border-blue-100">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                        <path d="M12 9v3.75m0 3h.01M10.29 3.86l-8.1 14.04A1.5 1.5 0 003.49 20h17.02a1.5 1.5 0 001.3-2.1l-8.1-14.04a1.5 1.5 0 00-2.6 0z"
                              stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>

                <div class="flex-1">
                    <h3 class="text-base font-semibold text-slate-900">
                        Excluir registro da base de dados
                    </h3>
                    <p class="mt-1 text-sm text-slate-600">
                        Tem certeza? Esta ação não pode ser desfeita.
                    </p>

                    <!-- opcional: mostrar OS / Cliente -->
                    <p id="confirm-delete-meta" class="mt-3 text-xs text-slate-500 hidden"></p>
                </div>

                <button
                    type="button"
                    id="confirm-delete-no-x"
                    class="rounded-xl p-2 text-slate-500 hover:bg-slate-50 hover:text-slate-700 focus:outline-none"
                    aria-label="Fechar"
                >
                    ✕
                </button>
            </div>

            <!-- footer -->
            <div class="mt-5 flex flex-col-reverse gap-2 px-5 pb-5 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    id="confirm-delete-no"
                    class="inline-flex w-full justify-center rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-800 hover:bg-slate-100 sm:w-auto"
                >
                    Cancelar
                </button>

                <button
                    type="button"
                    id="confirm-delete-yes"
                    class="inline-flex w-full justify-center rounded-2xl bg-blue-700 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 disabled:opacity-60 disabled:cursor-not-allowed sm:w-auto"
                >
                    Excluir
                </button>
            </div>
        </div>
    </div>
</dialog>
