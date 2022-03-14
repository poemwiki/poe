<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\Balance.
 *
 * @property int   $id
 * @property array $attributes
 * @property int   $status
 * @property bool  $isRelistable
 * @property bool  $isUnlistable
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

    public function getIsRelistableAttribute(): bool {
        return in_array($this->status, [self::STATUS['inactive'], self::STATUS['sold']]);
    }

    public function getIsUnlistableAttribute(): bool {
        return in_array($this->status, [self::STATUS['active']]);
    }

    public function nft() {
        return $this->belongsTo(\App\Models\NFT::class, 'nft_id');
    }

    public static function unlist(NFT $nft) {
        $listing = $nft->listing;
        if (!$listing) {
            throw new \Exception('NFT is not listed.');
        }
        if (!$listing->isUnlistable) {
            throw new \Exception('Listing status error.');
        }
        $nft->listing->status = Listing::STATUS['inactive'];

        return $nft->listing->save();
    }
}
