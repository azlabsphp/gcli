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

namespace Drewlabs\GCli\Iterators;

use Drewlabs\GCli\Config;
use Drewlabs\GCli\Validation\RulesFactory;
use IteratorAggregate;
use Traversable;
use Drewlabs\GCli\Contracts\ORMModelDefinition;

final class ConfigIterator implements IteratorAggregate
{

    /** @var string */
    private $directory = 'app';

    /** @var string */
    private $namespace = 'App';

    /** @var mixed */
    private $auth = true;

    /** @var string */
    private $domain;

    /** @var string */
    private $schema;

    /** @var bool */
    private $http = false;

    /** @var bool */
    private $policies = false;

    /** @var RulesFactory */
    private $rulesFactory;


    /** @var \Traversable */
    private $items;

    /**
     * Creates new class instance
     * 
     * @param Traversable $items 
     * @return void 
     */
    public function __construct(\Traversable $items)
    {
        $this->items = $items;
    }

    /**
     * Creates new class instance
     * 
     * @param Traversable $items 
     * @return static 
     */
    public static function new(\Traversable $items)
    {
        return new static($items);
    }

    /**
     * Add a factory function that generates validation rules
     * 
     * @param RulesFactory $factory
     * 
     * @return static 
     */
    public function withValidationFactory(RulesFactory $factory)
    {
        $this->rulesFactory = $factory;
        return $this;
    }


    /**
     * Provides a directory in which source code must be generated
     * 
     * @param string $directory
     * 
     * @return static 
     */
    public function inDirectory(string $directory)
    {
        $this->directory = $directory;
        return $this;
    }


    /**
     * Provides a namespace in which components are to be generated
     * 
     * @param string $name 
     * @return static 
     */
    public function inNamespace(string $name)
    {
        $this->namespace = $name;
        return $this;
    }

    /**
     * Disables authentication integration in module generated source code
     * 
     * @return static 
     */
    public function withoutAuth()
    {
        $this->auth = false;
        return $this;
    }

    /**
     * Set the namespace in which components are generated
     * 
     * @param string|null $domain
     * 
     * @return static 
     */
    public function setDomain(string $domain = null)
    {
        $this->domain = !empty($domain) ? $domain : $this->domain;

        return $this;
    }

    /**
     * Set the schema property used when compiling table name
     * 
     * @param string|null $value
     * 
     * @return static 
     */
    public function setSchema(string $value = null)
    {
        $this->schema = $value;

        return $this;
    }

    /**
     * Provides iterator with support for http handlers like controllers, routes, etc...
     * 
     * @return static
     */
    public function withHttpHandlers()
    {
        $this->http = true;

        return $this;
    }

    /**
     * Provides iterator with support for policy components
     * 
     * @return static 
     */
    public function withPolicies()
    {
        $this->policies = true;

        return $this;
    }

    /**
     * {@inheritDoc}
     * 
     * @return Traversable<Config>|Config[] 
     */
    public function getIterator(): \Traversable
    {

        // We apply filters to only generate code for tables that
        // passes the filters
        /** @var ORMModelDefinition */
        foreach ($this->items as $value) {
            $tableName = $value->table();
            $config = new Config(
                $value,
                $this->rulesFactory,
                $this->domain,
                $this->directory,
                $this->namespace,
                $this->schema,
                $this->http,
                $this->auth,
                $this->policies,
            );

            yield $tableName => $config;
        }
    }
}
