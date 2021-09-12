<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Qcloud\Cos\Client;

class UserAPIController extends Controller {
    public function update(Request $request) {
        $user = $request->user();
        if ($request->nickName) {
            $wechatApp = \EasyWeChat\Factory::miniProgram([
                'app_id'        => config('wechat.mini_program.default.app_id'),
                'secret'        => config('wechat.mini_program.default.secret'),
                'response_type' => 'object',
            ]);
            $result = $wechatApp->content_security->checkText($request->nickName);
            if ($result->errcode) {
                return $this->responseFail([], '请检查是否含有敏感词', Controller::$CODE['content_security_failed']);
            }

            $user->name = $request->nickName;
        }

        $user->update();

        return $this->responseSuccess($user);
    }

    public function avatar(Request $request) {
        $file = $request->file('avatar');

        if ($file->isValid()) {
            $ext      = $file->getClientOriginalExtension();
            $allow    = ['jpg', 'webp', 'png', 'jpeg']; // 支持的类型
            if (!in_array($ext, $allow)) {
                return $this->responseFail([], '不支持的图片类型，请上传 jpg/jpeg/png/webp 格式图片。', Controller::$CODE['img_format_invalid']);
            }

            $size = $file->getSize();
            $size = $size / 1024 / 1024;
            if ($size > 3) {
                return $this->responseFail([], '上传的图片不能超过3M', Controller::$CODE['upload_img_size_limit']);
            }

            $user     = $request->user();
            $fileName = $user->id . '.' . $ext;

            $fileID = $file->storeAs(
                config('app.avatar.user_path'),
                $fileName,
                'cosv5'
            );

            $client = new Client([
                'region'      => config('filesystems.disks.cosv5.region'),
                'credentials' => [
                    'secretId'  => config('filesystems.disks.cosv5.credentials.secretId'),
                    'secretKey' => config('filesystems.disks.cosv5.credentials.secretKey')
                ]
            ]);
            $bucket = config('filesystems.disks.cosv5.bucket');

            if ($fileID) {
                $objectUrlWithoutSign = $client->getObjectUrlWithoutSign($bucket, $fileID);
                $user->avatar         = $objectUrlWithoutSign;
                $user->save();

                return $this->responseSuccess(['avatar' => $objectUrlWithoutSign]);
            }
        }

        return $this->responseFail([], '图片上传失败。请稍后再试。');
    }

    public function data(Request $request) {
        $user     = $request->user();
        $campaign = Campaign::whereRaw('JSON_EXTRACT(settings, "$.resultUrl")')
            ->orderBy('end', 'desc')->limit(1)->first();

        $user->notify             = 1;
        $user->notify_url         = $campaign->settings ? $campaign->settings['resultUrl'] : null;
        $user->notify_title       = "赛诗会 #$campaign->name_lang 结果公布";
        $user->notify_campaign_id = $campaign->id;

        return $this->responseSuccess($user);
    }
}
