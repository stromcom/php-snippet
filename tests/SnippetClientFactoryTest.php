<?php
declare(strict_types=1);

namespace Stromcom\Snippet\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Stromcom\Snippet\Environment\Environment;
use Stromcom\Snippet\Options\UserOptions;
use Stromcom\Snippet\SnippetClient;
use Stromcom\Snippet\SnippetClientFactory;

class SnippetClientFactoryTest extends TestCase {

  #[Test]
  public function create_returns_snippet_client(): void {
    $client = SnippetClientFactory::create('key', 'secret');

    $this->assertInstanceOf(SnippetClient::class, $client);
  }

  #[Test]
  #[TestWith([null, true])]
  #[TestWith(['app-secret', false])]
  public function create_hashes_code_only_when_hash_secret_is_provided(?string $codeHashSecret, bool $containsRawId): void {
    $client = SnippetClientFactory::create('key', 'secret', codeHashSecret: $codeHashSecret);
    $code   = $client->user(new UserOptions('raw-id'))->getCode();

    if ($containsRawId) {
      $this->assertStringContainsString('raw-id', $code);
      return;
    }

    $this->assertStringNotContainsString('raw-id', $code);
  }

  #[Test]
  public function create_passes_environment(): void {
    $client = SnippetClientFactory::create('key', 'secret', environment: Environment::STAGING);
    $code   = $client->snippet()->getCode();

    $this->assertStringContainsString('cdn.staging.stromcom.cz', $code);
  }

  #[Test]
  public function create_passes_data_layer(): void {
    $client = SnippetClientFactory::create('key', 'secret', dataLayer: 'myLayer');
    $code   = $client->snippet()->getCode();

    $this->assertStringContainsString('myLayer', $code);
  }

  #[Test]
  public function create_passes_with_docs_flag(): void {
    $client = SnippetClientFactory::create('key', 'secret', withDocs: true);
    $code   = $client->user(new UserOptions('u1'))->getCode();

    $this->assertStringContainsString('/**', $code);
  }

  #[Test]
  public function create_with_base62_produces_alphanumeric_code_by_default(): void {
    $client = SnippetClientFactory::create('key', 'secret', codeHashSecret: 'app-secret');
    $code   = $client->user(new UserOptions('raw-id'))->getCode();

    $this->assertStringNotContainsString('raw-id', $code);
    // Extract the code value from the JS output — it appears as "code":"<value>"
    $matched = preg_match('/"code"\s*:\s*"([^"]+)"/', $code, $matches);
    $this->assertSame(1, $matched);
    $this->assertMatchesRegularExpression('/^[0-9A-Za-z]+$/', $matches[1]);
  }

  #[Test]
  public function create_base62_is_shorter_than_hex(): void {
    $hexClient    = SnippetClientFactory::create('key', 'secret', codeHashSecret: 'app-secret', codeHashBase62: false);
    $base62Client = SnippetClientFactory::create('key', 'secret', codeHashSecret: 'app-secret');

    $hexMatched = preg_match('/"code"\s*:\s*"([^"]+)"/', $hexClient->user(new UserOptions('raw-id'))->getCode(), $hexMatch);
    $b62Matched = preg_match('/"code"\s*:\s*"([^"]+)"/', $base62Client->user(new UserOptions('raw-id'))->getCode(), $b62Match);

    $this->assertSame(1, $hexMatched);
    $this->assertSame(1, $b62Matched);
    $this->assertLessThan(strlen($hexMatch[1]), strlen($b62Match[1]));
  }

}
