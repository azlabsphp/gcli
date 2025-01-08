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

interface ViewModelBuilder extends ComponentBuilder
{
    /**
     * returns the list of rules during create action.
     *
     * @return array
     */
    public function getRules(): array;

    /**
     * returns list of rules during update action.
     *
     * @return array
     */
    public function getUpdateRules(): array;
}