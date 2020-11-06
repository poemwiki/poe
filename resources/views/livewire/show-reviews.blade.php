@php
    /** @var \App\Models\Poem $poem */
    $wxPost = $poem->wx ? $poem->wx->first() : null;
@endphp

<section class="reviews full-row">
    <h4 class="reviews-h full-col add-review">
        评论
        <a class="add-review-wrapper add-review btn no-bg hidden"
           @auth
           href="#" id="open-review"
           @else
           href="{{ route('login', ['ref' => route('p/show', $poem->fake_id, false)]) }}"
           @endauth
           title="@lang('Write Review')"
        >
            <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg"
                 xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" xml:space="preserve"><g>
                    <path d="M446.029,0L130.498,267.303l-20.33,66.646c-8.624,7.369-19.857,11.39-32.017,11.391c-4.776,0-9.583-0.622-14.293-1.848
			l-14.438-3.761L0,512l172.268-49.421l-3.759-14.438c-4.454-17.1-0.883-34.137,9.54-46.309l66.648-20.331L512,65.971L446.029,0z
			 M136.351,441.068l-61.413,17.618l42.732-42.732L96.045,394.33l-42.731,42.732l17.627-61.444c2.401,0.202,4.807,0.303,7.21,0.303
			c16.215-0.001,31.518-4.56,44.35-13.043l26.609,26.609C139.202,404.41,134.73,422.458,136.351,441.068z M173.977,371.102
			l-33.079-33.078l10.109-33.14l56.109,56.109L173.977,371.102z M235.003,345.632l-68.636-68.636l46.828-39.671l61.478,61.478
        L235.003,345.632z M236.61,217.492L444.314,41.535l26.152,26.152L294.509,275.391L236.61,217.492z"/>
                </g>
        </svg>
        </a>
    </h4>

    <ol class="review-list">
        @foreach($reviews as $review)
            <li class="">
                <div class="review-h flex-center-vertically">
                    <div><img class="avatar" src="{{$review->user->avatarUrl}}"></div>
                    <div class="review-info"><b>{{$review->user->name}}</b><span class="review-time" title="{{$review->updated_at ?? $review->created_at}} UTC">{{\Illuminate\Support\Carbon::parse($review->updated_at ?? $review->created_at)->diffForHumans(now())}}</span></div>
                </div>

                @if(isset($userScore[$review->user_id]))
                    <svg class="stars"><use href="#stars-{{$userScore[$review->user_id]}}"></use></svg>
                @endif
                <h2 class="review-title">{{$review->title}}</h2>
                <p class="review-content">{!! nl2br(e($review->content)) !!}</p>
                @auth
                @if($review->user_id === Auth::user()->id || Auth::user()->is_admin)
                    <a href="#" wire:click.prevent="delete({{$review->id}})" class="btn">@lang('brackets/admin-ui::admin.btn.x')</a>
                @endif
                @endauth
            </li>
        @endforeach
        @if(count($reviews) <= 0)
            <li>
                <p class="review-none">
                    @lang('No reviews.')&nbsp;&nbsp;&nbsp;&nbsp;
                    <a
                    @auth
                        href="#" class="add-review btn btn-wire"
                    @else
                        href="{{ route('login', ['ref' => route('p/show', $poem->fake_id, false)]) }}" class="btn btn-wire"
                    @endauth >@lang('Write Review')</a>
                </p>
            </li>
        @endif
    </ol>

    <ol class="links full-col">
        @if($wxPost)
            @if($wxPost->link && $wxPost->title)
                <li>读首诗再睡觉公众号：<a target="_blank" href="{{ $wxPost->link }}">{{ $wxPost->title }}</a></li>
            @elseif($wxPost->link)
                <li><a target="_blank" href="{{ $wxPost->link }}">读首诗再睡觉公众号</a></li>
            @endif
        @endif
    </ol>

    <section id="review-modal" @if(!$isEditing) class="hidden" @endif>
        <div class="overlay close-review"></div>

        <form wire:submit.prevent="submit">
            <div class="review-form-header flex-center-vertically">
                <p class="review-form-h">@lang('Write Review')</p>
                <div class="review-form-btn"><a href="#" class="btn close-review">@lang('Close')</a><button class="btn btn-wire" type="submit">@lang('Submit')</button></div>
            </div>
            <input name="title" wire:model.lazy="title" type="text" class="review-title" placeholder="@lang('Title')">

            <textarea name="content" wire:model.lazy="content" id="review-content" cols="30" rows="10" class="review-content" placeholder="@lang('Content')"></textarea>
            <span class="error">@error('title') {{ $message }} @enderror @error('content') {{ $message }} @enderror</span>


        </form>
    </section>

</section>
@push('scripts')
<script type="text/javascript">
    document.addEventListener('livewire:load', function() {
        var $open = document.getElementById('open-review');
        var $modal = document.getElementById('review-modal');


        var $reviews = document.getElementsByClassName('reviews')[0];
        if($open && 'IntersectionObserver' in window) {
            var options = {root: null, rootMargin: '0px', threshold: [0.01, 1]};
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        $open.classList.remove('hidden');
                    } else {
                        $open.classList.add('hidden');
                    }
                });
            }, options);
            observer.observe($reviews);
        }
        $reviews.addEventListener('click', function (e) {
            for (var target = e.target; target && target !== this; target = target.parentNode) {
                if (target.matches('.add-review')) {
                    $modal.classList.remove('hidden');
                    e.preventDefault();
                    break;
                }
                if(target.matches('.close-review')) {
                    $modal.classList.add('hidden');
                    e.preventDefault();
                    if($open) $open.classList.remove('hidden');
                    break;
                }
            }
        });
    });
</script>
@endpush
