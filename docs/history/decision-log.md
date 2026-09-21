# Decision log

Questions that were put, and how they were answered. Append-only: an entry is
never edited once its answer is recorded, only superseded by a later one.

Where a decision produced a design still in force, the reasoning lives in
[docs/decisions/](../decisions/README.md). This log records *that it was
decided*, and when. The two are not duplicates: a decision record explains a
design, a log entry dates a choice.

## Before the first line of code, 2026-09-21

The package replaces `lsnepomuceno/laravel-autentique-v2`, which stopped at
Laravel 8 in 2021 unfinished and will be archived. The plan was drawn from a
full reading of the Autentique API v2 documentation, in its English, Portuguese
and Corporate spaces, and of the v2 source. The work is tracked by #1.

These questions were put with a recommendation, and the recommendation was taken
when the work was started on it.

| # | Question | Decision |
|---|---|---|
| 1 | One package, or a framework free core plus a Laravel adapter, like signet-pdf and laravel-a1-pdf-sign? | **One package** ([0001](../decisions/0001-one-laravel-package.md)) |
| 2 | Which floor? | **PHP 8.4.1, Laravel 13**, the same as laravel-a1-pdf-sign ([0002](../decisions/0002-php-and-laravel-floor.md)) |
| 3 | Keep the operations in `.graphql` files? | **Yes**, with variables only, an enum naming each one, shared fragments, and validation against the introspected schema ([0003](../decisions/0003-operations-live-in-graphql-files.md)) |
| 4 | Corporate endpoint and OAuth in 1.0.0? | **After**, as minor releases: #20 and #21 |
| 5 | Package name, namespace, first version | **`lsnepomuceno/laravel-autentique`**, `LSNepomuceno\LaravelAutentique`, starting at **1.0.0**. A new name rather than a v3 of the old package, because nothing of its API survives |
| 6 | Language of the code and the documentation | **English**, as in the sibling packages |

## Open

| # | Question | Needed by |
|---|---|---|
| 7 | A sandbox token to introspect the schema once, run by the maintainer so it never leaves the machine | #34. #9 shipped against a schema assembled from the documentation in the meantime |

## Superseded during the work

| # | Question | Decision |
|---|---|---|
| 4 | Corporate endpoint and OAuth in 1.0.0? | **In 1.0.0 after all.** Both were built before the first tag, in #20 and #21, so shipping them as minor releases later would have meant holding finished work back. Entry 4 above stands as what was decided at the time |

## At the release, 2026-09-21

| # | Question | Decision |
|---|---|---|
| 8 | Tag 1.0.0 with the schemas assembled and no run against Autentique? | **No: `1.0.0-rc.1`.** A stable major promises its API, and eleven type names and one exception's trigger rest on the documentation's spelling. Everything else on #19's checklist was done; the rest needs a token and the maintainer's Packagist account, and is #48 |

