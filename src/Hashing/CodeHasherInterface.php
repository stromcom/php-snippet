<?php
declare(strict_types=1);

namespace Stromcom\Snippet\Hashing;

/**
 * Converts a raw identifier (user ID, thread ID, …) into a hashed code
 * that is safe to expose on the client side.
 *
 * Implement this interface to provide a custom hashing strategy.
 * The default implementation is {@see HmacCodeHasher}.
 */
interface CodeHasherInterface {

  /**
   * Hash a raw code value.
   *
   * @param string $code The raw (unhashed) identifier
   *
   * @return string The hashed value (hex-encoded)
   */
  public function hash(string $code): string;

}
