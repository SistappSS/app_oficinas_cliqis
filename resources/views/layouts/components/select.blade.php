<div class="col-{{ $col }} offset-{{ $set }}">
    <label>{{$label}}</label>
    <select class="form-control select2-single" data-width="100%" name="{{ $name }}" id="{{ $id }}" {{$mult == "" ? "" : 'multiple'}}>
        {{$slot}}
    </select>
</div>
