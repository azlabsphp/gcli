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

namespace Drewlabs\GCli\Plugins\Laravel\Observers;

use Drewlabs\GCli\Contracts\Blueprint;


trait HasEventSource
{
    /** @var Blueprint|null */
    private $eventSource;


    public function getEventSource(): ?Blueprint
    {
        return $this->eventSource;
    }

    public function setEventSource(Blueprint $value)
    {
        $this->eventSource = $value;
    }
}