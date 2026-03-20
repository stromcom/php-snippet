<?php
declare(strict_types=1);

namespace Stromcom\Snippet\Environment;

enum Environment: string implements EnvironmentInterface {

  case PRODUCTION = 'https://cdn.stromcom.cz/loader.js';
  case STAGING    = 'https://cdn.staging.stromcom.cz/loader.js';

  public function getLoaderUrl(): string {
    return $this->value;
  }

}
