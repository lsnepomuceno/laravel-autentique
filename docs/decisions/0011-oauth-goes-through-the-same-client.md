# 0011: OAuth goes through the same client, and the tokens are the application's

**Status:** decided with #21, before the code.

## Context

Autentique offers OAuth 2.0, Authorization Code with PKCE (S256), for an
application that acts on behalf of other people's accounts rather than its own.
The flow has three steps the package can help with:

1. an authorization URL, with a `state` and a PKCE challenge;
2. the callback, which carries a `code`, or an `error` such as `access_denied`,
   and the `state` back;
3. the token endpoint, `POST /oauth/token`, form encoded, which exchanges the code
   for an access token (15 days) and a refresh token (365 days), and later
   exchanges the refresh token for a new pair. **Each refresh replaces both**,
   and the old refresh token must not be used again.

The token endpoint is HTTP, not GraphQL. [Invariant 1](../spec/invariants.md)
says one class reaches the network.

A token without the scope an operation needs is not refused with HTTP 401. It
arrives as HTTP 200 with `errors[].message = "Unauthorized"`.

## Decision

**The token endpoint is reached through `GraphQL\Client`**, by a method of its
own on `Contracts\GraphQLClient`. The name of the contract is narrower than what
it now does, and that is the smaller cost: a second transport would be a second
thing `Http::fake()` has to reach and the fake has to replace.

**`Api\OAuth` covers the three steps**: `begin()` returns the URL together with
the `state` and the PKCE verifier to keep in the session; `callback()` checks the
`state` in constant time, turns an `error` into an exception, and exchanges the
code; `refresh()` exchanges a refresh token.

**`Autentique::withToken($accessToken)`** returns the whole API bound to another
token, for one call or a whole job. The configured token is untouched.

**Storing tokens is the application's.** Where, encrypted how, and refreshed
under which lock depend on the application, and the package does not guess.
The guide shows the shape, including the lock: two workers refreshing the same
pair at once leave one of them with a refresh token Autentique has already
replaced.

**A missing scope is its own exception**, `InsufficientScope`. It is recognised
by the exact message `Unauthorized`, capitalised, which is what the OAuth
documentation shows, while the error table's `unauthorized`, lower case, stays
`Unauthenticated`. That distinction rests on the documentation's spelling and is
among what the sandbox run for the release verifies.

## Alternatives rejected

| | Why not |
|---|---|
| A separate HTTP client for OAuth | a second transport, against invariant 1 |
| Storing tokens in the database or the cache | an application's schema and encryption policy are not the package's to choose |
| Refreshing automatically on 401 | without the application's lock and storage, an automatic refresh is how refresh tokens get burnt |
| One exception for a rejected token and a missing scope | the fixes differ: refresh or reauthorize, against ask for more scopes |

## Outcome

Shipped in #21 as decided: `Contracts\GraphQLClient::oauthToken()` posts the form
through the same client, with no bearer token; `Api\OAuth` begins, handles the
callback, exchanges and refreshes; `Autentique::withToken()` binds the whole API
to another token; `InsufficientScope` is recognised by the capitalised
`Unauthorized`.

The verifier is 96 characters from `Str::random()`, inside RFC 7636's 43 to 128,
and the challenge is checked against the RFC's own example. The fake answers the
token endpoint too, so an application's OAuth flow is testable without
Autentique.

**Experimental in 1.0.0.** No OAuth application was registered for the release,
so the flow, and the capitalised `Unauthorized` that tells `InsufficientScope`
apart, have not run against Autentique. It stays outside the semantic versioning
promise until they have ([public API](../spec/public-api.md#experimental)).
