<?php


namespace App\Http\Controllers\Auth;


use App\Http\Controllers\Controller;
use App\Models\UserBind;
use App\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LoginWechatController extends Controller {
    //    use RedirectsUsers;

    /**
     * Login function for wechat webview user.
     * 用户在微信浏览器中打开网页，点击微信登录按钮（https://poemwiki.org/login），会触发以下流程：
     * wechat.oauth router middleware（see app/Http/Kernel.php and routes/web.php route '/login' middleware） 处理请求，
     * 跳转到微信授权页面（https://open.weixin.qq.com/connect/oauth2/authorize?appid=xxx&redirect_uri=https%3A%2F%2Fpoemwiki.org%2Flogin&response_type=code&scope=snsapi_userinfo&state=...），用户点击授权（如果之前已授权过，则不需要点击授权），授权后会跳转到网页。
     * 授权后，会获取到用户信息，如果用户已经绑定过，则直接登录，如果用户未绑定过，则创建用户并绑定。
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function login() {
        Log::info('Login from wechat webview, weixin server invoke login function.');
        $wechatUser = session('wechat.oauth_user.default'); // 拿到授权用户资料

        Log::info('wechat webview user info', json_encode($wechatUser));
        if ($userBind = $this->getUserBindInfoByOpenID($wechatUser->raw['openid'])) {
            // 如果小程序绑定的开放平台账号变更，union_id 也会变更，所以需要将同一 user 的 weapp 和 wechat 的绑定记录的 union_id 同步变更
            if ($wechatUser->raw['unionid'] && $userBind->union_id !== $wechatUser->raw['unionid']) {
                UserBind::where('user_id', $userBind->user_id)
                    ->whereIn('bind_ref', [
                        UserBind::BIND_REF['weapp'],
                        UserBind::BIND_REF['wechat'],
                        UserBind::BIND_REF['wechat-scan']
                    ])->update([
                        ['union_id' => $wechatUser->raw['unionid']]
                    ]);
            }

            $this->guard()->login(User::find($userBind->user_id));
        } else {
            // 注册过小程序，还未用微信登录过web版，有相同 unionid 的 BIND_REF['weapp'] 的 userBind, 无 BIND_REF['wechat'] 的 userBind
            $weappBind = $wechatUser->raw['unionid'] ? $this->getUserBindInfoByUnionID($wechatUser->raw['unionid'], UserBind::BIND_REF['weapp']) : null;

            if ($weappBind) {
                $newUser = $weappBind->user;
            } else {
                // TODO user.name should be unique
                $newUser = User::create([
                    'name'        => $wechatUser->nickname . '[from-wechat]',
                    'email'       => $wechatUser->email ?? '',
                    'invite_code' => hash('crc32', sha1(2 . $wechatUser->email)),
                    'invited_by'  => 2,
                    'password'    => '',
                    'avatar'      => $wechatUser->raw['headimgurl']
                ]);
                event(new Registered($newUser));
            }
            UserBind::create([
                'open_id'     => $wechatUser->raw['openid'],
                'union_id'    => $wechatUser->raw['unionid'] ?? '',
                'user_id'     => $newUser->id,
                'bind_status' => 1,
                'bind_ref'    => UserBind::BIND_REF['wechat'],
                'nickname'    => $wechatUser->nickname,
                'avatar'      => $wechatUser->avatar,
                'gender'      => $wechatUser->raw['sex'],
                'info'        => json_encode($wechatUser)
            ]);


            $this->guard()->login($newUser);
        }

        return redirect(request()->get('ref') ?? '');
    }


    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard() {
        return Auth::guard();
    }

    /**
     * @param $openID
     * @param $bindRef
     *                  TODO move it to BindInfoRepository
     * @return UserBind|null
     */
    public function getUserBindInfoByOpenID($openID, $bindRef = UserBind::BIND_REF['wechat']) {
        try {
            return UserBind::where([
                'open_id_crc32' => Str::crc32($openID),
                'open_id'       => $openID,
                'bind_status'   => 1,
                'bind_ref'      => $bindRef
            ])
                ->first();
        } catch (\Exception $exception) {
            return null;
        }
    }

    public function getUserBindInfoByUnionID($unionID, $bindRef = UserBind::BIND_REF['wechat']) {
        try {
            return UserBind::where([
                'union_id_crc32' => Str::crc32($unionID),
                'union_id'       => $unionID,
                'bind_status'    => 1,
                'bind_ref'       => $bindRef
            ])
                ->first();
        } catch (\Exception $exception) {
            return null;
        }
    }
}
