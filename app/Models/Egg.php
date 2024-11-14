<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
// use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\Egg.
 *
 * @property int    $id
 * @property string $cfg
 */
class Egg {
    use SoftDeletes;
    // use LogsActivity;
    protected $table = 'egg';

    protected $fillable = [
        'cfg'
    ];

    protected $casts = [
        'cfg' => 'json'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];
}
