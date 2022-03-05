<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\Balance.
 *
 * @property int   $id
 * @property array $attributes
 * @mixin \Eloquent
 */
class Listing extends Model {
    use LogsActivity;
    protected $table    = 'listing';
    public const STATUS = [
        'inactive' => 0,
        'active'   => 1,
        'sold'     => -1,
    ];

    protected $fillable = [
        'nft_id',
        'currency',
        'price',
        'status'
    ];

    protected $casts = [
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    public function nft() {
        return $this->belongsTo(\App\Models\NFT::class, 'nft_id');
    }
}
