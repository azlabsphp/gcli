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

interface Observer
{
    /**
     * returns the object from which event was dispatched
     * 
     * @return null|Blueprint 
     */
    public function getEventSource(): ?Blueprint;



    /**
     * set the observable event source
     * 
     * @return void 
     */
    public function setEventSource(Blueprint $value);
}