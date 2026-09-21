<?php

declare(strict_types=1);

use LSNepomuceno\LaravelAutentique\Enums\ErrorCode;
use LSNepomuceno\LaravelAutentique\Exceptions\UnexpectedResponse;
use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * The typed reading every value object is built on.
 */

it('reads strings, including numbers written as strings and back', function () {
    $payload = new Payload(['a' => 'x', 'b' => 179, 'c' => null, 'd' => ['nested' => 'y']]);

    expect($payload->string('a'))->toBe('x')
        ->and($payload->nullableString('b'))->toBe('179')
        ->and($payload->nullableString('c'))->toBeNull()
        ->and($payload->nullableString('d.nested'))->toBe('y')
        ->and($payload->has('d.nested'))->toBeTrue()
        ->and($payload->has('c'))->toBeFalse();
});

it('refuses a required field that is missing', function () {
    new Payload([])->string('id');
})->throws(UnexpectedResponse::class, 'id');

it('reads integers and floats from numbers and numeric strings', function () {
    $payload = new Payload(['a' => '42', 'b' => 7, 'c' => 'no', 'd' => '-27.6767']);

    expect($payload->int('a'))->toBe(42)
        ->and($payload->nullableInt('b'))->toBe(7)
        ->and($payload->nullableInt('c'))->toBeNull()
        ->and($payload->nullableFloat('d'))->toBe(-27.6767);
});

it('reads booleans, including the 0 and 1 webhooks send', function () {
    $payload = new Payload(['a' => true, 'b' => 0, 'c' => '1', 'd' => 'yes']);

    expect($payload->nullableBool('a'))->toBeTrue()
        ->and($payload->nullableBool('b'))->toBeFalse()
        ->and($payload->nullableBool('c'))->toBeTrue()
        ->and($payload->nullableBool('d'))->toBeNull()
        ->and($payload->bool('missing'))->toBeFalse()
        ->and($payload->bool('missing', default: true))->toBeTrue();
});

it('reads dates in both formats Autentique writes', function () {
    $payload = new Payload([
        'iso' => '2023-10-17T16:43:13.000000Z',
        'plain' => '2025-04-09 09:21:35',
        'brazilian' => '01/01/2001',
        'empty' => '',
        'nonsense' => 'not a date',
    ]);

    expect($payload->date('iso')?->toIso8601ZuluString())->toBe('2023-10-17T16:43:13Z')
        ->and($payload->date('plain')?->toDateTimeString())->toBe('2025-04-09 09:21:35')
        ->and($payload->date('brazilian')?->toDateString())->toBe('2001-01-01')
        ->and($payload->date('empty'))->toBeNull()
        ->and($payload->date('nonsense'))->toBeNull()
        ->and($payload->date('missing'))->toBeNull();
});

it('reads an enum, and gives null for a value it does not know', function () {
    $payload = new Payload(['known' => 'field_required', 'unknown' => 'new_thing']);

    expect($payload->enum('known', ErrorCode::class))->toBe(ErrorCode::FieldRequired)
        ->and($payload->enum('unknown', ErrorCode::class))->toBeNull();
});

it('reads nested objects and lists, skipping what is not one', function () {
    $payload = new Payload(['one' => ['id' => 1], 'many' => [['id' => 2], 'junk', ['id' => 3]], 'tags' => ['a', 1, 'b']]);

    expect($payload->object('one')?->int('id'))->toBe(1)
        ->and($payload->object('missing'))->toBeNull()
        ->and(array_map(fn(Payload $item): int => $item->int('id'), $payload->list('many')))->toBe([2, 3])
        ->and($payload->list('missing'))->toBe([])
        ->and($payload->strings('tags'))->toBe(['a', 'b']);
});

it('wraps anything, treating what is not an array as empty', function () {
    expect(Payload::of('text')->all())->toBe([])
        ->and(Payload::of(['a' => 1])->all())->toBe(['a' => 1]);
});
