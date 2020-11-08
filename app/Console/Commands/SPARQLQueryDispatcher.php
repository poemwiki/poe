<?php

namespace App\Console\Commands;


class SPARQLQueryDispatcher {
    private $endpointUrl;

    public function __construct(string $endpointUrl) {
        $this->endpointUrl = $endpointUrl;
    }

    public function query(string $sparqlQuery): array {
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    'Accept: application/sparql-results+json',
                    'User-Agent: WDQS-example PHP/' . PHP_VERSION, // TODO adjust this; see https://w.wiki/CX6
                ],
            ],
        ];
        $context = stream_context_create($opts);

        $url = $this->endpointUrl . '?query=' . urlencode($sparqlQuery);
        $response = file_get_contents($url, false, $context);
        return json_decode($response, true);
    }
}

