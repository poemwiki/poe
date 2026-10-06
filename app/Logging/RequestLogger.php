<?php

namespace App\Logging;

use Brackets\AdvancedLogger\Services\Benchmark;
use Brackets\AdvancedLogger\Services\RequestLoggerService;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Symfony\Component\HttpFoundation\Response;

class RequestLogger extends RequestLoggerService {
    private LogManager $logs;

    public function __construct(LogManager $logs) {
        // The package constructor replaces handlers on the default application logger.
        $this->logs = $logs;
    }

    public function log(Request $request, Response $response): void {
        if (!config('advanced-logger.request.enabled')) {
            return;
        }

        $content = $response->getContent();
        $this->logs->channel('requests')->info('request', [
            'ip'          => $request->ip(),
            'method'      => $request->method(),
            'path'        => $request->getBaseUrl() . $request->getPathInfo(),
            'status'      => $response->getStatusCode(),
            'duration_ms' => round(Benchmark::duration(config('advanced-logger.request.benchmark')) * 1000, 3),
            // Application body size, before web-server compression; streamed bodies are unknown.
            'response_bytes' => is_string($content) ? strlen($content) : null,
            'ua'             => $request->userAgent(),
            'referer_host'   => parse_url((string) $request->headers->get('referer'), PHP_URL_HOST) ?: null,
        ]);
    }
}
