<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data;

use Illuminate\Support\Str;
use LSNepomuceno\LaravelAutentique\Enums\ErrorCode;

/**
 * One validation failure Autentique reported for one field.
 *
 * Read from `extensions.validation`, where each field maps to a list of codes,
 * and a code may carry a parameter after a colon: `must_be_at_least_characters:3`
 * is the code `must_be_at_least_characters` with the parameter `3`.
 */
final readonly class Violation
{
    public function __construct(
        /** The field as Autentique names it, `folder.name`. */
        public string $field,
        /** The code as it arrived, without its parameter. */
        public string $rawCode,
        /** The code, when this package knows it. */
        public ?ErrorCode $code,
        public ?string $parameter,
    ) {}

    public static function parse(string $field, string $code): self
    {
        $raw = Str::before($code, ':');
        $parameter = Str::contains($code, ':') ? Str::after($code, ':') : null;

        return new self($field, $raw, ErrorCode::tryFrom($raw), $parameter);
    }

    /**
     * The failure in the application's locale, or the raw code when this
     * package does not know it.
     */
    public function message(): string
    {
        return $this->code?->message($this->parameter) ?? $this->rawCode;
    }
}
