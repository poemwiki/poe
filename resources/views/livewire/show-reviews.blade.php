@php
    /** @var \App\Models\Poem $poem */
    $wxPost = $poem->wx ? $poem->wx->first() : null;
@endphp

<section class="reviews full-row">
    <ol class="review-list">
        @foreach($reviews as $review)
            <li class="review-card">
                <div class="review-h flex-center-vertically">
                    <div><img class="avatar" src="{{$review->avatar}}"></div>
                    <div class="review-info"><b>{{$review->name}}</b><span class="review-time" title="{{$review->created_at}} UTC">{{\Illuminate\Support\Carbon::parse($review->created_at)->diffForHumans(now())}}</span></div>
                </div>

                @if(isset($userScore[$review->user_id]))
                    <svg class="stars"><use href="#stars-{{floor($userScore[$review->user_id] / 2)}}"></use></svg>
                @endif
                <h2 class="review-title">{{$review->title}}</h2>

                <div class="review-content">{!! strip_tags(nl2br($review->content), '<p><br><a>') !!}</div>


                @auth
                @if($review->user_id === Auth::user()->id || Auth::user()->is_admin)
                    <a href="#" wire:click.prevent="delete({{$review->id}})" class="btn">@lang('Delete')</a>
                @endif
                @endauth
            </li>
        @endforeach

        <li>
            <p class="review-none">
                @if(count($reviews) <= 0) @lang('No reviews.')&nbsp;&nbsp;&nbsp;&nbsp;@endif
                <a
                @auth
                    href="#" class="add-review btn btn-wire"
                @else
                    href="{{ route('login', ['ref' => route('p/show', $poem->fake_id, false)]) }}" class="btn btn-wire"
                @endauth >@lang('Write Review')</a>
            </p>
        </li>
    </ol>

    <section id="review-modal" @if(!$showModal) class="hidden" @endif>
        <div class="overlay close-review"></div>

        <form wire:submit.prevent="" wire:ignore>
            <div class="review-form-header flex-center-vertically">
                <p class="review-form-h">@lang('Write Review')</p>
                <div class="review-form-btn"><a href="#" class="btn close-review mr-4">@lang('Close')</a><button class="btn btn-wire" type="submit"
                    wire:loading.attr="disabled"
                    wire:click="submit()">@lang('Submit')</button>
                </div>
            </div>
{{--   see:  https://laracasts.com/discuss/channels/laravel/how-to-bind-ckeditor-value-to-laravel-livewire-component?page=1#reply=607889       --}}
            <input name="title" type="text" class="review-title text-base" placeholder="@lang('Title')"
                   wire:model.debounce.999999ms="title"
                   wire:key="review-title">

            <div id="content-warpper" wire:ignore>

                <textarea id="review-content" cols="30" rows="10" class="review-content medium-editor-textarea"
                    wire:model.defer="content" placeholder="@lang('Review')">
                    {!! $content !!}
                </textarea>

            </div>
            <span class="error">@error('title') {{ $message }} @enderror @error('content') {{ $message }} @enderror</span>


        </form>
    </section>

  @push('scripts')
    {{--<script src="/js/lib/zepto.min.js"></script>--}}
    <script src="/js/review.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function() {

        window.editor = new MediumEditor('#review-content', {
          toolbar: {
            buttons: ['anchor'],
            diffLeft: -90,
            diffTop: -70,
          },
          anchor: {
            linkValidation: true,
            placeholderText: '请粘贴或输入链接',
          },
          paste: {
            cleanPastedHTML: false,
            forcePlainText: true,
            cleanReplacements: [],
            cleanAttrs: ['class', 'style', 'dir'],
            unwrapTags: []
          },
          autoLink: true,
          targetBlank: true,
          // elementsContainer: $('#content-warpper').get('0'),
          placeholder: {
            /* This example includes the default options for placeholder,
               if nothing is passed this is what it used */
            text: '@lang('Review')',//$('#review-content').attr('placeholder'),
            hideOnClick: true
          },
          // static: true,
          // autoLink: true,

          imageDragging: false
        });
        editor.subscribe('blur', function () {
          Livewire.emit('contentUpdated', editor.getContent());
        });

        const $modal = document.getElementById('review-modal');

        const $reviews = document.getElementsByClassName('reviews')[0];
        $reviews.addEventListener('click', function (e) {
          for (let target = e.target; target && target !== this; target = target.parentNode) {
            if (target.matches('.add-review')) {
              $modal.classList.remove('hidden');
              e.preventDefault();
              break;
            }
            if(target.matches('.close-review')) {
              $modal.classList.add('hidden');
              e.preventDefault();
              break;
            }
          }
        });

      });

    </script>
  @endpush
</section>
