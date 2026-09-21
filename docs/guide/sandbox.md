# Sandbox

A sandbox document is a real document Autentique marks as a test: it is not
billed, it carries no legal validity, it cannot be verified, and Autentique
deletes it after a few days. Everything else behaves as it would in
production, including the messages sent to signers. **Do not use real documents
or real people's addresses in sandbox.**

There is no sandbox endpoint and no sandbox token: it is an argument of the
calls that accept it ([0006](/decisions/0006-sandbox-is-chosen-per-call)).

## Choosing it

```ini
# local and staging
AUTENTIQUE_SANDBOX=true
```

With that, every document is created in sandbox unless a call says otherwise:

```php
Autentique::newDocument('x')->sandbox()->send();          // sandbox, whatever the config says
Autentique::newDocument('x')->sandbox(false)->send();     // real, whatever the config says
```

**Leave it unset in production.** A forgotten variable there creates real
documents, which is the failure worth having: the opposite would create
documents with no legal validity, and nobody would notice until one was needed.

## Checking

`php artisan autentique:check` says whether documents are sandbox by default.
