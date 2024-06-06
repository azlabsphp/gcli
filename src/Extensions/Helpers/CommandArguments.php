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

use Drewlabs\Core\Helpers\Arr as HelpersArr;
use Drewlabs\GCli\Cache\Cache;
use Drewlabs\GCli\Cache\CacheableTables;
use Drewlabs\GCli\Exceptions\IOException;
use Drewlabs\GCli\IO\Path;
use Drewlabs\GCli\Options;

class CommandArguments
{
    /** @var string[] */
    public const SHOULD_RESOLVE = [
        'path',
        'cache',
        'plugins',
        'routingfilename',
        'routePrefix',
        'middleware',
        'only',
        'excepts',
        'package',
        'subPackage',
        'camelize-attributes',
        'relations',
        'toones',
        'onetomany',
        'manytomany',
        'onethroughs',
        'manythroughs',
        'no-model-accessors',
        'policies',
        'htr',
        'htrDir',
        'htrHost',
        'htrFormat',
        'disableCache',
        'input',
        'format',
    ];
    /**
     * @var array
     */
    private $options;

    /**
     * Creates a command line argument instance.
     *
     * @return void
     */
    public function __construct(array $options = [])
    {
        $this->options = $options ?? [];
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
        $options = new Options(HelpersArr::except($this->options, static::SHOULD_RESOLVE));
        $options = $options->merge([
            'path' => Path::new($basePath)->__toString(),
            'cache' => false === (bool) $this->getOption('disableCache'),
            'policies' => $this->getOption('policies') ?? false,
            'plugins' => $this->getOption('plugins', []),
        ]);

        if (null !== ($path = $this->getOption('input'))) {
            $result = 'json' === $this->getOption('format') ? Options::json($path) : Options::yaml($path);
            $options = $options->merge($result->all());
        }
        if ($filename = $this->getOption('routingfilename')) {
            $options = $options->merge(['routes.filename' => $filename]);
        }
        if ($prefix = $this->getOption('routePrefix')) {
            $options = $options->merge(['routes.prefix' => $prefix]);
        }
        if ($middleware = $this->getOption('middleware')) {
            $options = $options->merge(['routes.middleware' => $middleware]);
        }
        if ($tables = $this->getOption('only', [])) {
            $options = $options->merge(['includes' => $tables]);
        }

        if ($excludes = $this->getOption('excepts', [])) {
            $options = $options->merge(['excludes' => $excludes]);
        }
        /** @var CacheableTables $tablesPool */
        if ($options->get('cache', false) && null !== ($tablesPool = Cache::new((string) $cachePath)->load(CacheableTables::class))) {
            $options = $options->merge(['excludes' => $tablesPool->getTables()]);
        }
        if ($namespace = $this->getOption('package')) {
            $options = $options->merge(['namespace.default' => $namespace]);
        }

        if ($domain = $this->getOption('subPackage')) {
            $options = $options->merge(['namespace.domain' => $domain]);
        }

        if ($schema = $this->getOption('schema')) {
            $options = $options->merge(['schema' => $schema]);
        }

        if (null !== ($camelize = (bool) $this->getOption('camelize-attributes', $this->getOption('camelize'), false))) {
            $options = $options->merge(['models.attributes.camelize' => $camelize]);
        }

        if ((bool) $this->getOption('relations')) {
            $options = $options->merge([
                'models.relations.provides' => true,
                'models.relations.one-to-one' => iterator_to_array($this->flatten($this->getOption('toones', []))),
                'models.relations.one-to-many' => iterator_to_array($this->flatten($this->getOption('onetomany', []))),
                'models.relations.many-to-many' => iterator_to_array($this->flatten($this->getOption('manytomany', []))),
                'models.relations.one-to-one-though' => iterator_to_array($this->flatten($this->getOption('onethroughs', []))),
                'models.relations.one-to-many-though' => iterator_to_array($this->flatten($this->getOption('manythroughs', []))),
            ]);
        }
        // Add model accessors' flag
        $options = $options->merge(['models.attributes.accessors' => !(bool) $this->getOption('no-model-accessors')]);

        if ($this->getOption('htr', false)) {
            $options = $options->merge(['htr' => []]);
        }

        if ($htrDir = $this->getOption('htrDir')) {
            $options = $options->merge(['htr.directory' => $htrDir]);
        }

        if ($htrHost = $this->getOption('htrHost')) {
            $options = $options->merge(['htr.host' => $htrHost]);
        }

        if ($htrFormat = $this->getOption('htrFormat')) {
            $options = $options->merge(['htr.format' => $htrFormat]);
        }

        return $options;
    }

    /**
     * resolve an option matchin the provided name.
     *
     * @param mixed $default
     *
     * @return mixed
     */
    private function getOption(string $name, $default = null)
    {
        return $this->options[$name] ?? $default;
    }

    /**
     * Creates a generator of flatten array.
     *
     * @return \Generator<int, string, mixed, void>
     */
    private function flatten(array $composed)
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
