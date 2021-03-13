<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\HasApiTokens;
use Spatie\Activitylog\ActivitylogServiceProvider;

class User extends Authenticatable implements MustVerifyEmail {
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'invite_code', 'invited_by', 'avatar', 'is_v'
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
        'invite_code' => 'string',
        'last_online_at' => 'datetime'
    ];

    protected $dates = [
        'last_online_at'
    ];
    protected $appends = ['last_online_at', 'resource_url'];


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
        return $this->hasMany(\App\Models\review::class, 'user_id', 'id');
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

    public static function isWechat() {
        if (isset($_SERVER['HTTP_USER_AGENT'])
            && strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false
            && !self::isWeApp()
        ) {
            return true;
        }
        return false;
    }

    public static function isWeApp() {
        if (isset($_SERVER['HTTP_REFERER'])
            && strpos($_SERVER['HTTP_REFERER'], env('WECHAT_MINI_PROGRAM_APPID')) !== false
        ){
            return true;
        }
        return false;
    }

    /**
     * @param string $email The email address
     */
    public static function svgAvatar(User $user) {
        return $user->userBind()->first()->avatar ?? 'https://avatar.tobi.sh/' . $user->email . '.svg';
    }

    /**
     * Get either a Gravatar URL or complete image tag for a specified email address.
     *
     * @param string $email The email address
     * @param string $s Size in pixels, defaults to 80px [ 1 - 2048 ]
     * @param string $d Default imageset to use [ 404 | mp | identicon | monsterid | wavatar ]
     * @param string $r Maximum rating (inclusive) [ g | pg | r | x ]
     * @param bool $img True to return a complete IMG tag False for just the URL
     * @param array $atts Optional, additional key/value attributes to include in the IMG tag
     * @return String containing either just a URL or a complete image tag
     * @source https://gravatar.com/site/implement/images/php/
     */
    public static function getGravatar($email, $s = 80, $d = 'mp', $r = 'g', $img = false, $atts = array() ) {

        $url = 'https://www.gravatar.com/avatar/';
        $url .= md5( strtolower( trim( $email ) ) );
        $url .= "?s=$s&d=$d&r=$r";
        if ( $img ) {
            $url = '<img src="' . $url . '"';
            foreach ( $atts as $key => $val )
                $url .= ' ' . $key . '="' . $val . '"';
            $url .= ' />';
        }
        return $url;
    }

    public function getAvatarUrlAttribute() {
        return $this->avatar ?? self::getGravatar($this->email);
    }

    public function getVerifiedAvatarHtml() {
        $html =<<<HTML
<div class="avatar verify-avatar" title="$this->name" style="background-image: url(&quot;/images/verified.svg&quot;), url(&quot;$this->avatarUrl&quot;);"></div>
HTML;
        return $html;

    }

    public function getLastOnlineAtAttribute() {
        $redis = Redis::connection();
        return $redis->get('online_' . $this->id);
    }
}
