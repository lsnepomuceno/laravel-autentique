# 0006: Sandbox is chosen per call, with the default in the config

**Status:** decided before the code, tracked by #11 and #12.

## Context

Autentique's sandbox is not an environment. There is no second endpoint and no
second token. It is an argument:

- `createDocument(sandbox: true, …)` creates a test document, which is not
  billed, carries no legal validity, and is deleted after a few days;
- `documents(showSandbox: true)` includes test documents in a listing, and
  `documents(onlySandbox: true)` lists nothing else. By default they are hidden.

`laravel-autentique-v2` made it a constructor argument, `new Document(true)`, so
one object could only ever create one kind of document.

## Decision

**Sandbox is an argument of the calls that accept it,** `sandbox()` on the
document builder and a named argument on the listing. **When the call says
nothing, the value of `autentique.sandbox` in the config is used,** false by
default.

An application sets `AUTENTIQUE_SANDBOX=true` in its local and staging
environments and never mentions sandbox in its code. A test that needs a real
document in staging, or a sandbox document in production, says so at the call.

## Consequences

- A production deployment with the variable unset creates real, billed
  documents. That is the right default: the opposite would make a forgotten
  variable produce documents with no legal validity, silently.
- The listing's default follows the same key: in an environment configured for
  sandbox, test documents are included.

## Alternatives rejected

| | Why not |
|---|---|
| A config value only | A staging environment could never create one real document to test the path end to end |
| A per call argument only | Every call site repeats an environment decision, and the one that forgets creates billed documents from a developer's machine |
| A client instance per mode, as in v2 | The mode is a property of a request, not of a client |

## Outcome

Shipped in #11 for creation: `PendingDocument::sandbox()` and the `$sandbox`
argument of `Api\Documents::create()`, both falling back to `autentique.sandbox`
when null. The listing's half of the decision lands with #12.
