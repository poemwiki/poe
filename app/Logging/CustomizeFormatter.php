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
     * @param \Illuminate\Log\Logger $logger Laravel's logger wrapper
     */
    public function __invoke($logger): void {
        // Get the underlying Monolog logger instance
        $monolog = $logger instanceof \Illuminate\Log\Logger
            ? $logger->getLogger()
            : $logger;

        foreach ($monolog->getHandlers() as $handler) {
            if (method_exists($handler, 'setFormatter')) {
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
