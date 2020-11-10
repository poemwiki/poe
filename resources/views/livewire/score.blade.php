<div class="comments-wrapper">
    <section class="score full-row">
        <section class="rating-card">
            <h4 class="score-h">@lang('score.PoemWiki Rating')</h4>
            <div class="left @if(empty($score['score'])) no-score @endif">
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
                            <span class="bar bar-{{$v}}" title="{{$score['groupCount'][$v] / $score['count'] * 100}}%">
                            <span class="bar bar-percent"
                                  style="width: {{$score['groupCount'][$v] / $score['count'] * 100}}%">
                                <span class="bar bar-inner"></span>
                            </span>
                        </span>
                        @else
                            <span class="bar bar-{{$v}}" title="0%"></span>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>

        <section class="rate flex-center-vertically full-col">
            <legend class="left">
                @if(Auth::check() && !empty($rating))
                    @lang('score.my rating')<a wire:click.prevent="remove()" href="#" class="btn btn-grey unrate">@lang('score.remove my rating')</a>
                @else
                    @lang('score.rate & review')&nbsp;⇨
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
    </section>


    @livewire('show-reviews', [
        'poem' => $poem
    ])

    <svg class="hidden" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
        <symbol id="star" width="16" height="16" viewBox="0 0 1024 1024" preserveAspectRatio="xMinYMid meet">
            <path
                d="M987.428571 369.714286q0 12.571429-14.857142 27.428571l-207.428572 202.285714 49.142857 285.714286q0.571429 4 0.571429 11.428572 0 12-6 20.285714T791.428571 925.142857q-10.857143 0-22.857142-6.857143l-256.571429-134.857143-256.571429 134.857143q-12.571429 6.857143-22.857142 6.857143-12 0-18-8.285714T208.571429 896.571429q0-3.428571 1.142857-11.428572l49.142857-285.714286L50.857143 397.142857Q36.571429 381.714286 36.571429 369.714286q0-21.142857 32-26.285715l286.857142-41.714285L484 41.714286q10.857143-23.428571 28-23.428572t28 23.428572l128.571429 260 286.857142 41.714285q32 5.142857 32 26.285715z"></path>
        </symbol>


        <symbol id="stars-0" viewBox="0 0 102 18">
            <use xlink:href="#star" fill="#9b9b9b"/>
        </symbol>

        <symbol id="stars-1" viewBox="0 0 102 18">
            <use xlink:href="#star" transform="translate(84)"/>
        </symbol>

        <symbol id="stars-2" viewBox="0 0 102 18">
            <use xlink:href="#stars-1"/>
            <use xlink:href="#star" transform="translate(63)"/>
        </symbol>

        <symbol id="stars-3" viewBox="0 0 102 18">
            <use xlink:href="#stars-2"/>
            <use xlink:href="#star" transform="translate(42)"/>
        </symbol>

        <symbol id="stars-4" viewBox="0 0 102 18">
            <use xlink:href="#stars-3"/>
            <use xlink:href="#star" transform="translate(21)"/>
        </symbol>

        <symbol id="stars-5" viewBox="0 0 102 18">
            <use xlink:href="#stars-4"/>
            <use xlink:href="#star"/>
        </symbol>

        <symbol id="star-o" width="12" height="12" viewBox="0 0 1024 1024" preserveAspectRatio="xMinYMid meet">
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
<script>
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
