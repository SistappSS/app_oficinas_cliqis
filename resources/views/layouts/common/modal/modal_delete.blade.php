<dialog id="confirm-delete" class="fixed inset-0 size-auto max-h-none max-w-none bg-transparent backdrop:bg-transparent">
  <div class="fixed inset-0 bg-gray-900/50"></div>
  <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
    <div class="relative w-full sm:max-w-lg transform overflow-hidden rounded-lg bg-gray-800 text-left shadow-xl outline -outline-offset-1 outline-white/10">
      <div class="bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
        <div class="sm:flex sm:items-start">
          <div class="mx-auto flex size-12 shrink-0 items-center justify-center rounded-full bg-red-500/10 sm:mx-0 sm:size-10">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-6 text-red-400">
              <path d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="mt-3 sm:mt-0 sm:ml-4">
            <h3 class="text-base font-semibold text-white">Excluir registro</h3>
            <p class="mt-2 text-sm text-gray-400">Tem certeza? Esta ação não pode ser desfeita.</p>
          </div>
        </div>
      </div>
      <div class="bg-gray-700/25 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
        <button type="button" id="confirm-delete-yes" class="inline-flex w-full justify-center rounded-md bg-red-500 px-3 py-2 text-sm font-semibold text-white hover:bg-red-400 sm:ml-3 sm:w-auto">Excluir</button>
        <button type="button" id="confirm-delete-no"  class="mt-3 inline-flex w-full justify-center rounded-md bg-white/10 px-3 py-2 text-sm font-semibold text-white hover:bg-white/20 sm:mt-0 sm:w-auto">Cancelar</button>
      </div>
    </div>
  </div>
</dialog>
