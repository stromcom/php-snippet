# Changelog

## [0.4.0] - 2026-08-07
### Added
- `entityResolve` callback option on `ConfOptions` — resolves business-entity detail for the message editor's chip hover-card
- Optional `$language` parameter on `SnippetClient::snippet()` to force the widget UI language (`data-lang`)

## [0.3.4] - 2026-08-07
### Added
- `CspPolicy` — builds the Content-Security-Policy directives the host page needs (`script-src`, `connect-src`, `style-src`, `img-src`, `frame-src`); exposes `getDirectives()`, `getHeaderName()`, `getHeaderValue()` and `getMetaTag()`. Only the CDN origin comes from the loader URL; the API and application origins are taken from the environment or from the `apiUrl` / `applicationUrl` arguments, and an unknown origin throws a `CspException` instead of producing a guessed policy that would silently block the widget
- `OriginAwareEnvironmentInterface` — extends `EnvironmentInterface` with `getApiUrl()` and `getApplicationUrl()`. Implemented by `Environment` (real values per case) and by `CustomEnvironment`, which accepts both as optional constructor arguments. Existing `EnvironmentInterface` implementations are unaffected
- `SnippetClient::csp()` — policy pre-filled with the client's environment and nonce
- `nonce` parameter on `SnippetClient`, `SnippetClientFactory::create()` and `SnippetCode` — `getHTML()` renders `<script nonce="…">`, so integrators no longer need `'unsafe-inline'`. The nonce is validated against the base64 alphabet and propagates into `script-src`
- `CspException` for an invalid nonce or an origin that is neither known nor given
- CSP section in the README and a runnable `examples/csp.php`

## [0.3.1] - 2026-04-17
### Changed
- Add theme support to ConfOptions

## [0.3.0] - 2026-04-13
### Changed
- Add createHasher method to SnippetClientFactory for secure code generation

## [0.2.1] - 2026-03-21
### Changed
- Add examples

## [0.2.0] - 2026-03-21
### Changed
- Minimum PHP version lowered from 8.4 to **8.2**

## [0.1.0] - 2026-03-21
> ⚠️ **Pre-release – for testing only.** The API may change without notice.
### Added
- JS snippet code generation for the STROMCOM chat widget (loader, user, thread, conf, home)
- `SnippetClient` and `SnippetClientFactory` for easy initialization
- Automatic HMAC hashing of user/thread codes (`HmacCodeHasher`, `Base62CodeHasher`)
- Configurable hash algorithm (SHA-256, SHA-1) and base-62 encoding
- Environment support: Production, Staging, Custom URL (`EnvironmentInterface`)
- Options classes: `UserOptions`, `ThreadOptions`, `ConfOptions`, `SnippetOptions`
- `AvatarStyle` helper – Gravatar URL generation (Robohash, Identicon, Monsterid, Wavatar, Retro)
- Docs mode – annotated code output with inline JSDoc comments
- `SnippetCode` object with `getCode()` and `getHTML()` methods
- Exception hierarchy (`SnippetException` and specific subclasses)
- Requires PHP 8.4+, no runtime dependencies besides `tuupola/base62`
- MIT license
