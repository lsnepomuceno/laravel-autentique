<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data;

use ArrayIterator;
use Closure;
use Countable;
use IteratorAggregate;
use LSNepomuceno\LaravelAutentique\Support\Payload;
use Traversable;

/**
 * One page of a listing, and where it sits among the others.
 *
 * Autentique pages by number and size. Not every listing reports every total,
 * so the counts are nullable; `$hasMorePages` is the one to loop on.
 *
 * @template T
 *
 * @implements IteratorAggregate<int, T>
 */
final readonly class Page implements Countable, IteratorAggregate
{
    /**
     * @param  list<T>  $items
     */
    public function __construct(
        public array $items,
        public ?int $total,
        public ?int $perPage,
        public ?int $currentPage,
        public ?int $lastPage,
        public ?bool $hasMorePages,
    ) {}

    /**
     * @template TItem
     *
     * @param  Closure(Payload): TItem  $item
     * @return self<TItem>
     */
    public static function fromPayload(Payload $payload, Closure $item): self
    {
        $currentPage = $payload->nullableInt('current_page');
        $lastPage = $payload->nullableInt('last_page');

        return new self(
            items: array_map($item, $payload->list('data')),
            total: $payload->nullableInt('total'),
            perPage: $payload->nullableInt('per_page'),
            currentPage: $currentPage,
            lastPage: $lastPage,
            hasMorePages: $payload->nullableBool('has_more_pages')
                ?? ($currentPage !== null && $lastPage !== null ? $currentPage < $lastPage : null),
        );
    }

    /**
     * The same page with only the items the callback keeps. The counts stay
     * Autentique's, since they describe the listing it paged, not this page.
     *
     * @param  \Closure(T): bool  $keep
     * @return self<T>
     */
    public function filter(Closure $keep): self
    {
        return new self(
            items: array_values(array_filter($this->items, $keep)),
            total: $this->total,
            perPage: $this->perPage,
            currentPage: $this->currentPage,
            lastPage: $this->lastPage,
            hasMorePages: $this->hasMorePages,
        );
    }

    #[\Override]
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return Traversable<int, T>
     */
    #[\Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
