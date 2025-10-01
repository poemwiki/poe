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

    <div class="flex items-end justify-start mb-12">
      @if($author->avatarUrl)
        <img class="w-24 mr-2" style="max-width: unset" src="{{$author->avatarUrl}}"
             alt="avatar of {{$author->name_lang}}">
      @endif
      <div class="flex flex-col justify-between">
        <h1 class="text-xl font-bold">@lang('Edit Aliases')</h1>
        <p class="text-gray-600">{{$author->label}}</p>
      </div>
    </div>


    @if (session('success'))
      <div class="alert alert-success mb-4 p-4 border border-green-400 text-green-600">
        {{ session('success') }}
      </div>
    @endif

    @if ($errors->any())
      <div class="alert alert-danger mb-4 p-4 bg-red-100 border !border-red-400 text-red-700">
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
        <p class="text-gray-400 mb-8">
          编辑/添加作者别名
        </p>

        <div id="aliases-container">
          @foreach ($aliases as $index => $alias)
            <div class="alias-row flex items-center gap-6 mb-8">
              <div class="flex-1">
                <input type="text"
                       name="aliases[{{ $index }}][name]"
                       value="{{ old('aliases.' . $index . '.name', $alias->name) }}"
                       class="form-input w-full text-sm"
                       autocomplete="off" data-1p-ignore
                       placeholder="@lang('Alias Name')">
              </div>

              <div class="w-56">
                <select name="aliases[{{ $index }}][locale]"
                        class="form-select w-full text-sm">
                  <option value="" disabled selected>@lang('Select Language')</option>
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
           class="btn" id="cancel-btn">
          @lang('Cancel')
        </a>
        <button type="submit" class="btn btn-wire" id="submit-btn">
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
    const form = document.querySelector('form.wiki-form');
    const submitBtn = document.getElementById('submit-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    let aliasIndex = {{ $aliases->count() }};
    let submitting = false;

    // language options HTML snippet
    const languageOptions = `
        <option value="" disabled selected>@lang('Select Language')</option>
        @foreach($languages as $language)
            <option value="{{ $language->locale }}">{{ $language->name_lang }} ({{ $language->name }})</option>
        @endforeach
    `;

    function markInvalid(el, invalid = true) {
        if (invalid) {
            el.classList.remove('border-gray-300');
            el.classList.add('!border-red-400');
            el.dataset.invalid = 'true';
        } else {
            el.classList.remove('!border-red-400');
            delete el.dataset.invalid;
            if (!el.classList.contains('border-gray-300')) {
                el.classList.add('border-gray-300');
            }
        }
    }

    function createAliasRow(index, nameValue = '', localeValue = '') {
        const newRow = document.createElement('div');
        newRow.className = 'alias-row flex items-center gap-6 mb-8';
        newRow.innerHTML = `
            <div class="flex-1">
              <input type="text"
                      name="aliases[${index}][name]"
                      value="${nameValue}"
                      class="form-input w-full text-sm"
                      autocomplete="off" data-1p-ignore
                      placeholder="@lang('Alias Name')">
            </div>

            <div class="w-56">
              <select name="aliases[${index}][locale]"
                      class="form-select w-full text-sm">
                  ${languageOptions}
              </select>
            </div>

            <button type="button" title="@lang('Remove')" class="remove-alias btn btn-sm bg-red-500 text-white px-3 py-2 hover:bg-red-400 hover:text-white">
              -
            </button>
        `;
        if (localeValue) {
            newRow.querySelector('select').value = localeValue;
        }
        return newRow;
    }

    if (aliasIndex === 0) {
        const emptyRow = createAliasRow(0, '{{ old('aliases.0.name') }}', '{{ old('aliases.0.locale') }}');
        container.appendChild(emptyRow);
        aliasIndex = 1;
    }

    addButton.addEventListener('click', function() {
        if (submitting) return;
        container.appendChild(createAliasRow(aliasIndex));
        aliasIndex++;
    });

    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-alias')) {
            if (submitting) return;
            e.target.closest('.alias-row').remove();
        }
    });

    container.addEventListener('input', function(e) {
        if (e.target.matches('input[name*="[name]"], select[name*="[locale]"]')) {
            markInvalid(e.target, false);
        }
    });

    function validateAliases() {
        let valid = true;
        const rows = container.querySelectorAll('.alias-row');
        rows.forEach(row => {
            const nameInput = row.querySelector('input[name*="[name]"]');
            const localeSelect = row.querySelector('select[name*="[locale]"]');
            const nameEmpty = !nameInput.value.trim();
            const localeEmpty = !localeSelect.value;
            markInvalid(nameInput, nameEmpty);
            markInvalid(localeSelect, localeEmpty);
            if (nameEmpty || localeEmpty) valid = false;
        });
        return valid;
    }

    form.addEventListener('submit', function(e) {
        if (submitting) {
            e.preventDefault();
            return;
        }

        // clean up empty rows
        container.querySelectorAll('.alias-row').forEach(row => {
            const nameInput = row.querySelector('input[name*="[name]"]');
            const localeSelect = row.querySelector('select[name*="[locale]"]');
            if (!nameInput.value.trim() && !localeSelect.value) row.remove();
        });

        if (!validateAliases()) {
            e.preventDefault();
            const firstInvalid = form.querySelector('[data-invalid="true"]');
            if (firstInvalid) firstInvalid.scrollIntoView({behavior: 'smooth', block: 'center'});
            return;
        }

        // enter submitting state
        submitting = true;
        submitBtn.disabled = true;
        submitBtn.classList.add('pointer-events-none');
        cancelBtn.classList.add('pointer-events-none', 'opacity-50');
        submitBtn.dataset.originalText = submitBtn.innerHTML;
        // Use double quotes to avoid breaking by nested single quotes
        submitBtn.innerHTML = "@lang('Saving')";
    });

    if (document.querySelector('.alert-success')) {
        // Reset state if page loaded after success
        submitting = false;
        if (submitBtn.dataset.originalText) {
            submitBtn.innerHTML = submitBtn.dataset.originalText;
        }
        submitBtn.disabled = false;
        cancelBtn.classList.remove('pointer-events-none','opacity-50');
    }
});
</script>
@endpush