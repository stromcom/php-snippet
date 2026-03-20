<?php
declare(strict_types=1);

namespace Stromcom\Snippet\Environment;

/**
 * Use this when you received a custom loader URL (e.g. for testing or dedicated instances).
 *
 * Example:
 *   new CustomEnvironment('http://localhost:8082/loader.js')
 *   new CustomEnvironment('https://cdn.your-custom-domain.com/loader.js')
 */
class CustomEnvironment implements EnvironmentInterface {

  public function __construct(private string $loaderUrl) {
  }

  public function getLoaderUrl(): string {
    return $this->loaderUrl;
  }

}
