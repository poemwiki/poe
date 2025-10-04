<?php

namespace App\Logging;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Monolog processor that injects authenticated user id and request id into log records.
 */
class UserRequestProcessor {
    public function __invoke(array $record): array {
        // Attach user id if available
        $record['extra']['user_id'] = Auth::id();

        if (function_exists('request')) {
            $req = request();
            if ($req) {
                // Ensure a request id exists for correlation
                $rid = $req->headers->get('X-Request-Id')
                    ?? $req->headers->get('X-Correlation-Id')
                    ?? $req->attributes->get('request_id');
                if (!$rid) {
                    $rid = (string) Str::uuid();
                    $req->attributes->set('request_id', $rid);
                }

                $record['extra']['request_id'] = $rid;
                $record['extra']['user_agent'] = $req->userAgent();
                $record['extra']['ip']         = $req->ip();
                $record['extra']['method']     = $req->method();
                $record['extra']['path']       = $req->path();

                // Add sanitized GET and POST payloads
                $record['extra']['get'] = $this->sanitizeArray($req->query->all());
                // Laravel merges JSON body into request()->all(); prefer raw POST params
                $post = $req->request->all();
                if (empty($post) && str_contains((string)$req->header('Content-Type'), 'application/json')) {
                    $json = json_decode($req->getContent(), true);
                    if (is_array($json)) {
                        $post = $json;
                    }
                }
                $record['extra']['post'] = $this->sanitizeArray($post);
            }
        }

        return $record;
    }

    private function sanitizeArray($data): array {
        if (!is_array($data)) {
            return [];
        }
        $keysToMask = [
            'password','password_confirmation','_token','token','access_token','refresh_token',
            'authorization','cookie','cookies','remember_token','secret','api_key','apikey',
        ];

        $sanitize = function ($value) use (&$sanitize, $keysToMask) {
            if (is_array($value)) {
                return array_map($sanitize, $value);
            }
            if (is_object($value)) {
                return '[object ' . get_class($value) . ']';
            }
            if (is_string($value)) {
                // truncate very long strings
                return mb_strimwidth($value, 0, 256, '…');
            }

            return $value;
        };

        $out = [];
        foreach ($data as $k => $v) {
            $lower = is_string($k) ? strtolower($k) : $k;
            if (is_string($lower) && in_array($lower, $keysToMask, true)) {
                $out[$k] = '***';
            } else {
                $out[$k] = $sanitize($v);
            }
        }

        return $out;
    }
}
