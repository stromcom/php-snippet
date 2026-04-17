<?php
declare(strict_types=1);

namespace Stromcom\Snippet\Options;

use Stromcom\Snippet\Internal\Attribute\Docs;
use Stromcom\Snippet\Internal\JsValue;

class ConfOptions extends SnippetOptions {

  #[Docs('Custom function for rendering notifications', null, true, 'Function|null')]
  private ?string $notificationRenderer = null;

  #[Docs('Callback fired when a new message count changes', null, true, 'Function|null')]
  private ?string $onNotification = null;

  #[Docs('CSS file URL injected into the snippet iframe when loading the app', 'https://www.example.com/custom.css', true)]
  private ?string $pageCSSPath = null;

  #[Docs('Target element for the notification icon', null, true, 'Function|Promise|HTMLElement')]
  private ?string $notificationElementTargetElement = null;

  #[Docs('Always show the notification icon even when there are no new notifications', true, false, 'boolean')]
  private ?bool $notificationElementShowAlways = null;

  #[Docs('Notification icon position for the default renderer: 1=top-left, 2=top-right, 3=bottom-right, 4=bottom-left', '2', true, 'Number|null')]
  private ?int $notificationElementPosition = null;

  /** @var array<string, string|int|float|null>|null */
  #[Docs('Custom inline styles for the default notification button', ['zIndex' => 1000], true, 'Object|null')]
  private ?array $notificationElementStyles = null;

  #[Docs('CSS file URL used by the default notification renderer', 'https://www.example.com/notification.css', true)]
  private ?string $notificationElementCSSPath = null;

  #[Docs('Disable shadow-root for notifications. When enabled, no default CSS is injected.', true, true, 'boolean')]
  private bool $notificationElementNoShadowRoot = false;

  #[Docs('Callback invoked before the notification center opens (on notification icon click)', null, true, 'Function|null')]
  private ?string $homeBeforeRender = null;

  #[Docs('Callback invoked before the notification element is rendered', null, true, 'Function|null')]
  private ?string $notificationElementBeforeRender = null;

  #[Docs('Callback invoked after the notification element is rendered', null, true, 'Function|null')]
  private ?string $notificationElementAfterRender = null;

  #[Docs('Theme name applied to the host page via data-theme on <html>. Available: stromcom-default, stromcom-dark', 'stromcom-dark', true)]
  private ?string $theme = null;

  /** @param array<string, string|int|float|null>|null $notificationElementStyles */
  public function __construct(
    ?string $notificationRenderer = null,
    ?string $onNotification = null,
    ?string $pageCSSPath = null,
    ?string $notificationElementTargetElement = null,
    ?bool   $notificationElementShowAlways = null,
    ?int    $notificationElementPosition = null,
    ?array  $notificationElementStyles = null,
    ?string $notificationElementCSSPath = null,
    bool    $notificationElementNoShadowRoot = false,
    ?string $notificationElementBeforeRender = null,
    ?string $notificationElementAfterRender = null,
    ?string $homeBeforeRender = null,
    ?string $theme = null,
  ) {
    $this->notificationRenderer             = $notificationRenderer;
    $this->onNotification                   = $onNotification;
    $this->pageCSSPath                      = $pageCSSPath;
    $this->notificationElementTargetElement = $notificationElementTargetElement;
    $this->notificationElementShowAlways    = $notificationElementShowAlways;
    $this->notificationElementPosition      = $notificationElementPosition;
    $this->notificationElementStyles        = $notificationElementStyles;
    $this->notificationElementCSSPath       = $notificationElementCSSPath;
    $this->notificationElementNoShadowRoot  = $notificationElementNoShadowRoot;
    $this->notificationElementBeforeRender  = $notificationElementBeforeRender;
    $this->notificationElementAfterRender   = $notificationElementAfterRender;
    $this->homeBeforeRender                 = $homeBeforeRender;
    $this->theme                            = $theme;
  }

  public function getNotificationRenderer(): ?string {
    return $this->notificationRenderer;
  }

  public function renderNotificationRenderer(): ?JsValue {
    return $this->wrapJsValue($this->notificationRenderer);
  }

  public function getOnNotification(): ?string {
    return $this->onNotification;
  }

  public function renderOnNotification(): ?JsValue {
    return $this->wrapJsValue($this->onNotification);
  }

  public function getPageCSSPath(): ?string {
    return $this->pageCSSPath;
  }

  public function getNotificationElementTargetElement(): ?string {
    return $this->notificationElementTargetElement;
  }

  public function renderNotificationElementTargetElement(): ?JsValue {
    return $this->wrapJsValue($this->notificationElementTargetElement);
  }

  public function isNotificationElementShowAlways(): ?bool {
    return $this->notificationElementShowAlways;
  }

  public function getNotificationElementPosition(): ?int {
    return $this->notificationElementPosition;
  }

  /** @return array<string, string|int|float|null>|null */
  public function getNotificationElementStyles(): ?array {
    return $this->notificationElementStyles;
  }

  public function getNotificationElementCSSPath(): ?string {
    return $this->notificationElementCSSPath;
  }

  public function isNotificationElementNoShadowRoot(): bool {
    return $this->notificationElementNoShadowRoot;
  }

  public function getHomeBeforeRender(): ?string {
    return $this->homeBeforeRender;
  }

  public function renderHomeBeforeRender(): ?JsValue {
    return $this->wrapJsValue($this->homeBeforeRender);
  }

  public function getNotificationElementBeforeRender(): ?string {
    return $this->notificationElementBeforeRender;
  }

  public function renderNotificationElementBeforeRender(): ?JsValue {
    return $this->wrapJsValue($this->notificationElementBeforeRender);
  }

  public function getNotificationElementAfterRender(): ?string {
    return $this->notificationElementAfterRender;
  }

  public function renderNotificationElementAfterRender(): ?JsValue {
    return $this->wrapJsValue($this->notificationElementAfterRender);
  }

  public function getTheme(): ?string {
    return $this->theme;
  }

  private function wrapJsValue(?string $value): ?JsValue {
    return $value !== null ? JsValue::createDOMContentLoaded($value) : null;
  }

}
