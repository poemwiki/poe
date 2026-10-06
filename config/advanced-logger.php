<?php

return [
    'request' => [
        'enabled'        => true,
        'excluded-paths' => [],
        // Benchmark state is local to the HTTP process, so write synchronously.
        'queue'     => null,
        'benchmark' => 'application',
    ],
];
