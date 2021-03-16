<?php


namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserAPIController extends Controller{
    public function update(Request $request) {
        $user = $request->user();
        if($request->nickName) {
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