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

namespace Drewlabs\GCli\Contracts;

interface ProvidesRelations
{
    /**
     * Set the list realtions to be provideded.
     *
     * @return self|ORMModelBuilder
     */
    public function provideRelations(array $relations = []);

    /**
     * Makes the model a pivot model.
     *
     * @return self|ORMModelBuilder
     */
    public function asPivot();
}
