<?php

namespace App\Providers;

use \Illuminate\Support\Str;
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
            $arr = explode("\n", $this->value);
            $firstLine = (new static($arr[0]))->replaceMatches('@[[:punct:]]+$@u', '')
                ->replaceMatches('@\s+@u', '');
            return $firstLine->length > $lengthLimit
                ? new static($firstLine->split('@[,，.。:：;；]@u', 2)->first())
                : $firstLine;
        });

        Stringable::macro('toLines', function () {
            return $this->explode("\n");
        });

        Stringable::macro('isTranslatableJson', function () {
            json_decode($this->value);
            return Str::startsWith($this->value, '{') && Str::endsWith($this->value, '}') && (json_last_error() == JSON_ERROR_NONE);
        });

        Stringable::macro('addLinks', function () {
            return $this->replaceMatches(
            '%\b((https?://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))%s',
            '&nbsp;<a href="$1" target="_blank">$1</a>&nbsp;');
        });

        // CRC32的修正方法，修正php x86模式下出现的负值情况
        Stringable::macro('crc32', function () {
            return sprintf("%u", crc32($this->value));
        });
        Str::macro('crc32', function ($str) {
            return sprintf("%u", crc32($str));
        });

        Str::macro('trimSpaces', function ($str) {
            return preg_replace('#^\s+|\s+$#u', '', $str);
        });
        Str::macro('trimTailSpaces', function ($str) {
            return preg_replace('#\s+$#u', '', $str);
        });
        Str::macro('trimEmptyLines', function ($str) {
            return preg_replace('#^\s*\n|\s*\n+$#u', '', $str);
        });
        Str::macro('noSpace', function ($str) {
            return preg_replace("#\s+#u", '', $str);
        });
        Str::macro('noPunct', function ($str) {
            return preg_replace("#[[:punct:]]+#u", '', $str);
        });
        Str::macro('pureStr', function ($str) {
            return Str::noPunct(Str::noSpace($str));
        });

        // TODO 考虑大小写，简繁体
        Str::macro('contentHash', function ($str) {
            return hash('sha256', Str::pureStr($str));
        });
        Str::macro('contentFullHash', function ($str) {
            return hash('sha256', $str);
        });

        // TODO Str::macro('simHash')
    }
}
