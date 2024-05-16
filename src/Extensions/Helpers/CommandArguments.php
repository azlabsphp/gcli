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

namespace Drewlabs\GCli\Extensions\Helpers;

use Drewlabs\GCli\DBDriverOptions;
use Drewlabs\GCli\Exceptions\IOException;
use Drewlabs\GCli\Helpers\ComponentBuilder;
use Drewlabs\GCli\IO\Path;
use Drewlabs\GCli\Options;

class CommandArguments
{
    /**
     * @var Options
     */
    private $options;

    /**
     * Creates a command line argument instance.
     *
     * @return void
     */
    public function __construct(array $options)
    {
        $this->options = new Options($options);
    }

    /**
     * Creates an instance of {@see Options} class with internal state having
     * a predefined structure. The input state is a dictionnary with keys matching
     * command line options.
     *
     * @throws IOException
     * @throws \RuntimeException
     * @throws \Exception
     *
     * @return Options
     */
    public function providesoptions(string $cachePath, string $basePath)
    {

        // #endregion command options
        $options = ($inputpath = $this->options->get('input')) ? ('json' === $this->options->get('format') ? Options::json($inputpath) : Options::yaml($inputpath)) : new Options([]);
        // TODO : Override default option with parameters
        $options = $options->merge([
            'path' => Path::new($basePath)->__toString(),
            'cache' => false === (bool) $this->options->get('disableCache'),
            'plugins' => $this->options->get('plugins', []),
        ]);
        if ($routeingfilename = $this->options->get('routingfilename')) {
            $options = $options->merge(['routes.filename' => $routeingfilename]);
        }
        if ($prefix = $this->options->get('routePrefix')) {
            $options = $options->merge(['routes.prefix' => $prefix]);
        }
        if ($middleware = $this->options->get('middleware')) {
            $options = $options->merge(['routes.middleware' => $middleware]);
        }
        if ($includes = ($this->options->get('only') ?? [])) {
            $options = $options->merge(['includes' => $includes]);
        }
        if ($excludes = ($this->options->get('excepts') ?? [])) {
            $options = $options->merge(['excludes' => $excludes]);
        }
        if ($options->get('cache', false)) {
            // Get component definitions from cache
            $value = ComponentBuilder::getCachedComponentDefinitions((string) $cachePath);
            if (null !== $value) {
                $options = $options->merge(['excludes' => $value->getTables()]);
            }
        }
        if ($namespace = $this->options->get('package')) {
            $options = $options->merge(['namespace.default' => $namespace]);
        }

        if ($domain = $this->options->get('subPackage')) {
            $options = $options->merge(['namespace.domain' => $domain]);
        }

        if ($schema = $this->options->get('schema')) {
            $options = $options->merge(['schema' => $schema]);
        }

        if (null !== ($camelize = (bool) $this->options->get('camelize-attributes'))) {
            $options = $options->merge(['models.attributes.camelize' => $camelize]);
        }

        if ((bool) $this->options->get('relations')) {
            $options = $options->merge([
                'models.relations.provides' => true,
                'models.relations.one-to-one' => iterator_to_array(static::flattenComposed($this->options->get('toones') ?? [])),
                'models.relations.many-to-many' => iterator_to_array(static::flattenComposed($this->options->get('manytomany') ?? [])),
                'models.relations.one-to-one-though' => iterator_to_array(static::flattenComposed($this->options->get('onethroughs') ?? [])),
                'models.relations.one-to-many-though' => iterator_to_array(static::flattenComposed($this->options->get('manythroughs') ?? [])),
            ]);
        }
        // Add model accessors' flag
        $options = $options->merge(['models.no-accessors' => (bool) $this->options->get('no-model-accessors')]);

        if ($policies = ($this->options->get('policies') ?? false)) {
            $options = $options->merge(['policies' => $policies]);
        }

        if ($htr = ($this->options->get('htr') ?? false)) {
            $options = $options->merge(['htr' => []]);
        }

        if ($htrDir = ($this->options->get('htrDir') ?? null)) {
            $options = $options->merge(['htr.directory' => $htrDir]);
        }

        if ($htrHost = ($this->options->get('htrHost') ?? null)) {
            $options = $options->merge(['htr.host' => $htrHost]);
        }

        if ($htrFormat = ($this->options->get('htrFormat') ?? null)) {
            $options = $options->merge(['htr.format' => $htrFormat]);
        }

        return $options;
    }

    /**
     * Create database configuration options from command arguments.
     *
     * @param \Closure $queryConfig
     *
     * @return array
     */
    public function providesdboptions(\Closure $queryConfig = null)
    {
        $queryConfig = $queryConfig ?? static function ($key, $default = null) {
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
     * Checks if the table has a schema prefix.
     *
     * @return bool
     */
    public static function hasPrefix(string $table, string $prefix)
    {
        if (version_compare(\PHP_VERSION, '8.0.0') >= 0) {
            return str_starts_with($table, $prefix);
        }

        return ('' === $prefix) || (mb_substr($table, 0, mb_strlen($prefix)) === $prefix);
    }

    /**
     * Creates a generator of flatten array.
     *
     * @return \Generator<int, string, mixed, void>
     */
    private static function flattenComposed(array $composed)
    {
        foreach ($composed as $relation) {
            if (str_contains((string) $relation, ',')) {
                $values = explode(',', (string) $relation);
                foreach ($values as $part) {
                    yield $part;
                }
                continue;
            }
            yield $relation;
        }
    }
}
