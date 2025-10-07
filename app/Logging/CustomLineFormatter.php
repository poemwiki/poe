<?php

namespace App\Logging;

use Monolog\Formatter\LineFormatter;
use Throwable;

/**
 * Custom Monolog formatter that removes stacktraces from exception logging.
 * Logs only: exception class, message, file, and line number.
 */
class CustomLineFormatter extends LineFormatter {
    /**
     * {@inheritdoc}
     */
    public function format(array $record): string {
        // If there's an exception in context, replace it with a clean summary
        if (isset($record['context']['exception']) && $record['context']['exception'] instanceof Throwable) {
            $e = $record['context']['exception'];

            // Try to locate the first application frame to improve usefulness
            $appFile = $e->getFile();
            $appLine = $e->getLine();
            foreach ($e->getTrace() as $frame) {
                if (!isset($frame['file'], $frame['line'])) {
                    continue;
                }
                $file = (string)$frame['file'];
                // Prefer frames within app/, routes/, or database/ of this project
                if (strpos($file, DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR) !== false
                    || strpos($file, DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR) !== false
                    || strpos($file, DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR) !== false) {
                    $appFile = $file;
                    $appLine = (int)$frame['line'];
                    break;
                }
            }

            // Replace the exception object with a clean array to prevent stacktrace serialization
            $record['context'] = [
                'exception_class' => get_class($e),
                'message'         => $e->getMessage(),
                'file'            => $appFile,
                'line'            => $appLine,
                'user_id'         => auth()->id() ?? null,
            ];
        }

        return parent::format($record);
    }
}
