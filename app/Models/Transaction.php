<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\Transaction.
 *
 * @property int $id
 * @mixin \Eloquent
 */
class Transaction extends Model {
    use LogsActivity;
    protected $table        = 'transaction';
    public const UPDATED_AT = null;

    public const TYPE = [
        'gold' => 0,
        'nft'  => 1,
    ];
    public const ACTION = [
        'transfer'    => 0,
        'mint'        => 1,
        'listing'     => 2,
        'sell'        => 3,
        'unlisting'   => 4,
    ];

    protected $fillable = [
        'nft_id',
        'from_user_id',
        'to_user_id',
        'amount',
        'action',
        'f_id',
        'memo'
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

    public function children() {
        return $this->hasMany(\App\Models\Transaction::class, 'f_id');
    }

    public function childGoldPrice() {
        return $this->hasMany(\App\Models\Transaction::class, 'f_id')->where(['nft_id' => 0, 'action' => 0]);
    }

    /**
     * @throws \Throwable
     */
    public static function transferGold(int $fromUserId, int $toUserId, float $amount, int $f_tx_id = null): Transaction {
        $outerBalance = Balance::where(['user_id' => $fromUserId, 'nft_id' => 0])->first();
        $innerBalance = Balance::where(['user_id' => $toUserId, 'nft_id' => 0])->first();
        if (!$outerBalance || !$innerBalance) {
            throw new \Exception('Balance not found.');
        }
        if ($outerBalance->amount < $amount) {
            throw new \Exception('No enough gold.');
        }

        try {
            \DB::beginTransaction();
            $transaction               = new Transaction();
            $transaction->nft_id       = 0;
            $transaction->from_user_id = $fromUserId;
            $transaction->to_user_id   = $toUserId;
            $transaction->amount       = $amount;
            $transaction->action       = self::ACTION['transfer'];
            if ($f_tx_id) {
                $transaction->f_id = $f_tx_id;
            }
            $transaction->save();

            $outerBalance->update(['amount' => \DB::raw('amount - ' . $amount)]);
            $innerBalance->update(['amount' => \DB::raw('amount + ' . $amount)]);
            \DB::commit();
        } catch (\Exception $e) {
            Log::error('transfer gold error:' . $e->getMessage());
            \DB::rollBack();

            throw $e;
        }

        return $transaction;
    }
}
