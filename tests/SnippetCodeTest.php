<?php
declare(strict_types=1);

namespace Stromcom\Snippet\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Stromcom\Snippet\Exception\CspException;
use Stromcom\Snippet\SnippetCode;

class SnippetCodeTest extends TestCase {

  private const CODE  = 'stromCom.home(document.querySelector("#notifications"));';
  private const NONCE = 'r4nd0m+No/nce==';

  #[Test]
  public function html_without_nonce_renders_a_bare_script_tag(): void {
    $code = new SnippetCode(self::CODE);

    $this->assertSame("<script>\n" . self::CODE . "\n</script>", $code->getHTML());
    $this->assertNull($code->getNonce());
  }

  #[Test]
  public function html_with_nonce_renders_the_nonce_attribute(): void {
    $code = new SnippetCode(self::CODE, self::NONCE);

    $this->assertSame('<script nonce="' . self::NONCE . "\">\n" . self::CODE . "\n</script>", $code->getHTML());
    $this->assertSame(self::NONCE, $code->getNonce());
  }

  #[Test]
  public function raw_code_is_not_affected_by_the_nonce(): void {
    $this->assertSame(self::CODE, (new SnippetCode(self::CODE, self::NONCE))->getCode());
  }

  #[Test]
  #[TestWith([''])]
  #[TestWith(['nonce with space'])]
  #[TestWith(['"><script>alert(1)</script>'])]
  #[TestWith(["nonce'value"])]
  public function invalid_nonce_is_rejected(string $nonce): void {
    $this->expectException(CspException::class);

    new SnippetCode(self::CODE, $nonce);
  }

}
