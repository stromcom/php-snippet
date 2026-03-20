<?php
declare(strict_types=1);

namespace Stromcom\Snippet\Tests\Helper;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Stromcom\Snippet\Helper\AvatarStyle;

class AvatarStyleTest extends TestCase {

  #[Test]
  #[TestWith([AvatarStyle::ROBOHASH,  'robohash'])]
  #[TestWith([AvatarStyle::IDENTICON, 'identicon'])]
  #[TestWith([AvatarStyle::MONSTERID, 'monsterid'])]
  #[TestWith([AvatarStyle::WAVATAR,   'wavatar'])]
  #[TestWith([AvatarStyle::RETRO,     'retro'])]
  public function generate_url_contains_correct_style(AvatarStyle $style, string $expected): void {
    $url = $style->generateUrl('user@example.com');
    $this->assertStringContainsString($expected, $url);
  }

  #[Test]
  public function generate_url_from_email_produces_gravatar_url(): void {
    $url = AvatarStyle::ROBOHASH->generateUrl('User@Example.COM');
    $this->assertStringStartsWith('https://gravatar.com/avatar/', $url);
  }

  #[Test]
  public function generate_url_normalises_email_case(): void {
    $url1 = AvatarStyle::ROBOHASH->generateUrl('user@example.com');
    $url2 = AvatarStyle::ROBOHASH->generateUrl('USER@EXAMPLE.COM');
    $this->assertSame($url1, $url2);
  }

  #[Test]
  public function generate_url_accepts_precomputed_md5_hash(): void {
    $hash = md5('user@example.com');
    $url  = AvatarStyle::ROBOHASH->generateUrl($hash);
    $this->assertStringContainsString($hash, $url);
  }

  #[Test]
  public function example_url_is_deterministic(): void {
    $url1 = AvatarStyle::ROBOHASH->exampleUrl();
    $url2 = AvatarStyle::ROBOHASH->exampleUrl();
    $this->assertSame($url1, $url2);
  }

  #[Test]
  public function example_urls_differ_between_styles(): void {
    $urls = array_map(fn(AvatarStyle $s) => $s->exampleUrl(), AvatarStyle::cases());
    $this->assertCount(count(AvatarStyle::cases()), array_unique($urls));
  }

  #[Test]
  public function default_returns_robohash(): void {
    $this->assertSame(AvatarStyle::ROBOHASH, AvatarStyle::default());
  }

}
