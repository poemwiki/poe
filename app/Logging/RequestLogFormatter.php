<?php

namespace App\Logging;

use Monolog\Formatter\LineFormatter;

class RequestLogFormatter extends LineFormatter {
    public function format(array $record): string {
        $request = $record['context'];

        // Render directly so UA text such as %context% is never interpreted as a template.
        return sprintf("[%s] %s %s %d %s %s %s %s\n",
            $record['datetime']->format('c'),
            $this->quote($request['ip']),
            $this->quote($request['method'] . ' ' . $request['path']),
            $request['status'],
            $request['duration_ms'],
            $request['response_bytes'] ?? '-',
            $this->quote($request['ua']),
            $this->quote($request['referer_host'])
        );
    }

    private function quote(?string $value): string {
        return json_encode($value ?? '-', JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
