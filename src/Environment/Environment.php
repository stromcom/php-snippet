<?php
declare(strict_types=1);

namespace Stromcom\Snippet\Environment;

enum Environment: string implements OriginAwareEnvironmentInterface {

  case PRODUCTION = 'https://cdn.stromcom.cz/loader.js';
  case STAGING    = 'https://cdn.staging.stromcom.cz/loader.js';

  public function getLoaderUrl(): string {
    return $this->value;
  }

  public function getApiUrl(): string {
    return match ($this) {
      self::PRODUCTION => 'https://www.stromcom.cz',
      self::STAGING    => 'https://staging.stromcom.cz',
    };
  }

  public function getApplicationUrl(): string {
    return match ($this) {
      self::PRODUCTION => 'https://app.stromcom.cz',
      self::STAGING    => 'https://app.staging.stromcom.cz',
    };
  }

}
