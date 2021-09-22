<?php

namespace Drewlabs\ComponentGenerators\Helpers;

use Drewlabs\ComponentGenerators\Cache\CacheableRoutes;
use Drewlabs\ComponentGenerators\Cache\CacheableSerializer;
use Drewlabs\ComponentGenerators\Models\RouteController;
use Drewlabs\Filesystem\Exceptions\ReadFileException;
use Drewlabs\Filesystem\Exceptions\UnableToRetrieveMetadataException;
use Drewlabs\Filesystem\Exceptions\FileNotFoundException;
use League\Flysystem\Config;

use function Drewlabs\Filesystem\Proxy\LocalFileSystem;

class RouteDefinitionsHelper
{

    const ROUTE_DEFINITION_START = "\n// Drewlabs Generated MVC Route Defnitions, Please Do not delete to avoid duplicates route definitions";

    const ROUTE_DEFINITION_END = "// !End Drewlabs Generated MVC Route Defnitions, Please Do not delete to avoid duplicates route definitions\n";

    const HTTP_VERB_MAP = [
        'index' => 'get',
        'store' => 'post',
        'update' => 'put',
        'destroy' => 'delete',
    ];

    /**
     * Higher order function for building routes definitions based on route and controller name
     * 
     * @param string $name 
     * @param RouteController $controller 
     * @return Closure&#Function#2bf2b6cd 
     */
    public static function for(string $name, RouteController $controller)
    {
        return function ($is_lumen_app) use ($name, $controller) {
            $classPath = $controller->getName();
            $namespace = $controller->getNamespace();
            if ($is_lumen_app) {
                $classPath = drewlabs_core_strings_contains($classPath, "\\") ? array_reverse(explode("\\", $classPath))[0] ?? $classPath : $classPath;
                $classPath = !empty($namespace) ?
                    sprintf("%s\\%s", $namespace, $classPath) :
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
            $definitions = ['index' => "'/${name}/{id?}'", 'store' => "'/${name}'", 'update' => "'/${name}/{id}'", 'destroy' => "'/${name}/{id}'"];
            foreach ($definitions as $method => $route) {
                if (drewlabs_core_strings_contains($classPath, "\\")  && class_exists($classPath)) {
                    $lines[] = sprintf("Route::%s($route, [\\$classPath::class, '$method']);", self::HTTP_VERB_MAP[$method]);
                } else {
                    $classPath = drewlabs_core_strings_contains($classPath, "\\") ? array_reverse(explode("\\", $classPath))[0] ?? $classPath : $classPath;
                    $classPath = !empty($namespace) ?
                        sprintf("%s\\%s", $namespace, $classPath) :
                        $classPath;
                    $lines[] =  sprintf("Route::%s($route, ['uses' => '%s@$method']);", self::HTTP_VERB_MAP[$method], $classPath);
                }
            }
            return $lines;
        };
    }

    public static function writeRouteDefinitions(
        string $basePath,
        array $definitions,
        string $filename = 'web.php'
    ) {
        return function (
            string $is_lumen_app,
            string $prefix = null,
            string $middleware = null,
            \Closure $callback
        ) use ($definitions, $filename, $basePath) {
            $adapter = LocalFileSystem($basePath);
            $contentBefore = '';
            $contentAfter = '';
            $output = '';
            if ($adapter->fileExists($filename)) {
                // Read content and locate where to write the new data
                $content = $adapter->read($filename);
                if (empty(trim($content))) {
                    $output = '<?php' . PHP_EOL;
                }
                // Read the generated script start and end values
                if (
                    drewlabs_core_strings_contains($content, self::ROUTE_DEFINITION_START) ||
                    (drewlabs_core_strings_contains($content, self::ROUTE_DEFINITION_END))
                ) {
                    $contentBefore = drewlabs_core_strings_before(self::ROUTE_DEFINITION_START, $content);
                    $contentAfter = drewlabs_core_strings_after(self::ROUTE_DEFINITION_END, $content);
                } else {
                    $contentBefore = $content;
                }
            } else {
                $output = '<?php' . PHP_EOL;
            }
            // Write the content before to the output
            $output .= $contentBefore;
            // Write route definition start
            $output .= self::ROUTE_DEFINITION_START . PHP_EOL;
            $has_group_definition = (null !== $prefix) || (null !== $middleware);
            if ($has_group_definition) {
                $list_to_list_string = function ($values) {
                    $str_value_func = function ($v) {
                        return is_string($v) ? "'$v'" : "$v";
                    };
                    foreach ($values as $key => $value) {
                        yield is_numeric($key) ? $str_value_func($value) : "'$key' => " . $str_value_func($value);
                    }
                };
                $groupContainer = @json_encode(
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
                if ($groupContainer) {
                    $groupContainer = drewlabs_core_strings_replace('\\/', '/', $groupContainer);
                } else {
                    $groupContainer = '';
                }
                $output .=  sprintf(
                    "%s%s, function() %s {",
                    $is_lumen_app ? "\$router->group(" : "Route::group(",
                    drewlabs_core_strings_replace(
                        "\"",
                        "",
                        $groupContainer
                    ),
                    $is_lumen_app ? "use (\$router)" : ""
                );
            }
            $definitions = drewlabs_core_array_map(
                $definitions ?? [],
                function ($definition) use ($has_group_definition) {
                    return $has_group_definition ? array_map(function ($line) {
                        return "\t$line";
                    }, $definition) : $definition;
                }
            );
            foreach ($definitions as $key => $value) {
                $output .=  PHP_EOL . ($has_group_definition ? "\t" : "") . "// Route definitions for $key" . PHP_EOL;
                $output .= implode(PHP_EOL, $value);
                $output .= PHP_EOL . ($has_group_definition ? "\t" : "") . "// !End Route definitions for $key" . PHP_EOL;
            }
            if ((null !== $prefix) || (null !== $middleware)) {
                $output .=  "});";
            }
            $output .= PHP_EOL . self::ROUTE_DEFINITION_END;
            $output .= $contentAfter;
            $adapter->write($filename, $output, new Config());

            // Call the callback
            if ($callback) {
                $callback($definitions);
            }
        };
    }

    public static function cacheRouteDefinitions(string $path, array $routes, ?string $namespace = null)
    {
        (new CacheableSerializer($path))->dump(new CacheableRoutes([
            'routes' => $routes,
            'namespace' => $namespace
        ]));
    }

    /**
     * 
     * @param string $path 
     * @return CacheableRoutes 
     * @throws ReadFileException 
     * @throws UnableToRetrieveMetadataException 
     * @throws FileNotFoundException 
     */
    public static function getCachedRouteDefinitions(string $path)
    {
        $value = (new CacheableSerializer($path))->load(CacheableRoutes::class);
        return $value;
    }
}
