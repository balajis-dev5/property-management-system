# Design decisions

Short notes on the choices in this template and why they went this way.

## Why JWT + rotating refresh tokens (and not Sanctum)?

Sanctum's SPA mode is the right call when the SPA and API share a domain —
it's session cookies with CSRF protection, done well. This template targets
the other case: a fully separate API consumed by SPAs and mobile clients,
where stateless bearer tokens keep every request cheap (no session lookup).

The risk with bearer tokens is lifetime. The mitigation here:

- **Access tokens live 15 minutes.** A leaked one has a small window.
- **Refresh tokens rotate on every use.** The presented token is revoked and
  a new one is issued inside the same "family" (started at login).
- **Reuse is treated as theft.** If a revoked token is presented again, two
  clients are holding tokens from one login — one of them is an attacker.
  The whole family is revoked; both parties must re-authenticate. See
  `RotateRefreshToken` and the `test_reusing_a_rotated_token_kills_the_whole_family` test.

## Why a hand-rolled 60-line JWT class?

The access token needs exactly `sub`, `exp`, `iat` and HS256. A dependency
that covers ten algorithms and JWK sets is more surface to audit than
`app/Support/Jwt.php` is. If RS256 or key rotation entered the picture,
switching to `firebase/php-jwt` behind the same class is a small change.

## Why Actions instead of service classes?

`IssueTokenPair`, `RotateRefreshToken`, `RevokeUserTokens` — one verb each.
A `TokenService` accumulates methods until nobody knows what depends on what.
One-verb classes are independently testable, greppable, and honest about
their dependencies (constructor injection shows exactly what each verb needs).

## Why roles → permissions instead of permissions on users?

Support answers "what can this user do?" by looking at one role instead of
auditing a per-user grant list. Route middleware checks permissions
(`permission:users.view`), never roles — so adding a "support" role later
means seeding a row, not editing routes. Policies add row-level rules
(ownership) on top; `ExamplePolicy::before` gives admins a bypass.

## Why one error envelope?

Clients write one error handler. `{ message, errors, code }` — `code` is
machine-readable and stable, `message` is for humans, `errors` is per-field.
The exception renderers in `bootstrap/app.php` guarantee the shape for 401,
403, 404, 422 and 429 alike; the tests assert it.

## Why the `examples` module?

Every future module copies its shape: route → FormRequest → thin controller →
Resource, policy for row-level access, factory + feature test. It's the
template's template.
