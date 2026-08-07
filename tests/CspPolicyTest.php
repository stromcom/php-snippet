<?php
declare(strict_types=1);

namespace Stromcom\Snippet\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Stromcom\Snippet\CspPolicy;
use Stromcom\Snippet\Environment\CustomEnvironment;
use Stromcom\Snippet\Environment\Environment;
use Stromcom\Snippet\Exception\CspException;
use Stromcom\Snippet\Exception\EnvironmentException;

class CspPolicyTest extends TestCase {

  private const NONCE = 'r4nd0m+No/nce==';

  private CspPolicy $policy;

  protected function setUp(): void {
    $this->policy = new CspPolicy(Environment::PRODUCTION);
  }

  #[Test]
  public function production_directives_match_the_documented_policy(): void {
    $this->assertSame([
      'script-src'  => ['https://cdn.stromcom.cz'],
      'connect-src' => ['https://www.stromcom.cz'],
      'style-src'   => ['https://cdn.stromcom.cz'],
      'img-src'     => ['data:'],
      'frame-src'   => ['https://app.stromcom.cz'],
    ], $this->policy->getDirectives());
  }

  #[Test]
  public function staging_directives_use_the_staging_zone(): void {
    $policy = new CspPolicy(Environment::STAGING);

    $this->assertSame([
      'script-src'  => ['https://cdn.staging.stromcom.cz'],
      'connect-src' => ['https://staging.stromcom.cz'],
      'style-src'   => ['https://cdn.staging.stromcom.cz'],
      'img-src'     => ['data:'],
      'frame-src'   => ['https://app.staging.stromcom.cz'],
    ], $policy->getDirectives());
  }

  #[Test]
  #[DataProvider('customLoaderOrigins')]
  public function custom_environment_origins_are_derived_from_the_loader_url(
    string $loaderUrl,
    string $expectedCdnOrigin,
    string $expectedApiOrigin,
    string $expectedApplicationOrigin,
  ): void {
    $directives = (new CspPolicy(new CustomEnvironment($loaderUrl)))->getDirectives();

    $this->assertSame([$expectedCdnOrigin], $directives['script-src']);
    $this->assertSame([$expectedApiOrigin], $directives['connect-src']);
    $this->assertSame([$expectedApplicationOrigin], $directives['frame-src']);
  }

  #[Test]
  public function explicit_urls_override_the_derived_origins(): void {
    $policy = new CspPolicy(
      Environment::PRODUCTION,
      apiUrl: 'https://api.example.com/api/app/v1/',
      applicationUrl: 'https://widget.example.com',
    );
    $directives = $policy->getDirectives();

    $this->assertSame(['https://api.example.com'], $directives['connect-src']);
    $this->assertSame(['https://widget.example.com'], $directives['frame-src']);
    $this->assertSame(['https://cdn.stromcom.cz'], $directives['script-src']);
  }

  #[Test]
  public function nonce_is_added_to_script_src_only(): void {
    $directives = (new CspPolicy(Environment::PRODUCTION, self::NONCE))->getDirectives();

    $this->assertSame(['https://cdn.stromcom.cz', "'nonce-" . self::NONCE . "'"], $directives['script-src']);
    $this->assertSame(['https://cdn.stromcom.cz'], $directives['style-src']);
  }

  #[Test]
  public function header_name_is_the_standard_csp_header(): void {
    $this->assertSame('Content-Security-Policy', $this->policy->getHeaderName());
  }

  #[Test]
  public function header_value_joins_directives_with_semicolons(): void {
    $this->assertSame(
      'script-src https://cdn.stromcom.cz; connect-src https://www.stromcom.cz; '
      . 'style-src https://cdn.stromcom.cz; img-src data:; frame-src https://app.stromcom.cz',
      $this->policy->getHeaderValue(),
    );
  }

  #[Test]
  public function meta_tag_contains_the_escaped_header_value(): void {
    $metaTag = (new CspPolicy(Environment::PRODUCTION, self::NONCE))->getMetaTag();

    $this->assertStringStartsWith('<meta http-equiv="Content-Security-Policy" content="', $metaTag);
    $this->assertStringEndsWith('">', $metaTag);
    $this->assertStringContainsString('&#039;nonce-' . self::NONCE . '&#039;', $metaTag);
  }

  #[Test]
  public function policy_without_nonce_reports_no_nonce(): void {
    $this->assertNull($this->policy->getNonce());
    $this->assertStringNotContainsString('nonce', $this->policy->getHeaderValue());
  }

  #[Test]
  #[TestWith(["nonce with space"])]
  #[TestWith(['nonce"escape'])]
  #[TestWith([''])]
  public function invalid_nonce_is_rejected(string $nonce): void {
    $this->expectException(CspException::class);

    new CspPolicy(Environment::PRODUCTION, $nonce);
  }

  #[Test]
  #[TestWith(['loader.js'])]
  #[TestWith(['/loader.js'])]
  public function loader_url_without_an_origin_is_rejected(string $loaderUrl): void {
    $this->expectException(EnvironmentException::class);

    new CspPolicy(new CustomEnvironment($loaderUrl));
  }

  /** @return array<string, array{0: string, 1: string, 2: string, 3: string}> */
  public static function customLoaderOrigins(): array {
    return [
      'dedicated domain' => [
        'https://cdn.my-company.com/loader.js',
        'https://cdn.my-company.com',
        'https://www.my-company.com',
        'https://app.my-company.com',
      ],
      'nested zone' => [
        'https://cdn.testing.example.com/loader.js',
        'https://cdn.testing.example.com',
        'https://testing.example.com',
        'https://app.testing.example.com',
      ],
      'single label host keeps the loader origin' => [
        'http://localhost:8082/loader.js',
        'http://localhost:8082',
        'http://localhost:8082',
        'http://localhost:8082',
      ],
    ];
  }

}
