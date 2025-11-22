<!-- Modal de Nova Feature -->
<div id="feature-modal" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm">
    <div class="absolute left-1/2 top-1/2 w-[min(700px,95vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold">Nova Feature</h2>
            <button id="btn-close-feature-modal" class="rounded-lg p-2 hover:bg-slate-100">
                <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form method="POST" action="{{ route('feature.store') }}" class="space-y-6">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Módulo</label>
                <select name="module_id" required
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-blue-600 focus:ring-2 focus:ring-blue-200">
                    @foreach($modules as $m)
                        <option value="{{ $m->id }}">{{ $m->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nome da Feature</label>
                <input type="text" name="name" required
                       class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-blue-600 focus:ring-2 focus:ring-blue-200">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Preço</label>
                <input type="number" step="0.01" name="price" required
                       class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-blue-600 focus:ring-2 focus:ring-blue-200">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Roles Habilitadas</label>
                <div class="space-y-1 max-h-40 overflow-y-auto border rounded p-2">
                    @foreach(Spatie\Permission\Models\Role::all() as $role)
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="roles[]" value="{{ $role->name }}">
                            {{ ucwords($role->name) }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" id="is_required" name="is_required" value="1"
                       class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500">
                <label for="is_required" class="text-sm text-slate-700">
                    Obrigatória <span class="text-slate-400">(não pode ser desativada)</span>
                </label>
            </div>


            <div class="flex justify-end gap-2 pt-4">
                <button type="button" id="btn-cancel-feature"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Cancelar
                </button>
                <button type="submit"
                        class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                    Salvar Feature
                </button>
            </div>
        </form>
    </div>
</div>
