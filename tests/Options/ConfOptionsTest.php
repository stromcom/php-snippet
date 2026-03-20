<?php
declare(strict_types=1);

namespace Stromcom\Snippet\Tests\Options;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Stromcom\Snippet\Internal\JsValue;
use Stromcom\Snippet\Options\ConfOptions;

class ConfOptionsTest extends TestCase {

  #[Test]
  public function empty_conf_options_returns_empty_array(): void {
    $this->assertSame([], (new ConfOptions())->getOptions());
  }

  #[Test]
  public function no_shadow_root_default_false_is_not_included(): void {
    $options = new ConfOptions();
    $this->assertArrayNotHasKey('notificationElementNoShadowRoot', $options->getOptions());
  }

  #[Test]
  public function no_shadow_root_true_is_included(): void {
    $options = new ConfOptions(notificationElementNoShadowRoot: true);
    $result  = $options->getOptions();

    $this->assertArrayHasKey('notificationElementNoShadowRoot', $result);
    $this->assertTrue($result['notificationElementNoShadowRoot']);
  }

  #[Test]
  public function notification_position_is_included_when_set(): void {
    $options = new ConfOptions(notificationElementPosition: 2);
    $result  = $options->getOptions();

    $this->assertArrayHasKey('notificationElementPosition', $result);
    $this->assertSame(2, $result['notificationElementPosition']);
  }

  #[Test]
  public function notification_renderer_string_is_wrapped_in_js_value(): void {
    $options = new ConfOptions(notificationRenderer: 'function(n) { console.log(n); }');
    $result  = $options->getOptions();

    $this->assertArrayHasKey('notificationRenderer', $result);
    $this->assertInstanceOf(JsValue::class, $result['notificationRenderer']);
  }

  #[Test]
  public function on_notification_null_renders_as_null(): void {
    $options = new ConfOptions();
    $this->assertNull($options->renderOnNotification());
  }

  #[Test]
  public function notification_styles_array_is_included(): void {
    $styles  = ['zIndex' => 9999, 'top' => '10px'];
    $options = new ConfOptions(notificationElementStyles: $styles);
    $result  = $options->getOptions();

    $this->assertSame($styles, $result['notificationElementStyles']);
  }

  #[Test]
  public function show_always_null_is_not_included_without_docs(): void {
    $options = new ConfOptions();
    $this->assertArrayNotHasKey('notificationElementShowAlways', $options->getOptions());
  }

  #[Test]
  public function with_docs_includes_fields_marked_show_null_in_docs(): void {
    $options = new ConfOptions();
    $result  = $options->getOptions(true);

    // notificationRenderer has showNullInDocs=true → included even when null
    $this->assertArrayHasKey('notificationRenderer', $result);
    $this->assertNull($result['notificationRenderer']);
    $this->assertArrayHasKey('pageCSSPath', $result);
  }

  #[Test]
  public function with_docs_excludes_fields_with_show_null_in_docs_false(): void {
    $options = new ConfOptions();
    $result  = $options->getOptions(true);

    // notificationElementShowAlways has explicit showNullInDocs=false → excluded when null
    $this->assertArrayNotHasKey('notificationElementShowAlways', $result);
  }

  #[Test]
  public function get_options_with_docs_returns_all_property_schemas(): void {
    $schema = ConfOptions::getOptionsWithDocs();

    $this->assertArrayHasKey('notificationRenderer', $schema);
    $this->assertArrayHasKey('pageCSSPath', $schema);
    $this->assertArrayHasKey('notificationElementPosition', $schema);
    $this->assertFalse($schema['notificationRenderer']['required']);
  }

}
