<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\Balance.
 *
 * @property int   $id
 * @property array $attributes
 * @property int   $status
 * @property bool  $isRelistable
 * @property bool  $isUnlistable
 * @property int   $tx_id
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

    /**
     * @throws \Throwable
     */
    public static function unlisting(NFT $nft, $userID): ?NFT {
        $listing = $nft->listing;
        if (!$listing) {
            throw new \Exception('NFT is not listed.');
        }
        if (!$listing->isUnlistable) {
            throw new \Exception('Listing status error.');
        }

        \DB::beginTransaction();

        try {
            Transaction::create([
                'nft_id'       => $nft->id,
                'from_user_id' => $userID,
                'to_user_id'   => $userID,
                'amount'       => 1,
                'action'       => Transaction::ACTION['unlisting'],
            ]);
            $nft->listing->status = Listing::STATUS['inactive'];
            $nft->listing->save();
            \DB::commit();

            return $nft;
        } catch (\Exception $e) {
            \DB::rollBack();
            Log::error('unlisting error' . $e->getMessage());

            throw $e;
        }
    }
}
