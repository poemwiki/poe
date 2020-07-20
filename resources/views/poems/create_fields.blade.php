<!-- Title Field -->
<div class="form-group col-sm-6">
    {!! Form::label('title', '*标题:') !!}
    {!! Form::text('title', null, ['class' => 'form-control']) !!}
</div>


<!-- Poem Field -->
<div class="form-group col-sm-6 col-lg-6">
    {!! Form::label('poem', '*内容:') !!}
    {!! Form::textarea('poem', null, ['class' => 'form-control']) !!}
</div>


<!-- Poet Field -->
<div class="form-group col-sm-6">
    {!! Form::label('poet', '*作者:') !!}
    {!! Form::text('poet', null, ['class' => 'form-control']) !!}
</div>

<!-- Poet Cn Field -->
<div class="form-group col-sm-6">
    {!! Form::label('poet_cn', '作者中文名:') !!}
    {!! Form::text('poet_cn', null, ['class' => 'form-control']) !!}
</div>

<!-- Language Field -->
<div class="form-group col-sm-6">
    {!! Form::label('language', '*语言:') !!}
    <label class="checkbox-inline">
        {!! Form::hidden('language') !!}
        {!! Form::select('language', $langList, 1) !!}
    </label>
</div>


<!-- Is Original Field -->
<div class="form-group col-sm-6">
    {!! Form::label('is_original', '*类型:') !!}
    <label class="checkbox-inline">
        {!! Form::hidden('is_original') !!}
        {!! Form::select('is_original', [0 => '译作', 1=>'原作'], '1') !!}
    </label>
</div>

<!-- Bedtime Post Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('bedtime_post_id', '读睡博客ID') !!}
    {!! Form::text('bedtime_post_id', null, ['class' => 'form-control']) !!}
</div>


<!-- Translator Field -->
<div class="form-group col-sm-6">
    {!! Form::label('translator', '译者:') !!}
    {!! Form::text('translator', null, ['class' => 'form-control']) !!}
</div>

<!-- From Field -->
<div class="form-group col-sm-6">
    {!! Form::label('from', '来源:') !!}
    {!! Form::text('from', null, ['class' => 'form-control']) !!}
</div>

<!-- Year Field -->
<div class="form-group col-sm-6">
    {!! Form::label('year', '年:') !!}
    {!! Form::text('year', null, ['class' => 'form-control']) !!}
</div>

<!-- Month Field -->
<div class="form-group col-sm-6">
    {!! Form::label('month', '月:') !!}
    {!! Form::text('month', null, ['class' => 'form-control']) !!}
</div>

<!-- Date Field -->
<div class="form-group col-sm-6">
    {!! Form::label('date', '日:') !!}
    {!! Form::text('date', null, ['class' => 'form-control']) !!}
</div>

<!-- Dynasty Field -->
<div class="form-group col-sm-6">
    {!! Form::label('dynasty', '朝代:') !!}
    {!! Form::text('dynasty', null, ['class' => 'form-control']) !!}
</div>

<!-- Nation Field -->
<div class="form-group col-sm-6">
    {!! Form::label('nation', '国籍:') !!}
    {!! Form::text('nation', null, ['class' => 'form-control']) !!}
</div>

<!-- Need Confirm Field -->
<div class="form-group col-sm-6">
    {!! Form::label('need_confirm', '*审核状态:') !!}
    <label class="checkbox-inline">
        {!! Form::hidden('need_confirm') !!}
        {!! Form::select('need_confirm', [0=>"已审", 1=>"待审"], '1') !!}
    </label>
</div>




<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('保存', ['class' => 'btn btn-primary mr-4']) !!}
    <a href="{{ route('poems.index') }}" class="btn btn-secondary">取消</a>
</div>

