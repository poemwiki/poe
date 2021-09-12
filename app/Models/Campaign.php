<?php

namespace App\Models;

use App\Traits\HasTranslations;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Campaign.
 *
 * @property int                             $id
 * @property \Illuminate\Support\Carbon      $start
 * @property \Illuminate\Support\Carbon      $end
 * @property string                          $image
 * @property array                           $name_lang
 * @property array                           $describe_lang
 * @property int                             $tag_id
 * @property \Illuminate\Support\Carbon      $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property array|null                      $settings
 * @property mixed|null                      $weapp_url
 * @property mixed                           $image_url
 * @property mixed                           $master_i_ds
 * @property mixed                           $masters
 * @property mixed                           $poem_count
 * @property mixed                           $share_image_url
 * @property mixed                           $tag_name
 * @property array                           $translations
 * @property mixed                           $upload_user_count
 * @property mixed                           $user_count
 * @property \App\Models\Tag                 $tag
 * @method static \Illuminate\Database\Eloquent\Builder|Campaign newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Campaign newQuery()
 * @method static \Illuminate\Database\Query\Builder|Campaign onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Campaign query()
 * @method static \Illuminate\Database\Eloquent\Builder|Campaign whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Campaign whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Campaign whereDescribeLang($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Campaign whereEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Campaign whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Campaign whereImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Campaign whereNameLang($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Campaign whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Campaign whereStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Campaign whereTagId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Campaign whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Campaign whereWeappUrl($value)
 * @method static \Illuminate\Database\Query\Builder|Campaign withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Campaign withoutTrashed()
 * @mixin \Eloquent
 */
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
        return \Illuminate\Support\Facades\Storage::disk('cosv5')->url($this->image);
    }

    public function getMasterIDsAttribute() {
        if (isset($this->settings['masters'])) {
            return $this->settings['masters'];
        }

        $masterInfos = $this->settings['masterInfos'] ?? [];
        if (!$masterInfos) {
            return [];
        }

        return array_map(function ($master) {
            return $master['id'];
        }, $masterInfos);
    }

    public function isMaster(int $userID) {
        return in_array($userID, $this->masterIds);
    }

    public function getMastersAttribute() {
        $masters     = $this->settings['masters']     ?? null; // master user ids
        $masterInfos = $this->settings['masterInfos'] ?? [];

        // 优先使用masters内ID对应的用户信息，无masters则使用masterInfos
        if (!$masters) {
            if (!$masterInfos) {
                return null;
            }

            return array_map(function ($master) {
                // 优先使用 masterInfos 内的 name 和 avatar
                $ret = $master;

                if (isset($master['id'])) {
                    $user = User::find($master['id']);
                    if ($user) {
                        $ret['avatar'] = asset($user->avatarUrl);

                        if ($user->author) {
                            $ret['author_id'] = $user->author->id;
                        }
                    }
                }

                if (isset($master['avatar'])) {
                    $ret['avatar'] = asset($master['avatar']);
                }
                $ret['avatar'] = $ret['avatar'] ?? config('app.avatar.default');

                return $ret;
            }, $masterInfos);
        }

        $users = [];
        foreach ($masters as $index => $masterID) {
            $user = User::find($masterID);
            if (!$user) {
                continue;
            }

            $masterUser = $user->only(['id', 'avatar', 'name', 'author_id']);
            // 同时存在 masters和 masterInfos 的情况下，优先使用 masterInfos 内的 name 和 avatar
            if ($masterInfos && ($info = $masterInfos[$index])) {
                $masterUser['name']   = $info['name'];
                $masterUser['avatar'] = asset($info['avatar']);
            }
            if ($user->author) {
                $masterUser['author_id'] = $user->author->id;
            }
            $users[] = $masterUser;
        }

        return $users;
    }

    public function getShareImageUrlAttribute() {
        return asset($this->settings['share_image_url'] ?? $this->image);
    }

    public function getPoemCountAttribute() {
        // TODO delete taggable while delete poem
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
        $poemIds = Poem::select('id')->whereHas('tags', function ($q) {
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
