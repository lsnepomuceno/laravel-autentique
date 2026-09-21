# Quality policy

The gates a change has to pass, and why each sits where it does.

## `composer check`, and it must pass before any commit

```
pint --test        code style, PER-CS, strict types
phpstan            level max, no baseline
composer deps      unused and shadow dependencies
pest               the suite, --fail-on-skipped
pest --type-coverage --min=100
```

CI runs exactly this list and nothing else, on every cell of the matrix. **A
gate CI runs and the documented local command does not is a gate discovered on
a pull request**, which is how laravel-a1-pdf-sign learnt to put type coverage
in the list.

## PHPStan at level max, with no baseline

There has never been a baseline. The gate is "no errors", not "no new errors",
and the only ignores are for Pest's untypeable fluent API, scoped to `tests/*`.

`config/` is outside PHPStan's paths: Larastan reads it as a file outside the
application's config directory and reports every `env()` call in it, which is
exactly where `env()` belongs. Its shape is checked by the suite instead
([invariant 4](invariants.md)).

## Dead code is refused

PHPStan reports a private method nobody calls and a property only ever written.
A local variable assigned and never read is what it misses, so
`tests/Project/DeadCodeTest.php` walks the tree with `token_get_all()`.

It under-reports on purpose, and **unused public methods are deliberately not
checked**: the API exists for consumers whose code is not in this repository.

## Structure

`tests/Project/ArchTest.php` carries the rules that are about shape rather than
behaviour:

- only the GraphQL client reaches the network ([invariant 1](invariants.md))
- no GraphQL operation is written in PHP ([invariant 2](invariants.md))
- every secret parameter carries `#[\SensitiveParameter]` ([invariant 3](invariants.md))
- contracts are interfaces, facades extend Laravel's and are final
- value objects are readonly and final, enums are string backed
- every exception extends the package's base
- console commands stay in `Commands`
- no debug leftovers, no weak hashing, no `eval`
- every file declares `strict_types=1`
- docblocks are never stacked and document parameters that exist
- every contract method appears in the README

Several of these reach namespaces that are still empty. They are written before
the code they constrain, so the first class to land there lands under them.

`tests/Project/SpecTest.php` fails on a cited path or symbol that does not
resolve, and on a document no index reaches.

`tests/Project/DistributionTest.php` asks `git archive` what a release contains,
so nothing built for testing ships.

## The suite

Testbench, grouped by what it covers:

| Directory | Covers |
|---|---|
| `tests/Container` | bindings, the config, the stray request guard |
| `tests/GraphQL` | the operation files, the enum naming them, the loader |
| `tests/Project` | the structural rules above |

Each feature adds its own directory as it lands.

**Nothing skips.** `composer test` carries `--fail-on-skipped`, because every
check has to run somewhere and a skip is how one quietly stops.

**Nothing reaches Autentique.** Every request is faked, and one that is not
fails the test ([invariant 5](invariants.md)). Responses are fixtures copied from
the API documentation or recorded from the sandbox, stored beside the tests.

## The matrix

PHP 8.4 and 8.5 against Laravel 13
([0002](../decisions/0002-php-and-laravel-floor.md)). The workflow, the
`php` constraint in `composer.json` and the compatibility table in the README
must agree.

## What a patch is expected to carry

A test, and an update to every surface `CONTRIBUTING.md` enumerates that
describes the behaviour it changes. `tests/Project/ArchTest.php` is worth
reading before adding a class, since several of its rules constrain where things
live.
