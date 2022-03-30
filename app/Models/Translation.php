<?php

namespace App\Models;

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
}
