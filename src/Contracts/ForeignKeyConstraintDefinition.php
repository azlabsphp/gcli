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

interface ForeignKeyConstraintDefinition
{
    /**
     * Local table name.
     *
     * @return string
     */
    public function getLocalTableName();

    /**
     * Local columns names.
     *
     * @return string|string[]
     */
    public function localColumns();

    /**
     * Foreign table name.
     *
     * @return string
     */
    public function getForeignTableName();

    /**
     * Foreign column names.
     *
     * @return string[]
     */
    public function getForeignColumns();
}
