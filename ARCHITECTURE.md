# Architecture

Documentation is split by **lifecycle**, not by topic. Each part changes at a
different rate, and mixing them in one document is how a document drifts away
from the code it describes.

`tests/Project/SpecTest.php` fails when a reference into any of these stops
resolving, and when a file under `docs/` is reachable from none of them.

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
