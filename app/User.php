<?php

namespace App;

use App\Models\Poem;
use App\Traits\HasFakeId;
use App\Traits\Liker;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\HasApiTokens;
use Spatie\Activitylog\ActivitylogServiceProvider;

/**
 * @property string|null avatar
 * @property int is_v
 * @property string avatarUrl
 */
class User extends Authenticatable implements MustVerifyEmail {
    use HasApiTokens;
    use Notifiable;
    use Liker;
    use HasFakeId;
    public static $FAKEID_KEY    = 'user'; // Symmetric-key for xor
    public static $FAKEID_SPARSE = 66773;

    public static $defaultAvatarUrl = 'images/avatar-default.png';
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

    public static function inviteFromStr($inviteCode) {
        $user = self::where(['invite_code' => $inviteCode])->first();

        return $user->name . ' (' . $user->email . ')';
    }

    /**
     * request from wechat browser.
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
     * request from wechat mini program.
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
     * request from wechat mini program's webview component.
     * @return bool
     */
    public static function isWeAppWebview() {
        if (isset($_SERVER['HTTP_USER_AGENT'])
            && strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false
            && strpos($_SERVER['HTTP_USER_AGENT'], 'miniprogramhtmlwebview') !== false
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
     * @param string $s     Size in pixels, defaults to 80px [ 1 - 2048 ]
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
        return $this->avatar ?? asset(static::$defaultAvatarUrl);
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
}
