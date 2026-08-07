<?php
declare(strict_types=1);

namespace Stromcom\Snippet;

use Stromcom\Snippet\Exception\CspException;
use Stromcom\Snippet\Internal\NonceValidator;

class SnippetCode {

  private string $tagName = 'script';

  /**
   * @param string      $code  Raw JavaScript
   * @param string|null $nonce CSP nonce rendered as the `nonce` attribute of the generated <script> tag
   *
   * @throws CspException when the nonce is not a valid base64 value
   */
  public function __construct(private string $code, private ?string $nonce = null) {
    $this->nonce = NonceValidator::validate($nonce);
  }

  public function getCode(): string {
    return $this->code;
  }

  public function getNonce(): ?string {
    return $this->nonce;
  }

  public function getHTML(): string {
    return "<{$this->tagName}{$this->buildAttributes()}>\n{$this->code}\n</{$this->tagName}>";
  }

  private function buildAttributes(): string {
    if ($this->nonce === null) {
      return '';
    }

    return ' nonce="' . htmlspecialchars($this->nonce, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
  }

}
