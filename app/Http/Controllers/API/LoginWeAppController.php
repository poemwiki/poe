<?php


namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use App\Models\UserBind;
use App\User;

use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class LoginWeAppController extends Controller {
    //    use RedirectsUsers;
    private $weApp;

    public function __construct() {
        $this->weApp = \EasyWeChat\Factory::miniProgram([
            'app_id' => env('WECHAT_MINI_PROGRAM_APPID'),
            'secret' => env('WECHAT_MINI_PROGRAM_SECRET'),

            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'array',]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function login(Request $request) {
        Log::info('try weApp login', $request->toArray());

        if(!isset($request->code) or is_null($request->code)) {
            return $this->responseFail([], 'need code');
        }

        $code = $request->code;
        // 根据 code 获取微信 openid 和 session_key
        $data = $this->weApp->auth->session($code);
        if (isset($data['errcode'])) {
            Log::info('try weApp login failed:', $data);
            return $this->responseFail([], 'code已过期或不正确');
        }
        Log::info('wechat server reply:', $data);
        $weappOpenid = $data['openid'];
        $weixinSessionKey = $data['session_key'];
        $avatar = $request->avatar ?? '';
        $nickName = $request->nickName ?? '';
        $gender = $request->gender ?? 0;
        $email = $request->email ?? '';
        // $avatar = str_replace('/132', '/0', $request->avatar);//拿到分辨率高点的头像


        // 找到 openid 对应的用户
        // TODO 考虑解绑情况
        $userBind = $this->getUserBindInfoByOpenID($weappOpenid, UserBind::BIND_REF['weapp'], 1);
        // 待解决的问题：微信服务端返回的 $data 中是否会包含 unionid？在何种情况下包含？

        if ($userBind) {
            // 老用户
            $attributes['updated_at'] = now();
            $attributes['weapp_session_key'] = $weixinSessionKey;
            // 更新用户数据
            $userBind->update($attributes);
        } else {
            // 新用户
            $newUser = User::create([
                'name' => $nickName.'[from-weapp-tmp]',
                'email' => $email,
                'nickname' => $nickName,
                'avatar' => $avatar,
                'gender' => $gender,
                'invite_code' => hash('crc32', sha1(2 . $email)),
                'invited_by' => 2,
                'password' => ''
            ]);

            $userBind = UserBind::create([
                'open_id' => $weappOpenid,
                'union_id' => isset($data['unionid']) ? $data['unionid'] : '',
                'user_id' => $newUser->id,
                'bind_status' => 1, // TODO 暂无avatar nickname等详细信息时，暂为待绑状态 2
                'bind_ref' => UserBind::BIND_REF['weapp'],
                'nickname' => $nickName,
                'avatar' => $avatar,
                'gender' => $gender,
                'info' => json_encode($data),
                'weapp_session_key' => $weixinSessionKey
            ]);
            Log::info('new userBind from weapp:', $userBind->toArray());
        }

        // 直接创建token并设置有效期
        $createToken = $userBind->user->createToken($weappOpenid);
        $createToken->token->save();

        $token = $createToken->accessToken;
        return $this->responseSuccess([
            'access_token' => $token,
            'token_type' => "Bearer",
            'expires_in' => $createToken->token->expires_at,
            'data' => $userBind->user,
        ]);
    }

    public function decrypt(Request $request) {
        Log::info('weApp request for decrypt', $request->toArray());
        Log::info('from user:' . $request->user()->name . ' id=' . $request->user()->id);
        $detail = $request['detail'];

        $userBind = UserBind::where([
            'bind_ref' => UserBind::BIND_REF['weapp'],
            'user_id' => $request->user()->id
        ])->first();
        $decrypted = $this->weApp->encryptor->decryptData($userBind->weapp_session_key, $detail['iv'], $detail['encryptedData']);

        if($decrypted['unionId'])
        // 如果已存在相同 union_id 的 userBind 记录 a，则将当前 userBind.user_id 更改为a.user_id
        $existedUserBind = UserBind::where([
            'bind_status' => 1,
            'union_id' => $decrypted['unionId']
        ])->first();
        $userBind->user_id = $existedUserBind->user_id;
        $userBind->save();

        return $this->responseSuccess([
            'user' => $userBind->user,
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
     * @param $openID
     * @param $bindRef
     * TODO move it to BindInfoRepository
     * @return array
     */
    public function getUserBindInfoByOpenID($openID, $bindRef = UserBind::BIND_REF['weapp'], $bindStatus=null) {
        try {
            $q = UserBind::where([
                'open_id_crc32' => UserBind::crc32($openID),
                'open_id' => $openID,
                'bind_status' => $bindStatus,
                'bind_ref' => $bindRef]);
            if(is_null($bindStatus)) {
                $q->where('bind_ref', '=', $bindRef);
            }
            $data = $q->first();
            return $data;
        } catch (\Exception $exception) {
            return [];
        }
    }

    public function getUserBindInfoByUnionID($unionID, $bindRef = UserBind::BIND_REF['weapp'], $bindStatus=null) {
        try {
            $q = UserBind::where([
                'union_id_crc32' => UserBind::crc32($unionID),
                'union_id' => $unionID,
                'bind_status' => $bindStatus,
                'bind_ref' => $bindRef]);
            if(is_null($bindStatus)) {
                $q->where('bind_ref', '=', $bindRef);
            }
            $data = $q->first();
            return $data;
        } catch (\Exception $exception) {
            return [];
        }
    }
}