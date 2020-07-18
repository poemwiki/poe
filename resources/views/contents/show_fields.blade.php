<!-- Hash Field -->
<div class="form-group">
    {!! Form::label('hash', 'Hash:') !!}
    <p>{{ $content->hash }}</p>
</div>

<!-- New Hash Field -->
<div class="form-group">
    {!! Form::label('new_hash', 'New Hash:') !!}
    <p>{{ $content->new_hash }}</p>
</div>

<!-- Type Field -->
<div class="form-group">
    {!! Form::label('type', 'Type:') !!}
    <p>{{ $content->type }}</p>
</div>

<!-- Entry Id Field -->
<div class="form-group">
    {!! Form::label('entry_id', 'Entry Id:') !!}
    <p>{{ $content->entry_id }}</p>
</div>

<!-- Content Field -->
<div class="form-group">
    {!! Form::label('content', 'Content:') !!}
    <p>{{ $content->content }}</p>
</div>

