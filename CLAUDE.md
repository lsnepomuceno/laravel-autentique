# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

**A Laravel client for the [Autentique](https://docs.autentique.com.br/api) GraphQL API v2**, published as `lsnepomuceno/laravel-autentique`: documents, signers, folders, organizations and webhooks, as typed value objects behind a facade.

It replaces `lsnepomuceno/laravel-autentique-v2`, unfinished since 2021 and to be archived. What that package got wrong is recorded in `docs/history/from-laravel-autentique-v2.md`, and most of the rules below exist because of it.

**Nothing is released yet.** The road to 1.0.0 is tracked in issue #1, one issue per deliverable. Read the issue before starting one: each lists its scope, its tests and the documentation it must update.

The invariants are imported rather than summarised, so they are in context for every session:

@docs/spec/invariants.md

| Read | For |
|---|---|
| `docs/spec/public-api.md` | what this package exposes, and what changing it costs |
| `docs/spec/quality-policy.md` | the gates, and why each sits where it does |
| `docs/spec/conventions.md` | how the code is written |
| `docs/spec/graphql-operations.md` | how an operation is added or changed |
| `docs/decisions/` | why the design is what it is: one numbered file per decision |
| `docs/history/` | how the package got here |

`ARCHITECTURE.md` is the index. When you change behaviour a decision record justifies, update that record's outcome section too.

It is a sibling of `lsnepomuceno/signet-pdf` and `lsnepomuceno/laravel-a1-pdf-sign`, and follows their organization, gates and documentation layout. When in doubt about how something is done here, laravel-a1-pdf-sign is the reference.

## Commands

```bash
composer check          # everything CI runs: pint --test, phpstan, deps, pest, type coverage
composer test           # vendor/bin/pest --fail-on-skipped
composer analyse        # PHPStan level max, no baseline
composer lint           # Pint (PER-CS); append --test to only check
composer deps           # unused/shadow dependency report
composer test:cov       # line coverage (needs pcov or xdebug)
composer test:types     # type coverage, gated at 100%

vendor/bin/pest tests/Project/ArchTest.php           # single file
vendor/bin/pest --filter="refuses a request"         # single test
```

Tests run on Orchestra Testbench, not a host app, and **never reach Autentique**: `tests/TestCase.php` calls `Http::preventStrayRequests()`, so a request nobody faked fails. No token is needed and none belongs in the repository.

Shared helpers live in `tests/Pest.php`. A helper defined inside one test file is invisible to the others under `--parallel`, which fails as `Call to undefined function`.

### Docker

```bash
docker compose -f .docker/compose.yaml run --rm php composer check     # PHP 8.4
docker compose -f .docker/compose.yaml run --rm php85 composer check   # PHP 8.5
```

The Compose project name is pinned to `laravel-autentique`, so the vendor volumes are not shared with the sibling repositories, whose Compose files also live in a `.docker` directory.

A Husky `pre-commit` hook formats staged PHP files with Pint and runs PHPStan, and `pre-push` refuses a push to `main` (`npm install` to enable them).

### The documentation site

```bash
cd docs/.vitepress && npm install && npm run dev     # http://localhost:5175/laravel-autentique/
npm run build                                        # fails on a dead link or a page missing from the sidebar
```

## Architecture

Everything resolves through the container. `LaravelAutentiqueServiceProvider` merges `config/autentique.php` and binds `Contracts\Autentique` to `AutentiqueManager` as a singleton, behind the `Autentique` facade.

The shape the package is being built towards, each part landing with its issue:

| Part | Decision |
|---|---|
| Operations in `src/Resources/graphql/`, one per file, named by an enum, variables only, validated against the introspected schema | 0003 |
| One transport, `GraphQL\Client` on Laravel's HTTP client, for JSON and multipart uploads | 0004 |
| Exceptions per fault under one base, error codes as an enum; GraphQL errors arrive with HTTP 200 | 0005 |
| Sandbox per call, default from config | 0006 |
| Webhooks verified on the raw body with HMAC SHA-256, dispatched as a Laravel event | 0007 |
| Typed, immutable value objects in and out | 0008 |

## Quality gates

`composer check` must pass before any commit.

- **PHPStan `level: max`, no baseline.**
- **Type coverage gated at 100%.**
- **Dead code is refused**, by PHPStan plus `tests/Project/DeadCodeTest.php`.
- `tests/Project/ArchTest.php` enforces structural rules, so read it before adding a class. Several reach namespaces that are still empty, on purpose.
- `tests/Project/SpecTest.php` fails on a cited path or package symbol that does not resolve, and on an orphan document.
- `tests/Project/DistributionTest.php` asks `git archive` what a release contains.

Patches are expected to come with tests.

## Commits

Conventional Commits, in English (`feat:`, `fix:`, `chore(deps):`, `test:`, `docs:`, `build:`, `refactor:`). Breaking changes use `!` and a `BREAKING CHANGE:` footer.

**Never add a `Co-Authored-By` trailer**, and never mark a commit, pull request or issue as produced by an assistant.

**Never push to `main`.** Every change arrives through a pull request, with no exception and no size below which it stops applying. The only thing pushed to the remote directly is a release tag. A `pre-push` hook refuses it locally; treat the hook as a backstop, not as the rule.

## Conventions

The full text is `docs/spec/conventions.md`. The short version:

- **Laravel first.** Before writing a helper, check whether the framework already has it.
- **Enums, not class constants**, string backed with the API's own value.
- **No em dashes**, and no en dash or spaced hyphen used as a pause. Use a comma, a colon, parentheses, or two sentences.
- **Everything in English.**
- `final readonly` classes by default; named arguments at call sites; `#[\Override]`; `#[\SensitiveParameter]` on every token, secret and password.
- **Every file declares `strict_types=1`.**
- **No parentheses around `new` when chaining.**
- **Never cite a file that does not exist, and write it first.** A decision record comes before the code that cites it.
- `@throws` docblocks are maintained on every method that can throw.
