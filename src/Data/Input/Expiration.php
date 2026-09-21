<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data\Input;

use DateTimeInterface;
use LSNepomuceno\LaravelAutentique\Exceptions\InvalidInput;

/**
 * A reminder sent `daysBefore` days ahead of a due date.
 *
 * It only notifies. To stop signatures after a date, use the document's
 * deadline instead.
 */
final readonly class Expiration
{
    /**
     * @throws InvalidInput
     */
    public function __construct(
        public DateTimeInterface $notifyAt,
        public int $daysBefore,
    ) {
        if ($daysBefore < 1) {
            throw new InvalidInput("An expiration reminder is sent at least 1 day before, {$daysBefore} given.");
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        // Autentique's Date scalar is written dd/mm/yyyy.
        return ['days_before' => $this->daysBefore, 'notify_at' => $this->notifyAt->format('d/m/Y')];
    }
}
