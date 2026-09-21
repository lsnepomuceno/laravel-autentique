# 0002: PHP 8.4.1 and Laravel 13 as the floor

**Status:** decided before the code, tracked by #2 and #3.

## Context

The floor was surveyed in full on 2026-08-05 for laravel-a1-pdf-sign, and
nothing in it has moved since
([laravel-a1-pdf-sign 0005](https://github.com/lsnepomuceno/laravel-a1-pdf-sign/blob/main/docs/decisions/0005-php-and-laravel-floor.md)).
The short version:

- **Supporting PHP 8.5 decides the Laravel floor.** Only Laravel 12 and 13 reach
  8.5, and they are also the only two still under security support.
- **Laravel 12 cannot be installed with the toolchain.** It pins
  `symfony/process ^7.2`, and Pest 5 needs `^8.1`.
- **PHP 8.4 keeps the toolchain on one Pest major.** An 8.3 floor would force
  Pest 4 on one job and Pest 5 on the others.

laravel-a1-pdf-sign later moved its floor from 8.4 to 8.4.1 because of a
dependency. That reason does not apply here; the floor is kept identical anyway,
so the two sibling packages install on exactly the same runtimes and an
application using both meets no surprise.

## Decision

| | |
|---|---|
| PHP | `>=8.4.1 <8.6` |
| Laravel | 13 |
| Test harness | Pest 5 on Orchestra Testbench 11 |

The CI matrix is PHP 8.4 and 8.5 against Laravel 13.

## Consequences

- Modern PHP is available and expected: property hooks, `new` without
  parentheses when chaining, `#[\Override]`, `#[\SensitiveParameter]` on the
  token and the webhook secret.
- An application still on Laravel 12 cannot install the package. Laravel 12's
  security support ends on 2027-02-24, and the package does not exist yet, so
  nothing is taken away from anyone.
- The ceiling `<8.6` is raised deliberately, by a pull request that runs the
  suite on the new version, rather than by default.

## Alternatives rejected

| | Why not |
|---|---|
| Laravel 12 and 13 | Laravel 12 cannot be installed beside Pest 5 |
| PHP 8.3 | Two Pest majors in one matrix, to reach a version whose active support ended on 2025-12-31 |

## Outcome

Shipped as decided, and CI ran PHP 8.4 and 8.5 against Laravel 13 on every pull
request from #26 onwards. PHP 8.4 syntax is used throughout: `new` without
parentheses when chaining, typed class constants, `#[\Override]`,
`#[\SensitiveParameter]`, `array_all()`.

The one thing the record did not foresee is memory: type coverage runs PHPStan
inside the Pest process and outgrew the 128M a stock `php.ini` allows, so
`composer test:types` raises it.
