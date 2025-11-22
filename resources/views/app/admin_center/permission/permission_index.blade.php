@extends('layouts.templates.app')

@section('content')
<h1>Permissões</h1>

@if(session('success'))
<div>{{ session('success') }}</div>
@endif

<form action="{{ route('permissions.store') }}" method="POST">
    @csrf
    <input type="text" name="name" placeholder="Nome da permissão" required>
    <button type="submit">Criar Permissão</button>
</form>

<ul>
    @foreach($permissions as $permission)
    <li>{{ $permission->name }}</li>
    @endforeach
</ul>
@endsection
