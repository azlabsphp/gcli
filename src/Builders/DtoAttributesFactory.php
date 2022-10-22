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

namespace Drewlabs\ComponentGenerators\Builders;

interface DtoAttributesFactory
{
    /**
     * Creates the list of attributes required by the dto
     * object.
     *
     * @return array
     */
    public function createDtoAttributes();
}
