<?php
declare(strict_types=1);

namespace Stromcom\Snippet\Options;

use Stromcom\Snippet\Internal\Attribute\Docs;

class ThreadOptions extends SnippetOptions {

  #[Docs('Unique thread code. Cannot be changed later. Max length 100. Allowed characters: [a-zA-Z0-9-_]', 'dc127f5d2483352fd20eaddb38feb6d2')]
  private string $code;

  #[Docs('Thread display name', 'Invoice #12345')]
  private ?string $name = null;

  #[Docs('URL of the page where the thread is embedded. A link will appear in the thread header.', 'https://example.com/orders/12345')]
  private ?string $url = null;

  #[Docs('Enable or disable @mention suggestions for users', false)]
  private bool $userHint = true;

  /** @var array<array-key, mixed>|null */
  #[Docs('Thread attributes (experimental)', null, false)]
  private ?array $attributes = null;

  /** @param array<array-key, mixed>|null $attributes */
  public function __construct(
    string $code,
    ?string $name = null,
    ?string $url = null,
    bool $userHint = true,
    ?array $attributes = null,
  ) {
    $this->code       = $code;
    $this->name       = $name;
    $this->url        = $url;
    $this->userHint   = $userHint;
    $this->attributes = $attributes;
  }

  public function getCode(): string {
    return $this->code;
  }

  public function getName(): ?string {
    return $this->name;
  }

  public function getUrl(): ?string {
    return $this->url;
  }

  public function isUserHint(): bool {
    return $this->userHint;
  }

  /** @return array<array-key, mixed>|null */
  public function getAttributes(): ?array {
    return $this->attributes;
  }

}
