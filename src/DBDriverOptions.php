<?php

namespace Drewlabs\ComponentGenerators;

class DBDriverOptions
{
    private $options;

    public function __construct(array $options = [])
    {
        $this->options = $options ?? [];    
    }

    /**
     * 
     * @return self 
     */
    public function prepare()
    {
        $options = $this->options ?? [];
        // If database driver is SQLite we apply configuration values for SQLite PDO object
        if ('pdo_sqlite' === ($options['driver'] ?? null)) {
            $options = $this->prepareSQLiteOptions($options ?? []);
        }
        // Removes Null keys values
        $options = \array_filter($options, function ($value) {
            return $value !== null;
        });
        // We set the new state of the options property after preparing
        $this->options = $options;
        return $this;
    }

    /**
     * 
     * @return array 
     */
    public function get()
    {
        return $this->options;
    }

    /**
     * 
     * @param array $options 
     * @return array 
     */
    private function prepareSQLiteOptions(array $options = [])
    {
        $options['memory'] = false;
        $options['path'] = $options['dbname'] ?? 'database.sqlite'; // Str::startsWith($options['dbname'] ?? '', '///') ? $options['dbname'] : sprintf("///%s", $options['dbname']) ;
        unset($options['dbname']);
        return $options;
    }

}