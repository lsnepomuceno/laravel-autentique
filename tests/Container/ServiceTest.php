<?php

declare(strict_types=1);

use Illuminate\Support\ServiceProvider;
use LSNepomuceno\LaravelAutentique\AutentiqueManager;
use LSNepomuceno\LaravelAutentique\Contracts\Autentique as AutentiqueContract;
use LSNepomuceno\LaravelAutentique\Facades\Autentique;
use LSNepomuceno\LaravelAutentique\LaravelAutentiqueServiceProvider;

it('binds the contract to the default manager as a singleton', function () {
    expect(app(AutentiqueContract::class))->toBeInstanceOf(AutentiqueManager::class)
        ->and(app(AutentiqueContract::class))->toBe(app(AutentiqueContract::class));
});

it('resolves the facade to the bound contract', function () {
    expect(Autentique::getFacadeRoot())->toBe(app(AutentiqueContract::class));
});

it('merges the config with its defaults', function () {
    expect(config('autentique.url'))->toBe('https://api.autentique.com.br/v2/graphql')
        ->and(config('autentique.sandbox'))->toBeFalse()
        ->and(config('autentique.timeout'))->toBe(30);
});

it('publishes the config under its tag', function () {
    $published = ServiceProvider::pathsToPublish(LaravelAutentiqueServiceProvider::class, 'autentique-config');

    expect($published)->toHaveCount(1)
        ->and(array_values($published)[0])->toBe(config_path('autentique.php'));
});

it('keeps only scalars in the config, so config:cache can serialise it', function () {
    $config = require packageRoot() . '/config/autentique.php';

    if (! is_array($config)) {
        throw new UnexpectedValueException('config/autentique.php must return an array.');
    }

    foreach (Illuminate\Support\Arr::dot($config) as $key => $value) {
        // An empty list survives Arr::dot() as itself, and serialises as one.
        expect($value === null || $value === [] || is_scalar($value))->toBeTrue("autentique.{$key} is not a scalar");
    }
});
