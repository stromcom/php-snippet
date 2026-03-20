<?php
declare(strict_types=1);

namespace Stromcom\Snippet\Options;

use Stromcom\Snippet\Internal\Attribute\Docs;

class UserOptions extends SnippetOptions {

  #[Docs('Unique user code. Cannot be changed later. Max length 100. Allowed characters: [a-zA-Z0-9-_]', '35ba77a78f4465679c473747e39c43c9')]
  private string $code;

  #[Docs('User display name', 'John Doe')]
  private ?string $name = null;

  #[Docs('Email address used for notifications', 'john.doe@example.com')]
  private ?string $emailAddress = null;

  #[Docs('Read-only access — user can read messages but cannot send them', false)]
  private bool $readOnly = false;

  #[Docs('Avatar URL', 'https://gravatar.com/avatar/0e1589ede88a691bb8f356bcaf67c414?s=50&r=g&s=100&d=robohash')]
  private ?string $avatarURL = null;

  public function __construct(
    string $code,
    ?string $name = null,
    ?string $emailAddress = null,
    ?bool $readOnly = null,
    ?string $avatarURL = null,
  ) {
    $this->code         = $code;
    $this->name         = $name;
    $this->emailAddress = $emailAddress;
    $this->readOnly     = $readOnly ?? $this->readOnly;
    $this->avatarURL    = $avatarURL;
  }

  public function getCode(): string {
    return $this->code;
  }

  public function getName(): ?string {
    return $this->name;
  }

  public function getEmailAddress(): ?string {
    return $this->emailAddress;
  }

  public function isReadOnly(): bool {
    return $this->readOnly;
  }

  public function getAvatarURL(): ?string {
    return $this->avatarURL;
  }

}
