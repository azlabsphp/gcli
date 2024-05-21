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

namespace Drewlabs\GCli\Validation;

use Drewlabs\GCli\Contracts\ORMModelDefinition as AbstractTable;

interface RulesFactory
{
    /**
     * creates the list of rules for table columns.
     *
     * **Note** `$updates` flag when true, rules are generated
     *           for /PUT or /PATCH request
     */
    public function createRules(AbstractTable $table, bool $updates = false): array;
}
