<?php

namespace App\Logging;

use Monolog\Logger;

/**
 * Custom Monolog logger configurator.
 * Applies CustomLineFormatter to remove stacktraces from logs.
 */
class CustomizeFormatter {
    /**
     * Customize the given logger instance.
     *
     * @param \Illuminate\Log\Logger|\Monolog\Logger $logger Laravel's logger wrapper or Monolog logger
     */
    public function __invoke($logger): void {
        // Get the underlying Monolog logger instance
        $monolog = $logger instanceof \Illuminate\Log\Logger
            ? $logger->getLogger()
            : $logger;

        // Ensure we have a Monolog logger
        if (!$monolog instanceof \Monolog\Logger) {
            return;
        }

        foreach ($monolog->getHandlers() as $handler) {
            if ($handler instanceof \Monolog\Handler\FormattableHandlerInterface) {
                $handler->setFormatter(new CustomLineFormatter(
                    null, // format
                    null, // dateFormat
                    true, // allowInlineLineBreaks
                    true  // ignoreEmptyContextAndExtra
                ));
            }
        }
    }
}
