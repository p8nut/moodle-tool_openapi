# OpenAPI documentation for Moodle

[![Moodle 4.5 – 5.2](https://img.shields.io/badge/Moodle-4.5%20%E2%80%93%205.2-orange)](https://moodledev.io/general/releases)
[![GPL v3 or later](https://img.shields.io/badge/license-GPLv3%2B-blue)](LICENSE)

`tool_openapi` publishes a Moodle site's own webservice catalog as an
**OpenAPI 3.1 document**, with a bundled Swagger UI viewer and four
independent ways to control who may read it.

It documents what the site actually has installed. The catalog is built by
introspecting `external_functions`, so a plugin that adds functions adds
operations, and one that is removed takes them away. Nothing is hand-written
and nothing goes stale.

```console
$ curl -H "Authorization: Bearer $TOKEN" \
    'https://moodle.example/admin/tool/openapi/openapi.php?format=yaml'
openapi: 3.1.0
info:
  title: My site web services
  version: 5.2.2
...
```

**Closed by default.** On a fresh install every access method is off and the
endpoint answers `403` to everyone, including you. Turning one on is a
deliberate act.

## Quick start

1. Install into `admin/tool/openapi` and visit **Site administration →
   Notifications**.
2. Go to **Site administration → Plugins → Admin tools → OpenAPI
   documentation**.
3. On **Access control**, turn on *Plugin token*, then use its cog to create
   one. Copy it — it is shown once.
4. Request the document with that token.

The long version, with what to check at each step, is in
[docs/quick-start.md](docs/quick-start.md).

## Documentation

| | |
| --- | --- |
| [Quick start](docs/quick-start.md) | From install to a first authorized request |
| [Installation](docs/installation.md) | Requirements, install, upgrade, uninstall |
| [Access control](docs/access-control.md) | The four methods, what each authorizes, and scopes |
| [Tokens](docs/tokens.md) | Issuing, scoping, restricting and deleting plugin tokens |
| [IP rules](docs/ip-rules.md) | Allowing a network without a credential |
| [The endpoint](docs/endpoint.md) | Parameters, responses, errors, examples |
| [The generated document](docs/openapi-document.md) | What is in it, and why it is shaped that way |
| [The viewer](docs/viewer.md) | Browsing the catalog and using Try it out |
| [Automation](docs/automation.md) | Consuming the document from a pipeline |
| [Privacy](docs/privacy.md) | What personal data is stored, exported and deleted |
| [Troubleshooting](docs/troubleshooting.md) | Failures that look like bugs and are not |
| [Development](docs/development.md) | Running the same checks CI runs |

The index with a one-line summary of each is at [docs/](docs/README.md).

## From the command line

Whoever can run PHP on the site can write the document without a request,
a token or an access method turned on:

```console
$ php admin/tool/openapi/cli/generate.php --service=my_service --protocol=restful --output=openapi.json
```

`--service` and `--protocol` mean what they mean on the endpoint; without
`--output` the JSON goes to standard output. The document is built fresh,
not read from the cache, and its server URL is relative to the site. An
unknown service or protocol is an error on standard error with a non-zero
exit status. See [docs/automation.md](docs/automation.md#from-the-command-line).

## What it is not

- **Not a webservice client.** It documents the API; it does not call it for
  you. The viewer's Try it out sends requests to Moodle's own REST endpoint.
- **Not a replacement for Moodle's web services settings.** Enabling web
  services, the REST protocol, external services and their tokens all stay
  where they were. This plugin reads that configuration; it never changes it.
- **Not a way to bypass permissions.** Every access method is opt-in, and the
  one that uses an existing webservice token grants exactly what that token
  already granted.

## Requirements

Moodle 4.5 through 5.2, and whichever PHP version that Moodle release
supports. The exact matrix CI runs against is derived from
`$plugin->supported` in `version.php`.

## Bundled libraries

The viewer uses [Swagger UI](https://github.com/swagger-api/swagger-ui)
(Apache 2.0), shipped under `thirdparty/swagger-ui` and declared in
`thirdpartylibs.xml`. Nothing is fetched from a CDN at runtime.

## License

[GPL v3 or later](LICENSE), the same as Moodle itself.
