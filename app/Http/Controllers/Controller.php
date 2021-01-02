<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController {
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


    public function response($data, string $message = '', int $code = 0) {
        if(is_string($data)) {
            return [
                'redirect' => $data,
                'message' => $message,
                'code' => $code
            ];
        }
        return compact('data', 'message', 'code');
    }

    public function responseSuccess($data=[], $message = null) {
        return $this->response(
            $data, $message ?? trans('success'));
    }
}
