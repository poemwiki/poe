<?php


namespace App\Http\Controllers\Auth;


use App\Http\Controllers\Controller;
use App\Models\UserBind;
use App\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LoginWechatController extends Controller {
    //    use RedirectsUsers;

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function login() {
        Log::info(json_encode(request()->all()));
        $wechatUser = session('wechat.oauth_user.default'); // 拿到授权用户资料
        //        if(request()->input('code'))
        if ($userBind = $this->getUserBindInfoByOpenID($wechatUser->raw['openid'])) {

            $this->guard()->login(User::find($userBind->user_id));
        } else {
            // TODO if union_id exists, get first user id by union_id
            // TODO user.name should be unique
            $newUser = User::create([
                'name' => $wechatUser->nickname,
                'email' => $wechatUser->email ?? '',
                'invite_code' => hash('crc32', sha1(2 . $wechatUser->email)),
                'invited_by' => 2,
                'password' => '',
            ]);
            UserBind::create([
                'open_id' => $wechatUser->raw['openid'],
                'union_id' => $wechatUser->raw['unionid'] ?? '',
                'user_id' => $newUser->id,
                'bind_status' => 1,
                'bind_ref' => UserBind::BIND_REF['wechat'],
                'nick_name' => $wechatUser->nickname,
                'avatar' => $wechatUser->avatar,
                'gender' => $wechatUser->raw['sex'],
                'info' => json_encode($wechatUser)
            ]);

            event(new Registered($newUser));

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
     * TODO move it to BindInfoRepository
     * @return array
     */
    public function getUserBindInfoByOpenID($openID, $bindRef = UserBind::BIND_REF['wechat']) {
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
}