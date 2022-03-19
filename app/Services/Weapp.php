<?php

namespace App\Services;

use EasyWeChat\Factory;

class Weapp {
    public function __construct() {
        $this->app = Factory::miniProgram([
            'app_id' => config('wechat.mini_program.default.app_id'),
            'secret' => config('wechat.mini_program.default.secret')
        ]);
    }

    /**
     * @param string $scene
     * @param string $appCodeImgDir
     * @param string $page
     * @param bool   $force           TODO add cache for this
     * @param string $appCodeFileName
     * @return false|string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     */
    public function fetchAppCodeImg(string $scene, string $appCodeImgDir, string $page = 'pages/detail/detail', bool $force = false, string $appCodeFileName = 'app-code.jpg') {
        // 注意微信对此接口调用频率有限制
        $response = $this->app->app_code->getUnlimit($scene, [
            'page'       => $page,
            'width'      => 280,
            'is_hyaline' => false
        ]);

        if ($response instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
            if (!is_dir($appCodeImgDir)) {
                mkdir($appCodeImgDir, 0755, true);
            }

            $response->saveAs($appCodeImgDir, $appCodeFileName);

            return $appCodeImgDir . '/' . $appCodeFileName;
        }

        return false;
    }
}
