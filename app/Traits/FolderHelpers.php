<?php

namespace App\Traits;

trait FolderHelpers
{
    protected function moveFolder(string $source, string $destination): bool
    {
        $command = PHP_OS_FAMILY === 'Windows' ? 'move' : 'mv';

        $result = null;
        exec($command.' '.escapeshellarg($source).' '.escapeshellarg($destination), $output, $result);

        return ! $result;
    }
}
