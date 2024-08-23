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

namespace Drewlabs\GCli;

use Drewlabs\Core\Helpers\Str;
use Drewlabs\GCli\Config;
use Drewlabs\GCli\Validation\RulesFactory;
use Traversable;
use Drewlabs\GCli\Contracts\ORMModelDefinition;
use Drewlabs\GCli\Traits\ProvidesTrimTableSchema;
use Drewlabs\GCli\DBAL\R\Basic;
use Drewlabs\GCli\DBAL\R\Through;
use Drewlabs\GCli\DBAL\R\Config\Basic as BasicConfig;
use Drewlabs\GCli\DBAL\R\Config\Through as ThroughConfig;
use Illuminate\Support\Pluralizer;
use InvalidArgumentException;
use Drewlabs\GCli\DBAL\R\Types;
use Drewlabs\GCli\Contracts\ForeignKeyConstraintDefinition as ForeignKey;

final class SQLDBCollector
{
    use ProvidesTrimTableSchema;

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

    /** @var string[] */
    private $manyToMany = [];

    /** @var string[] */
    private $toOnes = [];

    /** @var string[] */
    private $oneToMany = [];

    /** @var string[] */
    private $manyThroughs = [];

    /** @var string[] */
    private $oneThroughs = [];

    /** @var bool */
    private $relations = false;

    /**
     * Class constructor
     * 
     * @param array $manyToMany 
     * @param array $toOnes 
     * @param array $oneToMany 
     * @param array $manyThroughs 
     * @param array $oneThroughs 
     * @param string|null $schema 
     * @return void 
     */
    public function __construct(
        array $manyToMany = [],
        array $toOnes = [],
        array $oneToMany = [],
        array $manyThroughs = [],
        array $oneThroughs = [],
        string $schema = null
    ) {
        $this->manyThroughs = $manyThroughs;
        $this->oneThroughs = $oneThroughs;
        $this->oneToMany = $oneToMany;
        $this->toOnes = $toOnes;
        $this->manyToMany = $manyToMany;
        $this->schema = $schema;
    }


    /**
     * Creates new class instance
     * 
     * @param array $manyToMany 
     * @param array $toOnes 
     * @param array $oneToMany 
     * @param array $manyThroughs 
     * @param array $oneThroughs 
     * @param string|null $schema 
     * @return static 
     */
    public static function new(
        array $manyToMany = [],
        array $toOnes = [],
        array $oneToMany = [],
        array $manyThroughs = [],
        array $oneThroughs = [],
        string $schema = null
    ) {
        return new static(
            $manyToMany,
            $toOnes,
            $oneToMany,
            $manyThroughs,
            $oneThroughs,
            $schema
        );
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
    public function useSchema(string $value = null)
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
     * Set the `relations` flag to true to which forces the collector
     * to add relations to table configuration based on foreign key definition
     * 
     * @return static 
     */
    public function withRelations()
    {
        $this->relations = true;
        return $this;
    }


    /**
     * Creates a db configuration containing the list of tables, foreign and unique keys
     * 
     * @param Traversable<ORMModelDefinition> $items
     * 
     * @return DBConfig 
     */
    public function collect(\Traversable $items)
    {
        $foreignKeys = [];
        $uniqueKeys = [];
        $tables = [];

        foreach ($items as $value) {
            foreach ($value->columns() as $column) {
                if ($constraint = $column->foreignConstraint()) {
                    $foreignKeys[] = $constraint;
                }

                if ($constraint = $column->unique()) {
                    $uniqueKeys[] = $constraint;
                }
            }

            $config = new Config(
                $value,
                $this->domain,
                $this->directory,
                $this->namespace,
                $this->schema,
                $this->rulesFactory,
                $this->http,
                $this->auth,
                $this->policies,
            );

            $tables[$value->table()] = $config;
        }

        // write relationship information on the table configuration
        $pivots = $this->relations ? $this->provideRelations($tables, $foreignKeys, $this->schema) : [];

        return new DBConfig($tables, $foreignKeys, $uniqueKeys, $pivots);
    }


    /**
     * Remove the _id suffix from column name.
     *
     * @return string
     */
    public function trimidsuffix(string $column)
    {
        return self::suffixed($column, '_id') ?
            substr($column ?? '', 0, \strlen($column ?? '') - \strlen('_id')) : $column;
    }


    /**
     * Add relation instance to the table configuration based on foreign key definitions
     * 
     * @param array<string,Config> $values 
     * @param array<ForeignKey> $foreignKeys 
     * @param string|null $schema 
     * @return array 
     * @throws InvalidArgumentException 
     */
    private function provideRelations(array $values, array $foreignKeys, string $schema = null)
    {
        $pivots = [];
        $manyToMany = $this->projectToMany($foreignKeys, $this->manyToMany);
        $ones = array_map(static function ($current) {
            return BasicConfig::create($current);
        }, $this->toOnes ?? []);
        $oneToMany = array_map(static function (string $current) {
            return BasicConfig::create($current);
        }, $this->oneToMany ?? []);
        $manyThroughs = $this->projectTrhough($foreignKeys, $this->manyThroughs);
        $oneThroughs = $this->projectTrhough($foreignKeys, $this->oneThroughs);

        foreach ($values as $table => $tableConfig) {
            foreach ($foreignKeys as $foreign) {
                if (is_null($localColumn = ($foreign->localColumns() ?? [])[0] ?? null)) {
                    continue;
                }
                if (is_null($foreignColumn = ($foreign->getForeignColumns())[0] ?? null)) {
                    continue;
                }
                if ($table === $foreign->getLocalTableName()) {
                    $foreignTable = $foreign->getForeignTableName();
                    if (is_null($foreignTable)) {
                        continue;
                    }
                    if (is_null(($foreignTableConfig = $values[$foreignTable]) ?? null)) {
                        continue;
                    }

                    // Checks if the current table exists in the list of one - to - one relation
                    $isToMany = false;
                    $result = $this->find($ones, static function (BasicConfig $currrent) use ($foreignTable, $table) {
                        return ($currrent->leftTable() === $foreignTable) && ($currrent->rightTable() === $table);
                    });
                    if (is_null($result)) {
                        $isToMany = true;
                        $result = $this->find($oneToMany, static function (BasicConfig $currrent) use ($foreignTable, $table) {
                            return ($currrent->leftTable() === $foreignTable) && ($currrent->rightTable() === $table);
                        });
                    }

                    $basic = new Basic(
                        Str::camelize(!$isToMany ? ($result ? $result->getName() : Pluralizer::singular(self::trimschema($table, $this->schema))) : ($result ? $result->getName() : Pluralizer::plural(self::trimschema($table, $schema))), false),
                        $tableConfig->getTableConfig()->getClassPath(),
                        $foreignColumn,
                        $localColumn,
                        !$isToMany ? Types::ONE_TO_ONE : Types::ONE_TO_MANY,
                        $tableConfig->getTableDtoConfig()->getClassPath()
                    );

                    $reverse = Basic::reverse(
                        Str::camelize(Pluralizer::singular($this->trimidsuffix($localColumn)), false),
                        $foreignTableConfig->getTableConfig()->getClassPath(),
                        $foreignColumn,
                        $localColumn,
                        Types::MANY_TO_ONE,
                        $foreignTableConfig->getTableDtoConfig()->getClassPath()
                    );

                    $leftTable = $values[$foreignTable];
                    $rightTable = $values[$table];

                    if (!is_null($rightDefinition = $rightTable->getType())) {
                        $basic = $basic->withModuleName($rightDefinition->getModuleName());
                    }

                    if (!is_null($leftDefinition = $leftTable->getType())) {
                        $reverse = $reverse->withModuleName($leftDefinition->getModuleName());
                    }

                    $foreignTableConfig->addRelation($basic);
                    $tableConfig->addRelation($reverse);
                }
            }

            if (!is_null($t1 = $this->createManyToMany($manyToMany, $table, $values, $pivots, $schema))) {
                $tableConfig->addRelation($t1);
            }

            if (!is_null($t2 = $this->createThrough($manyThroughs, Types::ONE_TO_MANY_THROUGH, $table, $values, $schema))) {
                $tableConfig->addRelation($t2);
            }

            if (!is_null($t3 = $this->createThrough($oneThroughs, Types::ONE_TO_ONE_THROUGH, $table, $values, $schema))) {
                $tableConfig->addRelation($t3);
            }
        }

        return array_unique($pivots);
    }

    /**
     * Creates a 1 -> t -> 1 or 1 -> t -> * relation instance
     * 
     * @param mixed $items 
     * @param string $type 
     * @param string $table 
     * @param array $values 
     * @param string|null $schema 
     * @return mixed|array 
     * @throws InvalidArgumentException 
     */
    private function createThrough(
        array $items,
        string $type,
        string $table,
        array $values,
        string $schema = null
    ) {

        $result = array_values(array_filter($items, static function (ThroughConfig $currrent) use ($table) {
            return $currrent->leftTable() === $table;
        }));

        if (is_null($through = $result[0] ?? null)) {
            return null;
        }
        $intermediate = $values[$through->intermediateTable()] ?? null;
        $right = $values[$through->rightTable()] ?? null;

        if (
            is_null($intermediate)
            || is_null($right)
            || is_null($rightDefinition = $right->getType())
            || is_null($rightclasspath = $right->getTableConfig()->getClassPath())
            || is_null($intermediateclasspath = $intermediate->getTableConfig()->getClassPath())
        ) {
            return null;
        }

        $name = Types::ONE_TO_ONE_THROUGH === $type ?
            $through->getName() ?? Str::camelize(Pluralizer::singular(self::trimschema($through->rightTable(), $schema)), false) :
            $through->getName() ?? Str::camelize(Pluralizer::plural(self::trimschema($through->rightTable(), $schema)), false);

        $through = new Through(
            $name,
            $type,
            $rightclasspath,
            $intermediateclasspath,
            null,
            $through->getLeftForeignKey(),
            $through->getRightForeignKey(),
            $through->getLeftLocalkey(),
            $through->getRightLocalkey(),
            $right->getTableDtoConfig()->getClassPath()
        );

        return $through->withModuleName($rightDefinition->getModuleName());
    }


    /**
     * Creates a * -> t -> * through relation instance
     * 
     * @param array $items 
     * @param string $table 
     * @param array $values 
     * @param array &$pivots 
     * @param string|null $schema 
     * @return null|Through 
     * @throws InvalidArgumentException 
     */
    private function createManyToMany(
        array $items,
        string $table,
        array $values,
        array &$pivots,
        string $schema = null
    ) {
        // #region Many To Many relations
        $result = array_values(array_filter($items, static function (ThroughConfig $currrent) use ($table) {
            return $currrent->leftTable() === $table;
        }));

        if (is_null($match = $result[0] ?? null)) {
            return null;
        }
        $intermediate = $values[$match->intermediateTable()] ?? null;
        $right = $values[$match->rightTable()];

        if (
            is_null($intermediate)
            || is_null($right)
            || is_null($rightclasspath = $right->getTableConfig()->getClassPath())
            || is_null($rightDefinition = $right->getType())
            || is_null($throughclasspath = $intermediate->getTableConfig()->getClassPath())
            || is_null($throughtable = $intermediate->getTableConfig()->getTable())
        ) {
            return null;
        }
        $pivots[] = $throughtable;
        $through = new Through(
            $match->getName() ?? Str::camelize(Pluralizer::plural(self::trimschema($match->rightTable(), $schema)), false),
            Types::MANY_TO_MANY,
            $rightclasspath,
            $throughtable,
            $throughclasspath,
            $match->getLeftForeignKey(),
            $match->getRightForeignKey(),
            $match->getLeftLocalkey(),
            $match->getRightLocalkey(),
            $right->getTableDtoConfig()->getClassPath()
        );

        return $through->withModuleName($rightDefinition->getModuleName());
        // #endregion Many To Many relations
    }

    /**
     * Project through relations to supported class instance.
     *
     * @param mixed $foreignKeys
     *
     * @return array<array-key, ThroughConfig>
     */
    private function projectTrhough($foreignKeys, array $throughs)
    {
        return array_map(static function ($current) use ($foreignKeys) {
            $object = ThroughConfig::create($current);
            $left = array_values(array_filter($foreignKeys, static function ($foreign) use ($object) {
                return $foreign->getLocalTableName() === $object->intermediateTable() && $foreign->getForeignTableName() === $object->leftTable();
            }));
            $right = array_values(!empty($left) ? array_filter($foreignKeys, static function ($foreign) use ($object) {
                return $foreign->getLocalTableName() === $object->rightTable() && $foreign->getForeignTableName() === $object->intermediateTable();
            }) : []);
            if (!empty($left) && !empty($right)) {
                $object = $object->setLeftForeignKey($left[0]->localColumns()[0])
                    ->setRightForeignKey($right[0]->localColumns()[0])
                    ->setLeftLocalkey($left[0]->getForeignColumns()[0])
                    ->setRightLocalkey($right[0]->getForeignColumns()[0]);
            }

            return $object;
        }, $throughs);
    }

    /**
     * Project many to many relation to supported class instance.
     *
     * @return array<array-key, ThroughConfig>
     */
    private function projectToMany(array $foreignKeys, array $array)
    {
        return array_map(static function ($current) use ($foreignKeys) {
            $instance = ThroughConfig::create($current);
            $left = array_values(array_filter($foreignKeys, static function ($foreign) use ($instance) {
                return $foreign->getLocalTableName() === $instance->intermediateTable() && $foreign->getForeignTableName() === $instance->leftTable();
            }));
            $right = array_values(!empty($left) ? array_filter($foreignKeys, static function ($foreign) use ($instance) {
                return $foreign->getLocalTableName() === $instance->intermediateTable() && $foreign->getForeignTableName() === $instance->rightTable();
            }) : []);
            if (!empty($left) && !empty($right)) {
                // The local key is the foreign key on symfony foreign keys constraint
                // and the foreign column the table primary key
                $instance = $instance
                    ->setLeftForeignKey($left[0]->localColumns()[0])
                    ->setRightForeignKey($right[0]->localColumns()[0])
                    ->setLeftLocalkey($left[0]->getForeignColumns()[0])
                    ->setRightLocalkey($right[0]->getForeignColumns()[0]);
            }

            return $instance;
        }, $array ?? []);
    }


    /**
     * Find the first element in the array returns true for the predicate
     * 
     * @psalm-template T
     * @template T
     * 
     * @param T[] $values 
     * @param callable $predicate
     * 
     * @return T|null
     */
    private function find(array $values, callable $predicate)
    {
        $result = null;
        foreach ($values as $key => $value) {
            if ($predicate($value, $key)) {
                $result = $value;
                break;
            }
        }
        return $result;
    }
}
