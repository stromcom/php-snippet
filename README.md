# stromcom/snippet-php
PHP library for generating [STROMCOM](https://www.stromcom.cz) integration snippets.
Generates the JavaScript code you embed on your page to load the STROMCOM chat widget, identify users, and attach threads. No runtime dependencies — pure PHP 8.4.
## Requirements
- PHP **8.4+**
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
| `snippet()` | Async loader script. Place once per page. |
| `conf(ConfOptions)` | SDK configuration (notification renderer, CSS, callbacks…). |
| `user(UserOptions)` | Identifies the current user. |
| `thread(string $selector, ThreadOptions)` | Embeds a thread into a DOM element. |
| `home(string $selector)` | Embeds the notification center into a DOM element. |
All methods return a `SnippetCode` object with:
- `->getCode()` — raw JavaScript string
- `->getHTML()` — wrapped in `<script>…</script>`
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
// outputs: stromCom.initUser({ /** User unique code (required) */ "code": "u1", … });
```
