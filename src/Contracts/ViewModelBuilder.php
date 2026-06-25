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
     * set data transfer object class path on the builder
     * 
     * @param string $path 
     * @return static 
     */
    public function withDto(string $path);

    /**
     * returns the list of rules during create action.
     */
    public function getRules(): array;

    /**
     * returns list of rules during update action.
     */
    public function getUpdateRules(): array;
}
