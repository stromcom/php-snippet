<?php
declare(strict_types=1);

namespace Stromcom\Snippet\Tests\Options;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Stromcom\Snippet\Options\UserOptions;

class UserOptionsTest extends TestCase {

  #[Test]
  public function only_code_is_included_when_only_required_field_set(): void {
    $options = new UserOptions('user-abc');
    $result  = $options->getOptions();

    $this->assertArrayHasKey('code', $result);
    $this->assertSame('user-abc', $result['code']);
    $this->assertArrayNotHasKey('name', $result);
    $this->assertArrayNotHasKey('emailAddress', $result);
    $this->assertArrayNotHasKey('avatarURL', $result);
  }

  #[Test]
  public function readonly_default_false_is_not_included(): void {
    $options = new UserOptions('u1');
    $this->assertArrayNotHasKey('readOnly', $options->getOptions());
  }

  #[Test]
  public function readonly_true_is_included(): void {
    $options = new UserOptions('u1', readOnly: true);
    $result  = $options->getOptions();

    $this->assertArrayHasKey('readOnly', $result);
    $this->assertTrue($result['readOnly']);
  }

  #[Test]
  public function optional_fields_are_included_when_set(): void {
    $options = new UserOptions('u1', 'Jane', 'jane@example.com', false, 'https://example.com/avatar.png');
    $result  = $options->getOptions();

    $this->assertSame('Jane', $result['name']);
    $this->assertSame('jane@example.com', $result['emailAddress']);
    $this->assertSame('https://example.com/avatar.png', $result['avatarURL']);
  }

  #[Test]
  public function with_docs_does_not_include_fields_that_have_example_values_when_null(): void {
    // Fields annotated with a non-null example get showNullInDocs=false by default,
    // so they are excluded from docs output when their value is null.
    $options = new UserOptions('u1');
    $result  = $options->getOptions(true);

    $this->assertArrayNotHasKey('name', $result);
    $this->assertArrayNotHasKey('emailAddress', $result);
    $this->assertArrayNotHasKey('avatarURL', $result);
    $this->assertArrayHasKey('code', $result);
  }

  #[Test]
  public function get_options_with_docs_returns_metadata_for_all_properties(): void {
    $schema = UserOptions::getOptionsWithDocs();

    $this->assertArrayHasKey('code', $schema);
    $this->assertTrue($schema['code']['required']);
    $this->assertArrayHasKey('name', $schema);
    $this->assertFalse($schema['name']['required']);
    $this->assertArrayHasKey('readOnly', $schema);
  }

}
