<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data\Input;

use LSNepomuceno\LaravelAutentique\Enums\{CreditsRecurrence, IntervalType, Tier};
use LSNepomuceno\LaravelAutentique\Exceptions\InvalidInput;

/**
 * A custom plan for child organizations, or its new terms.
 *
 * It renews every `$interval` `$intervalType`s, a month by default.
 */
final readonly class NewSubscriptionPlan
{
    /**
     * @throws InvalidInput
     */
    public function __construct(
        public string $displayName,
        public int $documentAmount,
        public int $creditAmount,
        public ?int $interval = null,
        public ?IntervalType $intervalType = null,
        public ?Tier $tier = null,
        public ?CreditsRecurrence $creditsRecurrence = null,
    ) {
        if ($documentAmount < 1 || $creditAmount < 1) {
            throw new InvalidInput('A plan gives at least one document and one verification credit per interval.');
        }

        if ($interval !== null && $interval < 1) {
            throw new InvalidInput("A plan renews every 1 or more intervals, {$interval} given.");
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'display_name' => $this->displayName,
            'document_amount' => $this->documentAmount,
            'credit_amount' => $this->creditAmount,
            'interval' => $this->interval,
            'interval_type' => $this->intervalType?->value,
            'tier' => $this->tier?->value,
            'credits_recurrence_method' => $this->creditsRecurrence?->value,
        ], fn(mixed $value): bool => $value !== null);
    }
}
