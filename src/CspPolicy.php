<?php
declare(strict_types=1);

namespace Stromcom\Snippet;

use Stromcom\Snippet\Environment\Environment;
use Stromcom\Snippet\Environment\EnvironmentInterface;
use Stromcom\Snippet\Environment\OriginAwareEnvironmentInterface;
use Stromcom\Snippet\Exception\CspException;
use Stromcom\Snippet\Exception\EnvironmentException;
use Stromcom\Snippet\Internal\NonceValidator;

/**
 * Content-Security-Policy directives the host page must allow for the widget to work.
 *
 * What the widget touches on the host page:
 *   - loads the loader and the widget bundle from the CDN origin (`script-src`)
 *   - loads the widget stylesheets from the same CDN origin (`style-src`)
 *   - polls the notification API on the site origin (`connect-src`)
 *   - renders inline SVG icons embedded as `data:` URIs (`img-src`)
 *   - embeds the application in an iframe (`frame-src`)
 *
 * Avatars, attachments, fonts and media are loaded inside that iframe and are therefore
 * governed by the policy of the application origin, not by the policy of the host page.
 *
 * ```php
 * $policy = new CspPolicy(Environment::PRODUCTION, $nonce);
 * header($policy->getHeaderName() . ': ' . $policy->getHeaderValue());
 * ```
 *
 * The CDN origin comes from the loader URL, which contains it. The API and application
 * origins are separate hosts and are never guessed — they are read from an
 * {@see OriginAwareEnvironmentInterface} or passed as `$apiUrl` / `$applicationUrl`.
 * When neither provides them, a {@see CspException} is thrown rather than a policy that
 * silently blocks the widget.
 */
class CspPolicy {

  public const HEADER_NAME = 'Content-Security-Policy';

  public const DIRECTIVE_SCRIPT_SRC  = 'script-src';
  public const DIRECTIVE_CONNECT_SRC = 'connect-src';
  public const DIRECTIVE_STYLE_SRC   = 'style-src';
  public const DIRECTIVE_IMG_SRC     = 'img-src';
  public const DIRECTIVE_FRAME_SRC   = 'frame-src';

  private const SOURCE_DATA_URI = 'data:';

  private string $cdnOrigin;
  private string $apiOrigin;
  private string $applicationOrigin;
  private ?string $nonce;

  /**
   * @param EnvironmentInterface $environment    Target environment (default: production)
   * @param string|null          $nonce          CSP nonce of the page; when set it is added to `script-src`
   * @param string|null          $apiUrl         API origin; required unless the environment provides it
   * @param string|null          $applicationUrl Application (iframe) origin; required unless the environment provides it
   *
   * @throws CspException         when the nonce is invalid or an origin is neither known nor given
   * @throws EnvironmentException when an URL cannot be reduced to an origin
   */
  public function __construct(
    EnvironmentInterface $environment = Environment::PRODUCTION,
    ?string $nonce = null,
    ?string $apiUrl = null,
    ?string $applicationUrl = null,
  ) {
    $this->cdnOrigin = self::toOrigin($environment->getLoaderUrl());

    $this->apiOrigin = self::toOrigin(self::resolveUrl(
      $apiUrl ?? self::apiUrlOf($environment),
      $environment,
      'apiUrl',
      self::DIRECTIVE_CONNECT_SRC,
    ));

    $this->applicationOrigin = self::toOrigin(self::resolveUrl(
      $applicationUrl ?? self::applicationUrlOf($environment),
      $environment,
      'applicationUrl',
      self::DIRECTIVE_FRAME_SRC,
    ));

    $this->nonce = NonceValidator::validate($nonce);
  }

  /**
   * Directive name => list of sources, so the integrator can merge them into an existing policy.
   *
   * @return array<string, list<string>>
   */
  public function getDirectives(): array {
    $scriptSources = [$this->cdnOrigin];

    if ($this->nonce !== null) {
      $scriptSources[] = "'nonce-{$this->nonce}'";
    }

    return [
      self::DIRECTIVE_SCRIPT_SRC  => $scriptSources,
      self::DIRECTIVE_CONNECT_SRC => [$this->apiOrigin],
      self::DIRECTIVE_STYLE_SRC   => [$this->cdnOrigin],
      self::DIRECTIVE_IMG_SRC     => [self::SOURCE_DATA_URI],
      self::DIRECTIVE_FRAME_SRC   => [$this->applicationOrigin],
    ];
  }

  public function getHeaderName(): string {
    return self::HEADER_NAME;
  }

  public function getHeaderValue(): string {
    $directives = [];

    foreach ($this->getDirectives() as $directive => $sources) {
      $directives[] = $directive . ' ' . implode(' ', $sources);
    }

    return implode('; ', $directives);
  }

  public function getMetaTag(): string {
    return sprintf(
      '<meta http-equiv="%s" content="%s">',
      self::HEADER_NAME,
      htmlspecialchars($this->getHeaderValue(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
    );
  }

  public function getNonce(): ?string {
    return $this->nonce;
  }

  private static function apiUrlOf(EnvironmentInterface $environment): ?string {
    return $environment instanceof OriginAwareEnvironmentInterface ? $environment->getApiUrl() : null;
  }

  private static function applicationUrlOf(EnvironmentInterface $environment): ?string {
    return $environment instanceof OriginAwareEnvironmentInterface ? $environment->getApplicationUrl() : null;
  }

  /**
   * @throws CspException
   */
  private static function resolveUrl(?string $url, EnvironmentInterface $environment, string $parameterName, string $directive): string {
    if ($url !== null) {
      return $url;
    }

    throw new CspException(sprintf(
      'Cannot build the "%s" directive: environment %s does not provide the URL. '
      . 'Pass it as the "%s" argument of %s, or make the environment implement %s. '
      . 'The origin is deliberately not guessed from the loader URL — a wrong guess would silently block the widget.',
      $directive,
      get_debug_type($environment),
      $parameterName,
      self::class,
      OriginAwareEnvironmentInterface::class,
    ));
  }

  /**
   * @throws EnvironmentException
   */
  private static function toOrigin(string $url): string {
    $parsed = parse_url($url);

    if ($parsed === false || ($parsed['scheme'] ?? '') === '' || ($parsed['host'] ?? '') === '') {
      throw new EnvironmentException(sprintf(
        'Cannot derive a CSP source from "%s". An absolute URL including scheme and host is required.',
        $url,
      ));
    }

    $origin = "{$parsed['scheme']}://{$parsed['host']}";

    return isset($parsed['port']) ? "{$origin}:{$parsed['port']}" : $origin;
  }

}
