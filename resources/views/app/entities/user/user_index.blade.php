   @extends('layouts.templates.template')
   @section('content')
       <x-table-header title="Usuários" current="Entidades | Usuários"></x-table-header>

       <x-table tableId="tableUser">
           <x-slot name="slot">
               <tr>
                   <th>Usuário</th>
                   <th class="text-center">Permissão</th>
                   <th class="text-center">Módulos</th>
                   <th class="text-center">Foto</th>
                   <th class="text-center">Status</th>
                   <th class="text-center">Cadatrado</th>
                   <th class="text-center">Ações</th>
               </tr>
           </x-slot>
       </x-table>

       <script>
           var route = "{{ route('user-api.index') }}"
       </script>

       <script type="module" src="{{ asset('assets/js/views/entities/user.js') }}"></script>

       @include('app.entities.user.user_modal')
       @include('layouts.common.modal.modal_delete')
    @endsection


