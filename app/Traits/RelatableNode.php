<?php

namespace App\Traits;



trait RelatableNode {

    public function relateTo(): \Illuminate\Database\Eloquent\Relations\MorphMany {
        return $this->morphMany(\App\Models\Relatable::class, 'start');
    }

    public function relatedBy(): \Illuminate\Database\Eloquent\Relations\MorphMany {
        return $this->morphMany(\App\Models\Relatable::class, 'end');
    }
}
