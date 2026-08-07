<?php

namespace Tests\APIs;

use App\Http\Controllers\API\LoginWeAppController;
use App\Http\Controllers\Auth\LoginWechatController;
use App\Models\UserBind;
use App\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use ReflectionProperty;
use RuntimeException;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class WeChatLoginConcurrencyTest extends TestCase {
    private string $openId;
    private string $unionId;
    private array $createdUserIds = [];

    protected function setUp(): void {
        parent::setUp();

        $suffix        = bin2hex(random_bytes(8));
        $this->openId  = 'race-openid-' . $suffix;
        $this->unionId = 'race-unionid-' . $suffix;

        $this->cleanupIdentity();
    }

    protected function tearDown(): void {
        DB::purge();
        DB::reconnect();
        $this->cleanupIdentity();

        parent::tearDown();
    }

    /** @test */
    public function concurrent_mini_program_logins_return_the_same_user(): void {
        $workerCount = 6;
        $barrierDir  = sys_get_temp_dir() . '/poe-wechat-login-' . bin2hex(random_bytes(8));

        mkdir($barrierDir, 0700, true);

        $workers = [];
        for ($worker = 0; $worker < $workerCount; $worker++) {
            $process = new Process([
                PHP_BINARY,
                base_path('tests/Fixtures/run_weapp_login.php'),
                $barrierDir,
                $this->openId,
                '',
                (string) $worker,
                'weapp',
                'read-committed',
            ], base_path(), [
                'APP_ENV'          => 'testing',
                'ENVIRONMENT_FILE' => '.env.testing',
            ]);
            $process->setTimeout(20);
            $process->start();
            $workers[] = $process;
        }

        $this->releaseWorkersWhenReady($barrierDir, $workerCount);

        $results = [];
        foreach ($workers as $process) {
            $process->wait();
            $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
            $result    = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
            $results[] = $result;
        }

        DB::purge();
        DB::reconnect();

        $userIds = array_values(array_unique(array_column($results, 'user_id')));
        $this->assertCount(1, $userIds, 'Concurrent logins for one verified identity must return one user.');
        $this->assertCommittedSideEffects($barrierDir, 6);

        $this->removeBarrierDirectory($barrierDir);
    }

    /** @test */
    public function concurrent_wechat_channels_with_one_unionid_return_the_same_user(): void {
        $workerCount = 6;
        $barrierDir  = sys_get_temp_dir() . '/poe-wechat-login-' . bin2hex(random_bytes(8));

        mkdir($barrierDir, 0700, true);

        $workers = [];
        for ($worker = 0; $worker < $workerCount; $worker++) {
            $channel = $worker % 2 === 0 ? 'weapp' : 'wechat';
            $openId  = $this->openId . '-' . $channel;
            $process = new Process([
                PHP_BINARY,
                base_path('tests/Fixtures/run_weapp_login.php'),
                $barrierDir,
                $openId,
                $this->unionId,
                (string) $worker,
                $channel,
                'read-committed',
            ], base_path(), [
                'APP_ENV'          => 'testing',
                'ENVIRONMENT_FILE' => '.env.testing',
            ]);
            $process->setTimeout(20);
            $process->start();
            $workers[] = $process;
        }

        $this->releaseWorkersWhenReady($barrierDir, $workerCount);

        $results = [];
        foreach ($workers as $process) {
            $process->wait();
            $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
            $result = json_decode($process->getOutput(), true);
            $this->assertIsArray(
                $result,
                'Login worker returned invalid JSON: ' . $process->getOutput() . $process->getErrorOutput()
            );
            $results[] = $result;
        }

        DB::purge();
        DB::reconnect();

        $userIds = array_values(array_unique(array_column($results, 'user_id')));
        $this->assertCount(1, $userIds, 'Concurrent WeChat channels for one unionid must return one user.');
        $this->assertCommittedSideEffects($barrierDir, 3);

        $this->removeBarrierDirectory($barrierDir);
    }

    /** @test */
    public function a_binding_query_error_does_not_register_a_user(): void {
        Event::fake([Registered::class]);
        DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
        $controller = $this->miniProgramController();
        $armed      = true;

        DB::listen(static function ($query) use (&$armed): void {
            if ($armed && str_contains($query->sql, 'from `user_bind_info`')) {
                $armed = false;

                throw new RuntimeException('Forced binding lookup failure.');
            }
        });

        try {
            $controller->login($this->miniProgramRequest());
            $this->fail('A binding lookup failure must abort login.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced binding lookup failure.', $exception->getMessage());
        }

        Event::assertNotDispatched(Registered::class);
        $this->assertSame('READ-COMMITTED', $this->sessionTransactionIsolation());
    }

    /** @test */
    public function login_restores_the_connection_isolation_level_after_success(): void {
        Event::fake([Registered::class]);
        DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');

        $response = $this->miniProgramController()->login($this->miniProgramRequest());

        $this->assertNotEmpty($response['data']['access_token']);
        $this->assertSame('READ-COMMITTED', $this->sessionTransactionIsolation());
        Event::assertDispatchedTimes(Registered::class, 1);
    }

    /** @test */
    public function wechat_web_login_restores_the_connection_isolation_level_after_success(): void {
        Event::fake([Registered::class]);
        DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');

        $this->wechatWebController()->login();

        $this->assertNotNull(auth()->id());
        $this->assertSame('READ-COMMITTED', $this->sessionTransactionIsolation());
        Event::assertDispatchedTimes(Registered::class, 1);
    }

    /** @test */
    public function wechat_web_login_restores_the_connection_isolation_level_after_query_error(): void {
        Event::fake([Registered::class]);
        DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
        $armed = true;

        DB::listen(static function ($query) use (&$armed): void {
            if ($armed && str_contains($query->sql, 'from `user_bind_info`')) {
                $armed = false;

                throw new RuntimeException('Forced web binding lookup failure.');
            }
        });

        $exception = null;

        try {
            $this->wechatWebController()->login();
        } catch (RuntimeException $caught) {
            $exception = $caught;
        }

        $this->assertNotNull($exception, 'A web binding lookup failure must abort login.');
        $this->assertSame('Forced web binding lookup failure.', $exception->getMessage());
        $this->assertSame('READ-COMMITTED', $this->sessionTransactionIsolation());
        $this->assertTrue(auth()->guest());
        Event::assertNotDispatched(Registered::class);
    }

    /** @test */
    public function login_rejects_an_openid_and_unionid_owned_by_different_users(): void {
        Event::fake([Registered::class]);
        $openIdUser = $this->createUser('openid-owner');
        $unionUser  = $this->createUser('unionid-owner');

        UserBind::create([
            'open_id'     => $this->openId,
            'union_id'    => '',
            'user_id'     => $openIdUser->id,
            'bind_status' => 1,
            'bind_ref'    => UserBind::BIND_REF['weapp'],
            'nickname'    => '',
            'avatar'      => '',
            'gender'      => 0,
            'info'        => null,
        ]);
        UserBind::create([
            'open_id'     => $this->openId . '-wechat',
            'union_id'    => $this->unionId,
            'user_id'     => $unionUser->id,
            'bind_status' => 1,
            'bind_ref'    => UserBind::BIND_REF['wechat'],
            'nickname'    => '',
            'avatar'      => '',
            'gender'      => 0,
            'info'        => null,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The WeChat openid and unionid resolve to different users.');

        $this->miniProgramController()->login($this->miniProgramRequest());
    }

    /** @test */
    public function login_rejects_an_active_openid_bound_to_multiple_users(): void {
        Event::fake([Registered::class]);
        $firstUser  = $this->createUser('first-openid-owner');
        $secondUser = $this->createUser('second-openid-owner');

        foreach ([$firstUser, $secondUser] as $user) {
            UserBind::create([
                'open_id'     => $this->openId,
                'union_id'    => '',
                'user_id'     => $user->id,
                'bind_status' => 1,
                'bind_ref'    => UserBind::BIND_REF['weapp'],
                'nickname'    => '',
                'avatar'      => '',
                'gender'      => 0,
                'info'        => null,
            ]);
        }

        $exception = null;

        try {
            $this->miniProgramController()->login($this->miniProgramRequest());
        } catch (RuntimeException $caught) {
            $exception = $caught;
        }

        $this->assertNotNull($exception, 'An openid owned by multiple users must not log in.');
        $this->assertSame('The WeChat openid is bound to multiple users.', $exception->getMessage());
        Event::assertNotDispatched(Registered::class);
        $this->assertSame(
            0,
            DB::table('oauth_access_tokens')->whereIn('user_id', [$firstUser->id, $secondUser->id])->count()
        );
    }

    /** @test */
    public function wechat_web_login_rejects_an_active_openid_bound_to_multiple_users(): void {
        Event::fake([Registered::class]);
        $firstUser  = $this->createUser('first-web-openid-owner');
        $secondUser = $this->createUser('second-web-openid-owner');

        foreach ([$firstUser, $secondUser] as $user) {
            UserBind::create([
                'open_id'     => $this->openId,
                'union_id'    => '',
                'user_id'     => $user->id,
                'bind_status' => 1,
                'bind_ref'    => UserBind::BIND_REF['wechat'],
                'nickname'    => '',
                'avatar'      => '',
                'gender'      => 0,
                'info'        => null,
            ]);
        }

        $exception = null;

        try {
            $this->wechatWebController()->login();
        } catch (RuntimeException $caught) {
            $exception = $caught;
        }

        $this->assertNotNull($exception, 'A web openid owned by multiple users must not log in.');
        $this->assertSame('The WeChat openid is bound to multiple users.', $exception->getMessage());
        $this->assertTrue(auth()->guest());
        Event::assertNotDispatched(Registered::class);
    }

    /** @test */
    public function login_rejects_an_active_unionid_bound_to_multiple_users(): void {
        Event::fake([Registered::class]);
        $firstUser  = $this->createUser('first-unionid-owner');
        $secondUser = $this->createUser('second-unionid-owner');

        foreach ([$firstUser, $secondUser] as $index => $user) {
            UserBind::create([
                'open_id'     => $this->openId . '-union-owner-' . $index,
                'union_id'    => $this->unionId,
                'user_id'     => $user->id,
                'bind_status' => 1,
                'bind_ref'    => $index === 0 ? UserBind::BIND_REF['weapp'] : UserBind::BIND_REF['wechat'],
                'nickname'    => '',
                'avatar'      => '',
                'gender'      => 0,
                'info'        => null,
            ]);
        }

        $exception = null;

        try {
            $this->miniProgramController()->login($this->miniProgramRequest());
        } catch (RuntimeException $caught) {
            $exception = $caught;
        }

        $this->assertNotNull($exception, 'A unionid owned by multiple users must not log in.');
        $this->assertSame('The WeChat unionid is bound to multiple users.', $exception->getMessage());
        Event::assertNotDispatched(Registered::class);
        $this->assertSame(
            0,
            DB::table('oauth_access_tokens')->whereIn('user_id', [$firstUser->id, $secondUser->id])->count()
        );
    }

    /** @test */
    public function wechat_web_login_rejects_an_active_unionid_bound_to_multiple_users(): void {
        Event::fake([Registered::class]);
        $firstUser  = $this->createUser('first-web-unionid-owner');
        $secondUser = $this->createUser('second-web-unionid-owner');

        foreach ([$firstUser, $secondUser] as $index => $user) {
            UserBind::create([
                'open_id'     => $this->openId . '-web-union-owner-' . $index,
                'union_id'    => $this->unionId,
                'user_id'     => $user->id,
                'bind_status' => 1,
                'bind_ref'    => $index === 0 ? UserBind::BIND_REF['weapp'] : UserBind::BIND_REF['wechat'],
                'nickname'    => '',
                'avatar'      => '',
                'gender'      => 0,
                'info'        => null,
            ]);
        }

        $exception = null;

        try {
            $this->wechatWebController()->login();
        } catch (RuntimeException $caught) {
            $exception = $caught;
        }

        $this->assertNotNull($exception, 'A web unionid owned by multiple users must not log in.');
        $this->assertSame('The WeChat unionid is bound to multiple users.', $exception->getMessage());
        $this->assertTrue(auth()->guest());
        Event::assertNotDispatched(Registered::class);
    }

    /** @test */
    public function login_does_not_rewrite_an_inactive_binding_when_unionid_changes(): void {
        Event::fake([Registered::class]);
        $user         = $this->createUser('unionid-migration-user');
        $inactiveUser = $this->createUser('inactive-unionid-owner');

        $activeBinding = UserBind::create([
            'open_id'     => $this->openId,
            'union_id'    => 'previous-active-unionid',
            'user_id'     => $user->id,
            'bind_status' => 1,
            'bind_ref'    => UserBind::BIND_REF['weapp'],
            'nickname'    => '',
            'avatar'      => '',
            'gender'      => 0,
            'info'        => null,
        ]);
        $inactiveBinding = UserBind::create([
            'open_id'     => $this->openId . '-inactive',
            'union_id'    => 'repaired-inactive-unionid',
            'user_id'     => $user->id,
            'bind_status' => 0,
            'bind_ref'    => UserBind::BIND_REF['wechat'],
            'nickname'    => '',
            'avatar'      => '',
            'gender'      => 0,
            'info'        => null,
        ]);
        $inactiveUnionIdCrc32 = $inactiveBinding->union_id_crc32;
        UserBind::create([
            'open_id'     => $this->openId . '-inactive-union-owner',
            'union_id'    => $this->unionId,
            'user_id'     => $inactiveUser->id,
            'bind_status' => 0,
            'bind_ref'    => UserBind::BIND_REF['wechat-scan'],
            'nickname'    => '',
            'avatar'      => '',
            'gender'      => 0,
            'info'        => null,
        ]);

        $response = $this->miniProgramController()->login($this->miniProgramRequest());

        $this->assertSame($user->id, $response['data']['data']['id']);
        $this->assertSame($this->unionId, $activeBinding->fresh()->union_id);
        $this->assertSame('repaired-inactive-unionid', $inactiveBinding->fresh()->union_id);
        $this->assertSame((string) $inactiveUnionIdCrc32, (string) $inactiveBinding->fresh()->union_id_crc32);
        Event::assertNotDispatched(Registered::class);
    }

    private function releaseWorkersWhenReady(string $barrierDir, int $workerCount): void {
        $deadline = microtime(true) + 10;
        do {
            $readyFiles = glob($barrierDir . '/ready-*');
            if (count($readyFiles) === $workerCount) {
                touch($barrierDir . '/start');

                return;
            }
            usleep(10000);
        } while (microtime(true) < $deadline);

        throw new RuntimeException('Login workers did not reach the concurrency barrier.');
    }

    private function cleanupIdentity(): void {
        $bindings = DB::table('user_bind_info')
            ->where(function ($query): void {
                $query->where('union_id', $this->unionId ?? '')
                    ->orWhere('open_id', 'like', ($this->openId ?? '') . '%');
            })
            ->get(['id', 'user_id']);
        $bindingIds = $bindings->pluck('id')->all();
        $userIds    = array_values(array_unique(array_merge(
            $bindings->pluck('user_id')->all(),
            $this->createdUserIds
        )));

        if ($userIds) {
            DB::table('oauth_access_tokens')->whereIn('user_id', $userIds)->delete();
        }
        if ($bindingIds) {
            DB::table('activity_log')
                ->where('subject_type', 'App\\Models\\UserBind')
                ->whereIn('subject_id', $bindingIds)
                ->delete();
            DB::table('user_bind_info')->whereIn('id', $bindingIds)->delete();
        }
        if ($userIds) {
            DB::table('users')->whereIn('id', $userIds)->delete();
        }
    }

    private function removeBarrierDirectory(string $barrierDir): void {
        foreach (glob($barrierDir . '/*') as $file) {
            unlink($file);
        }
        rmdir($barrierDir);
    }

    private function assertCommittedSideEffects(string $barrierDir, int $tokenCount): void {
        $registrationFiles = glob($barrierDir . '/registered-*');
        $this->assertCount(1, $registrationFiles, 'A concurrent registration must emit one Registered event.');
        $this->assertSame('0', file_get_contents($registrationFiles[0]), 'Registered must run after commit.');

        $tokenFiles = glob($barrierDir . '/token-*');
        $this->assertCount($tokenCount, $tokenFiles, 'Each mini program login must issue a token.');
        foreach ($tokenFiles as $tokenFile) {
            $this->assertSame('0', file_get_contents($tokenFile), 'Token creation must run after commit.');
        }
    }

    private function miniProgramController(): LoginWeAppController {
        $controller    = new LoginWeAppController();
        $property      = new ReflectionProperty(LoginWeAppController::class, 'weApp');
        $weApp         = $property->getValue($controller);
        $weApp['auth'] = new ImmediateMiniProgramAuthClient($this->openId, $this->unionId);

        return $controller;
    }

    private function wechatWebController(): LoginWechatController {
        session(['wechat.oauth_user.default' => (object) [
            'raw' => [
                'openid'     => $this->openId,
                'unionid'    => $this->unionId,
                'headimgurl' => '',
                'sex'        => 0,
            ],
            'nickname' => '登录测试用户',
            'email'    => '',
            'avatar'   => '',
        ]]);

        return new LoginWechatController();
    }

    private function miniProgramRequest(): Request {
        return Request::create('/api/v1/user/weapp-login', 'GET', [
            'code'     => 'verified-code',
            'nickName' => '登录测试用户',
        ]);
    }

    private function createUser(string $name): User {
        $user = User::create([
            'name'        => $name . '-' . bin2hex(random_bytes(4)),
            'email'       => '',
            'invite_code' => '',
            'invited_by'  => 2,
            'password'    => '',
        ]);
        $this->createdUserIds[] = $user->id;

        return $user;
    }

    private function sessionTransactionIsolation(): string {
        return DB::selectOne('SELECT @@SESSION.transaction_isolation AS isolation_level')->isolation_level;
    }
}

class ImmediateMiniProgramAuthClient {
    public function __construct(
        private string $openId,
        private string $unionId
    ) {
    }

    public function session(string $code): array {
        return [
            'openid'      => $this->openId,
            'unionid'     => $this->unionId,
            'session_key' => 'test-session-key',
        ];
    }
}
