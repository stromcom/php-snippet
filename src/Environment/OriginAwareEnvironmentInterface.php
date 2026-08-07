<?php
declare(strict_types=1);

namespace Stromcom\Snippet\Environment;

/**
 * An environment that also knows the origins the widget talks to besides the loader.
 *
 * The loader URL alone does not identify them — they are separate hosts that cannot be
 * guessed reliably (the API may even sit behind a CDN distribution with an unrelated
 * hostname), so {@see \Stromcom\Snippet\CspPolicy} reads them from here instead.
 *
 * Both getters may return null when the environment does not know the origin; the caller
 * then has to be given the URL explicitly.
 */
interface OriginAwareEnvironmentInterface extends EnvironmentInterface {

  /**
   * Origin the widget polls for notifications, e.g. `https://www.stromcom.cz`.
   */
  public function getApiUrl(): ?string;

  /**
   * Origin the widget iframe is embedded from, e.g. `https://app.stromcom.cz`.
   */
  public function getApplicationUrl(): ?string;

}
