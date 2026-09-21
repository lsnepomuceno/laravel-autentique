<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Support;

use BackedEnum;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use LSNepomuceno\LaravelAutentique\Exceptions\UnexpectedResponse;
use Throwable;

/**
 * A decoded piece of an answer, read one typed field at a time.
 *
 * Every value object is built from an array decoded from JSON, where every
 * value is `mixed`. `Arr::get()` finds the value and is used for that; what the
 * framework does not provide is the narrowing that follows, done once here
 * rather than in every value object: a string that may arrive as a number, a
 * boolean that webhooks send as `0`, a date in either of the two formats
 * Autentique writes, an enum value this package may not know yet.
 *
 * A required field that is missing throws `UnexpectedResponse`. Everything
 * else is nullable, as the API is.
 */
final readonly class Payload
{
    /**
     * @param  array<array-key, mixed>  $values
     */
    public function __construct(private array $values) {}

    public static function of(mixed $value): self
    {
        return new self(is_array($value) ? $value : []);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function all(): array
    {
        return $this->values;
    }

    public function has(string $key): bool
    {
        return Arr::get($this->values, $key) !== null;
    }

    /**
     * @throws UnexpectedResponse
     */
    public function string(string $key): string
    {
        return $this->nullableString($key) ?? throw UnexpectedResponse::missing($key);
    }

    public function nullableString(string $key): ?string
    {
        $value = Arr::get($this->values, $key);

        return match (true) {
            is_string($value) => $value,
            is_int($value), is_float($value) => (string) $value,
            default => null,
        };
    }

    /**
     * @throws UnexpectedResponse
     */
    public function int(string $key): int
    {
        return $this->nullableInt($key) ?? throw UnexpectedResponse::missing($key);
    }

    public function nullableInt(string $key): ?int
    {
        $value = Arr::get($this->values, $key);

        return is_numeric($value) ? (int) $value : null;
    }

    public function nullableFloat(string $key): ?float
    {
        $value = Arr::get($this->values, $key);

        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * A boolean, which webhooks sometimes send as `0` or `1`.
     */
    public function nullableBool(string $key): ?bool
    {
        $value = Arr::get($this->values, $key);

        return match (true) {
            is_bool($value) => $value,
            $value === 0, $value === '0' => false,
            $value === 1, $value === '1' => true,
            default => null,
        };
    }

    public function bool(string $key, bool $default = false): bool
    {
        return $this->nullableBool($key) ?? $default;
    }

    /**
     * A moment, from the ISO 8601 Autentique returns (`2023-10-17T16:43:13.000000Z`)
     * or the `dd/mm/yyyy` its `Date` scalar uses (`01/01/2001`).
     */
    public function date(string $key): ?CarbonImmutable
    {
        $value = $this->nullableString($key);

        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (preg_match('#^\d{2}/\d{2}/\d{4}$#', $value) === 1) {
                $date = CarbonImmutable::createFromFormat('!d/m/Y', $value);

                return $date instanceof CarbonImmutable ? $date : null;
            }

            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * A case of `$enum`, or null when the value is absent or one the package
     * does not know yet.
     *
     * @template T of BackedEnum
     *
     * @param  class-string<T>  $enum
     * @return T|null
     */
    public function enum(string $key, string $enum): ?BackedEnum
    {
        $value = $this->nullableString($key);

        return $value === null ? null : $enum::tryFrom($value);
    }

    /**
     * A nested object, or null when it is absent.
     */
    public function object(string $key): ?self
    {
        $value = Arr::get($this->values, $key);

        return is_array($value) ? new self($value) : null;
    }

    /**
     * A list of nested objects, skipping anything that is not one.
     *
     * @return list<self>
     */
    public function list(string $key): array
    {
        $value = Arr::get($this->values, $key);

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_map(
            fn(array $item): self => new self($item),
            array_filter($value, is_array(...)),
        ));
    }

    /**
     * A list of strings, skipping anything that is not one.
     *
     * @return list<string>
     */
    public function strings(string $key): array
    {
        $value = Arr::get($this->values, $key);

        return is_array($value) ? array_values(array_filter($value, is_string(...))) : [];
    }
}
