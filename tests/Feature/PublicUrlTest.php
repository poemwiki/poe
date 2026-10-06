<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Tests\TestCase;

class PublicUrlTest extends TestCase {
    /** @dataProvider entryPaths */
    public function test_public_links_use_clean_paths_for_both_entry_urls(string $entry): void {
        $request = Request::create('https://poemwiki.org' . $entry, 'GET', [], [], [], [
            'SCRIPT_FILENAME' => '/var/www/public/index.php',
            'SCRIPT_NAME'     => '/index.php',
            'PHP_SELF'        => '/index.php/p/poem-id',
        ]);
        $this->app->instance('request', $request);
        $this->assertSame('https://poemwiki.org/p/poem-id', route('p/show', ['fakeId' => 'poem-id']));
        $this->assertSame('/p/poem-id', route('p/show', ['fakeId' => 'poem-id'], false));
        $this->assertSame('https://poemwiki.org/login?ref=%2Fp%2Fpoem-id', route('login', ['ref' => '/p/poem-id']));
        $this->assertSame('https://poemwiki.org/login', url('/login'));
        $this->assertSame('https://poemwiki.org/p/poem-id', canonicalUrl(route('p/show', ['fakeId' => 'poem-id'])));
        $this->assertSame('https://poemwiki.org/img/logo.svg', asset('img/logo.svg'));
        $this->assertSame($entry, $request->getBaseUrl() . $request->getPathInfo());
    }

    public function entryPaths(): array {
        return [
            'front controller' => ['/index.php/p/poem-id'],
            'clean URL'        => ['/p/poem-id'],
        ];
    }

    /** @dataProvider canonicalPaths */
    public function test_canonical_links_normalize_legacy_paths_without_changing_other_url_parts(string $input, string $expected): void {
        config(['app.canonical_domain' => 'poemwiki.org', 'app.url' => 'https://poemwiki.org']);
        $this->assertSame($expected, canonicalUrl($input));
    }

    public function canonicalPaths(): array {
        return [
            'absolute legacy URL'   => ['https://mirror.example/index.php/p/123?lang=en#text', 'https://poemwiki.org/p/123?lang=en#text'],
            'relative legacy URL'   => ['/index.php/compare/123,456', 'https://poemwiki.org/compare/123,456'],
            'front controller root' => ['/index.php', 'https://poemwiki.org/'],
            'ordinary path'         => ['/p/123', 'https://poemwiki.org/p/123'],
            'similar path'          => ['/index.php-not-a-script/123', 'https://poemwiki.org/index.php-not-a-script/123'],
        ];
    }
}
