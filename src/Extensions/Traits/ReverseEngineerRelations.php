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

namespace Drewlabs\GCli\Extensions\Traits;

use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Core\Helpers\Str;
use Drewlabs\GCli\DBAL\R\Basic;
use Drewlabs\GCli\Contracts\ForeignKeyConstraintDefinition;
use Drewlabs\GCli\DBAL\R\Config\Basic as BasicConfig;
use Drewlabs\GCli\DBAL\R\Config\Through as ThroughConfig;
use Drewlabs\GCli\DBAL\R\Through;
use Drewlabs\GCli\DBAL\R\Types;
use Drewlabs\GCli\Traits\ProvidesTrimTableSchema;
use Illuminate\Support\Pluralizer;

trait ReverseEngineerRelations
{
    use ProvidesTrimTableSchema;

    /**
     * Remove the _id suffix from column name.
     *
     * @return string
     */
    public static function trimidsuffix(string $column)
    {
        return self::suffixed($column, '_id') ?
            substr($column ?? '', 0, \strlen($column ?? '') - \strlen('_id')) : $column;
    }

    /**
     * Returns the list of relationship for generated components based on foreign key entries.
     *
     * @param ForeignKeyConstraintDefinition[] $foreignKeys
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    private static function resolveRelations(
        array $values,
        // array $tablesindexes,
        array $foreignKeys,
        array $manyToMany,
        array $toones,
        array $oneToMany,
        array $manyThroughs = [],
        array $oneThroughs = [],
        string $schema = null
    ) {
        $relations = [];
        $pivots = [];
        $manyToMany = self::projectToMany($foreignKeys, $manyToMany);
        $ones = array_map(static function ($current) {
            return BasicConfig::create($current);
        }, $toones ?? []);
        $oneToMany = array_map(static function (string $current) {
            return BasicConfig::create($current);
        }, $oneToMany ?? []);
        $manyThroughs = self::projectTrhough($foreignKeys, $manyThroughs);
        $oneThroughs = self::projectTrhough($foreignKeys, $oneThroughs);

        foreach ($values as $component) {
            if ($table = Arr::get($component, 'table')) {
                foreach ($foreignKeys as $foreign) {
                    $foreigncolums = $foreign->getForeignColumns();
                    if (empty($foreigncolums) || \count($foreigncolums ?? []) > 1) {
                        continue;
                    }
                    $foreigncolum = $foreigncolums[0];
                    $localcolumn = ($foreign->localColumns() ?? [])[0] ?? null;
                    if (is_null($localcolumn)) {
                        continue;
                    }
                    if (is_null($foreigncolum)) {
                        continue;
                    }
                    if ($table === $foreign->getLocalTableName()) {
                        $foreigntable = $foreign->getForeignTableName();
                        if (is_null($foreigntable)) {
                            continue;
                        }

                        if (is_null(($foreingcomponent = $values[$foreigntable]) ?? null)) {
                            continue;
                        }

                        if (is_null($foreignclasspath = Arr::get($foreingcomponent, 'model.classPath'))) {
                            continue;
                        }

                        if (is_null($modelclasspath = Arr::get($component, 'model.classPath'))) {
                            continue;
                        }

                        // Checks if the current table exists in the list of one - to - one relation
                        $isToMany = false;
                        $result = static::find($ones, static function (BasicConfig $currrent) use ($foreigntable, $table) {
                            return ($currrent->leftTable() === $foreigntable) && ($currrent->rightTable() === $table);
                        });
                        if (is_null($result)) {
                            $result = static::find($oneToMany, static function (BasicConfig $currrent) use ($foreigntable, $table) {
                                return ($currrent->leftTable() === $foreigntable) && ($currrent->rightTable() === $table);
                            });
                            $isToMany = !is_null($result) ? true : $isToMany;
                        }

                        $basic = new Basic(
                            Str::camelize(!$isToMany ? ($result ? $result->getName() : Pluralizer::singular(self::trimschema($table, $schema))) : ($result ? $result->getName() : Pluralizer::plural(self::trimschema($table, $schema))), false),
                            $modelclasspath,
                            $foreigncolum,
                            $localcolumn,
                            !$isToMany ? Types::ONE_TO_ONE : Types::ONE_TO_MANY,
                            ($dtoBuilder = Arr::get($component, 'dto.class')) ? $dtoBuilder->getClassPath() : null
                        );

                        $reverse = Basic::reverse(
                            Str::camelize(Pluralizer::singular(self::trimidsuffix($localcolumn)), false),
                            $foreignclasspath,
                            $foreigncolum,
                            $localcolumn,
                            Types::MANY_TO_ONE,
                            ($dtoBuilder = Arr::get($foreingcomponent, 'dto.class')) ? $dtoBuilder->getClassPath() : null
                        );

                        $leftTable = $values[$foreigntable];
                        $rightTable = $values[$table];

                        if (!is_null($rightDefinition = Arr::get($rightTable ?? [], 'model.definition'))) {
                            $basic = $basic->withModuleName($rightDefinition->getModuleName());
                        }

                        if (!is_null($leftDefinition = Arr::get($leftTable ?? [], 'model.definition'))) {
                            $reverse = $reverse->withModuleName($leftDefinition->getModuleName());
                        }

                        $relations = self::mergearray(self::mergearray($relations, $foreignclasspath, $basic), $modelclasspath, $reverse);
                    }
                }
                // #region many to many relations
                $relations = self::appendManyToMany(
                    $manyToMany,
                    $table,
                    $values,
                    Arr::get($component, 'model.classPath'),
                    $relations,
                    $pivots,
                    $schema
                );
                // #endregion many to many relations
                // #region append to 1 -> many right relation
                $relations = self::appendToRightRelation(
                    $manyThroughs,
                    Types::ONE_TO_MANY_THROUGH,
                    $table,
                    $values,
                    Arr::get($component, 'model.classPath'),
                    $relations,
                    $schema
                );
                // #endregion append to 1 -> many right relation

                // #region append to 1 -> 1 right relation
                $relations = self::appendToRightRelation(
                    $oneThroughs,
                    Types::ONE_TO_ONE_THROUGH,
                    $table,
                    $values,
                    Arr::get($component, 'model.classPath'),
                    $relations,
                    $schema
                );
                // #endregion append to 1 -> 1 right relation
            }
        }

        return [$relations, array_unique($pivots)];
    }

    /**
     * @param mixed $throughs
     * @param mixed $relations
     *
     * @throws \InvalidArgumentException
     *
     * @return mixed
     */
    private static function appendToRightRelation(
        $throughs,
        string $type,
        string $table,
        array $values,
        string $classpath,
        $relations,
        string $schema = null
    ) {

        $result = array_values(array_filter($throughs, static function (ThroughConfig $currrent) use ($table) {
            return $currrent->leftTable() === $table;
        }));
        if (empty($result)) {
            return $relations;
        }
        $through = $result[0] ?? null;
        if (null === $through) {
            return $relations;
        }
        $intermediate = array_values(array_filter($values, static function ($c) use ($through) {
            return Arr::get($c, 'table', null) === $through->intermediateTable();
        }));
        $right = array_values(!empty($intermediate) ? array_filter($values, static function ($c) use ($through) {
            return Arr::get($c, 'table', null) === $through->rightTable();
        }) : []);

        if (
            empty($intermediate) || empty($right)
            || is_null($rightDefinition = Arr::get($right[0] ??  [], 'model.definition'))
            || is_null($rightclasspath = Arr::get($right[0] ?? [], 'model.classPath'))
            || is_null($intermediateclasspath = Arr::get($intermediate[0], 'model.classPath'))
        ) {
            return $relations;
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
            ($dtobuilder = Arr::get($right[0], 'dto.class')) ? $dtobuilder->getClassPath() : null
        );
        $through = $through->withModuleName($rightDefinition->getModuleName());

        return self::mergearray($relations, $classpath, $through);
    }

    /**
     * @param mixed $objects
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    private static function appendManyToMany(
        $objects,
        string $table,
        array $values,
        string $classpath,
        array $relations,
        array &$pivots,
        string $schema = null
    ) {
        // #region Many To Many relations
        $result = array_values(array_filter($objects, static function (ThroughConfig $currrent) use ($table) {
            return $currrent->leftTable() === $table;
        }));
        if (empty($result)) {
            return $relations;
        }
        $match = $result[0] ?? null;
        if (null === $match) {
            return $relations;
        }
        $intermediate = array_values(array_filter($values, static function ($c) use ($match) {
            return Arr::get($c, 'table', null) === $match->intermediateTable();
        }));

        $right = array_values(!empty($intermediate) ? array_filter($values, static function ($c) use ($match) {
            return Arr::get($c, 'table', null) === $match->rightTable();
        }) : []);

        if (empty($intermediate) || empty($right)) {
            return $relations;
        }
        if (
            is_null($rightclasspath = Arr::get($right[0] ?? [], 'model.classPath'))
            || is_null($rightDefinition = Arr::get($right[0] ??  [], 'model.definition'))
            || is_null($throughclasspath = Arr::get($intermediate[0] ?? [], 'model.classPath'))
            || is_null($throughtable = Arr::get($intermediate[0] ?? [], 'table'))
        ) {
            return $relations;
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
            ($dtobuilder = Arr::get($right[0], 'dto.class')) ? $dtobuilder->getClassPath() : null
        );
        $through = $through->withModuleName($rightDefinition->getModuleName());

        return self::mergearray($relations, $classpath, $through);
        // #endregion Many To Many relations
    }

    /**
     * Project through relations to supported class instance.
     *
     * @param mixed $foreignKeys
     *
     * @return array<array-key, ThroughConfig>
     */
    private static function projectTrhough($foreignKeys, array $throughs)
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
    private static function projectToMany(array $foreignKeys, array $array)
    {
        return array_map(static function ($current) use ($foreignKeys) {
            $object = ThroughConfig::create($current);
            $left = array_values(array_filter($foreignKeys, static function ($foreign) use ($object) {
                return $foreign->getLocalTableName() === $object->intermediateTable() && $foreign->getForeignTableName() === $object->leftTable();
            }));
            $right = array_values(!empty($left) ? array_filter($foreignKeys, static function ($foreign) use ($object) {
                return $foreign->getLocalTableName() === $object->intermediateTable() && $foreign->getForeignTableName() === $object->rightTable();
            }) : []);
            if (!empty($left) && !empty($right)) {
                // The local key is the foreign key on symfony foreign keys constraint
                // and the foreign column the table primary key
                $object = $object->setLeftForeignKey($left[0]->localColumns()[0])
                    ->setRightForeignKey($right[0]->localColumns()[0])
                    ->setLeftLocalkey($left[0]->getForeignColumns()[0])
                    ->setRightLocalkey($right[0]->getForeignColumns()[0]);
            }

            return $object;
        }, $array ?? []);
    }

    /**
     * Merge array values.
     *
     * @param mixed $args
     *
     * @return array
     */
    private static function mergearray(array $list, string $key, ...$args)
    {
        $list[$key] = [...(\array_key_exists($key, $list) ? $list[$key] ?? [] : []), ...$args];

        return $list;
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
    private static function find(array $values, callable $predicate)
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
