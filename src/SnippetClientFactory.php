<?php
declare(strict_types=1);

namespace Stromcom\Snippet;

use Stromcom\Snippet\Environment\Environment;
use Stromcom\Snippet\Environment\EnvironmentInterface;
use Stromcom\Snippet\Hashing\Base62CodeHasher;
use Stromcom\Snippet\Hashing\CodeHasherInterface;
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
        ? self::createHasher($codeHashSecret, $codeHashAlgo, $codeHashBase62)
        : null;

    return new SnippetClient($clientKey, $clientSecret, $environment, $dataLayer, $withDocs, $codeHasher);
  }

  /**
   * Creates a standalone hasher for generating safe user/thread codes to pass
   * to the frontend (e.g. via a JSON API endpoint or server-rendered variable).
   *
   * The secret never leaves the server — the browser only ever sees the hash.
   *
   * ```php
   * $hasher = SnippetClientFactory::createHasher('your-app-secret');
   *
   * // Safely expose these to the frontend:
   * header('Content-Type: application/json');
   * echo json_encode([
   *     'userCode'   => $hasher->hash($currentUser->id),
   *     'threadCode' => $hasher->hash($order->id),
   * ]);
   * ```
   *
   * @param string        $codeHashSecret Application secret used as the HMAC key
   * @param HashAlgorithm $codeHashAlgo   Hash algorithm (default: SHA-256)
   * @param bool          $codeHashBase62 When true (default), encode the hex output as a shorter base-62 string
   */
  public static function createHasher(
    string $codeHashSecret,
    HashAlgorithm $codeHashAlgo = HashAlgorithm::SHA256,
    bool $codeHashBase62 = true,
  ): CodeHasherInterface {
    $hasher = new HmacCodeHasher($codeHashSecret, $codeHashAlgo);

    return $codeHashBase62 ? new Base62CodeHasher($hasher) : $hasher;
  }

}
