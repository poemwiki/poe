<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\NFT.
 *
 * @property int   $id
 * @property array $attributes
 * @mixin \Eloquent
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
}
