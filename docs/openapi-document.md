# The generated document

The document is built from `external_functions`, this site's own registry of
webservice functions, by introspecting each one with
`external_api::external_function_info()`. It describes what the site
actually has installed, so a plugin that adds functions adds operations, and
one that is uninstalled removes them.

## One path per function

Moodle has a single REST endpoint, `/webservice/rest/server.php`, and picks
the function with a `wsfunction` parameter. OpenAPI cannot express that: a
path and method together identify exactly one operation, so several hundred
functions cannot share one path.

Every function therefore gets a synthetic path named after it:

```
POST /core_course_get_courses
```

This is a documentation convenience, not a URL that exists. Each operation
says so in three places: in its own `description`, in the
`x-moodle-real-endpoint` extension, and in the path itself, whose last
segment is always the `wsfunction` value: `/core_user_get_users` is
`server.php?wsfunction=core_user_get_users`. `wsfunction` is therefore not
declared as a parameter -- a field the caller could change but must not. A
client maps the path to the real endpoint the same way; the bundled viewer
does it for Try it out.

## Request shape

`POST`, always -- Moodle's REST protocol accepts POST for every function,
regardless of whether the function itself reads or writes.

Parameters go in the query string:

| Parameter | Notes |
| --- | --- |
| `wstoken` | Required. A Moodle webservice token, declared as the `wstoken` security scheme (`apiKey` in the query) rather than a parameter, so a viewer asks for it once and a generated client takes it as auth. |
| `wsfunction` | Required, and not a declared parameter: it is the last segment of the operation's path. |
| `moodlewsrestformat` | Required, `json` only. Moodle answers in XML when it is omitted, and every response schema here is JSON. |

The function's own arguments go in the body as
`application/x-www-form-urlencoded`. Not JSON: `parse_request()` in
`webservice/rest/locallib.php` merges `$_GET` and `$_POST` and never reads a
JSON body, and PHP only populates `$_POST` for form-encoded and multipart
requests.

Arrays and objects nest with PHP's bracket syntax (`courseids[0]=2`,
`users[0][username]=jdoe`), booleans are sent as `0`/`1`, and a key the
function does not declare is rejected. No OpenAPI encoding style says this,
so the request body's description does, with `x-moodle-encoding:
php-brackets`.

Types follow what Moodle's cleaning actually produces, not the PHP constant's
name: `PARAM_RAW` in a response is `string | number | boolean` (the value is
returned uncleaned), URLs are `uri-reference` and emails carry no `format`
(both are cleaned to `''` when invalid), a default is documented as the value
Moodle uses, and a `VALUE_DEFAULT` scalar in a response is `required`, since
Moodle fills it in.

## Grouping

Operations are tagged so a viewer can group them. A function registered by a
plugin is tagged with that plugin (`mod_quiz`, `tool_dataprivacy`). Core's
functions cannot be: all ~400 of them are registered under the single
component `moodle`, which would collapse core into one unusable group.

Their names carry the grouping their component does not, so a core function
is tagged with the subsystem in its name -- `core_user_get_users` becomes
`core_user`, `core_course_get_courses` becomes `core_course`. The subsystem
is resolved against `core_component::get_core_subsystems()` rather than
guessed by splitting on underscores, which matters both ways:
`core_courseformat_*` must not land under `core_course` (the longest
matching subsystem wins), and `core_get_string` must not invent a `core_get`
group out of a verb (anything with no subsystem match is tagged plain
`core`).

Every tag in use is also declared at the document root, in alphabetical
order, so a viewer has something to order the groups by. A document narrowed
by `?service=` or by the caller's scope declares only the tags its own
operations use.

## Responses

Each operation documents its real return shape, mapped from the function's
`returns_desc`, inside the `200` response as `anyOf` that shape or
`WebserviceError`: Moodle's REST server answers every error -- invalid token,
missing capability, invalid parameter -- with HTTP 200 and this body:

```json
{"exception": "...", "errorcode": "...", "message": "...", "debuginfo": "..."}
```

`errorcode` is only there when the exception has one, `debuginfo` only with
debugging on. A function without a return value answers the literal `null`.
The one non-200 answer, `403`, is web services or REST being disabled.

Core's warning and file items are shared as `#/components/schemas/Warning`
and `File` wherever a function uses them unchanged, so a generated client has
one type for each; a function that customised their descriptions keeps its
own schema.

A function whose schema has no properties is emitted as `{}`, an empty
object, not `[]` -- an empty PHP array would encode as a JSON array, and
`properties` must be an object.

## Versions and formats

`?version=3.0` downgrades the document: 3.1's `const` becomes a
single-element `enum`, `[X, "null"]` becomes `X` with `nullable: true`,
several types become an `anyOf`, and a null-only response becomes a nullable
`enum: [null]`.
`?format=yaml` emits YAML instead of JSON.

## Extensions

Each operation carries a few `x-moodle-*` fields that have no OpenAPI
equivalent:

| Extension | Meaning |
| --- | --- |
| `x-moodle-real-endpoint` | The one URL the request actually goes to |
| `x-moodle-capabilities` | Capabilities the function requires |
| `x-moodle-ajax-allowed` | Whether the function may be called from AJAX |

At the document root, `x-moodle-skipped-functions` lists the functions that
could not be introspected (see below), when there are any.

## Functions that cannot be introspected

A site can have an installed function whose implementation class does not
load -- a broken registration left by another plugin. Introspecting it
throws, and one such function must not take down the whole catalog, so it is
skipped and the rest is built, and its name is listed under
`x-moodle-skipped-functions`. This is not hypothetical: a stock Moodle 4.5
has one.
