<div>
    <label class="text-sm font-medium" for="{{$id}}">{{$label}}</label>
    <input type="{{ $type }}" id="{{ $id }}" name="{{ $name }}" placeholder="{{ $placeholder }}" {{ $disable == null ? "" : "disabled"}} max="{{$max}}" min="{{$min}}" {{ $read == null ? "" : "readonly"}} value="{{$value}}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none {{$class}}"/>
</div>
