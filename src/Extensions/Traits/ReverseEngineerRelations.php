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
use Drewlabs\GCli\BasicRelation;
use Drewlabs\GCli\Contracts\ForeignKeyConstraintDefinition;
use Drewlabs\GCli\RelationTypes;
use Drewlabs\GCli\ThroughRelation;
use Drewlabs\GCli\ThroughRelationTables;
use Drewlabs\GCli\ToOneRelationTables;
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
         * @var ToOneRelationTables[]
         */
        $ones = array_map(static function ($current) {
            return ToOneRelationTables::create($current);
        }, $toones ?? []);
        $manythroughs = self::projectTrhough($foreignKeys, $manythroughs);
        $onethroughs = self::projectTrhough($foreignKeys, $onethroughs);
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
                        /**
                         * @var ToOneRelationTables $current
                         */
                        $oneresult = array_values(array_filter($ones, static function (ToOneRelationTables $currrent) use ($foreigntable, $table) {
                            return ($currrent->leftTable() === $foreigntable) && ($currrent->rightTable() === $table);
                        }));
                        $relations = self::mergearray(
                            self::mergearray(
                                $relations,
                                $foreignclasspath,
                                new BasicRelation(
                                    Str::camelize(!empty($oneresult) ?
                                        // If the relation name is provided in the one to one configuration, we use it, else we
                                        // fallback to column name or table name
                                        ($oneresult[0]->getName() ?? Pluralizer::singular(self::trimschema($table, $schema))) :
                                        Pluralizer::plural(self::trimschema($table, $schema)), false),
                                    $modelclasspath,
                                    $foreigncolum,
                                    $localcolumn,
                                    !empty($oneresult) ? RelationTypes::ONE_TO_ONE : RelationTypes::ONE_TO_MANY,
                                    ($_dtobuilder = Arr::get($component, 'dto.class')) ? $_dtobuilder->getClassPath() : null
                                )
                            ),
                            $modelclasspath,
                            new BasicRelation(
                                Str::camelize(Pluralizer::singular(self::trimidsuffix($localcolumn)), false),
                                $foreignclasspath,
                                $foreigncolum,
                                $localcolumn,
                                RelationTypes::MANY_TO_ONE,
                                ($_dtobuilder = Arr::get($foreingcomponent, 'dto.class')) ? $_dtobuilder->getClassPath() : null
                            )
                        );
                    }
                }
                // #region many to many relations
                $relations = self::appendManyToMany(
                    $manytomany,
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
                    $manythroughs,
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
                    $onethroughs,
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
}
