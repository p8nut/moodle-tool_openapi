# The endpoint

```
GET <site>/admin/tool/openapi/openapi.php
```

The one URL the [access methods](access-control.md) govern. It returns the
OpenAPI document and nothing else: it is machine-facing, never renders a
page, and never redirects to a login form.

## Parameters

| Parameter | Values | Default | Meaning |
| --- | --- | --- | --- |
| `format` | `json`, `yaml` | `json` | Response encoding |
| `version` | `3.1`, `3.0` | `3.1` | OpenAPI version to emit |
| `service` | An external service shortname | — | Narrow to one service |
| `protocol` | `rest`, `restful` | `rest` | Web service protocol the document targets |

`version=3.0` exists because a good deal of tooling still cannot read 3.1.
What it changes is described in
[openapi-document.md](openapi-document.md#versions-and-formats).

`protocol=restful` documents Catalyst's
[webservice_restful](https://github.com/catalyst/moodle-webservice_restful)
protocol instead of core's REST one, see
[openapi-document.md](openapi-document.md#the-restful-protocol).

`service` is applied **after** authorization and on top of the caller's own
scope. Asking for a service your scope excludes returns the intersection,
which may be empty; it is never a way to see more than the scope allows.

## Authentication

Whichever enabled method accepts the request first. In practice:

```console
# A token issued by this plugin
curl -H "Authorization: Bearer <token>" '<site>/admin/tool/openapi/openapi.php'

# An existing Moodle webservice token
curl '<site>/admin/tool/openapi/openapi.php?wstoken=<token>'

# A browser session, for a user holding the capability
```

An IP rule needs no credential at all: the request is authorized by where it
came from.

## Responses

**200** with the document, as `application/json; charset=utf-8` or
`application/yaml; charset=utf-8`.

Errors are JSON, with a single `error` key:

| Status | Body | Cause |
| --- | --- | --- |
| `400` | `{"error":"invalid_version"}` | `version` was not `3.1` or `3.0` |
| `400` | `{"error":"invalid_format"}` | `format` was not `json` or `yaml` |
| `400` | `{"error":"invalid_service"}` | No external service has that shortname |
| `400` | `{"error":"invalid_protocol"}` | `protocol` was not `rest` or `restful` |
| `403` | `{"error":"access_denied"}` | No enabled method authorized the request |

`access_denied` never says *why*. Whether the method is off, the credential
is wrong, the IP does not match or the token has a restriction all produce
the same body, so an unauthorized caller cannot use the error to map the
site's configuration. The request is refused before `service` is even looked
at, so it cannot be used to discover which services exist either.

## Examples

Download as YAML:

```console
curl -fsS -H "Authorization: Bearer $TOKEN" \
  '<site>/admin/tool/openapi/openapi.php?format=yaml' -o openapi.yaml
```

`-f` matters: without it curl writes the error body to the file and exits 0,
so a pipeline stores an error document and calls it a success.

One service, for a generator that does not need the whole catalog:

```console
curl -fsS -H "Authorization: Bearer $TOKEN" \
  '<site>/admin/tool/openapi/openapi.php?service=my_integration&version=3.0'
```

## Size

A stock site exposes around 750 functions. That is roughly **10 MB of JSON**
or **5 MB of YAML** — measured, not estimated. If you are storing or diffing
the result, narrow it with `service` first; see
[automation.md](automation.md).

## Cost

Cheap. The document is built once and cached, an hourly scheduled task keeps
the cache warm, and a request is a cache read plus serialisation. The
expensive part — introspecting every function — happens on a cache miss, and
Moodle only invalidates the cache when a plugin is installed, upgraded or
removed, which is the only moment the catalog can change.

## Downloads from the admin area

The Documentation page offers the same document as a file download. That
route is **not** governed by the access methods: it requires
`tool/openapi:manage`, so an administrator can always retrieve the catalog
with every method switched off.
