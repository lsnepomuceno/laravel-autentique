<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Enums;

/**
 * What someone a folder is shared with may do in it.
 */
enum FolderRole: string
{
    /** See the folder and its documents. */
    case Viewer = 'VIEWER';

    /** Change and manage them too. */
    case Editor = 'EDITOR';
}
