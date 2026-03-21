# Changelog

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
