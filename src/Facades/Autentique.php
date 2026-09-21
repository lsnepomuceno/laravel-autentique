<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Facades;

use Illuminate\Support\Facades\Facade;
use LSNepomuceno\LaravelAutentique\Contracts\Autentique as AutentiqueContract;

/**
 * @method static \LSNepomuceno\LaravelAutentique\Api\Account account()
 * @method static \LSNepomuceno\LaravelAutentique\Api\Documents documents()
 * @method static \LSNepomuceno\LaravelAutentique\Api\Signers signers()
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
}
