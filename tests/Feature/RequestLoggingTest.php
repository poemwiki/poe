<?php

namespace Tests\Feature;

use Brackets\AdvancedLogger\Services\Benchmark;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class RequestLoggingTest extends TestCase {
    private string $logDirectory;

    protected function setUp(): void {
        parent::setUp();
        $this->logDirectory = sys_get_temp_dir() . '/poe-request-log-' . bin2hex(random_bytes(8));
        mkdir($this->logDirectory);
        config([
            'logging.default'                => 'single',
            'logging.channels.single.path'   => $this->logDirectory . '/application.log',
            'logging.channels.requests.path' => $this->logDirectory . '/request.log',
        ]);
    }

    protected function tearDown(): void {
        $this->app['log']->forgetChannel('requests');
        $this->app['log']->forgetChannel('single');
        File::deleteDirectory($this->logDirectory);
        parent::tearDown();
    }

    public function test_request_log_preserves_diagnostic_fields_in_one_json_record(): void {
        $request = Request::create('https://poemwiki.org/index.php/login?token=secret', 'POST', [
            'password' => 'private-password',
        ], [], [], [
            'REMOTE_ADDR'     => '203.0.113.7',
            'HTTP_USER_AGENT' => "Scanner/1.0 \"quoted\" | forged\r\nentry",
            'HTTP_REFERER'    => 'https://example.com/article?token=secret',
        ]);
        $this->app->instance('request', $request);
        Benchmark::start('application');
        $this->app['events']->dispatch(new RequestHandled($request, new Response('blocked', 429)));

        $files = glob($this->logDirectory . '/request-*.log');
        $this->assertCount(1, $files);
        $lines = file($files[0], FILE_IGNORE_NEW_LINES);
        $this->assertCount(1, $lines);
        $record = json_decode($lines[0], true, 512, JSON_THROW_ON_ERROR);
        $this->assertEqualsCanonicalizing([
            'time', 'ip', 'method', 'url', 'status', 'duration_ms', 'response_bytes', 'ua', 'referer_host',
        ], array_keys($record));
        $this->assertSame('203.0.113.7', $record['ip']);
        $this->assertSame('POST', $record['method']);
        $this->assertSame('https://poemwiki.org/index.php/login', $record['url']);
        $this->assertSame(429, $record['status']);
        $this->assertSame(7, $record['response_bytes']);
        $this->assertSame($request->userAgent(), $record['ua']);
        $this->assertSame('example.com', $record['referer_host']);
        $this->assertGreaterThanOrEqual(0, $record['duration_ms']);
        $this->assertNotFalse(strtotime($record['time']));
    }

    public function test_application_errors_keep_their_context_and_destination_after_a_request(): void {
        $this->app['log']->error('Import failed before request', ['operation' => 'poem-import']);
        $this->recordRequest();
        $this->app['log']->error('Import failed after request', ['operation' => 'author-import']);

        $errors = file($this->logDirectory . '/application.log', FILE_IGNORE_NEW_LINES);
        $this->assertCount(2, $errors);
        $this->assertStringContainsString('"operation":"poem-import"', $errors[0]);
        $this->assertStringContainsString('"operation":"author-import"', $errors[1]);
        $this->assertCount(1, file(glob($this->logDirectory . '/request-*.log')[0]));
    }

    public function test_rotation_keeps_the_requested_number_of_daily_files_without_touching_application_logs(): void {
        config(['logging.channels.requests.days' => 2]);
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $older     = date('Y-m-d', strtotime('-2 days'));
        file_put_contents($this->logDirectory . '/request-' . $yesterday . '.log', 'previous day');
        file_put_contents($this->logDirectory . '/request-' . $older . '.log', 'expired day');
        file_put_contents($this->logDirectory . '/application.log', 'keep errors');

        $this->recordRequest();

        $this->assertSame([
            $this->logDirectory . '/request-' . $yesterday . '.log',
            $this->logDirectory . '/request-' . date('Y-m-d') . '.log',
        ], glob($this->logDirectory . '/request-*.log'));
        $this->assertSame('previous day', file_get_contents($this->logDirectory . '/request-' . $yesterday . '.log'));
        $this->assertSame('keep errors', file_get_contents($this->logDirectory . '/application.log'));
    }

    public function test_streamed_responses_are_logged_without_consuming_the_stream(): void {
        $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function () {
            $this->fail('Logging must not execute the response stream.');
        });
        $this->recordRequest($response);

        $record = json_decode(file_get_contents(glob($this->logDirectory . '/request-*.log')[0]), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNull($record['response_bytes']);
        $this->assertSame(200, $record['status']);
    }

    /** @dataProvider disabledRequests */
    public function test_disabled_or_excluded_requests_do_not_create_a_log(array $settings): void {
        config($settings);
        $this->recordRequest();
        $this->assertSame([], glob($this->logDirectory . '/request-*.log'));
    }

    public function disabledRequests(): array {
        return [
            'disabled' => [['advanced-logger.request.enabled' => false]],
            'excluded' => [['advanced-logger.request.excluded-paths' => ['health']]],
        ];
    }

    private function recordRequest(?Response $response = null): void {
        $request = Request::create('https://poemwiki.org/health');
        $this->app->instance('request', $request);
        Benchmark::start('application');
        $this->app['events']->dispatch(new RequestHandled($request, $response ?? new Response('ok')));
    }
}
