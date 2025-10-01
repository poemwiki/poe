<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\UserBind;
use App\User;
use EasyWeChat\Factory;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

        // 找到 openid 对应的用户
        // TODO 考虑同一unionid下不同openid的虚拟身份（欢乐马、神经蛙等）
        // TODO 考虑解绑情况
        $userBind = $weappOpenid ? $this->getUserBindInfoByOpenID($weappOpenid, UserBind::BIND_REF['weapp'], 1) : null;

        if ($userBind) {
            // 由于小程序迁移主体，union_id 变更，需要将同一 user 的 weapp 和 wechat 的绑定记录的 union_id 同步变更
            if (isset($data['unionid']) && !empty($data['unionid']) && $userBind->union_id !== $data['unionid']) {
                UserBind::where('user_id', $userBind->user_id)
                    ->whereIn('bind_ref', [
                        UserBind::BIND_REF['weapp'],
                        UserBind::BIND_REF['wechat'],
                        UserBind::BIND_REF['wechat-scan']
                    ])->update([
                        'union_id' => $data['unionid']
                    ]);
            }

            // 已经登录过小程序
            $attributes = [
                'updated_at'        => now(),
                'open_id'           => $weappOpenid,
                'nickname'          => $nickName,
                'avatar'            => $avatar,
                'gender'            => $gender,
                'info'              => json_encode($data),
                'weapp_session_key' => $weixinSessionKey
            ];
            if (isset($data['unionid']) && !empty($data['unionid'])) {
                $attributes['union_id'] = $data['unionid'];
            }

            // 更新用户数据
            $userBind->update($attributes);
            $user = $userBind->user;
            $user->save();
        } else {
            // 以下登录逻辑适用于：
            // 1. 从未注册过的用户
            // 2. 注册过网站，但还未用微信登录过，没有任何微信相关的 userBind
            // 3. 用微信登录过web版，还未登录过小程序，有相同 unionid 且 BIND_REF['wechat'] 的 userBind, 无 BIND_REF['weapp'] 的 userBind

            $wechatBind = isset($data['unionid']) && !empty($data['unionid']) ? $this->getUserBindInfoByUnionID($data['unionid'], UserBind::BIND_REF['wechat'], 1) : null;

            if ($wechatBind) {
                $newUser = $wechatBind->user;
            } else {
                // TODO user.name should be unique
                $newUser = User::create([
                    'name'        => $nickName . '[from-weapp]',
                    'email'       => $email,
                    'nickname'    => $nickName,
                    'avatar'      => $avatar,
                    'gender'      => $gender,
                    'invite_code' => hash('crc32', sha1(2 . $email)),
                    'invited_by'  => 2,
                    'password'    => ''
                ]);
                event(new Registered($newUser));
            }

            $userBind = UserBind::create([
                'open_id'           => $weappOpenid,
                'union_id'          => isset($data['unionid']) ? $data['unionid'] : '',
                'user_id'           => $newUser->id,
                'bind_status'       => 1, // TODO 暂无avatar nickname等详细信息时，暂为待绑状态 2
                'bind_ref'          => UserBind::BIND_REF['weapp'],
                'nickname'          => $nickName,
                'avatar'            => $avatar,
                'gender'            => $gender,
                'info'              => json_encode($data),
                'weapp_session_key' => $weixinSessionKey
            ]);
            // Log::info('new userBind from weapp:', $userBind->toArray());
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
     * @param          $openID
     * @param          $bindRef
     * @param int|null $bindStatus
     *                             TODO move it to BindInfoRepository
     * @return UserBind|null
     */
    public function getUserBindInfoByOpenID($openID, $bindRef = UserBind::BIND_REF['weapp'], $bindStatus = null) {
        try {
            $q = UserBind::where([
                'open_id_crc32' => Str::crc32($openID),
                'open_id'       => $openID,
                'bind_ref'      => $bindRef
            ]);
            if (!is_null($bindStatus)) {
                $q->where('bind_status', '=', $bindStatus);
            }

            return $q->first();
        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * @param          $unionID
     * @param int      $bindRef
     * @param int|null $bindStatus
     * @return UserBind|null
     */
    public function getUserBindInfoByUnionID($unionID, int $bindRef = UserBind::BIND_REF['weapp'], ?int $bindStatus = null) {
        try {
            $q = UserBind::where([
                'union_id_crc32' => Str::crc32($unionID),
                'union_id'       => $unionID,
                'bind_ref'       => $bindRef
            ]);
            if (!is_null($bindStatus)) {
                $q->where('bind_status', '=', $bindStatus);
            }

            return $q->first();
        } catch (\Exception $exception) {
            return null;
        }
    }
}
