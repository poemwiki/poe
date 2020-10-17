@php
/** @var \App\Models\Poem $poem */
$wxPost = $poem->wx ? $poem->wx->first() : null;
@endphp

<div>
    <section class="score">
        <h4 class="score-h">@lang('score.PoemWiki Rating')</h4>
        <div class="left">
            <strong class="score-num">{{$score['score']}}</strong>
            <span
                class="user-num">@choice('score.number of people rated', $score['count'], ['value' => $score['count']])</span>
        </div>
        <div class="right">
            <div class="star-group">
                <svg class="stars">
                    <use href="#stars-5"/>
                </svg>
                <svg class="stars">
                    <use href="#stars-4"/>
                </svg>
                <svg class="stars">
                    <use href="#stars-3"/>
                </svg>
                <svg class="stars">
                    <use href="#stars-2"/>
                </svg>
                <svg class="stars">
                    <use href="#stars-1"/>
                </svg>
            </div>
            <div class="bars">
                @foreach([5,4,3,2,1] as $v)
                    @if(isset($score['groupCount'][$v]))
                        <span class="bar bar-{{$v}}" title="{{$score['groupCount'][$v] / $score['count'] * 100}}%"><span class="bar bar-percent"
                               style="width: {{$score['groupCount'][$v] / $score['count'] * 100}}%"></span></span>
                    @else
                        <span class="bar bar-{{$v}}" title="0%"></span>
                    @endif
                @endforeach
            </div>
        </div>

    </section>
    <section class="rate">
        <legend class="left">
        @if(Auth::check() && !empty($rating))
            @lang('score.my rating') <a wire:click.prevent="remove()" href="#" class="btn btn-grey unrate">@lang('score.remove my rating')</a>
        @else
            @lang('score.rate & review')
        @endif
        </legend>


        <fieldset class="starability-slot">
            <input type="radio" id="no-rate" class="input-no-rate" name="rating" value="0" @if($rating==null)
                    checked
                @endif
                   aria-label="@choice('score.rating', 0)"/>

            @foreach([1, 2, 3, 4, 5] as $v)
                <input type="radio" id="second-rate{{$v}}" name="rating" value="{{$v}}" @if($rating==$v)
                checked aria-label="{{$v}} star"
                    @endif/>
                <label @if($rating !== $v)
                       wire:click="$set('rating', {{$v}})"
                       @else
                       wire:click=""
                       @endif
                       for="second-rate{{$v}}" data-rating="{{$v}}" title="@choice('score.rating', $v)"></label>
            @endforeach
        </fieldset>

    </section>


    <h4 class="review-h">
        评论
        <a class="add-comment btn no-bg"
           href="{{ Auth::check() ? '' : route('login', ['ref' => route('p/show', $poem->fake_id, false)]) }}"
           title="编辑"
           style="display: none"
        >
            <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg"
                 xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" style="height: 1.4em;
    vertical-align: baseline;" xml:space="preserve"><g>
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
    <section class="review">
        <ol>
            @if($wxPost)
                @if($wxPost->link && $wxPost->title)
                    <li>读首诗再睡觉公众号：<a target="_blank" href="{{ $wxPost->link }}">{{ $wxPost->title }}</a></li>
                @elseif($wxPost->link)
                    <li><a target="_blank" href="{{ $wxPost->link }}">读首诗再睡觉公众号</a></li>
                @endif
            @endif

            @if($poem->bedtime_post_title && $poem->bedtime_post_id)
                <li>读睡博客存档：<a target="_blank"
                              href="https://bedtimepoem.com/archives/{{ $poem->bedtime_post_id }}">{{ $poem->bedtime_post_title }}</a>
                </li>
            @elseif($poem->bedtime_post_id)
                <li><a target="_blank" href="https://bedtimepoem.com/archives/{{ $poem->bedtime_post_id }}">读睡博客存档</a>
                </li
            @endif

            @if(!$wxPost && !$poem->bedtime_post_id)
                <li>@lang('No Comment')</li>
            @endif
        </ol>

    </section>

    <svg class="hidden" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
        <symbol id="star" width="16" height="16" fill="#666" viewBox="0 0 1024 1024" preserveAspectRatio="xMinYMid meet">
            <path
                d="M987.428571 369.714286q0 12.571429-14.857142 27.428571l-207.428572 202.285714 49.142857 285.714286q0.571429 4 0.571429 11.428572 0 12-6 20.285714T791.428571 925.142857q-10.857143 0-22.857142-6.857143l-256.571429-134.857143-256.571429 134.857143q-12.571429 6.857143-22.857142 6.857143-12 0-18-8.285714T208.571429 896.571429q0-3.428571 1.142857-11.428572l49.142857-285.714286L50.857143 397.142857Q36.571429 381.714286 36.571429 369.714286q0-21.142857 32-26.285715l286.857142-41.714285L484 41.714286q10.857143-23.428571 28-23.428572t28 23.428572l128.571429 260 286.857142 41.714285q32 5.142857 32 26.285715z"></path>
        </symbol>


        <symbol id="stars-0" viewBox="0 0 102 18">
            <use xlink:href="#star" fill="#9b9b9b"/>
        </symbol>

        <symbol id="stars-1" viewBox="0 0 102 18">
            <use xlink:href="#star" fill="#e9ba26" transform="translate(84)"/>
        </symbol>

        <symbol id="stars-2" viewBox="0 0 102 18">
            <use xlink:href="#stars-1"/>
            <use xlink:href="#star" fill="#e9ba26" transform="translate(63)"/>
        </symbol>

        <symbol id="stars-3" viewBox="0 0 102 18">
            <use xlink:href="#stars-2"/>
            <use xlink:href="#star" fill="#e9ba26" transform="translate(42)"/>
        </symbol>

        <symbol id="stars-4" viewBox="0 0 102 18">
            <use xlink:href="#stars-3"/>
            <use xlink:href="#star" fill="#e9ba26" transform="translate(21)"/>
        </symbol>

        <symbol id="stars-5" viewBox="0 0 102 18">
            <use xlink:href="#stars-4"/>
            <use xlink:href="#star"/>
        </symbol>

        <symbol id="star-o" width="12" height="12" fill="#666" viewBox="0 0 1024 1024" preserveAspectRatio="xMinYMid meet">
            <path
                d="M686.285714 573.714286l174.857143-169.714286-241.142857-35.428571-108-218.285715-108 218.285715-241.142857 35.428571 174.857143 169.714286-41.714286 240.571428 216-113.714285 215.428571 113.714285z m301.142857-204q0 12.571429-14.857142 27.428571l-207.428572 202.285714 49.142857 285.714286q0.571429 4 0.571429 11.428572 0 28.571429-23.428572 28.571428-10.857143 0-22.857142-6.857143l-256.571429-134.857143-256.571429 134.857143q-12.571429 6.857143-22.857142 6.857143-12 0-18-8.285714T208.571429 896.571429q0-3.428571 1.142857-11.428572l49.142857-285.714286L50.857143 397.142857Q36.571429 381.714286 36.571429 369.714286q0-21.142857 32-26.285715l286.857142-41.714285L484 41.714286q10.857143-23.428571 28-23.428572t28 23.428572l128.571429 260 286.857142 41.714285q32 5.142857 32 26.285715z"></path>
        </symbol>

        <symbol id="stars-o-5" viewBox="0 0 102 18">
            <use xlink:href="#star-o"/>
            <use xlink:href="#star-o" transform="translate(21)"/>
            <use xlink:href="#star-o" transform="translate(42)"/>
            <use xlink:href="#star-o" transform="translate(63)"/>
            <use xlink:href="#star-o" transform="translate(84)"/>
        </symbol>
    </svg>
</div>
@push('scripts')
<script type="text/javascript">
document.addEventListener('livewire:load', () => {
    var rateCurrent = null;
    document.querySelectorAll('label.rate').forEach(function($lable) {
        $lable.addEventListener('click', function () {

            // Set the value of the "count" property
            //@this.set('rating', this.dataset['rating']);
            console.log(this.dataset['rating']);

            // Call the increment component action
            // @this.call('increment')
        })
    });
});


</script>
@endpush
