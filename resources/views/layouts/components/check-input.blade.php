<label class="col-12 col-form-label mb-1" style="padding: 0;">{{$label}}</label>
<div class="custom-switch custom-switch-primary col-{{$col}} offset-{{$set}}" style="padding-left: 0;">
    <input class="custom-switch-input" type="{{$type}}" id="{{$id}}" name="{{$name}}" {{$check == null ? "" : "checked"}}>
    <label class="custom-switch-btn" for="{{$id}}"></label>
</div>
