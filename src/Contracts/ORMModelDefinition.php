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

namespace Drewlabs\GCli\Contracts;

interface ORMModelDefinition extends Type
{
    /**
     * Returns the model primary key.
     */
    public function primaryKey(): ?string;

    /**
     * Returns the model associated table name.
     */
    public function table(): ?string;

    /**
     * Returns the list of columns definitions.
     *
     * @return ORMColumnDefinition[]
     */
    public function columns(): array;

    /**
     * Indicates whether the primary key is auto incrementable.
     */
    public function shouldAutoIncrements(): bool;

    /**
     * Returns the namespace of the current model definition.
     *
     * @return string
     */
    public function namespace(): ?string;
}
