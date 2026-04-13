<?php
declare(strict_types=1);

namespace Stromcom\Snippet\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Stromcom\Snippet\Environment\CustomEnvironment;
use Stromcom\Snippet\Environment\Environment;
use Stromcom\Snippet\Exception\ConfGenerationException;
use Stromcom\Snippet\Exception\HomeGenerationException;
use Stromcom\Snippet\Exception\SnippetGenerationException;
use Stromcom\Snippet\Exception\ThreadGenerationException;
use Stromcom\Snippet\Exception\UserGenerationException;
use Stromcom\Snippet\Hashing\CodeHasherInterface;
use Stromcom\Snippet\Options\ConfOptions;
use Stromcom\Snippet\Options\ThreadOptions;
use Stromcom\Snippet\Options\UserOptions;
use Stromcom\Snippet\SnippetClient;
use Stromcom\Snippet\SnippetCode;

class SnippetClientTest extends TestCase {

  private SnippetClient $client;

  protected function setUp(): void {
    $this->client = new SnippetClient('test-key', 'test-secret', Environment::PRODUCTION);
  }

  #[Test]
  public function snippet_returns_snippet_code(): void {
    $this->assertInstanceOf(SnippetCode::class, $this->client->snippet());
  }

  #[Test]
  #[DataProvider('snippetContractTokens')]
  public function snippet_contains_required_bootstrap_tokens(string $expectedToken): void {
    $code = $this->client->snippet()->getCode();
    $this->assertStringContainsString($expectedToken, $code);
  }

  #[Test]
  public function snippet_uses_staging_url_for_staging_environment(): void {
    $client = new SnippetClient('key', 'secret', Environment::STAGING);
    $this->assertStringContainsString('cdn.staging.stromcom.cz', $client->snippet()->getCode());
  }

  #[Test]
  public function snippet_uses_custom_environment_url(): void {
    $client = new SnippetClient('key', 'secret', new CustomEnvironment('http://localhost:9000/loader.js'));
    $this->assertStringContainsString('localhost:9000', $client->snippet()->getCode());
  }

  #[Test]
  public function snippet_html_is_wrapped_in_script_tags(): void {
    $html = $this->client->snippet()->getHTML();
    $this->assertStringStartsWith('<script>', $html);
    $this->assertStringEndsWith('</script>', $html);
  }

  #[Test]
  public function conf_output_contains_conf_call(): void {
    $code = $this->client->conf(new ConfOptions())->getCode();
    $this->assertStringContainsString('stromCom.conf(', $code);
  }

  #[Test]
  public function conf_output_with_docs_contains_on_load(): void {
    $code = $this->client->conf(new ConfOptions(), true)->getCode();
    $this->assertStringContainsString('onLoad', $code);
  }

  #[Test]
  #[DataProvider('userOutputTokens')]
  public function user_output_contains_expected_tokens(string $expectedToken): void {
    $code = $this->client->user(new UserOptions('abc123'))->getCode();
    $this->assertStringContainsString($expectedToken, $code);
  }

  #[Test]
  #[DataProvider('threadOutputTokens')]
  public function thread_output_contains_expected_tokens(string $expectedToken): void {
    $code = $this->client->thread('#chat', new ThreadOptions('order-1'))->getCode();
    $this->assertStringContainsString($expectedToken, $code);
  }

  #[Test]
  #[DataProvider('homeOutputTokens')]
  public function home_output_contains_expected_tokens(string $expectedToken): void {
    $code = $this->client->home('#notifications')->getCode();
    $this->assertStringContainsString($expectedToken, $code);
  }

  #[Test]
  public function custom_data_layer_is_used_in_all_methods(): void {
    $client = new SnippetClient('key', 'secret', Environment::PRODUCTION, 'myLayer');

    $this->assertStringContainsString('myLayer', $client->snippet()->getCode());
    $this->assertStringContainsString('myLayer.conf(', $client->conf(new ConfOptions())->getCode());
    $this->assertStringContainsString('myLayer.initUser(', $client->user(new UserOptions('u1'))->getCode());
    $this->assertStringContainsString('myLayer.thread(', $client->thread('#el', new ThreadOptions('t1'))->getCode());
    $this->assertStringContainsString('myLayer.home(', $client->home('#el')->getCode());
  }

  /** @return array<string, array{0: string}> */
  public static function snippetContractTokens(): array {
    return [
      'client key' => ['test-key'],
      'client secret' => ['test-secret'],
      'loader url' => ['cdn.stromcom.cz/loader.js'],
    ];
  }

  /** @return array<string, array{0: string}> */
  public static function userOutputTokens(): array {
    return [
      'initUser call' => ['stromCom.initUser('],
      'user code' => ['abc123'],
    ];
  }

  /** @return array<string, array{0: string}> */
  public static function threadOutputTokens(): array {
    return [
      'thread call' => ['stromCom.thread('],
      'query selector' => ['#chat'],
      'thread code' => ['order-1'],
    ];
  }

  /** @return array<string, array{0: string}> */
  public static function homeOutputTokens(): array {
    return [
      'home call' => ['stromCom.home('],
      'query selector' => ['#notifications'],
    ];
  }
  /**
   * @param callable(self): void     $action
   * @param class-string<\Throwable> $expectedException
   */
  #[Test]
  #[DataProvider('invalidUtf8Cases')]
  public function methods_throw_specific_generation_exception_on_invalid_utf8(callable $action, string $expectedException): void {
    $this->expectException($expectedException);
    $action($this);
  }

  /** @return array<string, array{0: callable(self): void, 1: class-string<\Throwable>}> */
  public static function invalidUtf8Cases(): array {
    return [
      'snippet' => [
        static function (self $test): void {
          $client = new SnippetClient("\x80\x81invalid", 'secret', Environment::PRODUCTION);
          $client->snippet();
        },
        SnippetGenerationException::class,
      ],
      'conf' => [
        static function (self $test): void {
          $test->client->conf(new ConfOptions(pageCSSPath: "\x80\x81invalid"));
        },
        ConfGenerationException::class,
      ],
      'user' => [
        static function (self $test): void {
          $test->client->user(new UserOptions("\x80\x81invalid"));
        },
        UserGenerationException::class,
      ],
      'thread' => [
        static function (self $test): void {
          $test->client->thread('#chat', new ThreadOptions("\x80\x81invalid"));
        },
        ThreadGenerationException::class,
      ],
      'home' => [
        static function (self $test): void {
          $test->client->home("\x80\x81invalid");
        },
        HomeGenerationException::class,
      ],
    ];
  }

  #[Test]
  public function with_docs_enabled_globally_adds_docs_to_user(): void {
    $client = new SnippetClient('key', 'secret', Environment::PRODUCTION, null, true);
    $code   = $client->user(new UserOptions('u1'))->getCode();
    $this->assertStringContainsString('/**', $code);
  }

  #[Test]
  public function per_call_with_docs_overrides_global_setting(): void {
    $client = new SnippetClient('key', 'secret', Environment::PRODUCTION, null, false);
    $code   = $client->user(new UserOptions('u1'), true)->getCode();
    $this->assertStringContainsString('/**', $code);
  }

  #[Test]
  #[DataProvider('hashingScenarios')]
  public function code_is_replaced_by_hasher_output_in_supported_methods(callable $createOptions, callable $generateCode, string $rawCode, string $hashedCode): void {
    $hasher = $this->createStub(CodeHasherInterface::class);
    $hasher->method('hash')->willReturn($hashedCode);

    $client = new SnippetClient('key', 'secret', codeHasher: $hasher);
    $code   = $generateCode($client, $createOptions($rawCode));

    $this->assertStringContainsString($hashedCode, $code);
    $this->assertStringNotContainsString($rawCode, $code);
  }

  #[Test]
  public function hasher_receives_original_code(): void {
    $hasher = $this->createMock(CodeHasherInterface::class);
    $hasher->expects($this->once())
        ->method('hash')
        ->with('original-code')
        ->willReturn('x');

    $client = new SnippetClient('key', 'secret', codeHasher: $hasher);
    $client->user(new UserOptions('original-code'));
  }

  #[Test]
  #[DataProvider('plainCodeScenarios')]
  public function code_is_left_intact_when_hasher_is_missing(callable $createOptions, callable $generateCode): void {
    $rawCode = 'plain-code';
    $code    = $generateCode($this->client, $createOptions($rawCode));
    $this->assertStringContainsString($rawCode, $code);
  }

  /** @return array<string, array{0: callable(string): (UserOptions|ThreadOptions), 1: callable(SnippetClient, (UserOptions|ThreadOptions)): string, 2: string, 3: string}> */
  public static function hashingScenarios(): array {
    return [
      'user code' => [
        static fn(string $code): UserOptions => new UserOptions($code),
        static fn(SnippetClient $client, UserOptions|ThreadOptions $options): string => $client->user($options)->getCode(), // @phpstan-ignore argument.type
        'raw-id',
        'hashed-value',
      ],
      'thread code' => [
        static fn(string $code): ThreadOptions => new ThreadOptions($code),
        static fn(SnippetClient $client, UserOptions|ThreadOptions $options): string => $client->thread('#chat', $options)->getCode(), // @phpstan-ignore argument.type
        'raw-thread',
        'hashed-thread',
      ],
    ];
  }

  /** @return array<string, array{0: callable(string): (UserOptions|ThreadOptions), 1: callable(SnippetClient, (UserOptions|ThreadOptions)): string}> */
  public static function plainCodeScenarios(): array {
    return [
      'user code' => [
        static fn(string $code): UserOptions => new UserOptions($code),
        static fn(SnippetClient $client, UserOptions|ThreadOptions $options): string => $client->user($options)->getCode(), // @phpstan-ignore argument.type
      ],
      'thread code' => [
        static fn(string $code): ThreadOptions => new ThreadOptions($code),
        static fn(SnippetClient $client, UserOptions|ThreadOptions $options): string => $client->thread('#el', $options)->getCode(), // @phpstan-ignore argument.type
      ],
    ];
  }

  #[Test]
  public function original_options_are_not_mutated_by_hashing(): void {
    $hasher = $this->createStub(CodeHasherInterface::class);
    $hasher->method('hash')->willReturn('hashed');

    $client  = new SnippetClient('key', 'secret', codeHasher: $hasher);
    $options = new UserOptions('raw-id', 'Jane');

    $client->user($options);

    $this->assertSame('raw-id', $options->getCode());
    $this->assertSame('Jane', $options->getName());
  }

}
