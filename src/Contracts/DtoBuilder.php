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

interface DtoBuilder extends ComponentBuilder
{
    /**
     * Set the cast attributes.
     *
     * @return static
     */
    public function setCasts(array $casts = []);

    /**
     * Configure support of camel case transformation for the current instance.
     *
     * @return static
     */
    public function setCamelizeProperties(bool $value);

    /**
     * Set the list of hidden properties.
     *
     * @return static
     */
    public function setHidden(array $properties = []);
}
