@extends('layouts.templates.app')

@section('content')
    <div class="max-w-4xl mx-auto p-6 bg-white rounded shadow">
        <h1 class="text-2xl font-bold mb-6">Criar Role</h1>

        <form action="{{ route('roles.store') }}" method="POST">
            @csrf

            <div class="mb-4">
                <label for="name" class="block font-semibold mb-1">Nome do Role</label>
                <input type="text" name="name" id="name" class="w-full border border-gray-300 rounded px-3 py-2" required>
            </div>

            <h2 class="text-xl font-semibold mb-4">Permissões</h2>

            <div class="space-y-6 max-h-[60vh] overflow-y-auto border p-4 rounded">
                @foreach($groupedPermissions as $groupTitle => $permissions)
                    <div>
                        <h3 class="text-lg font-bold text-gray-700 mb-2">{{ $groupTitle }}</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                            @foreach($permissions as $permission)
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->name }}">
                                    <span class="text-sm">{{ $permission->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
                    Salvar Role
                </button>
            </div>
        </form>
    </div>
@endsection
