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

namespace Drewlabs\GCli\Traits;

trait ProvidesContracts
{
    /**
     * @var string[]
     */
    private $contracts;

    public function addContracts(...$contracts)
    {
        $this->contracts = array_unique(array_merge($this->contracts ?? [], $contracts));

        return $this;
    }

    public function getContracts()
    {
        return $this->contracts ?? [];
    }
}
