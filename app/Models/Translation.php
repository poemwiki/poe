<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\Transaction.
 *
 * @property int $id
 * @mixin \Eloquent
 */
class Translation extends \Brackets\AdminTranslations\Translation {
    use LogsActivity;
    protected $table = 'translations';

    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->logExcept(['created_at', 'updated_at']);
    }
}
