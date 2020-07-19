<!-- Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('name', 'Name:') !!}
    {!! Form::text('name', null, ['class' => 'form-control']) !!}
</div>

<!-- Name Cn Field -->
<div class="form-group col-sm-6">
    {!! Form::label('name_cn', 'Name Cn:') !!}
    {!! Form::text('name_cn', null, ['class' => 'form-control']) !!}
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('languages.index') }}" class="btn btn-secondary">Cancel</a>
</div>
