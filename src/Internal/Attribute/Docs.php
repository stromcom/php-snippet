<?php
declare(strict_types=1);

namespace Stromcom\Snippet\Internal\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Docs {

  private string $docs;

  /** @var string|bool|array<array-key, mixed>|null */
  private null|string|bool|array $exampleValue;

  private bool $showNullInDocs = true;

  private ?string $type;

  /** @param string|bool|array<array-key, mixed>|null $exampleValue */
  public function __construct(
    string $docs,
    null|string|bool|array $exampleValue = null,
    ?bool $showNullInDocs = null,
    ?string $type = null,
  ) {
    $this->docs = $docs;
    $this->exampleValue = $exampleValue;
    $this->type = $type;
    $this->showNullInDocs = $showNullInDocs ?? is_null($exampleValue);
  }

  public function getDocs(): string {
    return $this->docs;
  }

  public function getType(): ?string {
    return $this->type;
  }

  public function isShowNullInDocs(): bool {
    return $this->showNullInDocs;
  }

  public function getExample(): null|string|bool {
    if (is_array($this->exampleValue)) {
      return json_encode(
        $this->exampleValue,
        JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE,
      );
    }

    return $this->exampleValue;
  }

}
