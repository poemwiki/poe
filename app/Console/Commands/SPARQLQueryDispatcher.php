<?php

namespace App\Console\Commands;


use Illuminate\Support\Facades\Http;

class SPARQLQueryDispatcher {
    private $endpointUrl;

    public function __construct(string $endpointUrl = 'https://query.wikidata.org/sparql?query=') {
        $this->endpointUrl = $endpointUrl;
    }

    public function query(string $sparqlQuery): array {
        $options = config('app.env') === 'production' ? [] : [
            'http' => 'tcp://localhost:1087',
            'https' => 'tcp://localhost:1087'
        ];

        $response = Http::withOptions($options)->withHeaders([
            'Accept' => 'application/sparql-results+json',
            'User-Agent' => 'PoemWiki-bot/0.1 (https://poemwiki.org; poemwiki@126.com) PHP/' . PHP_VERSION, // TODO adjust this; see https://w.wiki/CX6
        ])->timeout(30)->retry(5, 10)->get('https://query.wikidata.org/sparql?query=' . urlencode($sparqlQuery));


        $url = $this->endpointUrl . urlencode($sparqlQuery);
        $body = (string)$response->getBody();
        return json_decode($body);
    }
}

