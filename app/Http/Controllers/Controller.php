<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController {
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    public static $CODE = [
        'success'                 => 0,
        'general_error'           => -1,
        'content_security_failed' => -2,
        'no_entry'                => -3,
        'duplicated'              => -4,
        'invalid_poem_length'     => -5,
        'poem_content_invalid'    => -6,
        'img_format_invalid'      => -7,
        'upload_img_size_limit'   => -8
    ];

    public function response($data, string $message = '', int $code = 0) {
        if (is_string($data)) {
            return [
                'redirect' => $data,
                'message'  => $message,
                'code'     => $code
            ];
        }

        return compact('data', 'message', 'code');
    }

    public function responseSuccess($data = [], $message = null) {
        return $this->response(
            $data, $message ?? '');
    }

    public function responseFail($data = [], $message = null, int $code = -1) {
        return $this->response(
            $data, $message ?? trans('fail'), $code);
    }
}
