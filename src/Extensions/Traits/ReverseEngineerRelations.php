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

namespace Drewlabs\ComponentGenerators\Extensions\Traits;

use Drewlabs\ComponentGenerators\BasicRelation;
use Drewlabs\ComponentGenerators\ThoughRelation;
use Drewlabs\ComponentGenerators\ThroughRelationTables;
use Drewlabs\ComponentGenerators\RelationTypes;
use Drewlabs\ComponentGenerators\ToOneTablesRelation;
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Core\Helpers\Str;
use Illuminate\Support\Pluralizer;
use InvalidArgumentException;
use Drewlabs\ComponentGenerators\Contracts\ForeignKeyConstraintDefinition;
use Drewlabs\ComponentGenerators\Traits\ProvidesTrimTableSchema;

trait ReverseEngineerRelations
{
    use ProvidesTrimTableSchema;

    /**
     * Returns the list of relationship for generated components based on foreign key entries
     * 
     * @param array $values 
     * @param array $tablesindexes 
     * @param ForeignKeyConstraintDefinition[] $foreignKeys 
     * @param array $manytomany 
     * @param array $toones 
     * @param array $manythroughs 
     * @param array $onethroughs 
     * @param string $schema 
     * @return array 
     * @throws InvalidArgumentException 
     */
    private static function resolveRelations(
        array $values,
        array $tablesindexes,
        array $foreignKeys,
        array $manytomany,
        array $toones,
        array $manythroughs = [],
        array $onethroughs = [],
        string $schema = null
    ) {

        $relations = [];
        $pivots = [];
        /**
         * @var ThroughRelationTables[]
         */
        $manytomany = self::projectToMany($foreignKeys, $manytomany);
        /**
         * @var ToOneTablesRelation[]
         */
        $ones = array_map(function ($current) {
            return ToOneTablesRelation::create($current);
        }, $toones ?? []);
        $manythroughs = self::projectTrhough($foreignKeys, $manythroughs);
        $onethroughs = self::projectTrhough($foreignKeys, $onethroughs);
        foreach ($values as $component) {
            if ($table = Arr::get($component, 'table')) {
                foreach ($foreignKeys as $foreign) {
                    $foreigncolums = $foreign->getForeignColumns();
                    if (empty($foreigncolums) || count($foreigncolums ?? []) > 1) {
                        continue;
                    }
                    $foreigncolum = $foreigncolums[0];
                    $localcolumn = ($foreign->localColumns() ?? [])[0] ?? 'id';
                    if (is_null($foreigncolum)) {
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
                        if (is_null($foreignclasspath = Arr::get($foreingcomponent, 'model.classPath'))) {
                            continue;
                        }
                        if (is_null($modelclasspath = Arr::get($component, 'model.classPath'))) {
                            continue;
                        }
                        // Checks if the current table exists in the list of one - to - one relation
                        /**
                         * @var ToOneTablesRelation $current
                         */
                        $filterResult = array_filter($ones, function (ToOneTablesRelation $currrent) use ($foreigntable, $table) {
                            return ($currrent->leftTable() === $foreigntable) && ($currrent->rightTable() === $table);
                        });
                        $relations = self::mergearray(
                            self::mergearray(
                                $relations,
                                $foreignclasspath,
                                new BasicRelation(
                                    Str::camelize(!empty($filterResult) ?
                                        Pluralizer::singular(self::trimschema($table, $schema)) :
                                        Pluralizer::plural(self::trimschema($table, $schema)), false),
                                    $modelclasspath,
                                    $foreigncolum,
                                    $localcolumn,
                                    !empty($filterResult) ? RelationTypes::ONE_TO_ONE : RelationTypes::ONE_TO_MANY,
                                    ($_dtobuilder = Arr::get($component, 'controller.dto.class')) ? $_dtobuilder->getClassPath() : null
                                )
                            ),
                            $modelclasspath,
                            new BasicRelation(
                                Str::camelize(Pluralizer::singular(self::trimschema($foreigntable, $schema)), false),
                                $foreignclasspath,
                                $foreigncolum,
                                $localcolumn,
                                RelationTypes::MANY_TO_ONE,
                                ($_dtobuilder = Arr::get($foreingcomponent, 'controller.dto.class')) ? $_dtobuilder->getClassPath() : null
                            )
                        );
                    }
                }
                //#region many to many relations
                $relations = self::appendManyToMany(
                    $manytomany,
                    $table,
                    $values,
                    Arr::get($component, 'model.classPath'),
                    $relations,
                    $pivots,
                    $schema
                );
                //#endregion many to many relations
                //#region append to 1 -> many right relation
                $relations = self::appendToRightRelation(
                    $manythroughs,
                    RelationTypes::ONE_TO_MANY_THROUGH,
                    $table,
                    $values,
                    Arr::get($component, 'model.classPath'),
                    $relations,
                    $schema
                );
                //#endregion append to 1 -> many right relation
                //#region append to 1 -> 1 right relation
                $relations = self::appendToRightRelation(
                    $onethroughs,
                    RelationTypes::ONE_TO_ONE_THROUGH,
                    $table,
                    $values,
                    Arr::get($component, 'model.classPath'),
                    $relations,
                    $schema
                );
                //#endregion append to 1 -> 1 right relation
            }
        }
        return [$relations, array_unique($pivots)];
    }

    /**
     * 
     * @param mixed $throughs 
     * @param string $type 
     * @param string $table 
     * @param array $values 
     * @param string $classpath 
     * @param mixed $relations
     * @param string $schema
     * @return mixed 
     * @throws InvalidArgumentException 
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
        $result = array_values(array_filter($throughs, function (ThroughRelationTables $currrent) use ($table) {
            return $currrent->leftTable() === $table;
        }));
        if (empty($result)) {
            return $relations;
        }
        $through = $result[0] ?? null;
        if (null === $through) {
            return $relations;
        }
        $intermediate = array_values(array_filter($values, function ($c) use ($through) {
            return Arr::get($c, 'table', null) === $through->intermediateTable();
        }));
        $right = array_values(!empty($intermediate) ? array_filter($values, function ($c) use ($through) {
            return Arr::get($c, 'table', null) === $through->rightTable();
        }) : []);

        if (
            empty($intermediate) ||
            empty($right) ||
            is_null($rightclasspath =
                Arr::get($right[0] ?? [], 'model.classPath')) ||
            is_null($intermediateclasspath = Arr::get($intermediate[0], 'model.classPath'))
        ) {
            return $relations;
        }
        return self::mergearray($relations, $classpath, new ThoughRelation(
            $type === RelationTypes::ONE_TO_ONE_THROUGH ?
                Str::camelize(Pluralizer::singular(self::trimschema($through->rightTable(), $schema)), false) :
                Str::camelize(Pluralizer::plural(self::trimschema($through->rightTable(), $schema)), false),
            $type,
            $rightclasspath,
            $intermediateclasspath,
            null,
            $through->getLeftForeignKey(),
            $through->getRightForeignKey(),
            $through->getLeftLocalkey(),
            $through->getRightLocalkey(),
            ($dtobuilder = Arr::get($right[0], 'controller.dto.class')) ? $dtobuilder->getClassPath() : null
        ));
    }


    /**
     * 

     * @param mixed $objects 
     * @param string $table 
     * @param array $values 
     * @param string $classpath 
     * @param array $relations 
     * @param array $pivots
     * @param string $schema
     * @return array 
     * @throws InvalidArgumentException 
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
        $result = array_values(array_filter($objects, function (ThroughRelationTables $currrent) use ($table) {
            return $currrent->leftTable() === $table;
        }));
        if (empty($result)) {
            return $relations;
        }
        $match = $result[0] ?? null;
        if (null === $match) {
            return $relations;
        }
        $intermediate = array_values(array_filter($values, function ($c) use ($match) {
            return Arr::get($c, 'table', null) === $match->intermediateTable();
        }));
        $right = array_values(!empty($intermediate) ? array_filter($values, function ($c) use ($match) {
            return Arr::get($c, 'table', null) === $match->rightTable();
        }) : []);
        if (empty($intermediate) || empty($right)) {
            return $relations;
        }
        if (
            is_null($rightclasspath = Arr::get($right[0] ?? [], 'model.classPath')) ||
            is_null($righttable = Arr::get($right[0] ?? [], 'table')) ||
            is_null($throughclasspath = Arr::get($intermediate[0] ?? [], 'model.classPath'))  ||
            is_null($throughtable = Arr::get($intermediate[0] ?? [], 'table'))
        ) {
            return $relations;
        }
        $pivots[] = $throughtable;
        return self::mergearray($relations, $classpath, new ThoughRelation(
            Str::camelize(Pluralizer::plural(self::trimschema($match->rightTable(), $schema)), false),
            RelationTypes::MANY_TO_MANY,
            $rightclasspath,
            $throughtable,
            $throughclasspath,
            $match->getLeftForeignKey(),
            $match->getRightForeignKey(),
            $match->getLeftLocalkey(),
            $match->getRightLocalkey(),
            ($dtobuilder = Arr::get($right[0], 'controller.dto.class')) ? $dtobuilder->getClassPath() : null
        ));
        // #endregion Many To Many relations
    }

    /**
     * Project through relations to supported class instance
     * 
     * @param mixed $foreignKeys 
     * @param array $throughs 
     * @return array<array-key, \Drewlabs\ComponentGenerators\ThroughRelationTables> 
     */
    private static function projectTrhough($foreignKeys, array $throughs)
    {
        return  array_map(function ($current) use ($foreignKeys) {
            $object = ThroughRelationTables::create($current);
            $left = array_values(array_filter($foreignKeys, function ($foreign) use ($object) {
                return $foreign->getLocalTableName() === $object->intermediateTable() && $foreign->getForeignTableName() === $object->leftTable();
            }));
            $right = array_values(!empty($left) ? array_filter($foreignKeys, function ($foreign) use ($object) {
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
     * Project many to many relation to supported class instance
     * 
     * @param array $foreignKeys 
     * @param array $array 
     * @return array<array-key, \Drewlabs\ComponentGenerators\ThroughRelationTables> 
     */
    private static function projectToMany(array $foreignKeys, array $array)
    {
        return array_map(function ($current) use ($foreignKeys) {
            $object = ThroughRelationTables::create($current);
            $left = array_values(array_filter($foreignKeys, function ($foreign) use ($object) {
                return $foreign->getLocalTableName() === $object->intermediateTable() && $foreign->getForeignTableName() === $object->leftTable();
            }));
            $right = array_values(!empty($left) ? array_filter($foreignKeys, function ($foreign) use ($object) {
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
     * Merge array values
     * 
     * @param array $list 
     * @param string $key 
     * @param mixed $args 
     * @return array 
     */
    private static function mergearray(array $list, string $key, ...$args)
    {
        $list[$key] = [...(array_key_exists($key, $list) ? $list[$key] ?? [] : []), ...$args];
        return $list;
    }
}
