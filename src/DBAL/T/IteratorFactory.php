<?php

namespace Drewlabs\GCli\DBAL\T;

use Doctrine\DBAL\DriverManager;

class IteratorFactory
{
    /** @var string */
    private $namespace;

    /** @var array */
    private $dbOptions;

    /** @var string[] */
    private $excepts = [];

    /** @var string[] */
    private $tables = [];

    /** @var string|null */
    private $schema;


    /**
     * Create factory class instance
     * 
     * @param string $namespace
     * @param string|null $schema 
     * @param array $dbOptions 
     * @param array $tables 
     * @param array $excepts 
     */
    public function __construct(string $namespace, ?string $schema = null, array $dbOptions, array $tables = [], array $excepts = [])
    {
        $this->namespace = $namespace;
        $this->schema = $schema;
        $this->dbOptions = $dbOptions;
        $this->tables = $tables;
        $this->excepts = $excepts;
    }

    /**
     * Specifies the tables for which code should be generated.
     *
     * @return self
     */
    public function only(array $tables)
    {
        $self = clone $this;
        $self->tables = $tables;
        return $self;
    }

    public function except(array $tables)
    {
        $self = clone $this;
        $self->excepts = $tables ?? [];
        return $self;
    }

    public function createIterator(): \IteratorAggregate
    {

        $connection = DriverManager::getConnection($this->dbOptions);
        $schemaManager = $connection->createSchemaManager();
        $connection->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
        $tables = $schemaManager->listTables();
        if (!empty($this->excepts)) {
            $tables = array_filter($tables, function (\Doctrine\DBAL\Schema\Table $table) {
                return !\in_array($table->getName(), $this->excepts, true);
            });
        }
        // We apply a filter that returns only tables having the name
        // matching the names specified in the {@see $this->tables_} properties
        if (!empty($this->tables)) {
            $tables = array_filter($tables, function (\Doctrine\DBAL\Schema\Table $table) {
                return \in_array($table->getName(), $this->tables, true);
            });
        }
        return new Iterator($tables, $this->namespace, $this->schema);
    }
}
