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

namespace Drewlabs\ComponentGenerators\Contracts;

interface ORMModelBuilder extends ComponentBuilder
{
    /**
     * Set the table binded to the Model/Entity.
     *
     * @return self
     */
    public function setTableName(string $table);

    /**
     * Set the list of columns that are attached to the Model/Entity properties.
     *
     * @param ORMColumnDefinition[] $columns
     *
     * @return self
     */
    public function setColumns(array $columns = []);

    /**
     * Set the primary key column name.
     *
     * @return self
     */
    public function setKeyName(string $name);

    /**
     * Indicates whether the model primary key column is auto incrementable.
     *
     * @return bool
     */
    public function doesNotAutoIncrements();

    /**
     * Creates the model as a view model.
     *
     * @return self
     */
    public function asViewModel();

    /**
     * Returns the ORM Model definition used to build the model
     * 
     * @return ORMModelDefinition
     */
    public function getDefinition();
}
