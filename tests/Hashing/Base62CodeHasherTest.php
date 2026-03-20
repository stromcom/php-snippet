<?php
declare(strict_types=1);

namespace Stromcom\Snippet\Tests\Hashing;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Stromcom\Snippet\Exception\CodeHasherException;
use Stromcom\Snippet\Hashing\Base62CodeHasher;
use Stromcom\Snippet\Hashing\CodeHasherInterface;
use Stromcom\Snippet\Hashing\HashAlgorithm;
use Stromcom\Snippet\Hashing\HmacCodeHasher;

class Base62CodeHasherTest extends TestCase {

  #[Test]
  public function output_contains_only_base62_characters(): void {
    $hasher = new Base62CodeHasher(new HmacCodeHasher('secret'));
    $result = $hasher->hash('user-42');

    $this->assertMatchesRegularExpression('/^[0-9A-Za-z]+$/', $result);
  }

  #[Test]
  public function output_is_shorter_than_hex(): void {
    $inner  = new HmacCodeHasher('secret');
    $hex    = $inner->hash('user-42');
    $base62 = (new Base62CodeHasher($inner))->hash('user-42');

    $this->assertLessThan(strlen($hex), strlen($base62));
  }

  #[Test]
  public function output_is_deterministic(): void {
    $hasher = new Base62CodeHasher(new HmacCodeHasher('secret'));

    $this->assertSame($hasher->hash('same-input'), $hasher->hash('same-input'));
  }

  #[Test]
  public function different_inputs_produce_different_outputs(): void {
    $hasher = new Base62CodeHasher(new HmacCodeHasher('secret'));

    $this->assertNotSame($hasher->hash('input-a'), $hasher->hash('input-b'));
  }

  #[Test]
  public function delegates_to_inner_hasher(): void {
    $inner = $this->createMock(CodeHasherInterface::class);
    $inner->expects($this->once())
        ->method('hash')
        ->with('the-code')
        ->willReturn('ab12'); // short hex for simplicity

    $hasher = new Base62CodeHasher($inner);
    $result = $hasher->hash('the-code');

    $this->assertMatchesRegularExpression('/^[0-9A-Za-z]+$/', $result);
  }

  #[Test]
  #[TestWith(['00', '0'])]
  #[TestWith(['0a', 'A'])]
  #[TestWith(['ff', '47'])]
  #[TestWith(['10', 'G'])]
  #[TestWith(['00ff', '047'])]
  public function known_hex_conversions(string $hex, string $expectedBase62): void {
    $inner = $this->createStub(CodeHasherInterface::class);
    $inner->method('hash')->willReturn($hex);

    $hasher = new Base62CodeHasher($inner);

    $this->assertSame($expectedBase62, $hasher->hash('anything'));
  }

  #[Test]
  public function works_with_sha1_algorithm(): void {
    $hasher = new Base62CodeHasher(new HmacCodeHasher('secret', HashAlgorithm::SHA1));
    $result = $hasher->hash('test');

    $this->assertMatchesRegularExpression('/^[0-9A-Za-z]+$/', $result);
    // SHA-1 hex = 40 chars → base62 ≈ 27 chars
    $this->assertLessThanOrEqual(40, strlen($result));
  }

  #[Test]
  public function works_with_sha256_algorithm(): void {
    $hasher = new Base62CodeHasher(new HmacCodeHasher('secret', HashAlgorithm::SHA256));
    $result = $hasher->hash('test');

    $this->assertMatchesRegularExpression('/^[0-9A-Za-z]+$/', $result);
    // SHA-256 hex = 64 chars → base62 ≈ 43 chars
    $this->assertLessThanOrEqual(64, strlen($result));
  }

  #[Test]
  public function throws_code_hasher_exception_on_invalid_hex(): void {
    $inner = $this->createStub(CodeHasherInterface::class);
    $inner->method('hash')->willReturn('zzzz');

    $hasher = new Base62CodeHasher($inner);

    $this->expectException(CodeHasherException::class);
    $hasher->hash('anything');
  }

}
