<?php
declare(strict_types=1);

namespace Stromcom\Snippet\Internal;

use Stromcom\Snippet\Exception\ConfGenerationException;
use Stromcom\Snippet\Exception\HomeGenerationException;
use Stromcom\Snippet\Exception\JsonEncodingException;
use Stromcom\Snippet\Exception\SnippetGenerationException;
use Stromcom\Snippet\Exception\ThreadGenerationException;
use Stromcom\Snippet\Exception\UserGenerationException;
use Stromcom\Snippet\Options\ConfOptions;
use Stromcom\Snippet\Options\SnippetOptions;
use Stromcom\Snippet\Options\ThreadOptions;
use Stromcom\Snippet\Options\UserOptions;
use Stromcom\Snippet\SnippetCode;

/**
 * @internal
 */
class Generator {

  private string $dataLayer = 'stromCom';
  private int $initialSpace = 2;
  private bool $withDocs;

  public function __construct(?string $dataLayer = null, bool $withDocs = false) {
    $this->dataLayer = $dataLayer ?? $this->dataLayer;
    $this->withDocs = $withDocs;
  }

  public function generateSnippet(string $loaderUrl, string $clientKey, string $clientSecret): SnippetCode {
    try {
      $layer  = $this->jsonEncode($this->dataLayer);
      $url    = $this->jsonEncode($loaderUrl . '?');
      $key    = $this->jsonEncode($clientKey);
      $secret = $this->jsonEncode($clientSecret);

      return new SnippetCode(<<<JS
              (function(win, d, e, l, k, s) {
              var dl=l+'DL',c=function(s,n,a){n=n||s;(a?win[dl][n]=[]:null);win[l][s]=function(...p){a?win[dl][n].push(p):win[dl][n]=p;}},f=d.getElementsByTagName(e)[0],j=d.createElement(e);
              win[dl]=win[dl]||{};win[l]={};c('initUser','user');c('thread','threads',!0);c('conf');c('home',0,!0);
              ;j.async=true;j.dataset.type='stromcom';j.dataset.l=l;j.dataset.dl=dl;j.dataset.ck=k;j.dataset.cs=s;
              j.src = {$url}+k;f.parentNode.insertBefore(j,f);
              })(window, document, 'script', {$layer}, {$key}, {$secret});
            JS);
    } catch (JsonEncodingException $Exception) {
      throw new SnippetGenerationException('Failed to generate snippet code.', 0, $Exception);
    }
  }

  public function generateConf(ConfOptions $options, ?bool $withDocs = null): SnippetCode {
    try {
      $useDocs = $withDocs ?? $this->withDocs;
      $json    = $this->buildJson($options, $useDocs);

      if ($useDocs) {
        $onLoad = <<<JS
                   {
                      /**
                       * @param {Object} api
                       */
                      onLoad : function(api) {
                        api.on(api.EVENT.APP_ADD, function(app) {
                          // Control the embedded app via the API
                        });
                      },
                JS;
        $json = $onLoad . "\n" . substr($json, 1);
      }

      return new SnippetCode(<<<JS
              {$this->dataLayer}.conf({$json});
            JS);
    } catch (JsonEncodingException $Exception) {
      throw new ConfGenerationException('Failed to generate conf code.', 0, $Exception);
    }
  }

  public function generateUser(UserOptions $options, ?bool $withDocs = null): SnippetCode {
    try {
      $json = $this->buildJson($options, $withDocs ?? $this->withDocs);

      return new SnippetCode(<<<JS
              {$this->dataLayer}.initUser({$json});
            JS);
    } catch (JsonEncodingException $Exception) {
      throw new UserGenerationException('Failed to generate user code.', 0, $Exception);
    }
  }

  public function generateThread(string $querySelector, ThreadOptions $options, ?bool $withDocs = null): SnippetCode {
    try {
      $json     = $this->buildJson($options, $withDocs ?? $this->withDocs);
      $selector = $this->jsonEncode($querySelector);

      return new SnippetCode(<<<JS
              {$this->dataLayer}.thread(document.querySelector({$selector}), {$json});
            JS);
    } catch (JsonEncodingException $Exception) {
      throw new ThreadGenerationException('Failed to generate thread code.', 0, $Exception);
    }
  }

  public function generateHome(string $querySelector): SnippetCode {
    try {
      $selector = $this->jsonEncode($querySelector);

      return new SnippetCode(<<<JS
              {$this->dataLayer}.home(document.querySelector({$selector}));
            JS);
    } catch (JsonEncodingException $Exception) {
      throw new HomeGenerationException('Failed to generate home code.', 0, $Exception);
    }
  }

  private function buildJson(SnippetOptions $options, bool $withDocs): string {
    if ($withDocs) {
      return $this->jsonEncodeDocs($options::getOptionsWithDocs(), $options->getOptions(true));
    }

    return $this->jsonEncode($options->getOptions());
  }

  /**
   * @param array<string, mixed> $schema
   * @param array<string, mixed> $values
   */
  private function jsonEncodeDocs(array $schema, array $values): string {
    $space     = str_repeat(' ', $this->initialSpace);
    $spaceDocs = str_repeat(' ', $this->initialSpace + 2);
    $jsValues  = $this->extractJsValues($values);
    $lastKey   = array_key_last($values);
    $output    = "{\n";

    foreach ($values as $field => $value) {
      $meta     = $schema[$field];
      $isLast   = ($lastKey === $field);
      $required = $meta['required'] ? 'required' : 'optional';
      $docs     = $meta['docs'] ?? '';
      $type     = $meta['type'] ?? null;

      $value         = $values[$field] ?? $meta['default'] ?? null;
      $encodedValue  = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
      $encodedValue  = $encodedValue === false ? "''" : $encodedValue;
      $encodedValue  = $this->replaceJsPlaceholders($encodedValue, $jsValues);

      $output .= "{$spaceDocs}/**\n{$spaceDocs} * {$docs} ({$required})\n";

      if ($meta['example'] !== null && is_null($values[$field] ?? null)) {
        $exampleEncoded = json_encode($meta['example'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $output .= "{$spaceDocs} * Example: {$exampleEncoded}\n";
      }

      if ($type !== null) {
        $output .= "{$spaceDocs} * @param {{$type}}\n";
      }

      $output .= "{$spaceDocs} */\n";
      $output .= "{$space}  \"{$field}\": {$encodedValue}" . ($isLast ? "\n" : ",\n\n");
    }

    return $output . "{$space}}";
  }

  private function jsonEncode(mixed $data): string {
    $jsValues = $this->extractJsValues($data);

    $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    if ($json === false) {
      throw new JsonEncodingException('JSON encoding failed: ' . json_last_error_msg());
    }

    $json = $this->replaceJsPlaceholders($json, $jsValues);

    if (is_iterable($data) && str_ends_with($json, '}')) {
      return substr($json, 0, -1) . str_repeat(' ', $this->initialSpace) . '}';
    }

    return $json;
  }

  /**
   * @param array<array-key, mixed> $result
   *
   * @return array<string, JsValue>
   */
  private function extractJsValues(mixed $data, array &$result = []): array {
    if ($data instanceof JsValue) {
      $result[$data->getPlaceholder()] = $data;
    } elseif (is_array($data)) {
      foreach ($data as $item) {
        $this->extractJsValues($item, $result);
      }
    }

    return $result;
  }

  /**
   * @param array<string, JsValue> $jsValues
   */
  private function replaceJsPlaceholders(string $json, array $jsValues): string {
    foreach ($jsValues as $placeholder => $JsValue) {
      $json = str_replace('"' . $placeholder . '"', $JsValue->getValue(), $json);
    }

    return $json;
  }

}
