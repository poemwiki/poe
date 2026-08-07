<?php

use App\Http\Controllers\API\LoginWeAppController;
use App\Http\Controllers\Auth\LoginWechatController;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

require dirname(__DIR__) . '/bootstrap.php';

$app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

[$script, $barrierDir, $openId, $unionId, $worker, $channel, $isolation] = $argv;

if ($isolation === 'read-committed') {
    DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
}

Event::listen(Registered::class, static function () use ($barrierDir): void {
    file_put_contents($barrierDir . '/registered-' . getmypid(), (string) DB::transactionLevel());
    usleep(300000);
});
DB::listen(static function (QueryExecuted $query) use ($barrierDir): void {
    if (str_contains($query->sql, 'insert into `oauth_access_tokens`')) {
        file_put_contents($barrierDir . '/token-' . getmypid(), (string) DB::transactionLevel());
    }
});

if ($channel === 'weapp') {
    $controller    = new LoginWeAppController();
    $property      = new ReflectionProperty(LoginWeAppController::class, 'weApp');
    $weApp         = $property->getValue($controller);
    $weApp['auth'] = new BarrierMiniProgramAuthClient($barrierDir, $openId, $unionId);

    $request = Request::create('/api/v1/user/weapp-login', 'GET', [
        'code'     => 'verified-code-' . $worker,
        'nickName' => '并发登录测试用户',
    ]);
    $response = $controller->login($request);
    $userId   = $response['data']['data']['id'];
} else {
    touch($barrierDir . '/ready-' . getmypid());
    waitForBarrier($barrierDir);

    $request = Request::create('/login', 'GET');
    $app->instance('request', $request);
    session(['wechat.oauth_user.default' => (object) [
        'raw' => [
            'openid'     => $openId,
            'unionid'    => $unionId,
            'headimgurl' => '',
            'sex'        => 0,
        ],
        'nickname' => '并发登录测试用户',
        'email'    => '',
        'avatar'   => '',
    ]]);

    (new LoginWechatController())->login();
    $userId = Auth::id();
}

echo json_encode(['user_id' => $userId], JSON_THROW_ON_ERROR);

function waitForBarrier(string $barrierDir): void {
    $deadline = microtime(true) + 10;
    while (!file_exists($barrierDir . '/start')) {
        if (microtime(true) >= $deadline) {
            throw new RuntimeException('Timed out waiting for concurrent login workers.');
        }
        usleep(10000);
    }
}

class BarrierMiniProgramAuthClient {
    public function __construct(
        private string $barrierDir,
        private string $openId,
        private string $unionId
    ) {
    }

    public function session(string $code): array {
        touch($this->barrierDir . '/ready-' . getmypid());
        waitForBarrier($this->barrierDir);

        return [
            'openid'      => $this->openId,
            'unionid'     => $this->unionId,
            'session_key' => 'test-session-key',
        ];
    }
}
