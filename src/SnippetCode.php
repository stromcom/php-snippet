<?php
declare(strict_types=1);

namespace Stromcom\Snippet;

class SnippetCode {

  private string $tagName = 'script';

  public function __construct(private string $code) {
  }

  public function getCode(): string {
    return $this->code;
  }

  public function getHTML(): string {
    return "<{$this->tagName}>\n{$this->code}\n</{$this->tagName}>";
  }

}
