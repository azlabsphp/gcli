<?php

namespace Drewlabs\GCli\Factories;

use Drewlabs\CodeGenerator\Helpers\Str;
use Drewlabs\GCli\Exceptions\IOException;
use Drewlabs\GCli\IO\Path;

class ComponentPath
{

    /**
     * Creates new factory class instance
     * 
     * @return static 
     */
    public static function new()
    {
        return new static;
    }

    /**
     * Creates component source code path
     * 
     * @param string $namespace 
     * @param string $path 
     * @return string 
     * @throws IOException 
     */
    public function create(string $namespace, string $path)
    {
        $namespace = $namespace ?? '';
        $dir = Str::contains($namespace, '\\') ? Str::afterLast('\\', $namespace) : $namespace;
        if (Str::lower($dir) !== Str::lower(Path::new($path)->basename())) {
            // If the last part of both namespace and path are not the same
            $parts = array_reverse(explode('\\', $namespace));
            foreach ($parts as $value) {
                if (Str::contains($path, $value)) {
                    $path = sprintf(
                        '%s%s%s',
                        rtrim($path, \DIRECTORY_SEPARATOR),
                        \DIRECTORY_SEPARATOR,
                        ltrim(str_replace('\\', \DIRECTORY_SEPARATOR, Str::afterLast($value, $namespace)), \DIRECTORY_SEPARATOR)
                    );
                    break;
                }
            }
        }

        return $path;
    }

}