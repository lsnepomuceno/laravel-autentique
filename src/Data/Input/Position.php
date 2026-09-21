<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data\Input;

use LSNepomuceno\LaravelAutentique\Enums\PositionElement;
use LSNepomuceno\LaravelAutentique\Exceptions\InvalidInput;

/**
 * Where on a page something is stamped once the signer acts.
 *
 * `x` and `y` are percentages of the page's width and height, from its top left
 * corner, and `page` starts at 1. Autentique receives the coordinates as
 * strings; they are written with one decimal.
 */
final readonly class Position
{
    /**
     * @throws InvalidInput
     */
    public function __construct(
        public float $x,
        public float $y,
        public int $page = 1,
        public PositionElement $element = PositionElement::Signature,
        public ?float $angle = null,
        public ?float $scale = null,
    ) {
        if ($x < 0 || $x > 100 || $y < 0 || $y > 100) {
            throw new InvalidInput("A position is a percentage of the page: x and y go from 0 to 100, {$x} and {$y} given.");
        }

        if ($page < 1) {
            throw new InvalidInput("Pages start at 1, {$page} given.");
        }

        if ($angle !== null && ($angle < 0 || $angle > 360)) {
            throw new InvalidInput("An angle goes from 0 to 360 degrees, {$angle} given.");
        }

        if ($scale !== null && ($scale < 0.5 || $scale > 4)) {
            throw new InvalidInput("A scale goes from 0.5 to 4, {$scale} given.");
        }
    }

    /**
     * @throws InvalidInput
     */
    public static function signature(float $x, float $y, int $page = 1): self
    {
        return new self($x, $y, $page, PositionElement::Signature);
    }

    /**
     * @throws InvalidInput
     */
    public static function name(float $x, float $y, int $page = 1): self
    {
        return new self($x, $y, $page, PositionElement::Name);
    }

    /**
     * @throws InvalidInput
     */
    public static function initials(float $x, float $y, int $page = 1): self
    {
        return new self($x, $y, $page, PositionElement::Initials);
    }

    /**
     * @throws InvalidInput
     */
    public static function date(float $x, float $y, int $page = 1): self
    {
        return new self($x, $y, $page, PositionElement::Date);
    }

    /**
     * @throws InvalidInput
     */
    public static function cpf(float $x, float $y, int $page = 1): self
    {
        return new self($x, $y, $page, PositionElement::Cpf);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'x' => number_format($this->x, 1, '.', ''),
            'y' => number_format($this->y, 1, '.', ''),
            'z' => $this->page,
            'element' => $this->element->value,
            'angle' => $this->angle,
            'scale' => $this->scale,
        ], fn(mixed $value): bool => $value !== null);
    }
}
