<?php

namespace App;

use App\Models\Balance;
use App\Models\MediaFile;
use App\Models\Message;
use App\Models\MessageStatus;
use App\Models\Poem;
use App\Models\Relatable;
use App\Models\Transaction;
use App\Traits\HasFakeId;
use App\Traits\Liker;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\HasApiTokens;
use Spatie\Activitylog\ActivitylogServiceProvider;

/**
 * @mixin \Eloquent
 * @property int id
 * @property string|null avatar
 * @property int is_v
 * @property string avatarUrl
 * @property Carbon created_at
 * @property bool   $walletActivated
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $invite_code
 * @property int    $invited_by
 * @property float  $weight
 * @property int    $newMessagesCount
 * @property $poemActivityLogs
 */
class User extends Authenticatable implements MustVerifyEmail {
    use HasApiTokens;
    use Notifiable;
    use Liker;
    use HasFakeId;

    public $table = 'users';

    public static $FAKEID_KEY    = 'user'; // Symmetric-key for xor
    public static $FAKEID_SPARSE = 66773;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'invite_code', 'invited_by', 'avatar', 'is_v', 'weight'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'invite_code'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'invite_code'       => 'string',
        'last_online_at'    => 'datetime',
        'weight'            => 'float'
    ];

    protected $dates = [
        'last_online_at'
    ];
    protected $appends = ['last_online_at', 'resource_url', 'fakeId'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     **/
    public function userBind() {
        return $this->hasMany(\App\Models\UserBind::class, 'user_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     **/
    public function scores() {
        return $this->hasMany(\App\Models\Score::class, 'user_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     **/
    public function reviews() {
        return $this->hasMany(\App\Models\Review::class, 'user_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function author() {
        return $this->hasOne(\App\Models\Author::class, 'user_id', 'id');
    }

    public function originalPoemsOwned() {
        return $this->hasMany(Poem::class, 'upload_user_id', 'id')->where('is_owner_uploaded', Poem::$OWNER['uploader']);
    }

    public function translatedPoemsOwned() {
        return $this->hasMany(Poem::class, 'upload_user_id', 'id')->where('is_owner_uploaded', Poem::$OWNER['translatorUploader']);
    }

    public function activityLogs(): MorphMany {
        return $this->morphMany(ActivitylogServiceProvider::determineActivityModel(), 'causer');
    }

    public function balance(): \Illuminate\Database\Eloquent\Relations\HasMany {
        return $this->hasMany(\App\Models\Balance::class, 'user_id', 'id');
    }

    public function specificMessages(): \Illuminate\Database\Eloquent\Relations\HasMany {
        return $this->hasMany(\App\Models\Message::class, 'user_id', 'id');
    }

    public function getTransactions() {
        return Transaction::where('f_id', '=', 0)->where(function ($query) {
            $query->where('from_user_id', $this->id)->orWhere('to_user_id', $this->id);
        })
            ->orderBy('created_at', 'desc')->get();
    }

    public function getGoldBalance() {
        $balance = Balance::where('user_id', $this->id)->where('nft_id', 0)->first();

        return $balance ? $balance->amount : null;
    }

    public function relateToImage($ID) {
        return Relatable::updateOrCreate([
            'relation'   => Relatable::RELATION['user_has_image'],
            'start_type' => self::class,
            'start_id'   => $this->id,
            'end_type'   => MediaFile::class
        ], [
            'end_id'     => $ID
        ]);
    }

    public function relateToAvatar($ID) {
        return Relatable::updateOrCreate([
            'relation'   => Relatable::RELATION['user_has_image'],
            'start_type' => self::class,
            'start_id'   => $this->id,
            'end_type'   => MediaFile::class
        ], [
            'end_id'     => $ID
        ]);
    }

    public function getResourceUrlAttribute() {
        return url('/admin/users/' . $this->getKey());
    }

    /**
     * @return string
     */
    public function getUrlAttribute() {
        return route('user/show', ['id' => $this->id]);
    }

    public function getNameAttribute() {
        return preg_replace('@\[from-.+\]$@', '', $this->attributes['name']);
    }

    public function getWalletActivatedAttribute(): bool {
        return Balance::where(['user_id' => $this->id, 'nft_id' => 0])->exists();
    }

    public static function inviteFromStr($inviteCode) {
        $user = self::where(['invite_code' => $inviteCode])->first();

        return $user->name . ' (' . $user->email . ')';
    }

    /**
     * request from WeChat browser.
     * @return bool
     */
    public static function isWechat() {
        if (isset($_SERVER['HTTP_USER_AGENT'])
            && strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false
            && !self::isWeApp()
        ) {
            return true;
        }

        return false;
    }

    /**
     * request from WeChat mini program.
     * @return bool
     */
    public static function isWeApp() {
        if (isset($_SERVER['HTTP_REFERER'])
            && strpos($_SERVER['HTTP_REFERER'], config('wechat.mini_program.default.app_id')) !== false
        ) {
            return true;
        }

        return false;
    }

    /**
     * request from WeChat mini program's webview component.
     * @return bool
     */
    public static function isWeAppWebview() {
        if (isset($_SERVER['HTTP_USER_AGENT'])
            && strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false
            && strpos($_SERVER['HTTP_USER_AGENT'], 'miniProgram') !== false
        ) {
            return true;
        }

        return false;
    }

    /**
     * request from wechat mini program, but not production version.
     * @return bool
     */
    public static function isWeAppNoneProduction() {
        $appID = config('wechat.mini_program.default.app_id');
        if (isset($_SERVER['HTTP_REFERER'])
            && strpos($_SERVER['HTTP_REFERER'], $appID) !== false
        ) {
            $matches = null;
            if (preg_match("@//servicewechat.com/$appID/([^/]+)/@", $_SERVER['HTTP_REFERER'], $matches)) {
                if (in_array($matches[1], ['devtools', 1])) {
                    return true;
                }
            }

            return false;
        }

        return false;
    }

    /**
     * @param User $user
     * @return string
     */
    public static function svgAvatar(User $user) {
        return $user->userBind()->first()->avatar ?? 'https://avatar.tobi.sh/' . $user->email . '.svg';
    }

    /**
     * Get either a Gravatar URL or complete image tag for a specified email address.
     *
     * @param string $email The email address
     * @param int    $s     Size in pixels, defaults to 80px [ 1 - 2048 ]
     * @param string $d     Default imageset to use [ 404 | mp | identicon | monsterid | wavatar ]
     * @param string $r     Maximum rating (inclusive) [ g | pg | r | x ]
     * @param bool   $img   True to return a complete IMG tag False for just the URL
     * @param array  $atts  Optional, additional key/value attributes to include in the IMG tag
     * @return string containing either just a URL or a complete image tag
     * @source https://gravatar.com/site/implement/images/php/
     */
    public static function getGravatar($email, $s = 80, $d = 'mp', $r = 'g', $img = false, $atts = []) {
        $url = 'https://www.gravatar.com/avatar/';
        $url .= md5(strtolower(trim($email)));
        $url .= "?s=$s&d=$d&r=$r";
        if ($img) {
            $url = '<img src="' . $url . '"';
            foreach ($atts as $key => $val) {
                $url .= ' ' . $key . '="' . $val . '"';
            }
            $url .= ' />';
        }

        return $url;
    }

    /**
     * use avatarUrl in case users.avatar is null.
     * @return string
     */
    public function getAvatarUrlAttribute() {
        return $this->avatar ?? config('app.avatar.default');
    }

    public function getVerifiedAvatarHtml() {
        $html = <<<HTML
<div class="avatar verify-avatar" title="$this->name" style="background-image: url(&quot;/images/verified.svg&quot;), url(&quot;$this->avatarUrl&quot;);"></div>
HTML;

        return $html;
    }

    public function getLastOnlineAtAttribute() {
        $redis = Redis::connection();

        return $redis->get('online_' . $this->id);
    }

    public function getNewMessagesCountAttribute() {
        $ID = $this->id;

        return Message::toUser($ID)->with(['userStatus' => function ($q) use ($ID) {
            $q->where('user_id', '=', $ID);
        }])->get()->map(function (Message $message) {
            return $message->userStatus ? $message->userStatus->status : MessageStatus::STATUS['unread'];
        })->filter(function ($status) {
            return $status === MessageStatus::STATUS['unread'];
        })->count();
    }

    public function getPoemActivityLogsAttribute() {
        // it's right to order by id desc instead of by created_at!
        // TODO including relatable record logs: mergeTo, translator...
        return $this->activityLogs()->orderBy('id', 'desc')->where('subject_type', '=', 'App\Models\Poem')->get()->map(function ($activity) {
            $oldVal = $activity->properties->get('old');

            // TODO: it's an ugly way to filter the redundant update log after create,
            // it should not be written to db at the poem creation
            if ($oldVal && array_key_exists('poem', $oldVal) && is_null($oldVal['poem'])
                && array_key_exists('title', $oldVal) && is_null($oldVal['title'])) {
                return false;
            }

            if ($activity->description === 'updated') {
                $diffs = $activity->diffs;
                $diffKeys = array_keys($activity->diffs);
                foreach ($diffKeys as $key) {
                    if (in_array($key, Poem::$ignoreChangedAttributes)) {
                        unset($diffs[$key]);
                    }
                }
                if (empty($diffs)) {
                    return false;
                }
            }

            return $activity;
        })->filter(function ($val) {
            return $val !== false;
        })->values(); // values() makes result keys a continuously increased integer sequence
    }
}
