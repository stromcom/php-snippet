<?php
declare(strict_types=1);

namespace Stromcom\Snippet\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Stromcom\Snippet\Environment\CustomEnvironment;
use Stromcom\Snippet\Exception\OptionsException;
use Stromcom\Snippet\Options\ConfOptions;
use Stromcom\Snippet\Options\ThreadOptions;
use Stromcom\Snippet\Options\UserOptions;
use Stromcom\Snippet\SnippetClient;
use Stromcom\Snippet\SnippetCode;

class ScriptBreakoutEscapingTest extends TestCase {

  private const FORBIDDEN = ['</script', '<!--', '<script'];

  private SnippetClient $client;

  protected function setUp(): void {
    $this->client = new SnippetClient('key', 'secret');
  }

  /**
   * @return array<string, array{string}>
   */
  public static function maliciousValues(): array {
    return [
      'closing script tag' => ['</script><b>x</b>'],
      'html comment'       => ['<!--<script>'],
      'opening script tag' => ['<script>alert(1)</script>'],
      'mixed case'         => ['</SCRIPT & <!-- x -->'],
    ];
  }

  #[Test]
  #[DataProvider('maliciousValues')]
  public function user_values_are_escaped_and_decode_unchanged(string $value): void {
    $code = $this->client->user(new UserOptions('u1', name: $value, emailAddress: $value, avatarURL: $value));

    $this->assertNoBreakout($code);
    $decoded = $this->decodeCall($code->getCode(), 'initUser');
    $this->assertSame($value, $decoded['name']);
    $this->assertSame($value, $decoded['emailAddress']);
    $this->assertSame($value, $decoded['avatarURL']);
  }

  #[Test]
  #[DataProvider('maliciousValues')]
  public function thread_values_and_selector_are_escaped_and_decode_unchanged(string $value): void {
    $attributes = ['key' => $value, 'list' => [$value]];
    $code       = $this->client->thread($value, new ThreadOptions('t1', name: $value, url: $value, attributes: $attributes));

    $this->assertNoBreakout($code);
    $matches = $this->match('~\.thread\(document\.querySelector\((".*?")\), (\{.*\})\);~s', $code->getCode());
    $this->assertSame($value, json_decode($matches[1], flags: JSON_THROW_ON_ERROR));

    $decoded = json_decode($matches[2], true, flags: JSON_THROW_ON_ERROR);
    $this->assertSame($value, $decoded['name']);
    $this->assertSame($value, $decoded['url']);
    $this->assertSame($attributes, $decoded['attributes']);
  }

  #[Test]
  #[DataProvider('maliciousValues')]
  public function home_selector_is_escaped_and_decodes_unchanged(string $value): void {
    $code = $this->client->home($value);

    $this->assertNoBreakout($code);
    $matches = $this->match('~\.home\(document\.querySelector\((".*")\)\);~s', $code->getCode());
    $this->assertSame($value, json_decode($matches[1], flags: JSON_THROW_ON_ERROR));
  }

  #[Test]
  #[DataProvider('maliciousValues')]
  public function conf_values_are_escaped_and_decode_unchanged(string $value): void {
    $code = $this->client->conf(new ConfOptions(theme: $value, appCSSPath: [$value]));

    $this->assertNoBreakout($code);
    $decoded = $this->decodeCall($code->getCode(), 'conf');
    $this->assertSame($value, $decoded['theme']);
    $this->assertSame([$value], $decoded['appCSSPath']);
  }

  #[Test]
  #[DataProvider('maliciousValues')]
  public function snippet_credentials_and_loader_url_are_escaped(string $value): void {
    $client = new SnippetClient($value, $value, new CustomEnvironment('https://example.com/' . $value));

    $this->assertNoBreakout($client->snippet($value));
  }

  #[Test]
  #[DataProvider('maliciousValues')]
  public function annotated_output_is_escaped(string $value): void {
    $client = new SnippetClient('key', 'secret', withDocs: true);

    $this->assertNoBreakout($client->user(new UserOptions('u1', name: $value)));
    $this->assertNoBreakout($client->thread($value, new ThreadOptions('t1', name: $value)));
  }

  #[Test]
  public function data_layer_must_be_an_identifier(): void {
    $this->expectException(OptionsException::class);

    new SnippetClient('key', 'secret', dataLayer: '</script><script>x');
  }

  #[Test]
  public function custom_identifier_data_layer_is_accepted(): void {
    $client = new SnippetClient('key', 'secret', dataLayer: '$my_Layer2');

    $this->assertStringStartsWith('$my_Layer2.home(', trim($client->home('#home')->getCode()));
  }

  private function assertNoBreakout(SnippetCode $code): void {
    foreach ([$code->getCode(), $code->getHTML()] as $output) {
      $inner = preg_replace('~^<script[^>]*>|</script>$~', '', $output);

      foreach (self::FORBIDDEN as $needle) {
        $this->assertStringNotContainsStringIgnoringCase($needle, (string) $inner);
      }
    }
  }

  /**
   * @return array<string, mixed>
   */
  private function decodeCall(string $code, string $method): array {
    $matches = $this->match('~^stromCom\.' . $method . '\((\{.*\})\);$~s', trim($code));

    return json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
  }

  /**
   * @return array<int, string>
   */
  private function match(string $pattern, string $subject): array {
    $this->assertSame(1, preg_match($pattern, $subject, $matches));

    return $matches;
  }

}
