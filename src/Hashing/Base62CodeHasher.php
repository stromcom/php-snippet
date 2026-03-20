<?php
declare(strict_types=1);

namespace Stromcom\Snippet\Hashing;

use Stromcom\Snippet\Exception\CodeHasherException;
use Tuupola\Base62 as Base62Encoder;

/**
 * Decorator that re-encodes the hex output of any {@see CodeHasherInterface}
 * into a shorter base-62 string (alphabet: `0-9 A-Z a-z`).
 *
 * The underlying hash value and cryptographic strength stay the same —
 * only the representation changes.
 *
 * Example (SHA-256, 64 hex chars → ~43 base-62 chars):
 * ```
 * $hasher = new Base62CodeHasher(new HmacCodeHasher('secret'));
 * $hasher->hash('user-42'); // e.g. "5Rz0K9qW7m…"
 * ```
 */
class Base62CodeHasher implements CodeHasherInterface {

  private Base62Encoder $encoder;

  public function __construct(
    private CodeHasherInterface $inner,
  ) {
    $this->encoder = new Base62Encoder();
  }

  /**
   * @throws CodeHasherException
   */
  public function hash(string $code): string {
    $hex = $this->inner->hash($code);
    $bin = @hex2bin($hex);

    if ($bin === false) {
      throw new CodeHasherException("Inner hasher returned an invalid hex string: '$hex'");
    }

    return $this->encoder->encode($bin);
  }

}
