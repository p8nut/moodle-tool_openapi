# The viewer

**Documentation → Open the interactive viewer.**

Swagger UI, shipped with the plugin, rendering this site's own catalog. It
browses the document and, with web services enabled, sends real requests.

## What it needs

Only `tool/openapi:manage`. The viewer loads the document through the
plugin's own admin-only endpoint, not through
`admin/tool/openapi/openapi.php`, so it works with every
[access method](access-control.md) switched off. Those methods decide who may
read the catalog from outside; they have nothing to do with whether an
administrator can look at it here.

The library is served from `thirdparty/swagger-ui` inside the plugin. Nothing
is fetched from a CDN, so it works on a site with no outbound internet
access.

## Finding a function

A stock site exposes around 750 operations, so the grouping is what makes it
usable. Operations are grouped by the plugin that registered them
(`mod_quiz`, `tool_dataprivacy`) and core's own are grouped by subsystem
(`core_user`, `core_course`, `core_group`) rather than collapsed into one
enormous `moodle` group. The reasoning is in
[openapi-document.md](openapi-document.md#grouping).

Everything starts collapsed. Expand a group, then an operation, to see its
parameters, its return shape, the capabilities it requires and whether it is
deprecated.

## Try it out

**Try it out** sends a real request to this site's REST endpoint. Three
things have to be true first.

**1. Moodle's web services must be enabled, with the REST protocol on.**
This is Moodle's own configuration, not the plugin's: **Site administration
→ Server → Web services**. Without it, `webservice/rest/server.php` answers
a bare `403` with an empty body, which explains nothing at all — so the
viewer checks the same condition and warns you before you press Execute.

**2. You need a token, entered once under Authorize.** A Moodle *webservice* token,
not one issued by this plugin: this is a real API call, and plugin tokens
only authorize reading documentation. Create one under **Site administration
→ Server → Web services → Manage tokens**.

**3. The token's user must be allowed to call that function**, through a
service that includes it. The viewer documents what the site exposes; it does
not grant anything.

Swagger UI adds it to every request as `?wstoken=`. There is no
`wsfunction` field: the viewer takes it from the operation's path. And
`moodlewsrestformat` defaults to `json`. Without that last one Moodle
answers in XML.

### Why the URL in the request is not the URL in the path

Each function is documented at a path of its own (`/core_user_get_users`),
because OpenAPI cannot describe one path as several hundred operations.
Moodle has a single endpoint and selects the function with a parameter. So
before sending, the viewer rewrites the request to
`/webservice/rest/server.php?wsfunction=<the path's name>`, keeping the
query string the operation built.

Only operations are rewritten. The viewer's own fetch of the document is left
alone.

### How the body is sent

Swagger UI's own form encoding is not one Moodle reads: it joins an array's
items with commas, sends an object as JSON and a boolean as `true`. The
viewer rebuilds the body from the values you typed, in Moodle's bracket
syntax: an array field's items become `name[0]`, `name[1]`; an object field
takes JSON (`{"ids": [2, 3]}` becomes `options[ids][0]=2&options[ids][1]=3`);
booleans are sent as `1`/`0`. An empty field, or a `null` inside the JSON,
is left out, so Moodle applies the parameter's default.

## Downloads

The same page downloads the document as JSON or YAML. Both need
`tool/openapi:manage`, and both always return the full catalog: an
administrator who can reach that page can already see everything, so
filtering it there would hide nothing and only confuse.

## The cache

**Purge OpenAPI catalog cache** forces the next read to rebuild the document.

You rarely need it. Moodle purges every application cache when a plugin is
installed, upgraded or uninstalled, which is the only moment
`external_functions` can change, and an hourly task rebuilds it so no request
pays the cost. Reach for it when you have changed function registrations by
hand and want the catalog to catch up now.
