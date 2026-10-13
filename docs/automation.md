# Automation

Pulling the document from a pipeline: keeping a snapshot under version
control, generating clients, or checking that an upgrade did not change the
API surface underneath you.

## Fetching it

Issue a [token](tokens.md), store it as a pipeline secret, and:

```console
curl -fsS \
  -H "Authorization: Bearer $OPENAPI_TOKEN" \
  "$MOODLE_URL/admin/tool/openapi/openapi.php?format=yaml" \
  -o openapi.yaml
```

`-f` is not optional in a pipeline. Without it, curl writes the error body to
the file and exits 0 — the job goes green having stored a 403 as though it
were a specification.

A scheduled example:

```yaml
on:
  schedule:
    - cron: '0 3 * * *'
  workflow_dispatch:

jobs:
  snapshot:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v5
      - run: |
          curl -fsS -H "Authorization: Bearer ${{ secrets.OPENAPI_TOKEN }}" \
            "${{ vars.MOODLE_URL }}/admin/tool/openapi/openapi.php?format=yaml" \
            -o spec/openapi.yaml
      - run: git diff --exit-code spec/openapi.yaml
```

## Three things that will bite you

**Size.** The full catalog is around 10 MB of JSON or 5 MB of YAML. Committed
daily, that is a repository nobody can clone within a year. Narrow it with
`?service=<shortname>` to what you actually consume, or publish it as a
release asset instead of committing it.

**Diffs that mean nothing.** `info.version` is the Moodle release, so every
site upgrade rewrites the file whether or not any function changed. Normalise
that field before comparing, or the pipeline reports an API change every time
Moodle is patched.

**Generation is not byte-stable.** At least one core function declares a
default value that is a live `time()` call, captured fresh on every build, so
two documents generated seconds apart are not identical. Compare on a
normalised copy, not on the raw bytes.

## What to do with it

- **Validate:** `redocly lint openapi.yaml` or `spectral lint openapi.yaml`.
- **Generate a client:** `openapi-generator-cli generate -i openapi.yaml -g <language>`.
- **Watch the API surface:** commit a normalised copy and let the pull request
  show what an upgrade added or removed.

A client generated from the default document cannot call Moodle as is: core's
REST protocol wants one URL and a PHP-bracket form body. With
`webservice_restful` installed, generate from `?protocol=restful` instead, see
[openapi-document.md](openapi-document.md#the-restful-protocol).

If a generator rejects the document, try `?version=3.0` first: plenty of
tooling still cannot read OpenAPI 3.1, and the plugin emits a 3.0 rendering of
the same catalog.

## Generating without a running site

The catalog is not derivable from source: `external_functions` is a database
table, populated at install and updated on upgrade. There is no static
analysis that produces it.

What you can do is install Moodle inside the job and throw it away —
`admin/cli/install_database.php` against a service container takes a couple
of minutes. That is how you produce a document for a Moodle version you do
not run anywhere, and how you would build a document per version across many
versions at once.

Two limits to plan around. The plugin declares Moodle 4.5 as its floor, so
earlier releases are out without a compatibility layer for the
`core_external` namespace. And a fresh install exposes core plus the plugins
Moodle bundles — if you need the catalog of a particular site, that comes
from the site, not from a clean install.
