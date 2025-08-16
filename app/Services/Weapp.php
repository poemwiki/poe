<?php

namespace App\Services;


class Weapp {
    /**
     * @var \Illuminate\Contracts\Foundation\Application|mixed
     */
    private \EasyWeChat\MiniProgram\Application $app;

    public function __construct() {
        $this->app = \EasyWeChat::miniProgram();
    }

    public function fetchAppCodeImg(string $scene, string $appCodeImgDir, string $page = 'pages/detail/detail', string $appCodeFileName = 'app-code.jpg'): bool|string {
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

    /**
     * @param string $content
     * @param int    $scene   1 资料；2 评论；3 论坛；4 社交日志
     * @return bool
     */
    public function checkText(string $content, int $scene = 1): bool {
        $result = $this->app->content_security->checkText($content, [
            'scene' => $scene
        ]);
        if ($result['errcode'] !== 0 && $result['errcode'] !== -1) {
            return false;
        }

        return true;
    }
}
