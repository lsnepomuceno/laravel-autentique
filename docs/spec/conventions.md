# Conventions

Rules about how the code is written, as opposed to what it must do. The rules
that break the product live in [the invariants](invariants.md); these break the
codebase slowly instead, which is why they are written down rather than left to
whoever reviews.

Where a rule can be checked by a machine, it is, and that is noted.

---

# 1. Laravel first

**This package only runs inside Laravel. Before writing a helper, check whether
the framework already has it, and use that**
([0001](../decisions/0001-one-laravel-package.md)).

1. **Look in the framework first.** `Str`, `Arr`, `Collection`, `Http`,
   `Storage`, `File`, `Cache`, `Event`, `Carbon`, the `Illuminate\Contracts\*`
   interfaces.
2. **If it exists there, use it**, even when the native call is shorter.
3. **If it does not, write it**, put it in `src/Support/`, and say in the
   docblock what the framework does not provide. A helper whose docblock cannot
   answer "why is this not `Str::something`" should not exist.

| Instead of | Use | Why |
|---|---|---|
| `curl_*`, Guzzle, `file_get_contents` over a URL | the package's GraphQL client, on `Illuminate\Http\Client\Factory` | [invariant 1](invariants.md): one transport, faked by `Http::fake()` |
| building a multipart body by hand | `PendingRequest::attach()` | it is the GraphQL multipart request, and it streams |
| `json_decode` on a response | `Response::json()` | one decoding, with the client's error handling |
| `date()`, `DateTime` | `CarbonImmutable` | the type Laravel applications already pass around |
| `array_map` / `array_filter` / `array_merge` chained over one value | `collect()` | one pipeline instead of three nested calls, when it genuinely reads better |
| a hand written `get($array, 'a.b.c')` | `Arr::get()` | |
| reading config with a cast and a default | `Illuminate\Contracts\Config\Repository`, injected | fakeable, and the same everywhere |
| a hand rolled `toArray()` | `Illuminate\Contracts\Support\Arrayable` | |

## Do not reach for

| Keep the native call | Why |
|---|---|
| `hash_hmac`, `hash_equals` | the webhook signature is an HMAC compared in constant time, and `Hash::` is password hashing, a different thing entirely |

---

# 2. Enums, not class constants

**A closed set of values is an enum.** A class constant is for the case where
exactly one value can ever exist.

Autentique's API is made of closed sets: actions, delivery methods,
verification types, position elements, reminders, document statuses, webhook
events, error codes. Each is an enum here, string backed with the API's own
value, so `Action::from('SIGN')` reads a response and `->value` writes a
request.

The test is **"could a second value of this kind ever be right?"** If yes, it is
an enum today, because the sibling arrives later and arrives as a constant
beside the first one.

*Enforced by* `tests/Project/ArchTest.php`, which requires every enum under
`Enums\` to be string backed.

---

# 3. How PHP is written here

- **Everything in English:** code, comments, docblocks, commit messages,
  documentation.
- **No em dashes**, and no en dash or spaced hyphen used as a pause. Not in
  prose, comments, docblocks, commit messages, documentation, pull request
  bodies or issue replies. Use a comma, a colon, parentheses, or two sentences.
- PER-CS via Pint; grouped `use` imports with braces.
- `final readonly` classes by default; fluent setters returning a new instance;
  named arguments at call sites.
- Modern PHP is expected: typed class constants, `#[\Override]`,
  `#[\SensitiveParameter]` on every token, secret and password argument.
- **No parentheses around `new` when chaining.** The floor is PHP 8.4, so
  `new Reader()->parse($json)` is the plain form.
- `@throws` docblocks are maintained on every method that can throw.
- **Nullable config backed arguments mean "use the configured default"**, rather
  than forcing every call site to repeat an infrastructure decision
  ([0006](../decisions/0006-sandbox-is-chosen-per-call.md) is the first).

---

# 4. A docblock documents the thing under it

## Never leave two docblocks in a row

When a method is inserted between a docblock and the method it described, the
first block ends up attached to the newcomer and the original method is left
undocumented. Every tool that reads docblocks then reports the wrong thing about
two methods, and the diff looks like a pure addition.

## Never leave a `@param` naming a parameter that is gone

A docblock that documents nothing is a comment nobody reads. A docblock that
documents the wrong thing is worse than no docblock, because it is believed.

*Both enforced by* `tests/Project/ArchTest.php`.

## Every file declares strict types

`declare(strict_types=1);` at the top of every PHP file in `src/`, `tests/` and
`config/`.

*Enforced by* `pint.json`, which writes the declaration, and by
`tests/Project/ArchTest.php` twice: an arch expectation over `src/`, and a file
walk for `tests/` and `config/`, whose files declare no class.

## Never cite a file that does not exist, and write it first

A comment, docblock or document may only name a path that resolves **at the
moment it is written**. Not "will exist when the record is written up", not
"exists on the branch that has not landed": now.

**The record comes first.** When a change wants a decision record or a
specification section, that file is created before the code referring to it, in
the same change and earlier in it.

*Enforced by* `tests/Project/SpecTest.php`, which walks every `.php`, `.md`,
`.yml` and `.graphql` file and resolves every documentation path any of them
cites. **Symbols are checked too**: a comment naming a class or a
`Class::member` of `LSNepomuceno\LaravelAutentique` must resolve.
`docs/history/` and `UPGRADE.md` are exempt, since recording what the package
used to be is their job.

## What is deliberately not checked

Whether the prose is *true*. No tool can, which is why the rules above are
narrow: they catch the failures that are mechanical, and leave the rest with
whoever changed the code.
