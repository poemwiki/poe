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
        if ($locale === 'zh-cn' && empty($translation)) {
            $translation = $this->getTranslation($key, 'zh');
            if(empty($translation)) {
                return $this->getTranslation($key, 'zh-Hans-CN');
            }
        }

        return $this->getTranslation($key, $locale);
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
