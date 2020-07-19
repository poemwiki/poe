<!-- Title Field -->
<div class="form-group col-sm-6">
    {!! Form::label('title', '标题:') !!}
    {!! Form::text('title', null, ['class' => 'form-control']) !!}
</div>

<!-- Language Field -->
<div class="form-group col-sm-6">
    {!! Form::label('language', '语言:') !!}
    <label class="checkbox-inline">
        {!! Form::hidden('language', 0) !!}
        {!! Form::select('language', [
        '汉语',
        '英语',
        '德语',
        '法语',
        '意大利语',
        '西班牙语',
        '日语',
        '韩语',
        '希腊语',
        '俄语',
        '葡萄牙语',
        '波兰语',
        '瑞典语',
        '印度语',
        '阿拉伯语'
        ], null) !!}
    </label>
</div>


<!-- Is Original Field -->
<div class="form-group col-sm-6">
    {!! Form::label('is_original', '类型:') !!}
    <label class="checkbox-inline">
        {!! Form::hidden('is_original', 0) !!}
        {!! Form::checkbox('is_original', '1', null) !!}
    </label>
</div>


<!-- Poet Field -->
<div class="form-group col-sm-6">
    {!! Form::label('poet', 'Poet:') !!}
    {!! Form::text('poet', null, ['class' => 'form-control']) !!}
</div>

<!-- Poet Cn Field -->
<div class="form-group col-sm-6">
    {!! Form::label('poet_cn', 'Poet Cn:') !!}
    {!! Form::text('poet_cn', null, ['class' => 'form-control']) !!}
</div>

<!-- Bedtime Post Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('bedtime_post_id', 'Bedtime Post Id:') !!}
    {!! Form::number('bedtime_post_id', null, ['class' => 'form-control']) !!}
</div>

<!-- Bedtime Post Title Field -->
<div class="form-group col-sm-6">
    {!! Form::label('bedtime_post_title', 'Bedtime Post Title:') !!}
    {!! Form::text('bedtime_post_title', null, ['class' => 'form-control']) !!}
</div>

<!-- Poem Field -->
<div class="form-group col-sm-12 col-lg-12">
    {!! Form::label('poem', 'Poem:') !!}
    {!! Form::textarea('poem', null, ['class' => 'form-control']) !!}
</div>

<!-- Length Field -->
<div class="form-group col-sm-6">
    {!! Form::label('length', 'Length:') !!}
    {!! Form::number('length', null, ['class' => 'form-control']) !!}
</div>

<!-- Translator Field -->
<div class="form-group col-sm-6">
    {!! Form::label('translator', 'Translator:') !!}
    {!! Form::text('translator', null, ['class' => 'form-control']) !!}
</div>

<!-- From Field -->
<div class="form-group col-sm-6">
    {!! Form::label('from', 'From:') !!}
    {!! Form::text('from', null, ['class' => 'form-control']) !!}
</div>

<!-- Year Field -->
<div class="form-group col-sm-6">
    {!! Form::label('year', 'Year:') !!}
    {!! Form::text('year', null, ['class' => 'form-control']) !!}
</div>

<!-- Month Field -->
<div class="form-group col-sm-6">
    {!! Form::label('month', 'Month:') !!}
    {!! Form::text('month', null, ['class' => 'form-control']) !!}
</div>

<!-- Date Field -->
<div class="form-group col-sm-6">
    {!! Form::label('date', 'Date:') !!}
    {!! Form::text('date', null, ['class' => 'form-control']) !!}
</div>

<!-- Dynasty Field -->
<div class="form-group col-sm-6">
    {!! Form::label('dynasty', 'Dynasty:') !!}
    {!! Form::text('dynasty', null, ['class' => 'form-control']) !!}
</div>

<!-- Nation Field -->
<div class="form-group col-sm-6">
    {!! Form::label('nation', 'Nation:') !!}
    {!! Form::text('nation', null, ['class' => 'form-control']) !!}
</div>

<!-- Need Confirm Field -->
<div class="form-group col-sm-6">
    {!! Form::label('need_confirm', 'Need Confirm:') !!}
    <label class="checkbox-inline">
        {!! Form::hidden('need_confirm', 0) !!}
        {!! Form::checkbox('need_confirm', '1', null) !!}
    </label>
</div>


<!-- Is Lock Field -->
<div class="form-group col-sm-6">
    {!! Form::label('is_lock', 'Is Lock:') !!}
    <label class="checkbox-inline">
        {!! Form::hidden('is_lock', 0) !!}
        {!! Form::checkbox('is_lock', '1', null) !!}
    </label>
</div>


<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('poems.index') }}" class="btn btn-secondary">Cancel</a>
</div>
