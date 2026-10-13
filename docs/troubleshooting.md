# Troubleshooting

Failures that look like plugin bugs and are not, each with the check that
tells them apart.

## The endpoint answers `403 {"error":"access_denied"}`

By design the body never says why, so work through the causes in order.

**Is any method on?** Access control shows the four switches. On a fresh
install all are off and every request is refused.

**Is the right one on?** A `Bearer` token needs *Plugin token*; a `?wstoken=`
needs *Existing webservice token*; a browser session needs *Moodle session*.
Turning on one does not authorize the others.

**Is the header reaching PHP?** With Apache and PHP-FPM or CGI, Apache does
not pass `Authorization` to the application unless told to:

```apache
CGIPassAuth On
# or, on older setups
SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
```

With mod_php it arrives on its own. The plugin reads
`apache_request_headers()` and falls back to `$_SERVER['HTTP_AUTHORIZATION']`,
but if the server never delivers the header there is nothing to read. Test
with a `?wstoken=` request: if that works and `Bearer` does not, this is why.

**Does the token carry an IP restriction?** A token restricted to an address
is refused from anywhere else, with the same `access_denied`. Check the
token's row on the Tokens page.

**Session gate, with plain `tool/openapi:view`?** That capability only
authorizes a request that also names a service: `?service=<shortname>`.
Without one there is nothing to authorize, because a capability cannot carry
a function list. `tool/openapi:viewfullcatalog` has no such requirement.

## Try it out returns `403` with an empty body

Not this plugin. `webservice/rest/server.php` answers a bare `403` with
`Content-Length: 0` when web services or the REST protocol are off:

```php
if (!webservice_protocol_is_enabled('rest')) {
    header("HTTP/1.0 403 Forbidden");
    ...
    die;
}
```

Enable them under **Site administration → Server → Web services**. The viewer
warns about this before you press Execute, with a link to the setting.

Reproduce it outside the browser to be sure:

```console
curl -i '<site>/webservice/rest/server.php?wstoken=X&wsfunction=core_webservice_get_site_info&moodlewsrestformat=json'
```

An empty `403` confirms the protocol is off. Anything else — including a
Moodle error in JSON — means web services are on and the problem is the
token or the function.

## Try it out returns a Moodle exception with HTTP 200

That is how Moodle reports webservice errors: status 200, body
`{"exception":…,"errorcode":…,"message":…}`. Read `errorcode`.
`invalidtoken` means the token is not a Moodle webservice token — a token
issued by this plugin will not work here, it only authorizes reading
documentation.

## A downloaded file is an HTML error page

Almost always a missing `-f` on curl: without it, an error response is
written to the file and the command still exits 0.

## The access method switches do nothing

Check that JavaScript loaded — the switches are a Moodle component and, like
core's own, assume JS. If the page was served from a stale cache after an
upgrade, purge caches: **Site administration → Development → Purge caches**,
or `php admin/cli/purge_caches.php`.

## The viewer is blank

Open the browser console. A syntax error inside the Swagger UI bundle means
the file is being altered in transit — the usual culprit is a proxy or an
optimiser minifying an already-minified bundle. The plugin loads it with a
plain script tag precisely so nothing in Moodle does that.

`Failed to load API definition` with a fetch error names the URL it could not
read; that URL is the plugin's own admin-only download endpoint, so the cause
is a session or capability problem, not the viewer.

## The catalog is missing a function

**Is the plugin that registers it installed and enabled?** The catalog lists
what `external_functions` holds, nothing more.

**Was it installed after the catalog was cached?** Moodle purges application
caches on plugin install, upgrade and uninstall, so normally this resolves
itself. If you changed registrations by hand, use **Purge OpenAPI catalog
cache** on the Documentation page.

**Is it one Moodle cannot introspect?** A function whose implementation class
does not load is skipped so that one broken registration does not take down
the whole catalog, and listed under `x-moodle-skipped-functions` at the
document root. A stock Moodle 4.5 has at least one.

**Is your scope narrower than you think?** A token or IP rule restricted to a
list of functions returns only those.

## `?version=3.0` output is rejected by a generator

Report it, but first check whether the generator is reading a field the 3.0
rendering could not express. The downgrade rewrites what has a 3.0
equivalent — `type: [X, "null"]` becomes `nullable: true`, `const` becomes a
single-element `enum` — and leaves the rest as it is.
