<?php
declare(strict_types=1);

namespace Stromcom\Snippet;

use Stromcom\Snippet\Environment\Environment;
use Stromcom\Snippet\Environment\EnvironmentInterface;
use Stromcom\Snippet\Exception\ConfGenerationException;
use Stromcom\Snippet\Exception\CspException;
use Stromcom\Snippet\Exception\HomeGenerationException;
use Stromcom\Snippet\Exception\SnippetGenerationException;
use Stromcom\Snippet\Exception\ThreadGenerationException;
use Stromcom\Snippet\Exception\UserGenerationException;
use Stromcom\Snippet\Hashing\CodeHasherInterface;
use Stromcom\Snippet\Internal\Generator;
use Stromcom\Snippet\Internal\NonceValidator;
use Stromcom\Snippet\Options\ConfOptions;
use Stromcom\Snippet\Options\SnippetOptions;
use Stromcom\Snippet\Options\ThreadOptions;
use Stromcom\Snippet\Options\UserOptions;

class SnippetClient {

  private Generator $generator;

  /**
   * @param string                   $clientKey    Project client key
   * @param string                   $clientSecret Project bearer token / client secret
   * @param EnvironmentInterface     $environment  Target environment (default: production)
   * @param string|null              $dataLayer    Custom JS data-layer name (default: "stromCom")
   * @param bool                     $withDocs     Output annotated code with inline JSDoc comments
   * @param CodeHasherInterface|null $codeHasher   When set, `code` in UserOptions/ThreadOptions is automatically hashed via the given hasher
   * @param string|null              $nonce        CSP nonce of the page; every generated <script> tag is rendered with it
   *
   * @throws CspException when the nonce is not a valid base64 value
   */
  public function __construct(
    private string $clientKey,
    private string $clientSecret,
    private EnvironmentInterface $environment = Environment::PRODUCTION,
    ?string $dataLayer = null,
    bool $withDocs = false,
    private ?CodeHasherInterface $codeHasher = null,
    private ?string $nonce = null,
  ) {
    $this->nonce     = NonceValidator::validate($nonce);
    $this->generator = new Generator($dataLayer, $withDocs, $this->nonce);
  }

  /**
   * Content-Security-Policy the host page needs for the widget, pre-filled with this
   * client's environment and nonce.
   */
  public function csp(): CspPolicy {
    return new CspPolicy($this->environment, $this->nonce);
  }

  /**
   * Generates the async loader snippet that bootstraps the SDK.
   * Place this once on every page where you want the widget to appear.
   *
   * @param string|null $language UI language (e.g. "cs", "en", "sk"). Any code is accepted;
   *                              one without a translation falls back to English. Null (default)
   *                              lets the widget detect it from the browser.
   *                              This is the initial value only — `ConfOptions::$language`
   *                              overrides it and can be changed later at runtime.
   *
   * @throws SnippetGenerationException
   */
  public function snippet(?string $language = null): SnippetCode {
    return $this->generator->generateSnippet(
      $this->environment->getLoaderUrl(),
      $this->clientKey,
      $this->clientSecret,
      $language,
    );
  }

  /**
   * Configures the SDK — call once after snippet(), before any other method.
   * Pass $withDocs = true to emit an annotated version (useful in integration guides).
   *
   * @throws ConfGenerationException
   */
  public function conf(ConfOptions $options, ?bool $withDocs = null): SnippetCode {
    return $this->generator->generateConf($options, $withDocs);
  }

  /**
   * Identifies the currently logged-in user.
   * Call this on every page load where the user is known.
   *
   * @throws UserGenerationException
   */
  public function user(UserOptions $options, ?bool $withDocs = null): SnippetCode {
    return $this->generator->generateUser($this->hashOptionCode($options), $withDocs);
  }

  /**
   * Embeds a conversation thread into the given DOM element.
   *
   * @param string $querySelector CSS selector of the container element, e.g. "#support-chat"
   *
   * @throws ThreadGenerationException
   */
  public function thread(string $querySelector, ThreadOptions $options, ?bool $withDocs = null): SnippetCode {
    return $this->generator->generateThread($querySelector, $this->hashOptionCode($options), $withDocs);
  }

  /**
   * Embeds the notification center into the given DOM element.
   *
   * @param string $querySelector CSS selector of the container element, e.g. "#notifications"
   *
   * @throws HomeGenerationException
   */
  public function home(string $querySelector): SnippetCode {
    return $this->generator->generateHome($querySelector);
  }

  /**
   * If a {@see CodeHasherInterface} is configured, returns a clone of the given options
   * with the `code` property replaced by its hashed value. Otherwise returns the original.
   *
   * @template T of SnippetOptions
   *
   * @param T $options
   *
   * @return T
   */
  private function hashOptionCode(SnippetOptions $options): SnippetOptions {
    if ($this->codeHasher === null) {
      return $options;
    }

    $clone    = clone $options;
    $Property = new \ReflectionProperty($clone, 'code');
    $Property->setValue($clone, $this->codeHasher->hash($Property->getValue($options)));

    return $clone;
  }

}
