<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasTranslations;

class Campaign extends Model {
    use SoftDeletes;
    use HasTranslations;

    protected $table = 'campaign';

    protected $fillable = [
        'tag_id',
        'describe_lang',
        'name',
        'name_lang',
        'start',
        'end',
        'image',
        'settings'
    ];


    protected $dates = [
        'created_at',
        'deleted_at',
        'updated_at',
        'start',
        'end'

    ];
    // these attributes are translatable
    public $translatable = [
        'describe_lang',
        'name_lang',
    ];

    public $casts = [
        'settings' => 'json'
    ];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['tag'];

    protected $appends = ['tag_name', 'image_url', 'masters', 'share_image_url'];

    /* ************************ ACCESSOR ************************* */

    // public function getUrlAttribute() {
    //     return url('/campaign/' . $this->getKey());
    // }
    public function getTagNameAttribute() {
        return $this->tag->name_lang;
    }

    public function getImageUrlAttribute() {
        return asset($this->image);
    }

    public function getMasterIDsAttribute() {
        if(isset($this->settings['masters'])) return $this->settings['masters'];

        $masterInfos = $this->settings['masterInfos'] ?? [];
        if(!$masterInfos) return [];

        return array_map(function($master) {
            return $master['id'];
        }, $masterInfos);
    }

    public function isMaster(int $userID) {
        return in_array($userID, $this->masterIds);
    }

    public function getMastersAttribute() {
        $masters = $this->settings['masters'] ?? null; // master user ids
        $masterInfos = $this->settings['masterInfos'] ?? [];

        // 优先使用masters内ID对应的用户信息，无masters则使用masterInfos
        if(!$masters) {
            if(!$masterInfos) return null;
            return array_map(function ($item) {
                // 优先使用 masterInfos 内的 name 和 avatar
                $ret = $item;
                $ret['avatar'] = isset($item['avatar']) ? asset($item['avatar'])
                    : ((isset($item['id']) && User::find($item['id'])) ? User::find($item['id'])->avatarUrl : asset(User::$defaultAvatarUrl));
                return $ret;
            }, $masterInfos);
        }

        $users = [];
        foreach ($masters as $index => $masterID) {
            $user = User::select(['id', 'avatar', 'name'])->find($masterID);
            if(!$user) continue;

            $user = $user->toArray();
            // 同时存在 masters和 masterInfos 的情况下，优先使用 masterInfos 内的 name 和 avatar
            if($masterInfos && ($info = $masterInfos[$index])) {
                $user['name'] = $info['name'];
                $user['avatar'] = asset($info['avatar']);
            }
            $users[] = $user;
        }

        return $users;
    }

    public function getShareImageUrlAttribute() {
        return asset($this->settings['share_image_url'] ?? $this->image);
    }

    public function getPoemCountAttribute() {
        return Taggable::where([
            ['tag_id', '=', $this->tag->id],
            ['taggable_type', '=', Poem::class]
        ])->distinct()->count('taggable_id');
    }

    public function getUploadUserCountAttribute() {
        $poems = Tag::where('id', '=', $this->tag->id)->first()->poems();
        return $poems->where('is_owner_uploaded', '=', '1')->distinct('upload_user_id')->count('upload_user_id');
    }

    public function getUserCountAttribute() {
        $poemIds = Poem::select('id')->whereHas('tags', function($q) {
            $q->where('tag.id', '=', $this->tag->id);
        })->pluck('id');
        // scorer
        $scorer = Score::select(['user_id'])->whereIn('poem_id', $poemIds)->pluck('user_id');

        // reviewer
        $reviewer = Review::select(['user_id'])->whereIn('poem_id', $poemIds)->pluck('user_id');

        // poem uploader
        $uploader = Poem::select(['upload_user_id'])->whereIn('id', $poemIds)->pluck('upload_user_id');

        return $scorer->concat($reviewer)->concat($uploader)->unique()->count();
    }

    public function tag() {
        return $this->belongsTo(\App\Models\Tag::class, 'tag_id', 'id');
    }
}
