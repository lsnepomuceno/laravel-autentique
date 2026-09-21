# Architecture

Documentation is split by **lifecycle**, not by topic. Each part below changes
at a different rate, and mixing them in one document is how a document drifts
away from the code it describes.

`tests/Project/SpecTest.php` fails when a reference into any of these stops
resolving, and when a file under `docs/` is reachable from none of them.

## Living: must be true of the code today

| | |
|---|---|
| [docs/spec/invariants.md](docs/spec/invariants.md) | Rules that break the product or the project when violated. **Read before touching the transport, the operations, the config file or the dependency list.** |
| [docs/spec/public-api.md](docs/spec/public-api.md) | What the package exposes, and what changing it costs. |
| [docs/spec/quality-policy.md](docs/spec/quality-policy.md) | The gates a change has to pass, and why each sits where it does. |
| [docs/spec/conventions.md](docs/spec/conventions.md) | How the code is written: reach for the framework before writing a helper, and use an enum where a set of values exists. |
| [docs/spec/graphql-operations.md](docs/spec/graphql-operations.md) | How an operation is added or changed, and how the schema is refreshed. |

## Decisions: why the design is what it is

[docs/decisions/](docs/decisions/README.md), one numbered file per decision.
The number is the identifier, so it survives the next reorganisation.

Each carries an outcome section, written when what it decided ships. A decision
record whose outcome is never written back is how a document drifts away from
the code it describes.

## History: frozen, kept because it answers what the code cannot

| | |
|---|---|
| [docs/history/decision-log.md](docs/history/decision-log.md) | Questions that were put and when they were answered. |
| [docs/history/from-laravel-autentique-v2.md](docs/history/from-laravel-autentique-v2.md) | What the previous package was, what was wrong with it, and what this one keeps. |

## The site

`docs/` is a VitePress site, built on every pull request that touches it and
published to GitHub Pages from a tag. `docs/guide/` is the consumer
documentation; everything else on this page is published as it is, because the
site's source directory is this one.

The guide's index is `docs/.vitepress/sidebar.ts`, which reads the directory
and refuses a list that disagrees with it. That is why the guide is not listed
here: a second index is a second thing to forget.

## For consumers, not contributors

[README.md](README.md) is the front door. [CHANGELOG.md](CHANGELOG.md) lists
every release.
