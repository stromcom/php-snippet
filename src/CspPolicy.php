<?php
declare(strict_types=1);

namespace Stromcom\Snippet;

use Stromcom\Snippet\Environment\Environment;
use Stromcom\Snippet\Environment\EnvironmentInterface;
use Stromcom\Snippet\Exception\CspException;
use Stromcom\Snippet\Exception\EnvironmentException;
use Stromcom\Snippet\Internal\NonceValidator;

/**
 * Content-Security-Policy directives the host page must allow for the widget to work.
 *
 * What the widget touches on the host page:
 *   - loads the loader and the snippet bundle from the CDN origin (`script-src`)
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
 * All origins are derived from the loader URL of the given environment, so a
 * {@see \Stromcom\Snippet\Environment\CustomEnvironment} works as well. The derivation
 * assumes the standard STROMCOM host layout — `cdn.<zone>` for static assets, `app.<zone>`
 * for the application, and `<zone>` (or `www.<zone>` for a bare registrable domain) for the
 * API. Pass `$apiUrl` / `$applicationUrl` explicitly for deployments that differ.
 */
class CspPolicy {

  public const HEADER_NAME = 'Content-Security-Policy';

  public const DIRECTIVE_SCRIPT_SRC  = 'script-src';
  public const DIRECTIVE_CONNECT_SRC = 'connect-src';
  public const DIRECTIVE_STYLE_SRC   = 'style-src';
  public const DIRECTIVE_IMG_SRC     = 'img-src';
  public const DIRECTIVE_FRAME_SRC   = 'frame-src';

  private const SOURCE_DATA_URI = 'data:';

  private const LABEL_CDN         = 'cdn';
  private const LABEL_APPLICATION = 'app';
  private const LABEL_CANONICAL   = 'www';

  private string $cdnOrigin;
  private string $apiOrigin;
  private string $applicationOrigin;
  private ?string $nonce;

  /**
   * @param EnvironmentInterface $environment    Target environment (default: production)
   * @param string|null          $nonce          CSP nonce of the page; when set it is added to `script-src`
   * @param string|null          $apiUrl         Overrides the derived API origin
   * @param string|null          $applicationUrl Overrides the derived application (iframe) origin
   *
   * @throws CspException         when the nonce is not a valid base64 value
   * @throws EnvironmentException when an URL cannot be reduced to an origin
   */
  public function __construct(
    EnvironmentInterface $environment = Environment::PRODUCTION,
    ?string $nonce = null,
    ?string $apiUrl = null,
    ?string $applicationUrl = null,
  ) {
    $loaderUrl = $environment->getLoaderUrl();

    $this->cdnOrigin         = self::toOrigin($loaderUrl);
    $this->apiOrigin         = $apiUrl === null ? self::deriveApiOrigin($loaderUrl) : self::toOrigin($apiUrl);
    $this->applicationOrigin = $applicationUrl === null ? self::deriveApplicationOrigin($loaderUrl) : self::toOrigin($applicationUrl);
    $this->nonce             = NonceValidator::validate($nonce);
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

  /**
   * The API lives on the site itself — `staging.stromcom.cz` for `cdn.staging.stromcom.cz`.
   * A bare registrable domain uses its canonical `www` host — `www.stromcom.cz` for `cdn.stromcom.cz`.
   *
   * @throws EnvironmentException
   */
  private static function deriveApiOrigin(string $loaderUrl): string {
    $url    = self::parseUrl($loaderUrl);
    $labels = self::zoneLabels($url['host']);

    if (count($labels) < 2) {
      return self::buildOrigin($url['scheme'], $url['host'], $url['port']);
    }

    $zone = implode('.', $labels);
    $host = count($labels) === 2 ? self::LABEL_CANONICAL . '.' . $zone : $zone;

    return self::buildOrigin($url['scheme'], $host, $url['port']);
  }

  /**
   * The application is embedded from the `app` subdomain of the zone —
   * `app.stromcom.cz` for `cdn.stromcom.cz`, `app.staging.stromcom.cz` for `cdn.staging.stromcom.cz`.
   *
   * @throws EnvironmentException
   */
  private static function deriveApplicationOrigin(string $loaderUrl): string {
    $url    = self::parseUrl($loaderUrl);
    $labels = self::zoneLabels($url['host']);

    if (count($labels) < 2) {
      return self::buildOrigin($url['scheme'], $url['host'], $url['port']);
    }

    return self::buildOrigin($url['scheme'], self::LABEL_APPLICATION . '.' . implode('.', $labels), $url['port']);
  }

  /**
   * Host labels of the zone the loader belongs to — the leading `cdn` label is dropped.
   *
   * @return list<string>
   */
  private static function zoneLabels(string $host): array {
    $labels = explode('.', $host);

    if (count($labels) > 1 && $labels[0] === self::LABEL_CDN) {
      array_shift($labels);
    }

    return $labels;
  }

  /**
   * @throws EnvironmentException
   */
  private static function toOrigin(string $url): string {
    $parsed = self::parseUrl($url);

    return self::buildOrigin($parsed['scheme'], $parsed['host'], $parsed['port']);
  }

  /**
   * @return array{scheme: string, host: string, port: int|null}
   *
   * @throws EnvironmentException
   */
  private static function parseUrl(string $url): array {
    $parsed = parse_url($url);

    if ($parsed === false || ($parsed['scheme'] ?? '') === '' || ($parsed['host'] ?? '') === '') {
      throw new EnvironmentException(sprintf(
        'Cannot derive a CSP source from "%s". An absolute URL including scheme and host is required.',
        $url,
      ));
    }

    return [
      'scheme' => (string) $parsed['scheme'],
      'host'   => (string) $parsed['host'],
      'port'   => $parsed['port'] ?? null,
    ];
  }

  private static function buildOrigin(string $scheme, string $host, ?int $port): string {
    return $port === null ? "{$scheme}://{$host}" : "{$scheme}://{$host}:{$port}";
  }

}
