<?php

namespace App\Traits;

use Brackets\Translatable\Traits\HasTranslations as ParentHasTranslations;
use Illuminate\Support\Str;

trait HasTranslations {
    use ParentHasTranslations;

    protected $locale;

    /**
     * Get an attribute from the model.
     *
     * @param string $key
     * @return mixed
     */
    public function getAttributeValue($key) {
        if (!$this->isTranslatableAttribute($key)) {
            return parent::getAttributeValue($key);
        }

        return $this->getTranslated($key, $this->getLocale());
    }

    /**
     * Convert the model instance to an array.
     *
     * By default, translations of only current locale of the model of each translated attribute is returned
     *
     * @return array
     */
    public function toArray(): array {
        $array = parent::toArray();
        collect($this->getTranslatableAttributes())->map(function ($attribute) use (&$array) {
            $array[$attribute] = $this->getAttributeValue($attribute);
        });

        date_default_timezone_set(config('app.timezone'));

        return $array;
    }

    /**
     * @param string $key
     * @param string $locale
     * @return mixed|string
     */
    public function fallback(string $key, string $locale) {
        $translations = parent::getAttributeValue($key);
        if (empty($translations)) {
            return '';
        }

        $lastFallback = array_key_first($translations);
        $zhFallback   = ['zh-hans', 'zh-cn', 'zh-Hans-CN', 'zh', 'zh-yue', 'zh-hant', 'zh-hk', 'zh-tw', 'zh-sg', 'wuu', 'yue', 'en', strtolower($lastFallback)];
        $zhTFallback  = ['zh-hant', 'zh-hk', 'zh-tw', 'zh', 'zh-cn', 'zh-hans', 'zh-yue', 'zh-Hans-CN', 'zh-sg', 'wuu', 'yue', 'en', strtolower($lastFallback)];
        // TODO in_array($lowerLocale, $zhCNLocales)
        $lowerLocale = strtolower($locale);
        if (in_array($lowerLocale, $zhFallback)) {
            return $this->getFallbackTranslation($key, $zhFallback);
        }
        // TODO in_array($lowerLocale, $zhHantLocales)
        if (in_array($lowerLocale, $zhTFallback)) {
            return $this->getFallbackTranslation($key, $zhTFallback);
        }

        return $translations[$lastFallback];
    }

    public function getTranslated(string $key, string $locale) {
        $translation = $this->getTranslation($key, $locale, false);

        if (!empty($translation)) {
            return $translation;
        }

        $lowerLocale = strtolower($locale);

        $translation = $this->getTranslation($key, $lowerLocale, false);
        if (!empty($translation)) {
            return $translation;
        }

        return $this->fallback($key, $locale);
    }

    public function getFallbackTranslation(string $key, array $fallbackArr): string {
        foreach ($fallbackArr as $locale) {
            $translation = $this->getTranslation($key, $locale, false);
            if (!empty($translation)) {
                // logic below is for wikidata.description_lang who's $translation is an array indexed by locales
                if (is_array($translation)) {
                    if (isset($translation[$locale])) {
                        return $translation[$locale];
                    }

                    continue;
                }

                return $translation;
            }
        }

        return '';
    }

    public function setAttribute($key, $value) {
        if ($this->isTranslatableAttribute($key) && is_array($value)) {
            return $this->setTranslations($key, $value);
        }

        // if $value is valid json string, write it to field instead of write it as a translation
        if ($this->isTranslatableAttribute($key) && is_string($value) && Str::of($value)->isTranslatableJson()) {
            return parent::setAttribute($key, json_decode($value));
        }

        // Pass arrays and untranslatable attributes to the parent method.
        if (!$this->isTranslatableAttribute($key) || is_array($value)) {
            return parent::setAttribute($key, $value);
        }

        // If the attribute is translatable and not already translated, set a
        // translation for the current app locale.
        return $this->setTranslation($key, $this->getLocale(), $value);
    }
}
