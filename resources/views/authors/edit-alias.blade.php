@extends('layouts.form')

@section('title', __('Edit Aliases') . ' - ' . $author->label)

@push('styles')
  <link href="{{ mix('/css/base.css') }}" rel="stylesheet">
  <link href="{{ mix('/css/author.css') }}" rel="stylesheet">
@endpush

@section('content')
  <article class="poet page">

    <!-- link to author page -->
    <div class="mb-4">
      <a href="{{ route('author/show', $author->fakeId) }}"
         class="!inline-flex justify-start items-center px-2">
        <svg class="w-10 h-10 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
        @lang('Back to Author Page')
      </a>
    </div>

    <div class="flex items-end justify-start mb-6">
      @if($author->avatarUrl)
        <img class="w-1/12 mr-2" style="max-width: unset" src="{{$author->avatarUrl}}"
             alt="avatar of {{$author->name_lang}}">
      @endif
      <div class="flex flex-col justify-between">
        <h1 class="text-xl font-bold">@lang('Edit Aliases')</h1>
        <p class="text-gray-600">{{$author->label}}</p>
      </div>
    </div>

    @if (session('success'))
      <div class="alert alert-success mb-4 p-4 bg-green-100 border border-green-400 text-green-700">
        {{ session('success') }}
      </div>
    @endif

    @if ($errors->any())
      <div class="alert alert-danger mb-4 p-4 bg-red-100 border border-red-400 text-red-700">
        <ul class="mb-0">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="POST" autocomplete="off" action="{{ route('author/alias/update', $author->fakeId) }}" class="wiki-form">
      @csrf

      <div class="mb-6">
        <p class="text-gray-400 mb-4">
          编辑/添加作者别名
        </p>

        <div id="aliases-container">
          @foreach ($aliases as $index => $alias)
            <div class="alias-row flex items-center gap-4 mb-3">
              <div class="flex-1">
                <input type="text"
                       name="aliases[{{ $index }}][name]"
                       value="{{ old('aliases.' . $index . '.name', $alias->name) }}"
                       class="form-input w-full px-4 py-4 border border-gray-300 text-sm"
                       autocomplete="off" data-1p-ignore
                       placeholder="@lang('Alias Name')">
              </div>

              <div class="w-80">
                <select name="aliases[{{ $index }}][locale]"
                        class="form-select w-full px-4 py-4 border border-gray-300 text-sm">
                  <option value="">@lang('Language')</option>
                  @foreach($languages as $language)
                    <option value="{{ $language->locale }}"
                            {{ old('aliases.' . $index . '.locale', $alias->locale) == $language->locale ? 'selected' : '' }}>
                      {{ $language->name_lang }} @if($language->name_lang <> $language->name)({{ $language->name }}) @endif
                    </option>
                  @endforeach
                </select>
              </div>

              <button type="button" title="@lang('Remove')" class="remove-alias btn btn-sm bg-red-500 text-white px-3 py-2 hover:bg-red-400 hover:text-white">
                -
              </button>
            </div>
          @endforeach
        </div>

        <button type="button" id="add-alias" class="btn mt-6">
          + @lang('Add New Alias')
        </button>
      </div>

      <div class="flex justify-end items-center gap-8">
        <a href="{{ route('author/show', $author->fakeId) }}"
           class="btn">
          @lang('Cancel')
        </a>
        <button type="submit" class="btn btn-wire">
          @lang('Save')
        </button>
      </div>
    </form>

  </article>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('aliases-container');
    const addButton = document.getElementById('add-alias');
    let aliasIndex = {{ $aliases->count() }};

    // 语言选项模板
    const languageOptions = `
        <option value="">@lang('Language')</option>
        @foreach($languages as $language)
            <option value="{{ $language->locale }}">{{ $language->name_lang }} ({{ $language->locale }})</option>
        @endforeach
    `;

    // 创建新别名行的函数
    function createAliasRow(index, nameValue = '', localeValue = '') {
        const newRow = document.createElement('div');
        newRow.className = 'alias-row flex items-center gap-4 mb-3';
        newRow.innerHTML = `
            <div class="flex-1">
              <input type="text"
                      name="aliases[${index}][name]"
                      value="${nameValue}"
                      class="form-input w-full px-4 py-4 border border-gray-300 text-sm"
                      autocomplete="off" data-1p-ignore
                      placeholder="@lang('Alias Name')">
            </div>

            <div class="w-80">
              <select name="aliases[${index}][locale]"
                      class="form-select w-full px-4 py-4 border border-gray-300 text-sm">
                  ${languageOptions}
              </select>
            </div>

            <button type="button" title="@lang('Remove')" class="remove-alias btn btn-sm bg-red-500 text-white px-3 py-2 hover:bg-red-400 hover:text-white">
              -
            </button>
        `;

        // 设置选中的语言
        if (localeValue) {
            const select = newRow.querySelector('select');
            select.value = localeValue;
        }

        return newRow;
    }

    // 如果没有现有别名，添加一个空行
    if (aliasIndex === 0) {
        const emptyRow = createAliasRow(0, '{{ old('aliases.0.name') }}', '{{ old('aliases.0.locale') }}');
        container.appendChild(emptyRow);
        aliasIndex = 1;
    }

    // 添加新别名行
    addButton.addEventListener('click', function() {
        const newRow = createAliasRow(aliasIndex);
        container.appendChild(newRow);
        aliasIndex++;
    });

    // 删除别名行
    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-alias')) {
            e.target.closest('.alias-row').remove();
        }
    });
});
</script>
@endpush