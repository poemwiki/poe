<?php

namespace App\Providers;

use Illuminate\Support\Stringable;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
        Stringable::macro('surround', function ($tagName = 'span', $attrFn = null) {
            $i = 0;
            return array_reduce(mb_str_split($this->value), function ($carry, $char) use ($tagName, &$i, $attrFn) {
                $content = preg_replace('@\s@', '&nbsp;', $char);

                $attr = is_callable($attrFn) ? call_user_func($attrFn, $i) : '';
                $i = $i + 1;
                return new static($carry . "<$tagName $attr>$content</$tagName>");
            });
        });
        Stringable::macro('firstLine', function ($lengthLimit = 20) {
            $arr = explode("\n", $this->value, 3);
            $firstLine = (new static($arr[0]))->replaceMatches('@[[:punct:]]+$@u', '');
            return $firstLine->length > $lengthLimit
                ? new static($firstLine->split('@[,，.。:：;；]@u', 2)->first())
                : $firstLine;
        });
    }
}
