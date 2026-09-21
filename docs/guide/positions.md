# Positions

A position is where something is stamped on the document once a signer acts.
Without any, the document can still be signed; nothing is drawn on its pages.

```php
use LSNepomuceno\LaravelAutentique\Data\Input\Position;

Position::signature(x: 50, y: 90);          // page 1
Position::initials(x: 90, y: 95, page: 3);
Position::name(x: 50, y: 93);
Position::date(x: 50, y: 96);
Position::cpf(x: 50, y: 99);                // ignored on documents outside Brazil
```

`x` and `y` are percentages of the page's width and height, from 0 to 100, and
pages start at 1. For a rotated or larger stamp:

```php
new Position(x: 50, y: 90, page: 1, element: PositionElement::Signature, angle: 90, scale: 1.5);
```

The angle goes from 0 to 360 degrees and the scale from 0.5 to 4. Anything
outside those ranges is refused before sending.

Signers cannot move a position, and positions cannot be changed after the
document is created: Autentique documents no way to.
