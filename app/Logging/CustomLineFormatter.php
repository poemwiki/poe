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

            // Replace the exception object with a clean array to prevent stacktrace serialization
            $record['context'] = [
                'exception_class' => get_class($e),
                'message'         => $e->getMessage(),
                'file'            => $e->getFile(),
                'line'            => $e->getLine(),
                'user_id'         => auth()->id() ?? null,
            ];
        }

        return parent::format($record);
    }
}
