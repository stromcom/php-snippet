<?php
declare(strict_types=1);

namespace Stromcom\Snippet\Hashing;

/**
 * Supported HMAC hash algorithms for {@see HmacCodeHasher}.
 */
enum HashAlgorithm: string {

  case SHA256 = 'sha256';
  case SHA1   = 'sha1';

}
