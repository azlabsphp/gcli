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
use Drewlabs\ComponentGenerators\ManyThoughRelation;
use Drewlabs\ComponentGenerators\ManyThroughTablesRelation;
use Drewlabs\ComponentGenerators\RelationTypes;
use Drewlabs\ComponentGenerators\ToOneTablesRelation;
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Core\Helpers\Str;
use Illuminate\Support\Pluralizer;
use InvalidArgumentException;

trait ReverseEngineerRelations
{
    /**
     * Returns the list of relationship for generated components based on foreign key entries
     * 
     * @param array $values 
     * @param array $tablesindexes
     * @param array $foreignKeys 
     * @param array $manytomany 
     * @param array $toones 
     * @return array 
     * @throws InvalidArgumentException 
     */
    private static function resolveRelations(
        array $values,
        array $tablesindexes,
        array $foreignKeys,
        array $manytomany,
        array $toones
    ) {

        $relations = [];
        $manytomanyrelations = [];
        /**
         * @var ManyThroughTablesRelation[]
         */
        $manytomanyobjects = array_map(function ($current) use ($foreignKeys) {
            $object = ManyThroughTablesRelation::create($current);
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
        }, $manytomany ?? []);

        /**
         * @var ToOneTablesRelation[]
         */
        $ones = array_map(function ($current) {
            return ToOneTablesRelation::create($current);
        }, $toones ?? []);
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
                        $manytomanyresult = array_filter($manytomanyobjects, function (ManyThroughTablesRelation $currrent) use ($foreigntable, $table) {
                            return $currrent->intermediateTable() === $table;
                        });

                        if (!empty($manytomanyresult)) {
                            $manythroughmatch = $manytomanyresult[0];
                            $lefttableresult = array_values(array_filter($values, function ($c) use ($manythroughmatch) {
                                return Arr::get($c, 'table', null) === $manythroughmatch->leftTable();
                            }));
                            $righttableresult = array_values(!empty($lefttableresult) ? array_filter($values, function ($c) use ($manythroughmatch) {
                                return Arr::get($c, 'table', null) === $manythroughmatch->rightTable();
                            }) : []);
                            if (!empty($lefttableresult) && !empty($righttableresult)) {
                                list($righttablecomponent, $lefttablecomponent) = [$righttableresult[0], $lefttableresult[0]];
                                if (
                                    !isset($manytomanyrelations[$table]) &&
                                    !is_null($lefttableclasspath = Arr::get($lefttablecomponent, 'model.classPath'))
                                ) {
                                    $manytomanyrelations[$table] = true;
                                    $relations = self::mergearray($relations, $foreignclasspath, new ManyThoughRelation(
                                        Str::camelize(Pluralizer::plural($manythroughmatch->rightTable()), false),
                                        RelationTypes::MANY_TO_MANY,
                                        $lefttableclasspath,
                                        $table,
                                        $modelclasspath,
                                        $manythroughmatch->getLeftForeignKey(),
                                        $manythroughmatch->getRightForeignKey(),
                                        $manythroughmatch->getLeftLocalkey(),
                                        $manythroughmatch->getRightLocalkey(),
                                        ($righttabledtobuilder = Arr::get($righttablecomponent, 'controller.dto.class')) ? $righttabledtobuilder->getClassPath() : null
                                    ));
                                }
                            }
                        }
                        $relations = self::mergearray(
                            $relations,
                            $foreignclasspath,
                            new BasicRelation(
                                Str::camelize(!empty($filterResult) ? Pluralizer::singular($table) : Pluralizer::plural($table), false),
                                $modelclasspath,
                                $foreigncolum,
                                $localcolumn,
                                !empty($filterResult) ? RelationTypes::ONE_TO_ONE : RelationTypes::ONE_TO_MANY,
                                ($_dtobuilder = Arr::get($component, 'controller.dto.class')) ? $_dtobuilder->getClassPath() : null
                            )
                        );
                        $relations = self::mergearray(
                            $relations,
                            $modelclasspath,
                            new BasicRelation(
                                Str::camelize(Pluralizer::singular($foreigntable), false),
                                $foreignclasspath,
                                $foreigncolum,
                                $localcolumn,
                                RelationTypes::MANY_TO_ONE,
                                ($_dtobuilder = Arr::get($foreingcomponent, 'controller.dto.class')) ? $_dtobuilder->getClassPath() : null
                            )
                        );
                    }
                }
            }
        }
        return $relations;
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
