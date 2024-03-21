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

namespace Drewlabs\GCli\Helpers;

use Closure;
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Core\Helpers\Str;
use Drewlabs\GCli\Cache\Cache;
use Drewlabs\GCli\Cache\CacheableRoutes;
use Drewlabs\GCli\IO\Disk;
use Drewlabs\GCli\Models\RouteController;

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
            $classPath = $controller->getClassPath();
            $namespace = $controller->getNamespace();
            if ($isLumen) {
                $classPath = Str::contains($classPath, '\\') ? array_reverse(explode('\\', $classPath))[0] ?? $classPath : $classPath;
                $classPath = !empty($namespace) ? sprintf('%s\\%s', $namespace, $classPath) : $classPath;

                return [
                    "\$router->get('/$name', ['uses' => '$classPath@index']);",
                    "\$router->get('/$name/{id}', ['uses' => '$classPath@show']);",
                    "\$router->post('/$name', ['uses' => '$classPath@store']);",
                    "\$router->put('/$name/{id}', ['uses' => '$classPath@update']);",
                    "\$router->delete('/$name/{id}', ['uses' => '$classPath@destroy']);",
                ];
            }
            $lines = [];
            $definitions = ['index' => "'/$name'", 'show' => "'/$name/{id}'", 'store' => "'/$name'", 'update' => "'/$name/{id}'", 'destroy' => "'/$name/{id}'"];
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
            string $prefix = null,
            string $middleware = null,
            \Closure $callback = null
        ) use ($definitions, $filename, $basePath, $partial) {
            $adapter = Disk::new($basePath);
            $output = '';
            [$before, $between, $after] = static::getRouteParts($lumen, $adapter, $filename, $partial);
            // Write the content before to the output
            $output .= $before;
            // Write route definition start
            $output .= self::ROUTE_DEFINITION_START . \PHP_EOL;
            // Write the existing route defintions
            $output .= $between;
            // Prepare the new routes script
            $groupRoutes = (null !== $prefix) || (null !== $middleware);
            if ($groupRoutes) {
                $output .= $lumen ? static::createLumenGroup($prefix, $middleware) : static::createLaravelGroup($prefix, $middleware);
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
            $adapter->write($filename, $output);
            // Call the callback
            if ($callback) {
                $callback($definitions);
            }
        };
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \Exception
     *
     * @return void
     */
    public static function cacheRouteDefinitions(string $path, array $routes, string $namespace = null)
    {
        Cache::new($path)->dump(new CacheableRoutes([
            'routes' => $routes,
            'namespace' => $namespace,
        ]));
    }

    /**
     * @throws \Exception
     *
     * @return CacheableRoutes
     */
    public static function getCachedRouteDefinitions(string $path)
    {
        return Cache::new($path)->load(CacheableRoutes::class);
    }

    /**
     * Creates route group script part.
     *
     * @param string          $prefix
     * @param string[]|string $middleware
     *
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
            )
        );

        return $output ? Str::replace('\\/', '/', $output) : '';
    }

    /**
     * Create the routing file parts.
     *
     * @param Disk $adapter
     *
     * @return array
     */
    private static function getRouteParts(bool $lumen, $adapter, string $filename, bool $partial = false)
    {
        if (!$adapter->exists($filename)) {
            return !$lumen ? ['<?php' . \PHP_EOL . "\n" . 'use Illuminate\Support\Facades\Route;' . PHP_EOL, '', ''] : ['<?php' . \PHP_EOL, '', ''];
        }
        // Read content and locate where to write the new data
        $content = $adapter->read($filename);
        if (empty(trim($content))) {
            return ['<?php' . \PHP_EOL, '', ''];
        }
        // Read the generated script start and end values
        if (
            Str::contains($content, self::ROUTE_DEFINITION_START)
            || Str::contains($content, self::ROUTE_DEFINITION_END)
        ) {
            return [
                Str::before(self::ROUTE_DEFINITION_START, $content),
                $partial ? Str::before(self::ROUTE_DEFINITION_END, Str::after(self::ROUTE_DEFINITION_START, $content)) : '',
                Str::after(self::ROUTE_DEFINITION_END, $content),
            ];
        }

        return [$content, '', ''];
    }

    private static function createLumenGroup(string $prefix = null, string $middleware = null)
    {
        return sprintf(
            '%s%s, function() %s {',
            '$router->group(',
            Str::replace('"', '', static::createGroupPart($prefix, $middleware)),
            'use ($router)'
        );
    }

    private static function createLaravelGroup(string $prefix = null, string $middleware = null)
    {
        $output = '';
        if (!is_null($prefix)) {
            $output .= sprintf("Route::prefix('%s')", $prefix);
        }

        if (!is_null($middleware)) {
            $output .= empty($output) ? sprintf("Route::middleware('%s')", $middleware) : sprintf("->middleware('%s')", $middleware);
        }

        if (empty($output)) {
            return $output;
        }

        return sprintf("%s->group(function() {", $output);
    }
}
