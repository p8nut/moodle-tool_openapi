# Changelog

All notable changes to this plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

<!--
.github/workflows/release.yml reads this file: when $plugin->release changes
in version.php on `main` OR on any MOODLE_XXX_STABLE branch, it looks for a
"## [<that release>]" heading below and uses everything under it, verbatim,
as the GitHub Release body. If no such heading exists yet, the release still
happens but with a generic one-line release note instead.

Keep an "## [Unreleased]" section above the latest release for changes that
have not shipped yet; rename it to "## [x.y.z]" (matching $plugin->release)
when you cut that release, and start a fresh "## [Unreleased]" above it. Each
branch keeps its own CHANGELOG.md history from the point it was cut, same as
its own $plugin->release line -- no need to reconcile entries across branches.
-->

## [Unreleased]

### Security

- The existing webservice token method now accepts a token only when
  Moodle's REST endpoint would: it enforces the token's IP restriction, its
  session for a session-bound token, web services being enabled, the user
  being confirmed, not suspended and not `nologin`, and the service's
  required capability and allowed users. It used to check only expiry and
  the service being enabled.

### Fixed

- Webservice errors are now documented inside the `200` response (`anyOf`
  the result or `WebserviceError`), since Moodle's REST server answers every
  error with HTTP 200. The `default` response, which no real request ever
  hit, is gone; `403` documents web services or REST being disabled.
- `WebserviceError` matches what Moodle emits: `errorcode` is optional and
  `debuginfo` (debugging on only) is declared.
- A function without a return value documents the JSON literal `null` on
  success instead of an empty body (3.0: `nullable` + `enum: [null]`).
- `moodlewsrestformat` is required and `json`-only. It used to advertise a
  `json` default although Moodle falls back to XML when it is omitted.
- Request bodies spell out Moodle's PHP bracket encoding
  (`courseids[0]=2`, `users[0][username]=…`), booleans as `0`/`1`, and
  unknown keys being rejected, with an `x-moodle-encoding: php-brackets`
  extension.
- `PARAM_RAW` in a response is `string | number | boolean`: Moodle hands
  the value back uncleaned, so a PHP int or bool reaches the client as is.
  Request bodies, form-encoded, keep `string`. 3.0 spells it as an `anyOf`.
- `PARAM_PERMISSION` is an `integer`.
- `PARAM_URL` and `PARAM_LOCALURL` use `format: uri-reference` (relative URLs
  and `''` are valid values); `PARAM_EMAIL` has no `format`, since an invalid
  address is cleaned to `''`.
- Parameter defaults are documented as the value Moodle uses (`0`, not
  `"0"`; `true`, not `1`), and a default Moodle would reject is left out.
- A `VALUE_DEFAULT` scalar in a response is `required`: Moodle fills it in
  when the function leaves it out.
- A document narrowed by `?service=` or by the caller's scope declares only
  the tags its operations use, instead of every group on the site.
- Blank lines in descriptions are kept as paragraph breaks.

### Added

- `x-moodle-skipped-functions` at the document root lists the functions that
  could not be introspected, instead of dropping them silently.
- Core's warning and file items are shared as `components/schemas/Warning`
  and `File` where a function uses them unchanged.

## [1.0.0]

First release.

### Added

- An OpenAPI 3.1 document generated from the site's own `external_functions`
  catalog, served from `admin/tool/openapi/openapi.php` as JSON or YAML, with
  `?version=3.0` for consumers that cannot read 3.1 and `?service=` to narrow
  it to one external service.
- Four independent access methods, composed with OR and all off on a fresh
  install: Moodle session, IP allowlist, tokens issued by this plugin, and
  reuse of an existing Moodle webservice token. Each can be limited to a
  chosen list of functions; a plugin token can also be limited by IP the way
  a core webservice token can.
- An admin area under Admin tools with an access control page (a switch and,
  where there is something to configure, a cog per method) and a
  documentation page (viewer, downloads, cache purge).
- An interactive viewer built on a bundled Swagger UI, whose Try it out
  sends requests to Moodle's real REST endpoint.
- Operations grouped by plugin, and core's own functions grouped by
  subsystem rather than collapsed into a single `moodle` group.
- Tokens are hashed, shown once at creation, deleted outright rather than
  flagged, and their creation and deletion are recorded in the site log.
- A privacy provider: the issuing administrator is exported without the
  token itself, and a deletion request detaches the token instead of
  destroying a credential still in use.
- Spanish, Portuguese, Italian and French translations.
- An hourly scheduled task that rebuilds the cached catalog, so no request
  pays the cost of building it.
- A plugin icon.
- Documentation under `docs/`: quick start, installation, one page per
  feature, an endpoint reference, automation from a pipeline, troubleshooting
  and development notes.

### Changed

- CI runs the test matrix on pull requests only. `CI complete` is the
  required status check on the protected branches, so a push to one is always
  an already-tested merge; `release.yml` still runs on push and decides
  whether to tag a release.
