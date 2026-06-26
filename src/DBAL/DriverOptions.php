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

namespace Drewlabs\GCli\DBAL;

class DriverOptions
{
    /** @var array */
    private $options;

    /**
     * class constructor.
     *
     * @return void
     */
    final public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * Creates a database driver options instance.
     *
     * @return DriverOptions
     */
    public static function new(array $options = [])
    {
        $object = new static($options);

        return $object->prepare();
    }

    /**
     * @return self
     */
    public function prepare()
    {
        $options = $this->options;

        if ('pdo_sqlite' === ($options['driver'] ?? null)) {
            $options = $this->prepareSQLiteOptions($options);
        }

        $options = array_filter($options, static function ($value) {
            return null !== $value;
        });

        $this->options = $options;

        return $this;
    }

    /**
     * @return array
     */
    public function get()
    {
        return $this->options;
    }

    /**
     * @return array
     */
    private function prepareSQLiteOptions(array $options = [])
    {
        $options['memory'] = false;
        $options['path'] = $options['dbname'] ?? 'database.sqlite';
        unset($options['dbname']);

        return $options;
    }
}
