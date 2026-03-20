<?php
declare(strict_types=1);

namespace Stromcom\Snippet\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Stromcom\Snippet\Environment\CustomEnvironment;
use Stromcom\Snippet\Environment\Environment;

class EnvironmentTest extends TestCase {

  #[Test]
  #[DataProvider('predefinedEnvironmentUrls')]
  public function predefined_environment_returns_expected_loader_url(Environment $environment, string $expectedUrl): void {
    $this->assertSame($expectedUrl, $environment->getLoaderUrl());
  }

  #[Test]
  #[TestWith(['http://localhost:8082/loader.js'])]
  #[TestWith(['https://cdn.my-company.com/loader.js'])]
  #[TestWith(['https://cdn.testing.example.com/loader.js'])]
  public function custom_environment_returns_provided_url(string $url): void {
    $env = new CustomEnvironment($url);
    $this->assertSame($url, $env->getLoaderUrl());
  }

  /** @return array<string, array{0: Environment, 1: string}> */
  public static function predefinedEnvironmentUrls(): array {
    return [
      'production' => [Environment::PRODUCTION, 'https://cdn.stromcom.cz/loader.js'],
      'staging' => [Environment::STAGING, 'https://cdn.staging.stromcom.cz/loader.js'],
    ];
  }

}
