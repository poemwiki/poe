<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\Transaction.
 *
 * @property int $id
 * @mixin \Eloquent
 */
class Transaction extends Model {
    use LogsActivity;
    protected $table = 'transaction';

    public const TYPE = [
        'gold' => 0,
        'nft'  => 1,
    ];
    public const ACTION = [
        'transfer'    => 0,
        'mint'        => 1,
        'listing'     => 2,
        'sell'        => 3,
    ];

    protected $fillable = [
        'nft_id',
        'from_user_id',
        'to_user_id',
        'amount',
        'action'
    ];

    protected $casts = [
    ];

    protected $dates = [
        'created_at'
    ];

    public function nft() {
        return $this->belongsTo(\App\Models\NFT::class, 'nft_id');
    }

    public function fromUser() {
        return $this->belongsTo(\App\User::class, 'from_user_id');
    }

    public function toUser() {
        return $this->belongsTo(\App\User::class, 'to_user_id');
    }
}
