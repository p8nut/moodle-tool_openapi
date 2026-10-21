# Documentation

Start at [Quick start](quick-start.md) if you have just installed the plugin,
or at [Access control](access-control.md) if you are deciding who should be
able to read the catalog.

## Using the plugin

- **[Quick start](quick-start.md)** — install, open the admin area, issue a
  token and make a first authorized request.
- **[Installation](installation.md)** — requirements, the three ways to
  install, what an upgrade does and what uninstalling removes.
- **[Access control](access-control.md)** — the four independent methods,
  what each one authorizes, and how a scope narrows what comes back.
- **[Tokens](tokens.md)** — issuing a plugin token, limiting it to a set of
  functions or a network, and what deleting one records.
- **[IP rules](ip-rules.md)** — allowing a whole address or range without a
  credential, and when that is and is not appropriate.
- **[The viewer](viewer.md)** — browsing the catalog, and what has to be
  enabled before Try it out can send a real request.

## Reference

- **[The endpoint](endpoint.md)** — every parameter, every response, every
  error code, with worked examples.
- **[The generated document](openapi-document.md)** — what the document
  contains, why each function gets a path of its own, how operations are
  grouped, and the `x-moodle-*` extensions.
- **[Privacy](privacy.md)** — the one field of personal data, how it is
  exported, and why deletion detaches instead of destroying.

## Beyond one site

- **[Automation](automation.md)** — pulling the document from a pipeline or the command line,
  keeping a snapshot under version control, and generating clients from it.

## When something goes wrong

- **[Troubleshooting](troubleshooting.md)** — the failures that look like
  plugin bugs and are configuration, with the check that tells them apart.
- **[Development](development.md)** — running the same checks CI runs, and
  the traps in doing so on Windows.
