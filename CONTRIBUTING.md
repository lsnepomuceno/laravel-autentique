# Contributing

Contributions are **welcome** and will be fully **credited**.

We accept contributions via Pull Requests on [GitHub](https://github.com/lsnepomuceno/laravel-autentique).

## Pull Requests

- **Nothing is pushed to `main`.** Every change arrives through a pull request, without
  exception, and that includes documentation, a one-line typo and a release note. Branch,
  push the branch, open the pull request. A `pre-push` hook refuses a push that lands on
  `main` before it leaves your machine, because the server-side rule can be bypassed by
  anyone with the permission to bypass it. Pushing a release tag is the one thing that
  goes to the remote directly.

- **Code style is [PER-CS](https://www.php-fig.org/per/coding-style/)**, enforced by
  [Pint](https://laravel.com/docs/pint). Run `composer lint` to apply it; CI runs
  `vendor/bin/pint --test` and fails on any difference.

- **Static analysis must stay clean.** PHPStan runs at `level: max` with no baseline, so the
  gate is "no errors", not "no new errors". Run `composer analyse`.

- **Reach for Laravel before writing a helper.** The package only runs inside the framework,
  so `Str`, `Arr`, `Http`, `Storage` and the rest are already there. See
  [the conventions](docs/spec/conventions.md).

- **A set of values is an enum, not a group of class constants.**

- **Operations live in `.graphql` files and carry variables only.** Never build GraphQL text
  in PHP. See [how to add an operation](docs/spec/graphql-operations.md).

- **Add tests!** Your patch won't be accepted if it doesn't have tests. No test may reach
  Autentique: fake the response.

- **Document any change in behaviour, in every place that describes it.** The list is
  enumerated below precisely so it cannot be read as "the obvious ones".

- **Consider our release cycle.** We follow [SemVer v2.0.0](http://semver.org/). Randomly
  breaking public APIs is not an option.

- **One pull request per feature.** If you want to do more than one thing, send multiple
  pull requests.

- **Send coherent history.** Make sure each individual commit in your pull request is
  meaningful.

## Every place that documents behaviour

A change to what the package does is not finished until each surface below says the same
thing. Some are gated, some are not, and the ones that are not are where drift happens.

| Surface | When it changes | Gate |
|---|---|---|
| **`README.md`** | any public API, and anything a new user should know | `tests/Project/ArchTest.php` fails when a contract method is missing from it |
| **`CHANGELOG.md`** | anything a consumer will notice | none: review |
| **`UPGRADE.md`** | anything a consumer has to change, under `## Unreleased` | none: review |
| **`docs/decisions/`** | a decision changes, or a record's outcome is written | `tests/Project/SpecTest.php` checks references resolve |
| **`docs/spec/invariants.md`** | a rule that breaks the product when violated | as above |
| **`docs/spec/conventions.md`** | how code here is written | as above |
| **`docs/spec/public-api.md`** | the exposed surface | as above |
| **`docs/spec/quality-policy.md`** | a gate moves, or a floor does | as above |
| **`docs/spec/graphql-operations.md`** | how an operation is added, loaded or checked | as above |
| **`docs/guide/`** | any public behaviour | the site build fails on a dead link or a page missing from the sidebar |
| **`ARCHITECTURE.md`** | the shape of the package, since it is the index | none: review |
| **`CLAUDE.md`** | anything an agent working here has to know before touching `src/` | none: review |
| **Class docblocks** | the class stops doing what its docblock says | two mechanical rules in `tests/Project/ArchTest.php` |
| **The release notes** | every tag, on GitHub | none: review |

### The site is built from a tag, not from a branch

The documentation site lives in `docs/` and is published to GitHub Pages **from a tag**,
never from a branch. A pull request builds it and throws the result away, which keeps the
gate without publishing behaviour that is merged and not yet released.

## Running the checks

``` bash
$ composer check       # style, static analysis, dependencies, tests and type coverage, the same as CI
$ composer test        # tests only
$ composer lint        # fix code style
$ composer analyse     # static analysis only
$ composer test:types  # type coverage across src/
$ composer test:cov    # line coverage (needs pcov or xdebug)
```

Tests are written with [Pest](https://pestphp.com). `tests/Project/` holds the structural
rules, which run with the rest of the suite.

Helpers shared between test files belong in `tests/Pest.php`. Defined anywhere else they are
invisible to the other files once the suite runs with `--parallel`.

### The Git hooks

[Husky](https://typicode.github.io/husky/) installs two:

``` bash
$ npm install     # once, to install them
```

| Hook | Does |
|---|---|
| `pre-commit` | runs Pint over the staged PHP files and stages the result, then PHPStan, so neither style nor static analysis is what a pull request gets blocked on |
| `pre-push` | refuses a push that lands on `main`. Tags are unaffected, so publishing a release still works |

Node is only used for the hooks and the documentation site: it is not a dependency of the
package. If your machine runs a PHP older than 8.4, `pre-commit` detects it and routes
through the Docker service described below.

Both hooks can be bypassed with `--no-verify`. That is deliberate, for stashing work in
progress; on `pre-push` it means you are choosing to push to `main` and saying so.

### Docker

The package targets PHP 8.4+ and Laravel 13. If your machine runs an older PHP, `.docker/`
reproduces either cell of the CI matrix:

``` bash
$ docker compose -f .docker/compose.yaml run --rm php composer check
$ docker compose -f .docker/compose.yaml run --rm php85 composer check
```

Each service keeps `vendor/` in its own named volume, so switching PHP versions does not
clobber the other install. The `vendor/` your editor indexes is never touched by those runs;
refresh it after a dependency change with `composer install --ignore-platform-reqs`.

### The documentation site

``` bash
$ cd docs/.vitepress
$ npm install
$ npm run dev      # a local preview
$ npm run build    # what CI runs
```

**Happy coding**!
