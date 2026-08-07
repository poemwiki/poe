<?php


namespace App\Http\Controllers\Auth;


use App\Http\Controllers\Controller;
use App\Models\UserBind;
use App\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

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
        // Log::info('Login from wechat webview, weixin server invoke login function.');
        $wechatUser = session('wechat.oauth_user.default'); // 拿到授权用户资料

        $unionID = !empty($wechatUser->raw['unionid']) ? $wechatUser->raw['unionid'] : '';

        // 对不存在的身份进行间隙加锁时，事务必须使用 REPEATABLE READ 隔离级别。
        DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $login = DB::transaction(function () use ($unionID, $wechatUser): array {
            $unionBinds = $unionID !== ''
                ? $this->getUserBindInfoByUnionID($unionID, null, true)
                : collect();
            $unionUserIDs = $unionBinds->pluck('user_id')->unique()->values();
            if ($unionUserIDs->count() > 1) {
                throw new RuntimeException('The WeChat unionid is bound to multiple users.');
            }

            $userBind = $this->getUserBindInfoByOpenID(
                $wechatUser->raw['openid'],
                UserBind::BIND_REF['wechat'],
                true
            );

            if ($userBind) {
                if ($unionUserIDs->isNotEmpty() && $unionUserIDs->first() !== $userBind->user_id) {
                    throw new RuntimeException('The WeChat openid and unionid resolve to different users.');
                }

                // 小程序迁移主体可能导致 union_id 变更，需要同步该用户所有微信渠道的绑定记录。
                if ($unionID !== '' && $userBind->union_id !== $unionID) {
                    UserBind::where('user_id', $userBind->user_id)
                        ->whereIn('bind_ref', [
                            UserBind::BIND_REF['weapp'],
                            UserBind::BIND_REF['wechat'],
                            UserBind::BIND_REF['wechat-scan']
                        ])->update([
                            'union_id'       => $unionID,
                            'union_id_crc32' => Str::crc32($unionID),
                        ]);
                }

                return ['user' => $userBind->user, 'registeredUser' => null];
            }

            // 当前微信网页渠道没有有效的 openid 绑定时，复用 unionid 在任意微信渠道绑定的用户；否则创建新用户。
            $user = $unionUserIDs->isNotEmpty()
                ? User::findOrFail($unionUserIDs->first())
                : User::create([
                    'name'        => $wechatUser->nickname . '[from-wechat]',
                    'email'       => $wechatUser->email ?? '',
                    'invite_code' => hash('crc32', sha1(2 . ($wechatUser->email ?? ''))),
                    'invited_by'  => 2,
                    'password'    => '',
                    'avatar'      => $wechatUser->raw['headimgurl']
                ]);

            UserBind::create([
                'open_id'     => $wechatUser->raw['openid'],
                'union_id'    => $unionID,
                'user_id'     => $user->id,
                'bind_status' => 1,
                'bind_ref'    => UserBind::BIND_REF['wechat'],
                'nickname'    => $wechatUser->nickname,
                'avatar'      => $wechatUser->avatar,
                'gender'      => $wechatUser->raw['sex'],
                'info'        => json_encode($wechatUser)
            ]);

            return [
                'user'           => $user,
                'registeredUser' => $unionUserIDs->isEmpty() ? $user : null,
            ];
        }, 5);

        if ($login['registeredUser']) {
            event(new Registered($login['registeredUser']));
        }
        $this->guard()->login($login['user']);

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
     * @TODO 移至 BindInfoRepository。
     * @return UserBind|null
     */
    public function getUserBindInfoByOpenID(
        $openID,
        $bindRef = UserBind::BIND_REF['wechat'],
        bool $lockForUpdate = false
    ) {
        $query = UserBind::where([
            'open_id_crc32' => Str::crc32($openID),
            'open_id'       => $openID,
            'bind_ref'      => $bindRef,
            'bind_status'   => 1,
        ]);
        if ($lockForUpdate) {
            $query->forceIndex(UserBind::OPEN_ID_LOCK_INDEX)->lockForUpdate();
        }

        return $query->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, UserBind>
     */
    public function getUserBindInfoByUnionID(
        $unionID,
        ?int $bindRef = UserBind::BIND_REF['wechat'],
        bool $lockForUpdate = false
    ) {
        $query = UserBind::where([
            'union_id_crc32' => Str::crc32($unionID),
            'union_id'       => $unionID,
            'bind_status'    => 1,
        ]);
        if (!is_null($bindRef)) {
            $query->where('bind_ref', '=', $bindRef);
        }
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->get();
    }
}
