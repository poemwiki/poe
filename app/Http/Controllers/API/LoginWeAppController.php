<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\UserBind;
use App\User;
use EasyWeChat\Factory;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class LoginWeAppController extends Controller {
    private \EasyWeChat\MiniProgram\Application $weApp;

    public function __construct() {
        $this->weApp = Factory::miniProgram([
            'app_id'        => config('wechat.mini_program.default.app_id'),
            'secret'        => config('wechat.mini_program.default.secret'),
            'response_type' => 'array',
        ]);
    }

    /**
     * Login function for weapp user.
     * 小程序端获取 code 后，传递到 server 端，server 端根据 code 获取 openid 和 session_key，
     * 如果 openid 对应的 user 已经存在，则更新 user 的 openid 和 session_key，
     * 如果 openid 对应的 user 不存在，则创建 user 并添加 userBind 记录。
     * 最后返回 access_token 和 user 信息。
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function login(Request $request) {
        // Log::info('try weApp login', $request->toArray());

        if (!isset($request->code) or is_null($request->code)) {
            return $this->responseFail([], 'need code');
        }

        $code = $request->code;

        // 根据 code 获取微信 openid 和 session_key
        try {
            $data = $this->weApp->auth->session($code);
        } catch (\Exception $e) {
            Log::error('try weApp login failed at getting openid: ' . $e->getMessage());

            return $this->responseFail([], 'Failed to get openid, please try again later');
        }
        if (isset($data['errcode'])) {
            // Log::info('try weApp login failed:', $data);

            return $this->responseFail([], 'code已过期或不正确');
        }
        // Log::info('wechat server reply:', $data);
        $weappOpenid      = $data['openid'];
        $weixinSessionKey = $data['session_key']; // 用于 this->decrypt 获取加密的用户信息
        $avatar           = $request->avatar   ?? '';
        $nickName         = $request->nickName ?? '';
        $gender           = $request->gender   ?? 0;
        $email            = $request->email    ?? '';
        // $avatar = str_replace('/132', '/0', $request->avatar);//拿到分辨率高点的头像

        $unionID = isset($data['unionid']) && !empty($data['unionid']) ? $data['unionid'] : '';

        $loginTransaction = function () use (
            $avatar,
            $data,
            $email,
            $gender,
            $nickName,
            $unionID,
            $weappOpenid,
            $weixinSessionKey
        ): array {
            $unionBinds = $unionID !== ''
                ? $this->getUserBindInfoByUnionID($unionID, null, true)
                : collect();
            $unionUserIDs = $unionBinds->pluck('user_id')->unique()->values();
            if ($unionUserIDs->count() > 1) {
                throw new RuntimeException('The WeChat unionid is bound to multiple users.');
            }

            // 查找当前小程序渠道的有效 openid 绑定，bind_status = 0 的解绑记录不参与登录。
            // TODO 当业务需要支持同一 unionid 下的多个虚拟身份（欢乐马、神经蛙等）时，需要重新定义用户归并规则。
            $openBinds = $this->getUserBindInfoByOpenID(
                $weappOpenid,
                UserBind::BIND_REF['weapp'],
                true
            );
            $openUserIDs = $openBinds->pluck('user_id')->unique()->values();
            if ($openUserIDs->count() > 1) {
                throw new RuntimeException('The WeChat openid is bound to multiple users.');
            }
            $userBind = $openBinds->first();

            if ($userBind) {
                if ($unionUserIDs->isNotEmpty() && $unionUserIDs->first() !== $userBind->user_id) {
                    throw new RuntimeException('The WeChat openid and unionid resolve to different users.');
                }

                // 小程序迁移主体可能导致 union_id 变更，需要同步该用户所有微信渠道的绑定记录。
                if ($unionID !== '' && $userBind->union_id !== $unionID) {
                    UserBind::where('user_id', $userBind->user_id)
                        ->where('bind_status', 1)
                        ->whereIn('bind_ref', [
                            UserBind::BIND_REF['weapp'],
                            UserBind::BIND_REF['wechat'],
                            UserBind::BIND_REF['wechat-scan']
                        ])->update([
                            'union_id'       => $unionID,
                            'union_id_crc32' => Str::crc32($unionID),
                        ]);
                }

                $attributes = [
                    'updated_at'        => now(),
                    'open_id'           => $weappOpenid,
                    'nickname'          => $nickName,
                    'avatar'            => $avatar,
                    'gender'            => $gender,
                    'info'              => json_encode($data),
                    'weapp_session_key' => $weixinSessionKey
                ];
                if ($unionID !== '') {
                    $attributes['union_id'] = $unionID;
                }
                $userBind->update($attributes);

                return ['userBind' => $userBind, 'registeredUser' => null];
            }

            // 当前小程序渠道没有有效的 openid 绑定：
            // 1. unionid 已在任意微信渠道绑定时，复用对应用户。
            // 2. unionid 没有有效绑定时，创建新用户。
            // 随后为当前小程序渠道创建有效绑定。
            $user = $unionUserIDs->isNotEmpty()
                ? User::findOrFail($unionUserIDs->first())
                : User::create([
                    'name'        => $nickName . '[from-weapp]',
                    'email'       => $email,
                    'nickname'    => $nickName,
                    'avatar'      => $avatar,
                    'gender'      => $gender,
                    'invite_code' => hash('crc32', sha1(2 . $email)),
                    'invited_by'  => 2,
                    'password'    => ''
                ]);

            $userBind = UserBind::create([
                'open_id'           => $weappOpenid,
                'union_id'          => $unionID,
                'user_id'           => $user->id,
                'bind_status'       => 1,
                'bind_ref'          => UserBind::BIND_REF['weapp'],
                'nickname'          => $nickName,
                'avatar'            => $avatar,
                'gender'            => $gender,
                'info'              => json_encode($data),
                'weapp_session_key' => $weixinSessionKey
            ]);

            return [
                'userBind'       => $userBind,
                'registeredUser' => $unionUserIDs->isEmpty() ? $user : null,
            ];
        };

        $previousIsolation = DB::selectOne(
            'SELECT @@SESSION.transaction_isolation AS isolation_level'
        )->isolation_level;

        try {
            // 对不存在的身份进行间隙加锁时，事务必须使用 REPEATABLE READ 隔离级别。
            DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            $login = DB::transaction($loginTransaction, UserBind::LOGIN_TRANSACTION_ATTEMPTS);
        } finally {
            DB::statement('SET SESSION transaction_isolation = ?', [$previousIsolation]);
        }

        /** @var UserBind $userBind */
        $userBind = $login['userBind'];
        if ($login['registeredUser']) {
            event(new Registered($login['registeredUser']));
        }

        // 直接创建token并设置有效期
        $createToken = $userBind->user->createToken($weappOpenid);
        $createToken->token->save();

        $token = $createToken->accessToken;

        return $this->responseSuccess([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'expires_in'   => $createToken->token->expires_at,
            'data'         => UserAPIController::appendMiscInfo($userBind->user),
        ]);
    }

    public function decrypt(Request $request) {
        // Log::info('weApp request for decrypt', $request->toArray());
        // Log::info('from user:' . $request->user()->name . ' id=' . $request->user()->id);
        $detail = $request['detail'];

        $userBind = UserBind::where([
            'bind_ref' => UserBind::BIND_REF['weapp'],
            'user_id'  => $request->user()->id
        ])->first();
        $decrypted = $this->weApp->encryptor->decryptData(
            $userBind->weapp_session_key, $detail['iv'], $detail['encryptedData']);

        return $this->responseSuccess([
            'user'      => $userBind->user,
            'decrypted' => $decrypted
        ]);
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
     * TODO 移至 BindInfoRepository。
     *
     * @param $openID
     * @param $bindRef
     * @return \Illuminate\Database\Eloquent\Collection<int, UserBind>
     */
    public function getUserBindInfoByOpenID(
        $openID,
        $bindRef = UserBind::BIND_REF['weapp'],
        bool $lockForUpdate = false
    ) {
        $q = UserBind::where([
            'open_id_crc32' => Str::crc32($openID),
            'open_id'       => $openID,
            'bind_ref'      => $bindRef,
            'bind_status'   => 1,
        ]);
        if ($lockForUpdate) {
            $q->forceIndex(UserBind::OPEN_ID_LOCK_INDEX)->lockForUpdate();
        }

        return $q->orderBy('id')->get();
    }

    /**
     * @param     $unionID
     * @param int $bindRef
     * @return \Illuminate\Database\Eloquent\Collection<int, UserBind>
     */
    public function getUserBindInfoByUnionID(
        $unionID,
        ?int $bindRef = UserBind::BIND_REF['weapp'],
        bool $lockForUpdate = false
    ) {
        $q = UserBind::where([
            'union_id_crc32' => Str::crc32($unionID),
            'union_id'       => $unionID,
            'bind_status'    => 1,
        ]);
        if (!is_null($bindRef)) {
            $q->where('bind_ref', '=', $bindRef);
        }
        if ($lockForUpdate) {
            $q->lockForUpdate();
        }

        return $q->get();
    }
}
