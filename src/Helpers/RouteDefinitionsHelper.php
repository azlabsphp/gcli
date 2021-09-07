<?php

namespace Drewlabs\ComponentGenerators\Helpers;

use League\Flysystem\Config;
use SebastianBergmann\CodeCoverage\Report\PHP;

use function Drewlabs\Filesystem\Proxy\File;
use function Drewlabs\Filesystem\Proxy\LocalFileSystem;
use function Drewlabs\Filesystem\Proxy\Path;

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
     * @param string $controllerClassPath 
     * @return Closure&#Function#2bf2b6cd 
     */
    public static function for(string $name, $controllerClassPath = 'TestsController')
    {
        return function ($is_lumen_app) use ($name, $controllerClassPath) {
            if ($is_lumen_app) {
                $controllerClassPath = drewlabs_core_strings_contains($controllerClassPath, "\\") ? array_reverse(explode("\\", $controllerClassPath))[0] ?? $controllerClassPath : $controllerClassPath;
                return [
                    "\$router->get('/${name}[/{id}]', ['uses' => '$controllerClassPath@index']);",
                    "\$router->post('/${name}', ['uses' => '$controllerClassPath@store']);",
                    "\$router->put('/${name}/{id}', ['uses' => '$controllerClassPath@update']);",
                    "\$router->delete('/${name}/{id}', ['uses' => '$controllerClassPath@destroy']);",
                ];
            }
            $lines = [];
            $definitions = ['index' => "'/${name}/{id?}'", 'store' => "'/${name}'", 'update' => "'/${name}/{id}'", 'destroy' => "'/${name}/{id}'"];
            foreach ($definitions as $method => $route) {
                $lines[] = drewlabs_core_strings_contains($controllerClassPath, "\\")  && class_exists($controllerClassPath) ?
                    sprintf("Route::%s($route, [\\$controllerClassPath::class, '$method']);", self::HTTP_VERB_MAP[$method]) :
                    sprintf("Route::%s($route, ['uses' => '%s@$method']);", self::HTTP_VERB_MAP[$method], drewlabs_core_strings_contains($controllerClassPath, "\\") ? array_reverse(explode("\\", $controllerClassPath))[0] ?? $controllerClassPath : $controllerClassPath);
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
            string $middleware = null
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
                $output .=  sprintf(
                    "%s%s, function() %s {",
                    $is_lumen_app ? "\$router->group(" : "Route::group(",
                    drewlabs_core_strings_replace(
                        "\"",
                        "",
                        json_encode(
                            iterator_to_array(
                                $list_to_list_string(
                                    array_merge(
                                        [],
                                        null !== $prefix ? ['prefix' => $prefix] : [],
                                        null !== $middleware ? ['middleware' => $middleware] : []
                                    )
                                )
                            ),
                        )
                    ),
                    $is_lumen_app ? "use (\$router)" : ""
                );
            }
            $definitions = array_map(
                function ($definition) use ($has_group_definition) {
                    return $has_group_definition ? array_map(function ($line) {
                        return "\t$line";
                    }, $definition) : $definition;
                },
                $definitions ?? []
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
        };
    }
}
