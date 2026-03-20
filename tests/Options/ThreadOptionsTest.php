<?php
declare(strict_types=1);

namespace Stromcom\Snippet\Tests\Options;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Stromcom\Snippet\Options\ThreadOptions;

class ThreadOptionsTest extends TestCase {

  #[Test]
  public function only_code_included_by_default(): void {
    $options = new ThreadOptions('order-123');
    $result  = $options->getOptions();

    $this->assertArrayHasKey('code', $result);
    $this->assertSame('order-123', $result['code']);
    $this->assertArrayNotHasKey('name', $result);
    $this->assertArrayNotHasKey('url', $result);
    $this->assertArrayNotHasKey('attributes', $result);
  }

  #[Test]
  public function user_hint_default_true_is_not_included(): void {
    $options = new ThreadOptions('t1');
    $this->assertArrayNotHasKey('userHint', $options->getOptions());
  }

  #[Test]
  public function user_hint_false_is_included(): void {
    $options = new ThreadOptions('t1', userHint: false);
    $result  = $options->getOptions();

    $this->assertArrayHasKey('userHint', $result);
    $this->assertFalse($result['userHint']);
  }

  #[Test]
  public function optional_fields_are_included_when_set(): void {
    $options = new ThreadOptions('t1', 'Order #42', 'https://example.com/order/42');
    $result  = $options->getOptions();

    $this->assertSame('Order #42', $result['name']);
    $this->assertSame('https://example.com/order/42', $result['url']);
  }

  #[Test]
  public function attributes_array_is_included_when_set(): void {
    $options = new ThreadOptions('t1', attributes: ['priority' => 'high']);
    $result  = $options->getOptions();

    $this->assertArrayHasKey('attributes', $result);
    $this->assertSame(['priority' => 'high'], $result['attributes']);
  }

  #[Test]
  public function get_options_with_docs_returns_metadata(): void {
    $schema = ThreadOptions::getOptionsWithDocs();

    $this->assertTrue($schema['code']['required']);
    $this->assertFalse($schema['name']['required']);
    $this->assertNotEmpty($schema['code']['docs']);
  }

}
