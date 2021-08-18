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

interface EloquentORMModelBuilder extends ORMModelBuilder
{
    /**
     * Set the list of appendable columns of the model.
     *
     * @return self
     */
    public function setAppends(array $columns);

    /**
     * Set the list of columns that must be hidden when model is being serialized.
     *
     * @return self
     */
    public function setHiddenColumns(array $columns);

    /**
     * Whether the model has updated_at and created_at columns.
     *
     * @return bool
     */
    public function hasTimestamps(bool $value);

    /**
     * Set list of relation methods names associated with the method.
     *
     * @return self
     */
    public function setRelationMethods(array $names);
}
