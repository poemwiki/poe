<?php


namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\Request;

class UserAPIController extends Controller{
    public function update(Request $request) {
        $user = $request->user();
        if($request->nickName) {

            $wechatApp = \EasyWeChat\Factory::miniProgram([
                'app_id' => config('wechat.mini_program.default.app_id'),
                'secret' => config('wechat.mini_program.default.secret'),
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
        $campaign = Campaign::whereRaw('JSON_EXTRACT(settings, "$.resultUrl")')
            ->orderBy('end', 'desc')->limit(1)->first();

        $user->notify = 1;
        $user->notify_url = $campaign->settings ? $campaign->settings['resultUrl'] : null;
        $user->notify_title = "赛诗会 #$campaign->name_lang 结果公布";
        $user->notify_campaign_id = $campaign->id;
        return $this->responseSuccess($user);
    }
}