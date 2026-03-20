<?php
declare(strict_types=1);

namespace Stromcom\Snippet;

use Stromcom\Snippet\Environment\Environment;
use Stromcom\Snippet\Environment\EnvironmentInterface;
use Stromcom\Snippet\Hashing\Base62CodeHasher;
use Stromcom\Snippet\Hashing\HashAlgorithm;
use Stromcom\Snippet\Hashing\HmacCodeHasher;

/**
 * Convenience factory for building a {@see SnippetClient} without manually
 * instantiating the hasher.
 *
 * ```php
 * $client = SnippetClientFactory::create('key', 'secret', 'hash-secret');
 * ```
 */
class SnippetClientFactory {

  /**
   * @param string               $clientKey      Project client key
   * @param string               $clientSecret   Project bearer token / client secret
   * @param string|null          $codeHashSecret When set, an {@see HmacCodeHasher} is created automatically
   * @param HashAlgorithm        $codeHashAlgo   Hash algorithm (default: SHA-256)
   * @param bool                 $codeHashBase62 When true (default), the hex output is re-encoded to a shorter base-62 string (0-9 A-Z a-z)
   * @param EnvironmentInterface $environment    Target environment (default: production)
   * @param string|null          $dataLayer      Custom JS data-layer name (default: "stromCom")
   * @param bool                 $withDocs       Output annotated code with inline JSDoc comments
   */
  public static function create(
    string $clientKey,
    string $clientSecret,
    ?string $codeHashSecret = null,
    HashAlgorithm $codeHashAlgo = HashAlgorithm::SHA256,
    bool $codeHashBase62 = true,
    EnvironmentInterface $environment = Environment::PRODUCTION,
    ?string $dataLayer = null,
    bool $withDocs = false,
  ): SnippetClient {
    $codeHasher = $codeHashSecret !== null
        ? new HmacCodeHasher($codeHashSecret, $codeHashAlgo)
        : null;

    if ($codeHasher !== null && $codeHashBase62) {
      $codeHasher = new Base62CodeHasher($codeHasher);
    }

    return new SnippetClient($clientKey, $clientSecret, $environment, $dataLayer, $withDocs, $codeHasher);
  }

}
