# 0010: Corporate is one more area of the API, on its own endpoint

**Status:** decided with #20, before the code.

## Context

The Corporate plan adds a second GraphQL endpoint,
`https://api.autentique.com.br/v2/graphql/corporate`, documented as "an
extension of the API v2". It manages child organizations, their members, their
plans, their webhook endpoints and their API usage. Seventeen operations, and
one of them, `organizations`, shares its name with a standard operation while
meaning something else: the child organizations rather than the user's own.

It also adds options to operations of the standard endpoint: per signer
`type: QUALIFIED`, `configs.session_behavior` and `configs.prefilled_fields`.

Two things are sensitive in a way nothing else in the package is:

- `organizationMembers` and the member mutations return each member's **API
  token**, which acts as that member;
- `createEndpoint` returns the **webhook secret** of the endpoint it creates.

## Decision

**Corporate is one more area, `Autentique::corporate()`, sent through the same
client.** Its operation files live under `src/Resources/graphql/corporate/`, its
enum cases say `Endpoint::Corporate`, and the client reads the URL from
`autentique.corporate_url`. The same token, the same transport, the same
exceptions, the same fake.

**Its schema is its own**, `tests/Resources/schema-corporate.graphql`, and its
operations are validated against it, not against the standard one.

**The Corporate signer options are methods on `Signer`**, because they are sent
by `createDocument` on the standard endpoint; a document created by a
non-Corporate account with one of them is refused by Autentique, not here, since
the package cannot know the plan.

**A member's token and an endpoint's secret are returned, and never logged.**
They are what the operations exist to return, so hiding them would make the
operations useless; the value objects carry them, and `#[\SensitiveParameter]`
keeps them out of traces.

## Alternatives rejected

| | Why not |
|---|---|
| A second package | the same client, the same exceptions, the same fake, and a second release for every fix |
| A second client class | a second transport, which [invariant 1](../spec/invariants.md) forbids |
| Validating Corporate operations against the standard schema | they are not in it, and one of them shares a name with a standard operation |
| Refusing Corporate signer options without a Corporate plan | the package does not know the plan, and asking would cost a request |

## Outcome

Shipped in #20 as decided: seventeen operation files under
`src/Resources/graphql/corporate/`, `Api\Corporate`, the Corporate signer options
on `Signer`, and `tests/Resources/schema-corporate.graphql`, assembled like the
standard one and just as provisional (#34).

**Webhook endpoints are checked before sending**, which the record did not
foresee. The Corporate enum of registrable events lacks two events the webhook
table documents, and an endpoint listening to one kind of resource ignores the
others' events. Both are refused with `InvalidInput`, so an endpoint is never
created listening to less than it was asked to.
