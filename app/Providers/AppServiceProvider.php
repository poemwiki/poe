<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Normalizer;

class AppServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
        if (env('APP_ENV') === 'local') {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('Debugbar', \Barryvdh\Debugbar\Facades\Debugbar::class);
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
        // 手动性能监控
        \Illuminate\Support\Facades\Log::info('Application boot time: ' . (microtime(true) - LARAVEL_START) . 's');
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
            return new static(Str::firstLine(Str::of($this->value)));
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

        Stringable::macro('trimPunct', function () {
            return new static(preg_replace('#^[[:punct:]]+|[[:punct:]]+$#u', '', $this->value));
        });

        Stringable::macro('noSpace', function () {
            return new static(Str::noSpace($this->value));
        });

        // CRC32的修正方法，修正php x86模式下出现的负值情况
        Stringable::macro('crc32', function () {
            return sprintf('%u', crc32($this->value));
        });
        Str::macro('crc32', function ($str) {
            return sprintf('%u', crc32($str));
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
            return preg_replace('#[[:punct:]┄┅┉┈─━⦁₋∙●◎▪︎▶︎▼´¨˛¸°˝˙ª˚º­¯¦ˉˆ˘ˇ❜❛︎︎❝❞❢❣❡⎧⎫⎡⎤⎛⎞⎨⎬⎜⎟⎢⎥⎪⎪⎣⎦⎝⎠⎩⎭−∘∗∖∕∴∵∶∷⊙⋄⋅⋆⋮⋯⨾⁻⧫℃℉®©℗™℠❤⭐★☆➤➣➢▲▵▴▿▾▽△▸▹►▻◁◀▷❖◇◆◘◼□☐☑☒▫◻■⦿◉◦○❗️❕❓❔️️]+#u', '', $str);
        });
        Str::macro('pureStr', function ($str) {
            return Str::noPunct(Str::noSpace($str));
        });
        Str::macro('normalize', function ($str) {
            return Normalizer::normalize($str, Normalizer::FORM_KC);
        });

        Str::macro('firstLine', function ($str, $lengthLimit = 20) {
            $arr = explode("\n", $str);
            // TODO first line like (1) 一 1 should be ignored
            $firstLine = Str::of(Str::noPunct($arr[0]))
                ->replaceMatches('@^\s+@u', '')
                ->replaceMatches('@\s+@u', ' ');

            return mb_strlen($firstLine) > $lengthLimit
                ? mb_substr($firstLine->split('@[,，.。:：;；]@u', 2)->first(), 0, $lengthLimit)
                : $firstLine->__toString();
        });

        // TODO 考虑大小写，简繁体
        Str::macro('contentHash', function ($str) {
            return hash('sha256', Str::pureStr(Str::normalize($str)));
        });

        Str::macro('contentFullHash', function ($str) {
            return hash('sha256', $str);
        });

        Str::macro('digest', function (array $metadata) {
            ksort($metadata);

            return hash('sha256', json_encode($metadata));
        });

        // TODO Str::macro('simHash')
    }
}
