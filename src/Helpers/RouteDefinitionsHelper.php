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

namespace Drewlabs\ComponentGenerators\Helpers;

use Closure;
use Drewlabs\ComponentGenerators\Cache\CacheableRoutes;
use Drewlabs\ComponentGenerators\Cache\CacheableSerializer;
use Drewlabs\ComponentGenerators\Models\RouteController;
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Core\Helpers\Str;
use Drewlabs\Filesystem\Exceptions\CreateDirectoryException;
use Drewlabs\Filesystem\Exceptions\FileNotFoundException;
use Drewlabs\Filesystem\Exceptions\ReadFileException;
use Drewlabs\Filesystem\Exceptions\UnableToRetrieveMetadataException;
use Drewlabs\Filesystem\Exceptions\WriteOperationFailedException;

use function Drewlabs\Filesystem\Proxy\LocalFileSystem;

use League\Flysystem\Config;

class RouteDefinitionsHelper
{
    /**
     * @var string
     */
    public const ROUTE_DEFINITION_START = "\n// Drewlabs Generated MVC Route Defnitions, Please Do not delete to avoid duplicates route definitions";

    /**
     * @var string
     */
    public const ROUTE_DEFINITION_END = "// !End Drewlabs Generated MVC Route Defnitions, Please Do not delete to avoid duplicates route definitions\n";

    /**
     * @var array
     */
    public const HTTP_VERB_MAP = [
        'index' => 'get',
        'show' => 'get',
        'store' => 'post',
        'update' => 'put',
        'destroy' => 'delete',
    ];

    /**
     * Higher order function for building routes definitions based on route and controller name.
     *
     * @return Closure&#Function#2bf2b6cd
     */
    public static function for(string $name, RouteController $controller)
    {
        return static function ($isLumen) use ($name, $controller) {
            $classPath = $controller->getName();
            $namespace = $controller->getNamespace();
            if ($isLumen) {
                $classPath = Str::contains($classPath, '\\') ? array_reverse(explode('\\', $classPath))[0] ?? $classPath : $classPath;
                $classPath = !empty($namespace) ?
                    sprintf('%s\\%s', $namespace, $classPath) :
                    $classPath;

                return [
                    "\$router->get('/${name}', ['uses' => '$classPath@index']);",
                    "\$router->get('/${name}/{id}', ['uses' => '$classPath@show']);",
                    "\$router->post('/${name}', ['uses' => '$classPath@store']);",
                    "\$router->put('/${name}/{id}', ['uses' => '$classPath@update']);",
                    "\$router->delete('/${name}/{id}', ['uses' => '$classPath@destroy']);",
                ];
            }
            $lines = [];
            $definitions = ['index' => "'/${name}'", 'show' => "'/${name}/{id}'", 'store' => "'/${name}'", 'update' => "'/${name}/{id}'", 'destroy' => "'/${name}/{id}'"];
            foreach ($definitions as $method => $route) {
                if (Str::contains($classPath, '\\') && class_exists($classPath)) {
                    $lines[] = sprintf("Route::%s($route, [\\$classPath::class, '$method']);", self::HTTP_VERB_MAP[$method]);
                } else {
                    $classPath = Str::contains($classPath, '\\') ? array_reverse(explode('\\', $classPath))[0] ?? $classPath : $classPath;
                    $classPath = !empty($namespace) ?
                        sprintf('%s\\%s', $namespace, $classPath) :
                        $classPath;
                    $lines[] = sprintf("Route::%s($route, ['uses' => '%s@$method']);", self::HTTP_VERB_MAP[$method], $classPath);
                }
            }

            return $lines;
        };
    }

    /**
     * @return \Closure
     */
    public static function writeRouteDefinitions(
        string $basePath,
        array $definitions,
        string $filename = 'web.php',
        bool $partial = false
    ) {
        return static function (
            bool $lumen,
            ?string $prefix = null,
            ?string $middleware = null,
            ?\Closure $callback = null
        ) use ($definitions, $filename, $basePath, $partial) {
            $adapter = LocalFileSystem($basePath);
            $output = '';
            list($before, $between, $after) = static::getRouteParts($adapter, $filename, $partial);
            // Write the content before to the output
            $output .= $before;
            // Write route definition start
            $output .= self::ROUTE_DEFINITION_START . \PHP_EOL;
            // Write the existing route defintions
            $output .= $between;
            // Prepare the new routes script
            $groupRoutes = (null !== $prefix) || (null !== $middleware);
            if ($groupRoutes) {
                $output .= sprintf(
                    '%s%s, function() %s {',
                    $lumen ? '$router->group(' : 'Route::group(',
                    Str::replace(
                        '"',
                        '',
                        static::createGroupPart($prefix, $middleware)
                    ),
                    $lumen ? 'use ($router)' : ''
                );
            }
            $definitions = Arr::map(
                $definitions ?? [],
                static function ($definition) use ($groupRoutes) {
                    return $groupRoutes ? array_map(static function ($line) {
                        return "\t$line";
                    }, $definition) : $definition;
                }
            );
            foreach ($definitions as $key => $value) {
                $output .= \PHP_EOL . ($groupRoutes ? "\t" : '') . "// Route definitions for $key" . \PHP_EOL;
                $output .= implode(\PHP_EOL, $value);
                $output .= \PHP_EOL . ($groupRoutes ? "\t" : '') . "// !End Route definitions for $key" . \PHP_EOL;
            }
            if ((null !== $prefix) || (null !== $middleware)) {
                $output .= '});';
            }
            $output .= \PHP_EOL . self::ROUTE_DEFINITION_END;
            $output .= $after;
            $adapter->write($filename, $output, new Config());
            // Call the callback
            if ($callback) {
                $callback($definitions);
            }
        };
    }

    /**
     * Creates route group script part
     * 
     * @param string $prefix 
     * @param string[]|string $middleware 
     * @return string 
     */
    private static function createGroupPart($prefix, $middleware)
    {
        $list_to_list_string = static function ($values) {
            $strfn = static function ($v) {
                return \is_string($v) ? "'$v'" : "$v";
            };
            foreach ($values as $key => $value) {
                yield is_numeric($key) ?
                    $strfn($value) :
                    "'$key' => " . $strfn($value);
            }
        };
        $output = @json_encode(
            iterator_to_array(
                $list_to_list_string(
                    array_merge(
                        [],
                        null !== $prefix ? ['prefix' => $prefix] : [],
                        null !== $middleware ? ['middleware' => $middleware] : []
                    )
                )
            ),
        );
        return $output ? Str::replace('\\/', '/', $output) : '';
    }

    /**
     * Create the routing file parts
     * 
     * @param mixed $adapter 
     * @param string $filename 
     * @param bool $partial 
     * @return array 
     */
    private static function getRouteParts($adapter, string $filename, bool $partial = false)
    {
        if (!$adapter->fileExists($filename)) {
            return ['<?php' . \PHP_EOL, '', ''];
        }
        // Read content and locate where to write the new data
        $content = $adapter->read($filename);
        if (empty(trim($content))) {
            return ['<?php' . \PHP_EOL, '', ''];
        }
        // Read the generated script start and end values
        if (
            Str::contains($content, self::ROUTE_DEFINITION_START) ||
            (Str::contains($content, self::ROUTE_DEFINITION_END))
        ) {
            return [
                Str::before(self::ROUTE_DEFINITION_START, $content),
                $partial ? Str::before(self::ROUTE_DEFINITION_END, Str::after(self::ROUTE_DEFINITION_START, $content)) : '',
                Str::after(self::ROUTE_DEFINITION_END, $content)
            ];
        }
        return [$content, '', ''];
    }

    /**
     * @throws UnableToRetrieveMetadataException
     * @throws CreateDirectoryException
     * @throws \InvalidArgumentException
     * @throws WriteOperationFailedException
     *
     * @return void
     */
    public static function cacheRouteDefinitions(string $path, array $routes, ?string $namespace = null)
    {
        (new CacheableSerializer($path))->dump(new CacheableRoutes([
            'routes' => $routes,
            'namespace' => $namespace,
        ]));
    }

    /**
     * @throws ReadFileException
     * @throws UnableToRetrieveMetadataException
     * @throws FileNotFoundException
     *
     * @return CacheableRoutes
     */
    public static function getCachedRouteDefinitions(string $path)
    {
        $value = (new CacheableSerializer($path))->load(CacheableRoutes::class);

        return $value;
    }
}
