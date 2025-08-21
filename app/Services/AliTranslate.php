<?php

namespace App\Services;

use AlibabaCloud\SDK\Alimt\V20181012\Models\GetDetectLanguageResponse;
use AlibabaCloud\Tea\Utils\Utils\RuntimeOptions;
use App\Models\Language;
use Darabonba\OpenApi\Models\Config;
use Darabonba\OpenApi\Models\OpenApiRequest;
use Darabonba\OpenApi\Models\Params;
use Darabonba\OpenApi\OpenApiClient;
use Exception;
use Illuminate\Support\Facades\Log;

class AliTranslate {
    /**
     * 使用AK&SK初始化账号Client.
     * @return OpenApiClient Client
     */
    public static function createClient(): OpenApiClient {
        $config = new Config([
            'accessKeyId'     => config('app.ali.id'),
            'accessKeySecret' => config('app.ali.secret'),
        ]);
        // 访问的域名
        $config->endpoint = 'mt.aliyuncs.com';

        return new OpenApiClient($config);
    }

    /**
     * API 相关.
     * @return Params OpenApi.Params
     */
    public static function params(string $action): Params {
        $params = new Params([
            // 接口名称
            'action' => $action,
            // 接口版本
            'version' => '2018-10-12',
            // 接口协议
            'protocol' => 'HTTPS',
            // 接口 HTTP 方法
            'method'   => 'POST',
            'authType' => 'AK',
            'style'    => 'RPC',
            // 接口 PATH
            'pathname' => '/',
            // 接口请求体内容格式
            'reqBodyType' => 'formData',
            // 接口响应体内容格式
            'bodyType' => 'json'
        ]);

        return $params;
    }

    /**
     * @param string $text
     * @return string|null
     */
    public static function detectLanguage(string $text): ?int {
        $client = self::createClient();
        $params = self::params('GetDetectLanguage');
        // runtime options
        $runtime = new RuntimeOptions([]);
        $request = new OpenApiRequest([
            'body' => ['SourceText' => $text],
        ]);

        try {
            $response = GetDetectLanguageResponse::fromMap($client->callApi($params, $request, $runtime));
            $locale   = $response->body->detectedLanguage;
            $locale   = $locale === 'zh' ? 'zh-CN' : $locale;
            $model    = Language::where('locale', '=', $locale)->inUse()->first();
            if (!$model) {
                return null;
            }

            return $model->id;
        } catch (Exception $error) {
            Log::error($error->getMessage());
        }

        return null;
    }
}