<!-- Title Field -->
<div class="form-group">
    <p>{{ $poem->title }}</p>
</div>

<!-- Poem Field -->
<div class="form-group">
    <pre>{{ $poem->poem }}


作者 / {{ $poem->poet }}
    </pre>
</div>
<br>
<br>
@if(Route::currentRouteName() === 'poems.show' && $poem->bedtime_post_id)
    <!-- Bedtime Post Id Field -->
    <div class="form-group">
        {!! Form::label('bedtime_post_id', '读睡博客链接') !!}
        <a target="_blank" href="https://bedtimepoem.com/archives/{{ $poem->bedtime_post_id }}"> {{ $poem->bedtime_post_title }}</a>
    </div>
@endif


<!-- Poet Cn Field -->
<div class="form-group">
    {!! Form::label('poet_cn', 'Poet Cn:') !!}
    <p>{{ $poem->poet_cn }}</p>
</div>

<!-- Language Field -->
<div class="form-group">
    {!! Form::label('language', 'Language:') !!}
    <p>{{ $langList[$poem->language] }}</p>
</div>

<!-- Is Original Field -->
<div class="form-group">
    {!! Form::label('is_original', 'Is Original:') !!}
    <p>{{ $poem->is_original }}</p>
</div>

<!-- Bedtime Post Id Field -->
<div class="form-group">
    {!! Form::label('bedtime_post_id', 'Bedtime Post Id:') !!}
    <p>{{ $poem->bedtime_post_id }}</p>
</div>

<!-- Bedtime Post Title Field -->
<div class="form-group">
    {!! Form::label('bedtime_post_title', 'Bedtime Post Title:') !!}
    <p>{{ $poem->bedtime_post_title }}</p>
</div>


<!-- Length Field -->
<div class="form-group">
    {!! Form::label('length', 'Length:') !!}
    <p>{{ $poem->length }}</p>
</div>

<!-- Translator Field -->
<div class="form-group">
    {!! Form::label('translator', 'Translator:') !!}
    <p>{{ $poem->translator }}</p>
</div>

<!-- From Field -->
<div class="form-group">
    {!! Form::label('from', 'From:') !!}
    <p>{{ $poem->from }}</p>
</div>

<!-- Year Field -->
<div class="form-group">
    {!! Form::label('year', 'Year:') !!}
    <p>{{ $poem->year }}</p>
</div>

<!-- Month Field -->
<div class="form-group">
    {!! Form::label('month', 'Month:') !!}
    <p>{{ $poem->month }}</p>
</div>

<!-- Date Field -->
<div class="form-group">
    {!! Form::label('date', 'Date:') !!}
    <p>{{ $poem->date }}</p>
</div>

<!-- Dynasty Field -->
<div class="form-group">
    {!! Form::label('dynasty', 'Dynasty:') !!}
    <p>{{ $poem->dynasty }}</p>
</div>

<!-- Nation Field -->
<div class="form-group">
    {!! Form::label('nation', 'Nation:') !!}
    <p>{{ $poem->nation }}</p>
</div>

<!-- Need Confirm Field -->
<div class="form-group">
    {!! Form::label('need_confirm', 'Need Confirm:') !!}
    <p>{{ $poem->need_confirm }}</p>
</div>

<!-- Is Lock Field -->
<div class="form-group">
    {!! Form::label('is_lock', 'Is Lock:') !!}
    <p>{{ $poem->is_lock }}</p>
</div>

