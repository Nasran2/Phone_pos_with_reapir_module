@props(['name','label','type'=>'text','value'=>null,'options'=>null,'required'=>false])
@php($fieldId='rf-'.preg_replace('/[^a-zA-Z0-9-]/','-',$name).'-'.\Illuminate\Support\Str::random(6))
<label class="repair-field" for="{{ $fieldId }}"><span>{{ $label }} @if($required)<b aria-label="Required">*</b>@endif</span>
@if($options !== null)
<select id="{{ $fieldId }}" name="{{ $name }}" @required($required) {{ $attributes }}>
    @foreach($options as $key=>$text)<option value="{{ $key }}" @selected((string)$value===(string)$key)>{{ $text }}</option>@endforeach
</select>
@elseif($type==='textarea')<textarea id="{{ $fieldId }}" name="{{ $name }}" rows="3" @required($required) {{ $attributes }}>{{ $value }}</textarea>
@else<input id="{{ $fieldId }}" type="{{ $type }}" name="{{ $name }}" @if($type!=='file') value="{{ $value }}" @endif @required($required) {{ $attributes }}>
@endif
</label>
