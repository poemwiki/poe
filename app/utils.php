<?php

if (! function_exists('file_get_contents_post')) {
    /**
     * post $data to $url
     * @param $url
     * @param $data
     * @param string $contentType
     * @param int $timeout
     * @return false|string
     * @throws Exception
     */
    function file_get_contents_post(string $url, $data, string $contentType = 'application/x-www-form-urlencoded', $timeout = 15) {

        $options = [
            'http' => [
                'header'  => "Content-type: " . $contentType,
                'method'  => "POST",
                'content' => $contentType==='application/json' ? json_encode($data) : http_build_query($data),
                'timeout' => $timeout,
            ],
        ];
        $context = stream_context_create($options);
        try {
            return file_get_contents($url, false, $context);
        } catch (Exception $e) {
            throw new Exception("Error on file_get_contents_post: " . $e->getMessage());
        }
    }
}

if (! function_exists('curl_post')) {
    function curl_post(string $url, $data, string $contentType = 'application/x-www-form-urlencoded') {
        $ch = curl_init();
        //请求地址
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

        $postData = $contentType === 'application/json' ? json_encode($data) : http_build_query($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        //获取的信息以文件流的形式返回，而不是直接输出
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 设置请求头
        $headers[] = "Content-type:" . $contentType;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        //发起请求
        try {
            return curl_exec($ch);
        } catch (Exception $e) {
            throw new Exception("Error on curl_post: " . $e->getMessage());
        }
    }
}

if (! function_exists('short_url')) {
    /**
     * get a short url from api.xiaomark.com started with https://sourl.cn
     * @param $origin string Origin url should under these 3 domains: mp.weixin.qq.com, poemwiki.com, poemwiki.org
     * @param null $cb
     * @return mixed
     */
    function short_url(string $origin, callable $cb = null) {
        // TODO check redis if n_links_today <= 0, return $origin

        $request_url = 'https://api.xiaomark.com/v1/link/create';
        $data = [
            'apikey' => 'fccab0cf923086937191cb3d7a523772',
            'origin_url' => $origin,
        ];

        $result_str = curl_post($request_url, $data, 'application/json');

        if (!$result_str) {
            return $origin;
        }

        $result = json_decode($result_str, true);

        if ($result && $result['code'] == "0" && isset($result['data']['link'])) {
            $url = $result['data']['link']['url'];
            // TODO save $result['data']['n_links_today'] to redis
            if(is_callable($cb)) {
                $cb($url, $result['data']['n_links_today'] ?? 0);
            }
            return $url;
        }

        return $origin;
    }
}

if (! function_exists('create_image')) {

    /**
     * @param $imgPath
     * @return false|resource
     * @throws Exception
     */
    function create_image($imgPath) {
        $type = \File::mimeType($imgPath);
        switch ($type) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($imgPath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($imgPath);
                break;
            default:
                throw new Exception('Image type not supported. front image: ' . $imgPath);
        }
        return $image;
    }
}

if (! function_exists('img_overlay')) {
    /**
     * @param $bg
     * @param $front
     * @param $dist_x
     * @param $dist_y
     * @return false|resource
     * @throws Exception
     */
    function img_overlay($bg, $front, $dist_x, $dist_y, $dist_w, $dist_h) {
        // TODO use image type from getimagesize
        $bgImg = create_image($bg);
        $frontImg = create_image($front);

        list($width, $height) = getimagesize($bg);
        list($frontWidth, $frontHeight) = getimagesize($front);
        $out = imagecreatetruecolor($width, $height);
        imagecopyresampled($out, $bgImg, 0, 0, 0, 0, $width, $height, $width, $height);
        imagecopyresampled($out, $frontImg, $width-220, $height-160, 0, 0, $dist_w, $dist_h, $frontWidth, $frontHeight);
        return $out;
    }

}