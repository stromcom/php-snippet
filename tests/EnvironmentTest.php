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
  #[DataProvider('predefinedEnvironmentOrigins')]
  public function predefined_environment_knows_its_api_and_application_origin(
    Environment $environment,
    string $expectedApiUrl,
    string $expectedApplicationUrl,
  ): void {
    $this->assertSame($expectedApiUrl, $environment->getApiUrl());
    $this->assertSame($expectedApplicationUrl, $environment->getApplicationUrl());
  }

  #[Test]
  #[TestWith(['http://localhost:8082/loader.js'])]
  #[TestWith(['https://cdn.my-company.com/loader.js'])]
  #[TestWith(['https://cdn.testing.example.com/loader.js'])]
  public function custom_environment_returns_provided_url(string $url): void {
    $env = new CustomEnvironment($url);
    $this->assertSame($url, $env->getLoaderUrl());
  }

  #[Test]
  public function custom_environment_has_no_origins_unless_they_are_given(): void {
    $env = new CustomEnvironment('https://cdn.example.com/loader.js');

    $this->assertNull($env->getApiUrl());
    $this->assertNull($env->getApplicationUrl());
  }

  #[Test]
  public function custom_environment_returns_provided_origins(): void {
    $env = new CustomEnvironment('https://cdn.example.com/loader.js', 'https://example.com', 'https://chat.example.com');

    $this->assertSame('https://example.com', $env->getApiUrl());
    $this->assertSame('https://chat.example.com', $env->getApplicationUrl());
  }

  /** @return array<string, array{0: Environment, 1: string}> */
  public static function predefinedEnvironmentUrls(): array {
    return [
      'production' => [Environment::PRODUCTION, 'https://cdn.stromcom.cz/loader.js'],
      'staging' => [Environment::STAGING, 'https://cdn.staging.stromcom.cz/loader.js'],
    ];
  }

  /** @return array<string, array{0: Environment, 1: string, 2: string}> */
  public static function predefinedEnvironmentOrigins(): array {
    return [
      'production' => [Environment::PRODUCTION, 'https://www.stromcom.cz', 'https://app.stromcom.cz'],
      'staging' => [Environment::STAGING, 'https://staging.stromcom.cz', 'https://app.staging.stromcom.cz'],
    ];
  }

}
