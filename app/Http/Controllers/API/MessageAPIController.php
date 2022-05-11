<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\MessageStatus;
use Illuminate\Http\Request;

class MessageAPIController extends Controller {
    public function __construct() {
    }

    public function recent(Request $request): array {
        $user     = $request->user();
        $messages = Message::toUser($user->id)->with(['userStatus' => function ($q) use ($user) {
            $q->where('user_id', '=', $user->id);
        }])->orderBy('id', 'desc')->get(); // TODO paginate

        $ret = $messages->map(function (Message $message) {
            $ret = $message->only(['id', 'sender_id', 'text']);

            $ret['sender_name'] = $message->sender ? $message->sender->name : config('app.name');
            $ret['nav_to'] = $message->params['nav_to'] ?? '';
            $ret['date'] = date_ago($message->created_at);
            $ret['user_status'] = $message->userStatus ? $message->userStatus->status : MessageStatus::STATUS['unread'];

            return $ret;
        });

        return $this->responseSuccess($ret);
    }

    public function read(Request $request, int $id): array {
        $user    = $request->user();
        $message = Message::find($id);

        if (!$message) {
            return $this->responseFail('Message not found');
        }

        $message->updateOrCreateStatus($user->id, MessageStatus::STATUS['read']);

        return $this->responseSuccess();
    }
}