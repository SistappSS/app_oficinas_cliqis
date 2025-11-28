@extends('layouts.templates.template')

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <div class="my-4 flex items-center gap-4">
            <div class="ml-auto flex items-center gap-2 shrink-0">
                <a href="{{route('chat.view')}}" class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-800">
                    Chat
                </a>
                <button id="toggle-header"
                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow hover:bg-slate-50"
                        aria-expanded="true" aria-controls="header-collapsible" type="button"
                        title="Expandir/contrair cabeçalho">
                    <i id="toggle-icon" class="fa-solid fa-up-right-and-down-left-from-center"></i>
                </button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-slate-900 mb-1">Adicionar conhecimento</h2>
            <p class="text-xs text-slate-500 mb-4">
                Cole texto ou envie um arquivo (PDF / Excel / CSV). O conteúdo será usado pelo chat como base.
            </p>

            @if(session('success'))
                <div class="mb-3 rounded-lg bg-emerald-50 border border-emerald-200 px-3 py-2 text-xs text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-3 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-xs text-red-700">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('knowledge.store') }}"
                  method="POST"
                  enctype="multipart/form-data"  {{-- ISSO É OBRIGATÓRIO --}}
                  class="space-y-4">
                @csrf

                <div>
                    <label class="text-xs font-medium text-slate-600">Título</label>
                    <input type="text" name="title" class="mt-1 w-full rounded-xl border px-3 py-2 text-sm">
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-xs font-medium text-slate-600">Upload de arquivo</label>
                        <input type="file" name="file" class="mt-1 block w-full text-xs text-slate-600">
                    </div>

                    <div>
                        <label class="text-xs font-medium text-slate-600">Texto (opcional)</label>
                        <textarea name="content" rows="6"
                                  class="mt-1 w-full rounded-xl border px-3 py-2 text-sm"></textarea>
                    </div>
                </div>

                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">
                    Salvar na base
                </button>
            </form>

        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-slate-900 mb-1">Documentos cadastrados</h2>
            <p class="text-xs text-slate-500 mb-4">
                O chat vai pesquisar primeiro nesses documentos para responder.
            </p>

            <div class="max-h-[420px] overflow-y-auto divide-y divide-slate-100 text-sm">
                @forelse($documents as $doc)
                    <div class="py-3">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                @php
                                    $badge = 'TXT';
                                    if ($doc->source_type === 'pdf')   $badge = 'PDF';
                                    if ($doc->source_type === 'excel') $badge = 'XLS';
                                    if ($doc->source_type === 'csv')   $badge = 'CSV';
                                @endphp
                                <span class="inline-flex h-6 items-center justify-center rounded-lg bg-slate-100 px-2 text-[11px] font-semibold text-slate-600">
                                    {{ $badge }}
                                </span>
                                <p class="font-medium text-slate-900">
                                    {{ $doc->title }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="text-[10px] text-slate-400">
                                    {{ $doc->created_at->format('d/m/Y H:i') }}
                                </span>

                                <form action="{{ route('knowledge.destroy', $doc) }}"
                                      method="POST"
                                      onsubmit="return confirm('Tem certeza que deseja excluir este documento da base?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center justify-center rounded-lg border border-red-100 bg-red-50 px-2 py-1 text-[10px] font-semibold text-red-600 hover:bg-red-100">
                                        Excluir
                                    </button>
                                </form>
                            </div>
                        </div>

                        <p class="mt-1 text-xs text-slate-500 line-clamp-2">
                            {{ \Illuminate\Support\Str::limit($doc->content, 160) }}
                        </p>
                    </div>
                @empty
                    <p class="text-xs text-slate-500">Nenhum documento cadastrado ainda.</p>
                @endforelse
            </div>
        </div>

    </div>
    </div>
@endsection
