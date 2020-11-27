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

    public function getTranslated(string $key, string $locale) {
        $translation = $this->getTranslation($key, $locale, false);
        if(!empty($translation)) {
            return $translation;
        }

        $lowerLocale = strtolower($locale);

        $translation = $this->getTranslation($key, $lowerLocale, false);
        if(!empty($translation)) {
            return $translation;
        }

        $translations = parent::getAttributeValue($key);
        if(empty($translations)) return '';

        $lastFallback = array_key_first($translations);
        $zhFallback = ['zh', 'zh-cn', 'zh-hans', 'zh-Hans-CN', 'zh-hant', 'zh-hk', 'zh-tw', 'zh-yue', 'zh-sg', $lastFallback];
        $zhTFallback = ['zh-hant', 'zh-hk', 'zh-tw', 'zh-yue', 'zh', 'zh-cn', 'zh-hans', 'zh-Hans-CN', 'zh-sg', $lastFallback];
        if (in_array($lowerLocale, $zhFallback)){
            return $this->getFallbackTranslation($key, $zhFallback);
        }
        if (in_array($lowerLocale, $zhTFallback)){
            return $this->getFallbackTranslation($key, $zhTFallback);
        }

        return $translations[$lastFallback];
    }

    function getFallbackTranslation(String $key, Array $fallbackArr): string {
        foreach ($fallbackArr as $locale) {
            $translation = $this->getTranslation($key, $locale);
            if(!empty($translation)) {
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
            return parent::setAttribute($key, $value);
        }

        // Pass arrays and untranslatable attributes to the parent method.
        if (! $this->isTranslatableAttribute($key) || is_array($value)) {
            return parent::setAttribute($key, $value);
        }

        // If the attribute is translatable and not already translated, set a
        // translation for the current app locale.
        return $this->setTranslation($key, $this->getLocale(), $value);
    }

}
