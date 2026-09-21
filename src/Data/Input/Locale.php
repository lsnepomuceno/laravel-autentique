<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data\Input;

use DateTimeZone;
use LSNepomuceno\LaravelAutentique\Enums\{DateFormat, Language};
use LSNepomuceno\LaravelAutentique\Exceptions\InvalidInput;

/**
 * Where a document belongs, which changes what Autentique does with it.
 *
 * A document outside Brazil is always created with the new signature style,
 * never asks for a CPF, ignores CPF positions and the SERPRO and SMS checks,
 * and may refuse SMS delivery. The language changes the interface and the
 * messages, never the document.
 */
final readonly class Locale
{
    /**
     * @param  ?string  $country  ISO 3166 alpha-2, `BR` when absent.
     * @param  ?string  $timezone  An IANA identifier, `America/Sao_Paulo` when absent.
     *
     * @throws InvalidInput
     */
    public function __construct(
        public ?string $country = null,
        public ?Language $language = null,
        public ?string $timezone = null,
        public ?DateFormat $dateFormat = null,
    ) {
        if ($country !== null && preg_match('/^[A-Z]{2}$/', $country) !== 1) {
            throw new InvalidInput("A country is its ISO 3166 alpha-2 code, BR or US; {$country} given.");
        }

        if ($timezone !== null && ! in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
            throw new InvalidInput("{$timezone} is not a timezone identifier. See DateTimeZone::listIdentifiers().");
        }
    }

    public function isBrazilian(): bool
    {
        return $this->country === null || $this->country === 'BR';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'country' => $this->country,
            'language' => $this->language?->value,
            'timezone' => $this->timezone,
            'date_format' => $this->dateFormat?->value,
        ], fn(mixed $value): bool => $value !== null);
    }
}
