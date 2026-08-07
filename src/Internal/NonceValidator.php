<?php
declare(strict_types=1);

namespace Stromcom\Snippet\Internal;

use Stromcom\Snippet\Exception\CspException;

/**
 * @internal
 */
class NonceValidator {

  /**
   * A CSP nonce is a base64 value; the URL-safe alphabet is accepted as well,
   * because random_bytes() output is commonly encoded with strtr() or base64url.
   */
  private const NONCE_PATTERN = '~^[A-Za-z0-9+/=_-]+$~';

  /**
   * Returns the nonce unchanged, or null when no nonce is used.
   *
   * @throws CspException when the nonce is an empty string or contains characters outside the base64 alphabet
   */
  public static function validate(?string $nonce): ?string {
    if ($nonce === null) {
      return null;
    }

    if (preg_match(self::NONCE_PATTERN, $nonce) !== 1) {
      throw new CspException(sprintf(
        'Invalid CSP nonce "%s". The nonce must be a non-empty base64 value using only [A-Za-z0-9+/=_-].',
        $nonce,
      ));
    }

    return $nonce;
  }

}
