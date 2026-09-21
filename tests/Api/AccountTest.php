<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use LSNepomuceno\LaravelAutentique\Exceptions\UnexpectedResponse;
use LSNepomuceno\LaravelAutentique\Facades\Autentique;

beforeEach(fn() => config()->set('autentique.token', 'the-token'));

it('reads who the token belongs to', function () {
    Http::fake(['*' => Http::response(responseFixture('me'))]);

    $user = Autentique::account()->me();

    expect($user->id)->toBe('1ac41a793ed27015abd0a381eb2846e1c3e7fe01')
        ->and($user->name)->toBe('Mateus Zanella')
        ->and($user->email)->toBe('mateus@autentique.com.br')
        ->and($user->phone)->toBeNull()
        ->and($user->cpf)->toBe('012.345.678-90')
        ->and($user->birthday?->toDateString())->toBe('2001-01-01')
        ->and($user->subscription?->hasPremiumFeatures)->toBeFalse()
        ->and($user->subscription?->documents)->toBe(20)
        ->and($user->subscription?->credits)->toBe(200)
        ->and($user->organization?->id)->toBe(179)
        ->and($user->organization?->uuid)->toBe('91155c91-a411-4d93-b2a4-92e37548256b')
        ->and($user->organization?->cnpj)->toBe('29.423.653/0001-65')
        ->and($user->organization?->groups)->toBe([]);

    Http::assertSent(fn(Request $request): bool => $request['operationName'] === 'me');
});

it('refuses an answer with no user in it', function () {
    Http::fake(['*' => Http::response(['data' => ['me' => null]])]);

    Autentique::account()->me();
})->throws(UnexpectedResponse::class, 'id');
