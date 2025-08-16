<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\Balance.
 *
 * @property int   $id
 * @property array $attributes
 * @mixin \Eloquent
 */
class Balance extends Model {
    use LogsActivity;
    protected $table       = 'balance';
    public const PRECISION = 27; // the maximum number of digits (the precision)
    public const DECIMAL   = 18; // the number of digits to the right of the decimal point (the scale). It has a range of 0 to 30 and must be no larger than PRECISION.

    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty();
    }

    protected $fillable = [
        'user_id',
        'nft_id',
        'amount'
    ];

    protected $casts = [
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    public function user() {
        return $this->belongsTo(\App\User::class, 'user_id');
    }

    public function nft() {
        return $this->belongsTo(\App\Models\NFT::class, 'nft_id');
    }
}
