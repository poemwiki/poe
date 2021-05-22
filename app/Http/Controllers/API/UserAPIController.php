<?php


namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserAPIController extends Controller{
    public function update(Request $request) {
        $user = $request->user();
        if($request->nickName) {

            $wechatApp = \EasyWeChat\Factory::miniProgram([
                'app_id' => env('WECHAT_MINI_PROGRAM_APPID'),
                'secret' => env('WECHAT_MINI_PROGRAM_SECRET'),
                'response_type' => 'object',
            ]);
            $result = $wechatApp->content_security->checkText($request->nickName);
            if($result->errcode) {
                return $this->responseFail([], '请检查是否含有敏感词', Controller::$CODE['content_security_failed']);
            }

            $user->name = $request->nickName;
        }
        if($request->avatar) {
            $user->avatar = $request->avatar;
        }
        $user->update();

        return $this->responseSuccess($user);
    }

    public function data(Request $request) {
        $user = $request->user();
        return $this->responseSuccess($user);
    }
}