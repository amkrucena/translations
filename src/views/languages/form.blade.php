<div class="form-group{{ $errors->has('iso_code') ? ' has-error' : '' }}">
    <label class="col-md-4 control-label">{{ Arr::get($uiTranslations, 'iso_code') }}</label>
    <div class="col-md-4">
        {!! Form::text('iso_code', null, ['class' => 'form-control', 'readonly' => isset($language)]) !!}
    </div>
</div>

<div class="form-group{{ $errors->has('title') ? ' has-error' : '' }}">
    <label class="col-md-4 control-label">{{ Arr::get($uiTranslations, 'title') }}</label>
    <div class="col-md-4">
        {!! Form::text('title', null, ['class' => 'form-control']) !!}
    </div>
</div>

<div class="form-group{{ $errors->has('title_localized') ? ' has-error' : '' }}">
    <label class="col-md-4 control-label">{{ Arr::get($uiTranslations, 'title_localized') }}</label>
    <div class="col-md-4">
        {!! Form::text('title_localized', null, ['class' => 'form-control']) !!}
    </div>
</div>

<div class="form-group{{ $errors->has('is_fallback') ? ' has-error' : '' }}">
    <label class="col-md-4 control-label">{{ Arr::get($uiTranslations, 'is_fallback') }}</label>
    <div class="col-md-4">
        {!! Form::select('is_fallback', [
            0 => Arr::get($uiTranslations, 'no'),
            1 => Arr::get($uiTranslations, 'yes')
        ], null , ['class' => 'form-control']) !!}
    </div>
</div>

<div class="form-group{{ $errors->has('is_visible') ? ' has-error' : '' }}">
    <label class="col-md-4 control-label">{{ Arr::get($uiTranslations, 'is_visible') }}</label>
    <div class="col-md-4">
        {!! Form::select('is_visible', [
            1 => Arr::get($uiTranslations, 'yes'),
            0 => Arr::get($uiTranslations, 'no')
        ], null , ['class' => 'form-control']) !!}
    </div>
</div>