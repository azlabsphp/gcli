<?php

declare(strict_types=1);

/*
 * This file is part of the drewlabs namespace.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\GCli\Plugins\Laravel\Traits;

use Drewlabs\Core\Helpers\Str;
use Drewlabs\GCli\IO\Path;

trait HasNamespaceAttribute
{
    use HasNameAttribute;
    use HasPathAttribute;

    /**
     * @var ?string
     */
    private $package;

    public function setNamespace(string $namespace)
    {
        $namespace = ltrim($namespace, '\\');
        $this->package = $namespace;
        if (empty($this->getWritePath())) {

        $currentWsClasses = array_filter(get_declared_classes(), static function ($item) use ($namespace) {
                return Str::startsWith($item, $namespace);
            });

            if (null === ($first = $currentWsClasses[0] ?? null)) {
                return $this->createPathFromNamespace($namespace);
            }

            $name = (new \ReflectionClass(new $first()))->getFileName();
            $dirname = $name ? Path::new($name)->dirname() : null;
            if ($dirname) {
                $this->setWritePath($dirname);
            }
        }

        return $this;
    }

    public function namespace(): ?string
    {
        return $this->package;
    }

    public function getClassPath()
    {
        return sprintf('%s\\%s', $this->namespace(), $this->name());
    }

    private function createPathFromNamespace(string $namespace)
    {
        $folders = Str::split($namespace, '\\');
        if (empty($folders)) {
            return $this;
        }
        $folder0 = $folders[0];
        $remaining = \array_slice($folders, 1);
        $path = implode(\DIRECTORY_SEPARATOR, array_merge([Str::lower($folder0)], $remaining));
        $this->setWritePath($path);

        return $this;
    }
}
