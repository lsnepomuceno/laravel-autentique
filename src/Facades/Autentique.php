<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Facades;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use LSNepomuceno\LaravelAutentique\Contracts\Autentique as AutentiqueContract;
use LSNepomuceno\LaravelAutentique\Testing\AutentiqueFake;

/**
 * @method static \LSNepomuceno\LaravelAutentique\Api\Account account()
 * @method static \LSNepomuceno\LaravelAutentique\Api\Documents documents()
 * @method static \LSNepomuceno\LaravelAutentique\Api\Signers signers()
 * @method static \LSNepomuceno\LaravelAutentique\Api\Folders folders()
 * @method static \LSNepomuceno\LaravelAutentique\Api\Organizations organizations()
 * @method static \LSNepomuceno\LaravelAutentique\Api\Corporate corporate()
 * @method static \LSNepomuceno\LaravelAutentique\Api\PendingDocument newDocument(string $name)
 * @method static \LSNepomuceno\LaravelAutentique\Contracts\FileSource fromPath(string $path, ?string $name = null)
 * @method static \LSNepomuceno\LaravelAutentique\Contracts\FileSource fromUpload(\Illuminate\Http\UploadedFile $file, ?string $name = null)
 * @method static \LSNepomuceno\LaravelAutentique\Contracts\FileSource fromDisk(string $disk, string $path, ?string $name = null)
 * @method static array<string, mixed> query(string $graphql, array<string, mixed> $variables = [])
 *
 * @see \LSNepomuceno\LaravelAutentique\AutentiqueManager
 */
final class Autentique extends Facade
{
    #[\Override]
    protected static function getFacadeAccessor(): string
    {
        return AutentiqueContract::class;
    }

    /**
     * Sends nothing to Autentique, and records what would have been sent.
     *
     * ```php
     * $autentique = Autentique::fake();
     *
     * // … the application runs …
     *
     * $autentique->assertDocumentSent(fn(array $document) => $document['name'] === 'Service agreement');
     * ```
     *
     * It replaces the GraphQL client rather than this facade's binding,
     * because every `Api\` class and the builder reach Autentique through the
     * client: faking only the facade would leave them sending real requests.
     */
    public static function fake(): AutentiqueFake
    {
        return AutentiqueFake::install(Container::getInstance());
    }
}
