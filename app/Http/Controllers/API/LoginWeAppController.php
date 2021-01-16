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

    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function login(Request $request) {
        Log::info('try weApp login', $request->toArray());

        $code = $request->code;
        // 根据 code 获取微信 openid 和 session_key
        $miniProgram = \EasyWeChat\Factory::miniProgram([
            'app_id' => env('WECHAT_MINI_PROGRAM_APPID'),
            'secret' => env('WECHAT_MINI_PROGRAM_SECRET'),

            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'array',]);;
        // $miniProgram = \Overtrue\EasyWeChat::miniProgram(); // 小程序
        $data = $miniProgram->auth->session($code);
        if (isset($data['errcode'])) {
            return $this->responseFail([], 'code已过期或不正确');
        }
        Log::info('wechat server reply:', $data);
        $weappOpenid = $data['openid'];
        $weixinSessionKey = $data['session_key'];
        $nickname = $request->nickname;
        $avatar = $request->avatar;
        // $avatar = str_replace('/132', '/0', $request->avatar);//拿到分辨率高点的头像
        $country = $request->country ? $request->country : '';
        $province = $request->province ? $request->province : '';
        $city = $request->city ? $request->city : '';
        $gender = $request->gender;
        $language = $request->language ? $request->language : '';

        //找到 openid 对应的用户
        $userBind = $this->getUserBindInfoByOpenID($weappOpenid);

        if ($userBind) {
            // 老用户
            $attributes['updated_at'] = now();
            $attributes['weapp_session_key'] = $weixinSessionKey;
            $attributes['avatar'] = $avatar;
            if ($nickname) {
                $attributes['nickname'] = $nickname;
            }
            // 更新用户数据
            $userBind->update($attributes);


            // 直接创建token并设置有效期
            $createToken = $userBind->user->createToken($weappOpenid);
            $createToken->token->expires_at = Carbon::now()->addDays(30);
            $createToken->token->save();
            $token = $createToken->accessToken;
            $this->responseSuccess([
                'access_token' => $token,
                'token_type' => "Bearer",
                'expires_in' => Carbon::now()->addDays(30),
                'data' => $userBind->user,
            ]);

        } else {
            // 新用户
            $userBind = $this->getUserBindInfoByUnionID($data['unionid']);
            if ($userBind && $userBind->user) {
                // if same union_id user exists, get first user id by union_id
                // and new userBind to this user
                $existedUserId = $userBind->user->id;
            }
            // TODO user.name should be unique
            $newUser = User::create([
                'name' => $nickname . '[from-wechat]',
                'email' => '',
                'invite_code' => hash('crc32', sha1(2 . '')),
                'invited_by' => 2,
                'password' => '',
                'avatar' => $avatar
            ]);
            UserBind::create([
                'open_id' => $weappOpenid,
                'union_id' => $data['unionid'] ?? '',
                'user_id' => $newUser->id,
                'bind_status' => 1,
                'bind_ref' => UserBind::BIND_REF['wechat'],
                'nickname' => $nickname,
                'avatar' => $avatar,
                'gender' => $gender,
                'info' => json_encode($data)
            ]);

            event(new Registered($newUser));

            $this->guard()->login($newUser);
        }

        return $this->responseSuccess([]);
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
    public function getUserBindInfoByOpenID($openID, $bindRef = UserBind::BIND_REF['weapp']) {
        try {
            $data = UserBind::where([
                'open_id_crc32' => UserBind::crc32($openID),
                'open_id' => $openID,
                'bind_status' => 1,
                'bind_ref' => $bindRef])
                ->first();
            return $data;
        } catch (\Exception $exception) {
            return [];
        }
    }

    public function getUserBindInfoByUnionID($unionID, $bindRef = UserBind::BIND_REF['weapp']) {
        try {
            $data = UserBind::where([
                'union_id_crc32' => UserBind::crc32($unionID),
                'union_id' => $unionID,
                'bind_status' => 1,
                'bind_ref' => $bindRef])
                ->first();
            return $data;
        } catch (\Exception $exception) {
            return [];
        }
    }
}