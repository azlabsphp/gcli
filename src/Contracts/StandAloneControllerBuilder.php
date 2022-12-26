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

interface StandaloneControllerBuilder
{
    /**
     * Makes the controller a standalone controller builder
     * 
     * **Note** Standalone controllers does not depend on service
     * classes. They provide query direct calls to databse management
     * layer 
     * 
     * @return ControllerBuilder 
     */
    public function standalone();
}