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

namespace Drewlabs\ComponentGenerators\Extensions\Helpers;

use Closure;
use Drewlabs\ComponentGenerators\DBDriverOptions;
use Drewlabs\ComponentGenerators\Exceptions\IOException;
use Drewlabs\ComponentGenerators\Helpers\ComponentBuilderHelpers;
use Drewlabs\ComponentGenerators\IO\Path;
use Drewlabs\ComponentGenerators\Options;
use RuntimeException;

class CommandArguments
{
    /**
     * 
     * @var Options
     */
    private $options;

    /**
     * Creates a command line argument instance
     * 
     * @param array $options 
     * @return void 
     */
    public function __construct(array $options)
    {
        $this->options = new Options($options);
    }

    /**
     * Creates an instance of {@see Options} class with internal state having
     * a predefined structure. The input state is a dictionnary with keys matching
     * command line options
     * 
     * @param array $base 
     * @param string $cachePath 
     * @param string $basePath 
     * @return Options 
     * @throws IOException 
     * @throws RuntimeException  
     * @throws \Exception 
     */
    public function providesoptions(string $cachePath, string $basePath)
    {

        //#endregion command options
        $options =  ($inputpath = $this->options->get('input')) ?
            ('json' === $this->options->get('format') ? Options::json($inputpath) : Options::yaml($inputpath)) :
            new Options([]);
        // TODO : Override default option with parameters
        $options = $options->merge([
            'path' => Path::new($basePath)->__toString(),
            'cache' => false === boolval($this->options->get('disableCache'))
        ]);
        if ($routeingfilename  = $this->options->get('routingfilename')) {
            $options = $options->merge(['routes.filename' => $routeingfilename]);
        }
        if ($prefix  = $this->options->get('routePrefix')) {
            $options = $options->merge(['routes.prefix' => $prefix]);
        }
        if ($middleware  = $this->options->get('middleware')) {
            $options = $options->merge(['routes.middleware' => $middleware]);
        }
        if ($includes  = ($this->options->get('only') ?? [])) {
            $options = $options->merge(['includes' => $includes]);
        }
        if ($excludes  = ($this->options->get('excepts') ?? [])) {
            $options = $options->merge(['excludes' => $excludes]);
        }
        if ($options->get('cache', false)) {
            // Get component definitions from cache
            $value = ComponentBuilderHelpers::getCachedComponentDefinitions((string) $cachePath);
            if (null !== $value) {
                $options = $options->merge(['excludes' => $value->getTables()]);
            }
        }
        if ($namespace  = ($this->options->get('package'))) {
            $options = $options->merge(['namespace.default' => $namespace]);
        }
        if ($schema  = ($this->options->get('schema'))) {
            $options = $options->merge(['schema' => $schema]);
        }

        if (null !== ($camelize = boolval($this->options->get('camelize-attributes')))) {
            $options = $options->merge(['models.attributes.camelize' => $camelize]);
        }

        if (boolval($this->options->get('relations'))) {
            $options = $options->merge([
                'models.relations.provides' => true,
                'models.relations.one-to-one' => iterator_to_array(static::flattenComposed($this->options->get('toones') ?? [])),
                'models.relations.many-to-many' => iterator_to_array(static::flattenComposed($this->options->get('manytomany') ?? [])),
                'models.relations.one-to-one-though' => iterator_to_array(static::flattenComposed($this->options->get('onethroughs') ?? [])),
                'models.relations.one-to-many-though' => iterator_to_array(static::flattenComposed($this->options->get('manythroughs') ?? []))
            ]);
        }
        return $options;
    }

    /**
     * Create database configuration options from command arguments
     * 
     * @param Closure $queryConfig 
     * @return array 
     */
    public function providesdboptions(\Closure $queryConfig = null)
    {
        $queryConfig = $queryConfig ?? function ($key, $default = null) {
            return $default;
        };
        if (null !== ($url = $this->options->get('connectionURL'))) {
            return DBDriverOptions::new(['url' => $url])->get();
        }
        $default_driver = $queryConfig('database.default');
        $driver = $this->options->get('driver') ?
            (self::hasPrefix(
                $this->options->get('driver'),
                'pdo'
            ) ? $this->options->get('driver') :
                sprintf(
                    'pdo_%s',
                    $this->options->get('driver')
                )) : sprintf('pdo_%s', $default_driver);
        $database = $this->options->get('dbname') ?? $queryConfig("database.connections.$default_driver.database");
        $port = $this->options->get('port') ?? $queryConfig("database.connections.$default_driver.port");
        $username = $this->options->get('user') ?? $queryConfig("database.connections.$default_driver.username");
        $host = $this->options->get('host') ?? $queryConfig("database.connections.$default_driver.host");
        $password = $this->options->get('password') ?? $queryConfig("database.connections.$default_driver.password");
        $charset = $this->options->get('charset') ?? ('pdo_mysql' === $driver ? 'utf8mb4' : ('pdo_sqlite' === $driver ? null : 'utf8'));
        $server_version = $this->options->get('server_version') ?? null;
        return DBDriverOptions::new([
            'dbname' => $database,
            'host' => 'pdo_sqlite' === $driver ? null : $host ?? '127.0.0.1',
            'port' => 'pdo_sqlite' === $driver ? null : $port ?? 3306,
            'user' => $username,
            'password' => $password,
            'driver' => $driver ?? 'pdo_sqlite',
            'server_version' => $server_version,
            'charset' => $charset,
        ])->get();
    }

    /**
     * Creates a generator of flatten array
     * 
     * @param array $composed 
     * @return \Generator<int, string, mixed, void> 
     */
    private static function flattenComposed(array $composed)
    {
        foreach ($composed as $relation) {
            if (false !== strpos((string)$relation, ',')) {
                $values = explode(',', (string)$relation);
                foreach ($values as $part) {
                    yield $part;
                }
                continue;
            }
            yield $relation;
        }
    }

    /**
     * Checks if the table has a schema prefix
     * 
     * @param string $table 
     * @param string $needle 
     * @return bool 
     */
    public static function hasPrefix(string $table, string $prefix)
    {
        if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
            return str_starts_with($table, $prefix);
        }
        return ('' === $prefix) || (mb_substr($table, 0, mb_strlen($prefix)) === $prefix);
    }
}
