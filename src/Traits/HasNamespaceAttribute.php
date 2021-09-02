<?php

declare(strict_types=1);

/*
 * This file is part of the Drewlabs package.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\ComponentGenerators\Traits;

use function Drewlabs\Filesystem\Proxy\Path;

trait HasNamespaceAttribute
{
    use HasPathAttribute;
    use HasNameAttribute;

    /**
     * @var string
     */
    private $namespace_;

    public function setNamespace(string $namespace)
    {
        $namespace = ltrim($namespace, '\\');
        $this->namespace_ = $namespace;
        $path_ = $this->getWritePath();
        if (null === $path_ || empty($path_)) {
            // Get the list of files in the current workspace
            $currentWsClasses = array_filter(get_declared_classes(), static function ($item) use ($namespace) {
                return drewlabs_core_strings_starts_with($item, $namespace);
            });
            // Get the first element of the list if any
            if (null === ($first = $currentWsClasses[0] ?? null)) {
                return $this->createPathFromNamespace($namespace);
            }
            // Extract file and dirname from the current directory
            $filename = (new \ReflectionClass(new $first()))->getFileName();
            $dirname = $filename ? Path($filename)->dirname() : null;
            if ($dirname) {
                $this->setWritePath($dirname);
            }
        }

        return $this;
    }

    public function namespace(): ?string
    {
        return $this->namespace_;
    }

    private function createPathFromNamespace(string $namespace)
    {
        $folders = drewlabs_core_strings_to_array($namespace, '\\');
        if (empty($folders)) {
            return $this;
        }
        $folder0 = $folders[0];
        $remaining = \array_slice($folders, 1);
        $path = implode(\DIRECTORY_SEPARATOR, array_merge([drewlabs_core_strings_to_lower_case($folder0)], $remaining));
        $this->setWritePath($path);

        return $this;
    }
}
