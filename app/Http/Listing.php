<?php

namespace App\Http;


use Brackets\AdminListing\AdminListing;
use Brackets\AdminListing\Exceptions\NotAModelClassException;
use Illuminate\Database\Eloquent\Model;

class Listing extends AdminListing {

    /**
     * Init properties
     */
    private function initial(): void {
        // this class name is hard-coded because we don't want to have dependency on brackets/translatable package, if it's not completely necessary
        if (in_array('Brackets\Translatable\Traits\HasTranslations', class_uses($this->model), true)
            or in_array('App\Traits\HasTranslations', class_uses($this->model), true)
        ) {
            $this->modelHasTranslations = true;
            $this->locale = $this->model->locale ?: app()->getLocale();
        }

        $this->query = $this->model->newQuery();

        $this->orderBy = $this->model->getKeyName();
    }

    public function setModel($model): AdminListing {
        if (is_string($model)) {
            $model = app($model);
        }

        if (!is_a($model, Model::class)) {
            throw new NotAModelClassException("AdminListing works only with Eloquent Models");
        }

        $this->model = $model;

        $this->initial();

        return $this;
    }

    /**
     * @param $column
     * @param bool $translated
     * @return string
     */
    protected function materializeColumnName($column, $translated = false): string {
        // TODO add fallback for zh-CN
        // parent AdminListing return below, but it will fail at most of time
        // return $column['table'].'.'.$column['column'].($translated ? ($column['translatable'] ? '->'.$this->locale : '') : '');
        return $column['table'] . '.' . $column['column'];
    }
}
