<?php

namespace App\Traits;

use Brackets\Translatable\Traits\HasTranslations as ParentHasTranslations;
use Illuminate\Support\Facades\App;

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
        $locale = $this->getLocale();
        $translation = $this->getTranslation($key, $locale);
        if(!empty($translation)) {
            return $translation;
        }

        $translations = parent::getAttributeValue($key);
        if(empty($translations)) return '';

        $lastFallback = array_key_first($translations);
        $zhFallback = ['zh', 'zh-cn', 'zh-hans', 'zh-Hans-CN', 'zh-hant', 'zh-hk', 'zh-tw', 'zh-yue', 'zh-sg', $lastFallback];
        $zhTFallback = ['zh-hant', 'zh-hk', 'zh-tw', 'zh-yue', 'zh', 'zh-cn', 'zh-hans', 'zh-Hans-CN', 'zh-sg', $lastFallback];
        if (in_array($locale, $zhFallback)){
            return $this->getFallbackTranslation($key, $zhFallback);
        }
        if (in_array($locale, $zhTFallback)){
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

    /**
     * Get current locale of the model
     *
     * @return string
     */
    public function getLocale() {
        $locale = $this->locale ?? App::getLocale();
        if($locale === 'zh-CN') {
            return 'zh-cn';
        }
        return $locale;
    }
}
