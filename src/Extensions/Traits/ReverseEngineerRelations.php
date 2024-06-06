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
use Drewlabs\GCli\DirectRelation;
use Drewlabs\GCli\Contracts\ForeignKeyConstraintDefinition;
use Drewlabs\GCli\RelationTypes;
use Drewlabs\GCli\ThroughRelation;
use Drewlabs\GCli\ThroughRelationTables;
use Drewlabs\GCli\DirectRelationTables;
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
        array $tablesindexes,
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
        /** @var ThroughRelationTables[] $manyToMany */
        $manyToMany = self::projectToMany($foreignKeys, $manyToMany);
        /** @var DirectRelationTables[] $ones */
        $ones = array_map(static function ($current) {
            return DirectRelationTables::create($current);
        }, $toones ?? []);
        /** @var DirectRelationTables[] $oneToMany */
        $oneToMany = array_map(static function ($current) {
            return DirectRelationTables::create($current);
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
                    if (null === $localcolumn) {
                        continue;
                    }
                    if (null === $foreigncolum) {
                        continue;
                    }
                    if ($table === $foreign->getLocalTableName()) {
                        $foreigntable = $foreign->getForeignTableName();
                        $foreigntableindex = $tablesindexes[$foreign->getForeignTableName()] ?? null;
                        if (null === $foreigntableindex) {
                            continue;
                        }
                        if (null === (($foreingcomponent = $values[$foreigntableindex]) ?? null)) {
                            continue;
                        }
                        if (null === ($foreignclasspath = Arr::get($foreingcomponent, 'model.classPath'))) {
                            continue;
                        }
                        if (null === ($modelclasspath = Arr::get($component, 'model.classPath'))) {
                            continue;
                        }
                        // Checks if the current table exists in the list of one - to - one relation
                        $oneResult = static::find($ones, static function (DirectRelationTables $currrent) use ($foreigntable, $table) {
                            return ($currrent->leftTable() === $foreigntable) && ($currrent->rightTable() === $table);
                        });
                        if ($oneResult === null) {
                            /** @var DirectRelationTables */
                            $oneToManyResult = static::find($oneToMany, static function (DirectRelationTables $currrent) use ($foreigntable, $table) {
                                return ($currrent->leftTable() === $foreigntable) && ($currrent->rightTable() === $table);
                            });
                        }
                        $relations = self::mergearray(
                            self::mergearray(
                                $relations,
                                $foreignclasspath,
                                // If the relation name is provided in the one to one configuration, we use it, else we fallback to column name or table name
                                new DirectRelation(
                                    Str::camelize(
                                        !is_null($oneResult) ? ($oneResult->getName() ?? Pluralizer::singular(self::trimschema($table, $schema))) : (!is_null($oneToManyResult) ? $oneToManyResult->getName() : Pluralizer::plural(self::trimschema($table, $schema))),
                                        false
                                    ),
                                    $modelclasspath,
                                    $foreigncolum,
                                    $localcolumn,
                                    !is_null($oneResult) ? RelationTypes::ONE_TO_ONE : RelationTypes::ONE_TO_MANY,
                                    ($dtoBuilder = Arr::get($component, 'dto.class')) ? $dtoBuilder->getClassPath() : null
                                )
                            ),
                            $modelclasspath,
                            new DirectRelation(
                                Str::camelize(Pluralizer::singular(self::trimidsuffix($localcolumn)), false),
                                $foreignclasspath,
                                $foreigncolum,
                                $localcolumn,
                                RelationTypes::MANY_TO_ONE,
                                ($dtoBuilder = Arr::get($foreingcomponent, 'dto.class')) ? $dtoBuilder->getClassPath() : null
                            )
                        );
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
                    RelationTypes::ONE_TO_MANY_THROUGH,
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
                    RelationTypes::ONE_TO_ONE_THROUGH,
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
        /**
         * @var ThroughRelationTables[]
         */
        $result = array_values(array_filter($throughs, static function (ThroughRelationTables $currrent) use ($table) {
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
            empty($intermediate)
            || empty($right)
            || null === ($rightclasspath =
                Arr::get($right[0] ?? [], 'model.classPath'))
            || null === ($intermediateclasspath = Arr::get($intermediate[0], 'model.classPath'))
        ) {
            return $relations;
        }

        $name = RelationTypes::ONE_TO_ONE_THROUGH === $type ?
            $through->getName() ?? Str::camelize(Pluralizer::singular(self::trimschema($through->rightTable(), $schema)), false) :
            $through->getName() ?? Str::camelize(Pluralizer::plural(self::trimschema($through->rightTable(), $schema)), false);

        return self::mergearray($relations, $classpath, new ThroughRelation(
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
        ));
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
        /**
         * @var ThroughRelationTables[]
         */
        $result = array_values(array_filter($objects, static function (ThroughRelationTables $currrent) use ($table) {
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
            null === ($rightclasspath = Arr::get($right[0] ?? [], 'model.classPath'))
            || null === ($throughclasspath = Arr::get($intermediate[0] ?? [], 'model.classPath'))
            || null === ($throughtable = Arr::get($intermediate[0] ?? [], 'table'))
        ) {
            return $relations;
        }
        $pivots[] = $throughtable;

        return self::mergearray($relations, $classpath, new ThroughRelation(
            $match->getName() ?? Str::camelize(Pluralizer::plural(self::trimschema($match->rightTable(), $schema)), false),
            RelationTypes::MANY_TO_MANY,
            $rightclasspath,
            $throughtable,
            $throughclasspath,
            $match->getLeftForeignKey(),
            $match->getRightForeignKey(),
            $match->getLeftLocalkey(),
            $match->getRightLocalkey(),
            ($dtobuilder = Arr::get($right[0], 'dto.class')) ? $dtobuilder->getClassPath() : null
        ));
        // #endregion Many To Many relations
    }

    /**
     * Project through relations to supported class instance.
     *
     * @param mixed $foreignKeys
     *
     * @return array<array-key, \Drewlabs\GCli\ThroughRelationTables>
     */
    private static function projectTrhough($foreignKeys, array $throughs)
    {
        return array_map(static function ($current) use ($foreignKeys) {
            $object = ThroughRelationTables::create($current);
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
     * @return array<array-key, \Drewlabs\GCli\ThroughRelationTables>
     */
    private static function projectToMany(array $foreignKeys, array $array)
    {
        return array_map(static function ($current) use ($foreignKeys) {
            $object = ThroughRelationTables::create($current);
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
