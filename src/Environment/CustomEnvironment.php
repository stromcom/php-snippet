<?php
declare(strict_types=1);

namespace Stromcom\Snippet\Environment;

/**
 * Use this when you received a custom loader URL (e.g. for testing or dedicated instances).
 *
 * Example:
 *   new CustomEnvironment('http://localhost:8082/loader.js')
 *   new CustomEnvironment('https://cdn.your-custom-domain.com/loader.js')
 *
 * The API and application origins are only needed to build a {@see \Stromcom\Snippet\CspPolicy};
 * pass them when you want the policy to be generated for this environment:
 *   new CustomEnvironment('https://cdn.example.com/loader.js', 'https://example.com', 'https://chat.example.com')
 */
class CustomEnvironment implements OriginAwareEnvironmentInterface {

  /**
   * @param string      $loaderUrl      URL of the loader script
   * @param string|null $apiUrl         Origin the widget polls for notifications
   * @param string|null $applicationUrl Origin the widget iframe is embedded from
   */
  public function __construct(
    private string $loaderUrl,
    private ?string $apiUrl = null,
    private ?string $applicationUrl = null,
  ) {
  }

  public function getLoaderUrl(): string {
    return $this->loaderUrl;
  }

  public function getApiUrl(): ?string {
    return $this->apiUrl;
  }

  public function getApplicationUrl(): ?string {
    return $this->applicationUrl;
  }

}
