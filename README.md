# stromcom/snippet-php
PHP library for generating [STROMCOM](https://www.stromcom.cz) integration snippets.
Generates the JavaScript code you embed on your page to load the STROMCOM chat widget, identify users, and attach threads. No runtime dependencies — pure PHP 8.2+.
## Requirements
- PHP **8.2+**
## Installation
```bash
composer require stromcom/php-snippet
```

## Quick start
```php
use Stromcom\Snippet\SnippetClientFactory;
use Stromcom\Snippet\Options\UserOptions;
use Stromcom\Snippet\Options\ThreadOptions;

$client = SnippetClientFactory::create(
    clientKey:      'your-client-key',
    clientSecret:   'your-bearer-token',
    codeHashSecret: 'your-app-secret',   // optional — auto-hashes user & thread codes
);

// 1. Loader — place once in <head> or before </body>
echo $client->snippet()->getHTML();

// 2. Identify the logged-in user (optional but recommended)
//    The raw ID 'user-id-42' is automatically HMAC-hashed and base-62 encoded before output.
echo $client->user(new UserOptions(
    code:         'user-id-42',
    name:         'Jane Doe',
    emailAddress: 'jane@example.com',
    avatarURL:    'https://example.com/avatars/jane.png',
))->getHTML();

// 3. Embed a conversation thread
echo $client->thread('#support-chat', new ThreadOptions(
    code: 'order-12345',
    name: 'Order #12345',
    url:  'https://yourapp.com/orders/12345',
))->getHTML();
```

### Generated output

```html
<!-- snippet() — loader bootstrap -->
<script>
  (function(win, d, e, l, k, s) {
  var dl=l+'DL',c=function(s,n,a){n=n||s;(a?win[dl][n]=[]:null);win[l][s]=function(...p){a?win[dl][n].push(p):win[dl][n]=p;}},f=d.getElementsByTagName(e)[0],j=d.createElement(e);
  win[dl]=win[dl]||{};win[l]={};c('initUser','user');c('thread','threads',!0);c('conf');c('home',0,!0);
  ;j.async=true;j.dataset.type='stromcom';j.dataset.l=l;j.dataset.dl=dl;j.dataset.ck=k;j.dataset.cs=s;
  j.src = "https://cdn.stromcom.cz/loader.js?"+k;f.parentNode.insertBefore(j,f);
  })(window, document, 'script', "stromCom", "your-client-key", "your-bearer-token");
</script>

<!-- user() — raw ID is HMAC-SHA256 + base-62 encoded -->
<script>
  stromCom.initUser({
    "code": "fsbARKQmdbVZkaxP9fQPrQjrUwgmceVFoBPPKc5nZQD",
    "name": "Jane Doe",
    "emailAddress": "jane@example.com",
    "avatarURL": "https://example.com/avatars/jane.png"
  });
</script>

<!-- thread() — thread code is hashed the same way -->
<script>
  stromCom.thread(document.querySelector("#support-chat"), {
    "code": "UOONLyVBicFCCTyIdiSGX7YxpZm1g449KLnPKLvg7fd",
    "name": "Order #12345",
    "url": "https://yourapp.com/orders/12345"
  });
</script>
```

A runnable version of this example is available in [`examples/basic.php`](examples/basic.php).

## Environments
The default environment is **production**. Use `Environment::STAGING` for the staging CDN, or `CustomEnvironment` for any custom URL (testing, self-hosted, localhost).
```php
use Stromcom\Snippet\Environment\Environment;
use Stromcom\Snippet\Environment\CustomEnvironment;

// Production (default)
$client = new SnippetClient('key', 'secret', Environment::PRODUCTION);

// Staging
$client = new SnippetClient('key', 'secret', Environment::STAGING);

// Custom / testing
$client = new SnippetClient('key', 'secret', new CustomEnvironment('http://localhost:8082/loader.js'));
```
You can also implement `EnvironmentInterface` yourself if you need custom logic (e.g. URL from a config service):
```php
use Stromcom\Snippet\Environment\EnvironmentInterface;

class MyEnv implements EnvironmentInterface {
    public function getLoaderUrl(): string {
        return getenv('STROMCOM_LOADER_URL');
    }
}

$client = new SnippetClient('key', 'secret', new MyEnv());
```
## Content Security Policy
If your page sends a `Content-Security-Policy`, it has to allow the sources the widget uses. For **production** these are:
```
script-src  https://cdn.stromcom.cz
connect-src https://www.stromcom.cz
style-src   https://cdn.stromcom.cz
img-src     data:
frame-src   https://app.stromcom.cz
```
| Directive | Why |
|---|---|
| `script-src` | The loader and the widget bundle are served from the CDN. |
| `connect-src` | The widget polls the notification API on the STROMCOM site. |
| `style-src` | The widget stylesheets are served from the CDN. |
| `img-src` | Built-in icons (e.g. the loading spinner) are inlined as `data:` URIs. |
| `frame-src` | The chat itself runs in an iframe on the application origin. |

**Avatars, attachments, fonts and media do not need any directive on your page** — they are loaded *inside* the iframe and are therefore covered by the policy of the application origin, not by yours.

`'unsafe-inline'` is not needed either — see [Nonce for inline scripts](#nonce-for-inline-scripts) below for the `<script>` tags this library generates.

### `CspPolicy`
Instead of copying the list around, let the library build it for the environment you use:
```php
use Stromcom\Snippet\CspPolicy;
use Stromcom\Snippet\Environment\Environment;

$policy = new CspPolicy(Environment::PRODUCTION);

header($policy->getHeaderName() . ': ' . $policy->getHeaderValue());
// Content-Security-Policy: script-src https://cdn.stromcom.cz; connect-src https://www.stromcom.cz; …

echo $policy->getMetaTag();
// <meta http-equiv="Content-Security-Policy" content="script-src https://cdn.stromcom.cz; …">
```
`getDirectives()` returns `directive => list of sources`, so you can merge STROMCOM into a policy you already have:
```php
$ownPolicy = ['default-src' => ["'self'"], 'script-src' => ["'self'"]];

foreach ($policy->getDirectives() as $directive => $sources) {
    $ownPolicy[$directive] = [...$ownPolicy[$directive] ?? [], ...$sources];
}
```
`Environment::PRODUCTION` and `Environment::STAGING` know all their origins, so nothing else is needed for them. For a `CustomEnvironment` the API and application origins are **not** guessed from the loader URL — they are separate hosts, and a wrong guess would silently produce a policy that blocks the widget. Give them to the environment:
```php
$environment = new CustomEnvironment(
    'https://cdn.example.com/loader.js',
    'https://example.com',       // API — the origin the widget polls
    'https://chat.example.com',  // application — the origin of the iframe
);

$policy = new CspPolicy($environment);
```
…or straight to the policy, which also overrides what the environment says:
```php
$policy = new CspPolicy($environment, apiUrl: 'https://example.com', applicationUrl: 'https://chat.example.com');
```
If neither provides them, `CspPolicy` throws a `CspException` naming the directive it could not build and the argument to pass.

> **Writing your own `EnvironmentInterface`?** `CspPolicy` reads the two origins from `OriginAwareEnvironmentInterface`, which extends `EnvironmentInterface` with `getApiUrl()` and `getApplicationUrl()` (both may return `null` when unknown). Either implement it as well, or pass `apiUrl` / `applicationUrl` to `CspPolicy`. Implementations of plain `EnvironmentInterface` keep working everywhere else — the extra interface only matters for CSP.

### Nonce for inline scripts
`getHTML()` emits an inline `<script>` tag. Rather than allowing `'unsafe-inline'`, pass the nonce of the current response — it is set **once** and applied to every tag the client generates:
```php
$nonce = base64_encode(random_bytes(16));  // a new value for every response

$client = SnippetClientFactory::create(
    clientKey:    'key',
    clientSecret: 'secret',
    nonce:        $nonce,
);

// The same nonce ends up in script-src
header($client->csp()->getHeaderName() . ': ' . $client->csp()->getHeaderValue());
// … script-src https://cdn.stromcom.cz 'nonce-4mB1r0EYA0lZ2Kk1J7bWpQ=='; …

echo $client->snippet()->getHTML();
// <script nonce="4mB1r0EYA0lZ2Kk1J7bWpQ==">…</script>
```
The nonce must be a non-empty base64 value (`[A-Za-z0-9+/=_-]`); anything else throws a `CspException`. `getCode()` is unaffected — it still returns the raw JavaScript. Without a nonce the output is unchanged, so upgrading changes nothing for existing integrations.

A runnable version of these examples is in [`examples/csp.php`](examples/csp.php).

## Code hashing
User and thread `code` values should be hard to guess. Instead of hashing IDs manually, enable automatic hashing and every `user()` / `thread()` call will HMAC-hash the code for you.

### Using the factory (recommended)
The simplest way — pass `codeHashSecret` to `SnippetClientFactory::create()`:
```php
use Stromcom\Snippet\SnippetClientFactory;
use Stromcom\Snippet\Options\UserOptions;
use Stromcom\Snippet\Options\ThreadOptions;

$client = SnippetClientFactory::create(
    clientKey:      'key',
    clientSecret:   'secret',
    codeHashSecret: 'your-app-secret',
);

// 'user-42' is automatically HMAC-hashed and base-62 encoded (~43 chars for SHA-256)
echo $client->user(new UserOptions('user-42'))->getHTML();

// Same for threads
echo $client->thread('#chat', new ThreadOptions('order-123'))->getHTML();
```
The default algorithm is **SHA-256**. You can switch to **SHA-1** via the `codeHashAlgo` parameter:
```php
$client = SnippetClientFactory::create(
    clientKey:      'key',
    clientSecret:   'secret',
    codeHashSecret: 'your-app-secret',
    codeHashAlgo:   HashAlgorithm::SHA1,
);
```
#### Base-62 encoding (default)
By default the HMAC hash is encoded as a **base-62** string (`0-9 A-Z a-z`, ~43 chars for SHA-256) instead of the longer hex representation (64 chars). If you need the raw hex output, disable base-62:
```php
$client = SnippetClientFactory::create(
    clientKey:       'key',
    clientSecret:    'secret',
    codeHashSecret:  'your-app-secret',
    codeHashBase62:  false,  // use raw hex output (64 chars for SHA-256)
);
```
You can also apply the decorator manually:
```php
use Stromcom\Snippet\Hashing\HmacCodeHasher;
use Stromcom\Snippet\Hashing\Base62CodeHasher;

$hasher = new Base62CodeHasher(new HmacCodeHasher('your-app-secret'));
$client = new SnippetClient('key', 'secret', codeHasher: $hasher);
```
### Using the constructor with a custom hasher
For full control, pass any `CodeHasherInterface` implementation to the `SnippetClient` constructor:
```php
use Stromcom\Snippet\SnippetClient;
use Stromcom\Snippet\Hashing\CodeHasherInterface;

class MyCustomHasher implements CodeHasherInterface {
    public function hash(string $code): string {
        return hash('sha256', $code . getenv('APP_SECRET'));
    }
}

$client = new SnippetClient('key', 'secret', codeHasher: new MyCustomHasher());
```
## Methods
| Method | Description |
|---|---|
| `snippet(?string $language = null)` | Async loader script. Place once per page. Optional initial UI language (any code; one without a translation falls back to the browser language, then English). |
| `conf(ConfOptions)` | SDK configuration (notification renderer, CSS, callbacks…). |
| `user(UserOptions)` | Identifies the current user. |
| `thread(string $selector, ThreadOptions)` | Embeds a thread into a DOM element. |
| `home(string $selector)` | Embeds the notification center into a DOM element. |
All methods return a `SnippetCode` object with:
- `->getCode()` — raw JavaScript string
- `->getHTML()` — wrapped in `<script>…</script>` (with a `nonce` attribute when a nonce is configured)
## Options reference
### `UserOptions`
| Parameter | Type | Required | Description |
|---|---|---|---|
| `code` | `string` | ✅ | Unique user identifier (hash of your internal user ID + salt). Max 100 chars, `[a-zA-Z0-9-_]`. |
| `name` | `?string` | | Display name. |
| `emailAddress` | `?string` | | Used for email notifications. |
| `readOnly` | `?bool` | | When `true`, the user can read but not send messages. |
| `avatarURL` | `?string` | | Full URL to the user's avatar image. See `AvatarStyle` helper below. |
### `ThreadOptions`
| Parameter | Type | Required | Description |
|---|---|---|---|
| `code` | `string` | ✅ | Unique thread identifier. Cannot be changed later. Max 100 chars, `[a-zA-Z0-9-_]`. |
| `name` | `?string` | | Display name shown in the thread header. |
| `url` | `?string` | | Canonical URL of the page. A link appears in the thread header. |
| `userHint` | `bool` | | Enable @mention suggestions. Default: `true`. |
### `ConfOptions`
See the full list of parameters in [`src/Options/ConfOptions.php`](src/Options/ConfOptions.php). Notable options:
| Parameter | Type | Description |
|---|---|---|
| `notificationRenderer` | `?string` | JS function for custom notification rendering. |
| `onNotification` | `?string` | JS callback for new message count changes. |
| `pageCSSPath` | `?string` | CSS file URL injected into the snippet iframe. |
| `notificationElementTargetElement` | `?string` | JS expression returning the target DOM element. |
| `notificationElementPosition` | `?int` | Icon position: 1=top-left, 2=top-right, 3=bottom-right, 4=bottom-left. |
| `language` | `?string` | UI language of the app. Any language code is accepted; one without a translation falls back to the browser language, then English. `null` follows the browser. |
| `theme` | `?string` | Light/dark mode for the app. Values: `null` (follows browser preference), `stromcom-light`, `stromcom-dark`. |
| `entityResolve` | `?string` | JS callback resolving business-entity detail (order, ticket…) for the message editor's chip hover-card. Receives `{type, id}`, returns (or resolves to) `{title, url?, fields: [{label, value}]}`. |

### UI language
Set the language on `ConfOptions`, the same way as the theme. Any language code is accepted — the widget uses English for a language it has no translation for, so passing one is never an error. When omitted, the language is detected from the browser.
```php
echo $client->conf(new ConfOptions(language: 'cs'))->getHTML();
```
`snippet()` also takes a language code, which sets the initial value before `conf()` runs. Use it when the language is known at page render and you want the widget to boot into it without waiting for `conf()`; `ConfOptions::$language` wins whenever both are given.
```php
echo $client->snippet('cs')->getHTML();
```
## Avatar helper
`AvatarStyle` generates Gravatar URLs without any external dependency:
```php
use Stromcom\Snippet\Helper\AvatarStyle;

$url = AvatarStyle::ROBOHASH->generateUrl('user@example.com');
// or pass a pre-computed MD5 hash
$url = AvatarStyle::ROBOHASH->generateUrl(md5('user@example.com'));

echo $client->user(new UserOptions('u1', avatarURL: $url))->getHTML();
```
Available styles: `ROBOHASH`, `IDENTICON`, `MONSTERID`, `WAVATAR`, `RETRO`.
## Docs mode
Pass `withDocs: true` to the constructor (or per method) to generate annotated code with inline JSDoc comments — useful for generating integration guides:
```php
$client = new SnippetClient('key', 'secret', withDocs: true);

echo $client->user(new UserOptions('u1'))->getHTML();
```
```html
<script>
  stromCom.initUser({
    /**
     * Unique user code. Cannot be changed later. Max length 100. Allowed characters: [a-zA-Z0-9-_] (required)
     */
    "code": "u1"
  });
</script>
```
