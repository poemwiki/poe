<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageAPIController extends Controller {
    public function __construct() {
    }

    public function recent(Request $request): array {
        $user     = $request->user();
        $messages = Message::toUser($user->id)->get(); // TODO paginate

        $ret = $messages->map(function (Message $message) {
            $ret = $message->only(['id', 'sender_id', 'text']);

            $ret['sender_name'] = $message->sender ? $message->sender->name : config('app.name');
            $ret['nav_to'] = $message->params['nav_to'] ?? '';
            $ret['date'] = date_ago($message->created_at);

            return $ret;
        });

        return $this->responseSuccess($ret);
    }
}