<?php
declare(strict_types=1);

namespace Stromcom\Snippet\Helper;

/**
 * Gravatar-based avatar URL helper.
 *
 * Usage:
 *   AvatarStyle::ROBOHASH->generateUrl('user@example.com')
 *   AvatarStyle::ROBOHASH->generateUrl('precomputed-md5-hash')
 */
enum AvatarStyle: string {

  case ROBOHASH  = 'robohash';
  case IDENTICON = 'identicon';
  case MONSTERID = 'monsterid';
  case WAVATAR   = 'wavatar';
  case RETRO     = 'retro';

  /**
   * Generate an avatar URL from an email address or a pre-computed MD5 hash.
   * If a 32-char hex string is passed it is used as-is; otherwise it is treated as an email.
   */
  public function generateUrl(string $emailOrHash): string {
    $hash = (mb_strlen($emailOrHash) === 32 && ctype_xdigit($emailOrHash))
        ? $emailOrHash
        : md5(mb_strtolower(mb_trim($emailOrHash)));

    return sprintf('https://gravatar.com/avatar/%s?s=100&r=g&d=%s', $hash, $this->value);
  }

  /** Returns a deterministic example URL for documentation or previews. */
  public function exampleUrl(): string {
    return $this->generateUrl(md5(self::class . $this->value));
  }

  public static function default(): self {
    return self::ROBOHASH;
  }

}
