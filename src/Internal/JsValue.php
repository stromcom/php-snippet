<?php
declare(strict_types=1);

namespace Stromcom\Snippet\Internal;

/**
 * @internal
 * Wraps a raw JavaScript expression so it is not JSON-encoded as a string.
 * The value is substituted back after JSON serialization via placeholder replacement.
 */
class JsValue implements \JsonSerializable {

  private const PLACEHOLDER_PREFIX = '__JS_VALUE_';
  private const PLACEHOLDER_SUFFIX = '__';

  private string $value;
  private string $placeholder;

  public function __construct(string $value) {
    $this->value = $value;
    $this->placeholder = self::PLACEHOLDER_PREFIX . bin2hex(random_bytes(8)) . self::PLACEHOLDER_SUFFIX;
  }

  /**
   * Wraps the expression in a Promise that resolves after DOMContentLoaded.
   * Use this for DOM-dependent callbacks (e.g. querySelector results).
   */
  public static function createDOMContentLoaded(string $value): self {
    $template = /** @lang JavaScript */
        "new Promise((resolve) => {
        document.addEventListener('DOMContentLoaded', () => { resolve(%s); });
      })";

    return new self(sprintf($template, $value));
  }

  public function getValue(): string {
    return $this->value;
  }

  public function getPlaceholder(): string {
    return $this->placeholder;
  }

  public function jsonSerialize(): mixed {
    return $this->placeholder;
  }

}
