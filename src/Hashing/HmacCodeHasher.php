<?php
declare(strict_types=1);

namespace Stromcom\Snippet\Hashing;

/**
 * Default {@see CodeHasherInterface} implementation using HMAC.
 *
 * Supported algorithms: sha256 (default), sha1 (fallback).
 *
 * @see HashAlgorithm
 */
class HmacCodeHasher implements CodeHasherInterface {

  /**
   * @param string        $secret Application secret used as the HMAC key
   * @param HashAlgorithm $algo   Hash algorithm (default: SHA-256)
   */
  public function __construct(
    private string $secret,
    private HashAlgorithm $algo = HashAlgorithm::SHA256,
  ) {
  }

  public function hash(string $code): string {
    return hash_hmac($this->algo->value, $code, $this->secret);
  }

}
