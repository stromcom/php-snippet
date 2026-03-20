<?php
declare(strict_types=1);

namespace Stromcom\Snippet\Options;

use Stromcom\Snippet\Internal\Attribute\Docs;

abstract class SnippetOptions {

  /**
   * Returns the options as a key-value array.
   * Only properties that differ from their default value are included,
   * unless $withDocs is true (then nullable docs-annotated properties are included too).
   *
   * @return array<string, mixed>
   */
  public function getOptions(bool $withDocs = false): array {
    $result     = [];
    $Reflection = new \ReflectionClass($this);

    foreach ($Reflection->getProperties(\ReflectionProperty::IS_PRIVATE) as $Property) {
      $Docs         = self::resolvePropertyDocs($Property);
      $name         = $Property->getName();
      $currentValue = $this->resolvePropertyValue($Property);

      $includeByDefault = $currentValue !== $Property->getDefaultValue();
      $includeForDocs   = $withDocs && $currentValue === null && ($Docs?->isShowNullInDocs() ?? false);

      if ($includeByDefault || $includeForDocs) {
        $result[$name] = $currentValue;
      }
    }

    return $result;
  }

  /**
   * Returns full schema metadata for all properties — used for docs-mode code generation.
   *
   * @return array<string, array{default: mixed, required: bool, docs: ?string, example: null|string|bool, type: ?string, isShowNullInDocs: bool}>
   */
  public static function getOptionsWithDocs(): array {
    $result     = [];
    $Reflection = new \ReflectionClass(static::class);

    foreach ($Reflection->getProperties(\ReflectionProperty::IS_PRIVATE) as $Property) {
      $Docs = self::resolvePropertyDocs($Property);

      $result[$Property->getName()] = [
        'default'          => $Property->getDefaultValue(),
        'required'         => !$Property->hasDefaultValue(),
        'docs'             => $Docs?->getDocs(),
        'example'          => $Docs?->getExample(),
        'type'             => $Docs?->getType(),
        'isShowNullInDocs' => $Docs?->isShowNullInDocs() ?? false,
      ];
    }

    return $result;
  }

  private static function resolvePropertyDocs(\ReflectionProperty $Property): ?Docs {
    $attrs = $Property->getAttributes(Docs::class);
    return isset($attrs[0]) ? $attrs[0]->newInstance() : null;
  }

  /**
   * Resolution order: render{Name}() → get{Name}() → is{Name}() → direct value.
   * The render* prefix is used for properties whose value must be wrapped in JsValue.
   */
  private function resolvePropertyValue(\ReflectionProperty $Property): mixed {
    $name = $Property->getName();

    foreach (['render', 'get', 'is'] as $prefix) {
      $method = $prefix . $name;
      if (method_exists($this, $method)) {
        return $this->$method();
      }
    }

    return $Property->getValue($this);
  }

}
