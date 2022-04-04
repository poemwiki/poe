<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\NFT.
 *
 * @property int       $id
 * @property int       $content_id
 * @property NFT::TYPE $type
 * @property string    $hash
 * @property string    $external_url
 * @property array     $poemwiki
 * @mixin \Eloquent
 * @property \App\User|null           $owner
 * @property \App\Models\Listing|null $listing
 */
class NFT extends Model {
    use LogsActivity;
    protected $table = 'nft';

    // protected $with = ['poem:id,title', 'content:id,content'];

    public const TYPE = [
        'ERC721'  => 0,
        'ERC1155' => 1,
    ];

    protected $fillable = [
        'poem_id',
        'content_id',
        'type',
        'external_url',
        'hash',
        'animation_url',
        'description',
        'image',
        'image_url',
        'image_data',
        'background_color',
        'name',
        'poemwiki'
    ];

    protected $casts = [
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    public function poem() {
        return $this->belongsTo(\App\Models\Poem::class, 'poem_id');
    }

    public function content() {
        return $this->belongsTo(\App\Models\Content::class, 'content_id');
    }

    public function txs() {
        return $this->hasMany(\App\Models\Transaction::class, 'nft_id');
    }

    public function listing() {
        return $this->hasOne(\App\Models\Listing::class, 'nft_id');
    }

    public function balance() {
        return $this->hasOne(\App\Models\Balance::class, 'nft_id');
    }

    public function getOwnerAttribute() {
        return $this->balance ? $this->balance->user : null;
    }

    public function getShortedHashAttribute() {
        return '0x' . substr($this->hash, 0, 4) . '...' . substr($this->hash, -4);
    }

    public static function mint(Poem $poem, int $userID) {
        \DB::beginTransaction();

        try {
            $nft = self::create([
                'poem_id'      => $poem->id,
                'content_id'   => $poem->content_id,
                'type'         => NFT::TYPE['ERC721'],
                'name'         => $poem->title,
                'external_url' => $poem->url,
                // todo: poet and translator page url
                'poemwiki' => json_encode($poem->only(['title', 'subtitle', 'preface', 'poem', 'year', 'month', 'date', 'location', 'poet', 'poet'])),
                'hash'     => Str::digest([
                    'author_user_id' => $poem['user_id'],
                    'title'          => $poem['title'],
                    'subtitle'       => $poem['subtitle'],
                    'preface'        => $poem['preface'],
                    'content'        => $poem['poem'],
                    'poet'           => $poem['poet'],
                ])
            ]);

            Transaction::create([
                'nft_id'       => $nft->id,
                'from_user_id' => 0,
                'to_user_id'   => $userID,
                'amount'       => 1,
                'action'       => Transaction::ACTION['mint'],
            ]);

            Balance::create([
                'nft_id'  => $nft->id,
                'user_id' => $userID,
                'amount'  => 1,
            ]);

            \DB::commit();

            return $nft;
        } catch (\Throwable $e) {
            \DB::rollback();

            Log::error($e->getMessage());

            throw new \Exception('Minting error occurred.');
        }
    }

    public static function transfer(int $nftID, int $fromUserID, int $toUserID, int $amount) {
        \DB::beginTransaction();

        try {
            Transaction::create([
                'nft_id'       => $nftID,
                'from_user_id' => $fromUserID,
                'to_user_id'   => $toUserID,
                'amount'       => $amount,
                'action'       => Transaction::ACTION['transfer'],
            ]);
            $criteria = [
                'nft_id'  => $nftID,
                'user_id' => $fromUserID,
            ];
            $balance = Balance::where($criteria);

            if (!$balance) {
                Log::error('Balance not found.', $criteria);

                throw new \Exception('Nft is not owned by user.');
            }
            $balance->update([
                'user_id' => $toUserID,
            ]);

            \DB::commit();

            return true;
        } catch (\Throwable $e) {
            \DB::rollback();

            Log::error($e->getMessage());

            throw new \Exception('Transferring error occurred.');
        }
    }

    public function isListableByUser($userID) {
        if ($this->owner && $this->owner->id === $userID) {
            return !$this->listing || $this->listing->isRelistable;
        }

        return false;
    }

    public function isUnlistableByUser($userID) {
        if ($this->owner && $this->owner->id === $userID) {
            return $this->listing && $this->listing->isUnlistable;
        }

        return false;
    }

    public static function isMintable(Poem $poem, int $userID) {
        return !$poem->isTranslated && $poem->owner && $poem->owner->id === $userID;
    }
}
