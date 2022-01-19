<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Author;
use App\Models\Campaign;
use App\Models\MediaFile;
use App\Models\Poem;
use App\Models\Review;
use App\Services\Tx;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use League\MimeTypeDetection\GeneratedExtensionToMimeTypeMap;

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

        if (!$file->isValid()) {
            logger()->error('user avatar upload Error: file invalid.');

            return $this->responseFail([], '图片上传失败。请稍后再试。');
        }

        $ext   = $file->getClientOriginalExtension();
        $allow = ['jpg', 'webp', 'png', 'jpeg', 'bmp']; // 支持的类型
        if (!in_array($ext, $allow)) {
            return $this->responseFail([], '不支持的图片类型，请上传 jpg/jpeg/png/webp/bmp 格式图片。', Controller::$CODE['img_format_invalid']);
        }

        $size = $file->getSize();
        if ($size > 3 * 1024 * 1024) {
            return $this->responseFail([], '上传的图片不能超过3M', Controller::$CODE['upload_img_size_limit']);
        }

        $user     = $request->user();
        $fileName = $user->fakeId . '.' . $ext;

        [$width, $height] = getimagesize($file);
        $corpSize         = min($width, $height, 600);

        $client     = new Tx();
        $format     = TX::SUPPORTED_FORMAT['webp'];
        $toFileName = config('app.avatar.user_path') . '/' . $user->fakeId . '.' . $format;
        $fileID     = config('app.avatar.user_path') . '/' . $fileName;

        try {
            $result = $client->scropAndUpload($fileID, $toFileName, $file->getContent(), $format, $corpSize, $corpSize);
            // Tencent cos client has set default timezone to PRC
            date_default_timezone_set(config('app.timezone', 'UTC'));
            logger()->info('scropAndUpload finished:', $result);
        } catch (\Exception $e) {
            logger()->error('scropAndUpload Error:' . $e->getMessage());

            return $this->responseFail([], '图片上传失败。请稍后再试。');
        }

        $avatarImage = $result['Data']['ProcessResults']['Object'][0];
        if (isset($avatarImage['Location'])) {
            $objectUrlWithoutSign = 'https://' . $avatarImage['Location'];
            $user->avatar         = $objectUrlWithoutSign . '?v=' . now()->timestamp;
            $user->save();

            $this->saveAuthorMediaFile($user, MediaFile::TYPE['avatar'], $avatarImage['Key'], $fileName, $format, $avatarImage['Size']);

            $client->deleteObject($fileID);

            return $this->responseSuccess(['avatar' => $objectUrlWithoutSign]);
        }

        return $this->responseFail([], '图片上传失败。请稍后再试。');
    }

    /**
     * TODO pagination.
     * @param int $id
     * @return array
     */
    public function timeline(int $id, int $page = 1, int $pageSize = 10) {
        $user = User::find($id);
        if (!$user) {
            return $this->responseFail([], '用户不存在', Controller::$CODE['not_found']);
        }

        $poemLogs = ActivityLog::where([
            'causer_id'    => $user->id,
            'causer_type'  => User::class,
            'subject_type' => Poem::class,
            'description'  => 'created'
        ])->select(['id', 'subject_type', 'subject_id', 'created_at'])->whereHas('poem')->orderByDesc('created_at');

        $reviewLogs = ActivityLog::where([
            'causer_id'    => $user->id,
            'causer_type'  => User::class,
            'subject_type' => Review::class,
            'description'  => 'created'
        ])->select(['id', 'subject_type', 'subject_id', 'created_at'])->whereHas('review')->orderByDesc('created_at');

        $data = DB::query()->select()
            ->fromSub($poemLogs->union($reviewLogs)->orderByDesc('created_at'), 'sub_query')
            ->paginate($pageSize, null, '', $page)->toArray();

        $data['data'] = array_map(function ($item) {
            $ret = [];
            $ret['type'] = array_search($item->subject_type, ActivityLog::SUBJECT);
            $ret['id'] = $item->subject_id;
            $log = ActivityLog::find($item->id);

            switch ($ret['type']) {
                case 'poem':
                    $ret['subject'] = $log->poem ? $log->poem->only(['id', 'title', 'first_line']) : null;

                    break;
                case 'review':
                    $ret['subject'] = $log->review ? $log->review->only(['id', 'title', 'content']) : null;

                    break;
            }

            return $ret;
        }, $data['data']);

        return $this->responseSuccess([
            'user'     => $user->toArray(),
            'timeline' => $data
        ]);
    }

    /**
     * @param Author $author
     * @param string $type
     * @param string $path
     * @param string $name
     * @param string $toFormat
     * @param int    $size
     * @param int    $fid
     * @return MediaFile
     */
    protected function saveAuthorMediaFile(User $user, string $type, string $path, string $name, string $format, int $size, int $fid = 0): MediaFile {
        $mediaFile = MediaFile::updateOrCreate([
            'model_type'     => User::class,
            'model_id'       => $user->id,
            'type'           => $type,
        ], [
            'path'      => $path,
            'name'      => $name,
            'mime_type' => GeneratedExtensionToMimeTypeMap::MIME_TYPES_FOR_EXTENSIONS[$format],
            'disk'      => 'cosv5',
            'size'      => $size,
            'fid'       => $fid
        ]);

        $user->relateToAvatar($mediaFile->id);
        /* @var MediaFile $mediaFile */
        return $mediaFile;
    }

    public function data(Request $request) {
        /** @var User $user */
        $user     = $request->user();
        $campaign = Campaign::whereRaw('JSON_EXTRACT(settings, "$.resultUrl")')
            ->orderBy('end', 'desc')->limit(1)->first();

        // TODO $user->settings
        $user->notify             = $user->created_at->diffInMinutes(now()) > 3;
        $user->notify_url         = $campaign->settings ? $campaign->settings['resultUrl'] : null;
        $user->notify_title       = "赛诗会 #$campaign->name_lang 结果公布";
        $user->notify_campaign_id = $campaign->id;

        return $this->responseSuccess($user);
    }
}
