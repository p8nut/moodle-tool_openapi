# Access control

Four independent methods decide who may read the OpenAPI document. They are
composed with OR: a request is authorized if **any enabled method** accepts
it, and refused if none does. With nothing enabled -- which is how the
plugin installs -- every request is refused.

They govern **one URL and nothing else**:

```
<site>/admin/tool/openapi/openapi.php
```

The plugin's own pages -- the viewer, the downloads, these settings -- answer
to the `tool/openapi:manage` capability instead, so an administrator can
always reach the catalog with every method switched off. The Access control
page states this at the top, with the address for your site.

Each method also answers a second question: *how much* of the catalog the
caller gets. That is the scope.

## Moodle session

Authorizes a logged-in, non-guest user through the browser session they
already have. Useful for letting developers on the site read the catalog
without issuing them a credential.

Two capabilities, both in the system context:

- `tool/openapi:viewfullcatalog` grants the whole catalog.
- `tool/openapi:view` grants one service at a time, and **only** with
  `?service=<shortname>` in the request -- without it there is nothing to
  authorize, because a capability is a boolean and cannot carry a function
  list the way a token or an IP rule can. A request naming a service that
  does not exist is refused, not answered empty.

There is nothing to configure here beyond the capabilities, which is why the
method has no cog on the access control page -- assign them from **Site
administration → Users → Permissions → Define roles**.

## IP address

Authorizes a request coming from an address matched by an enabled IP rule,
with no credential at all. Intended for a server on a network you control.

A rule is an address (`192.0.2.1`) or a CIDR range (`192.0.2.0/24`), several
allowed per rule when comma-separated. Matching uses Moodle's own
`address_in_subnet()`, the same function core uses for webservice token IP
restrictions.

Scope: each rule can be limited to a chosen list of functions, or left
unrestricted for the full catalog.

> Anyone able to reach the site from a listed address is authorized. Do not
> list an address you do not control, and do not list a shared egress
> address such as an office NAT gateway unless you accept that everyone
> behind it is included.

Managing rules: [ip-rules.md](ip-rules.md).

## Plugin token

Authorizes a request carrying `Authorization: Bearer <token>`, where the
token was issued from this plugin's Tokens page.

Only a SHA-256 hash of the token is ever stored, so the plaintext is shown
exactly once, immediately after creation, and cannot be recovered
afterwards. Losing it means issuing a new token.

A token can be limited to a list of functions, and independently limited to
an address or CIDR range, exactly as a Moodle webservice token can.

Deleting a token removes the row outright -- there is no revoked state
lingering in the table. The lasting record is in the site log: this plugin
logs a `token_created` and a `token_deleted` event, neither of which ever
carries the token or its hash.

Issuing and restricting them: [tokens.md](tokens.md).

## Existing webservice token

Authorizes a request carrying a token this site already issued for its web
services, so an integration that has one does not need a second secret just
to read the documentation. It is passed as `?wstoken=<token>`, the same way
Moodle's own REST endpoint takes it -- not as a `Bearer` header, which is
this plugin's own tokens.

The token is accepted only when Moodle's own REST endpoint would accept it:
web services enabled, token not expired, request from an address its IP
restriction allows, session still open for a session-bound token, user
confirmed, not suspended and not `nologin`, service enabled, and the
service's required capability and allowed-users list (with their own expiry
and IP restriction) satisfied. Maintenance mode is the one check left out:
reading documentation runs no function.

Scope: always exactly that token's service functions, computed live from
`external_services_functions`. Never the full catalog, not even for an
administrator's token. Enabling this method therefore adds no authorization
surface: whoever holds such a token could already call those functions.

The tokens themselves are created and revoked on Moodle's own page (**Site
administration → Server → Web services → Manage tokens**), which is where
this method's cog leads.

## Scopes and the `service` parameter

`?service=<shortname>` narrows the document to one external service. It is
applied *after* authorization, and on top of the caller's own scope: asking
for a service the caller's scope excludes returns the intersection, which
may be empty. It is never a way to see more than the scope allows.

A request that is not authorized is refused before the service is even
looked at, so an unauthorized caller cannot use the error to discover which
services exist.
