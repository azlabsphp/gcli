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

namespace Drewlabs\GCli\Exceptions;

class BuildErrorException extends \Exception
{
    /**
     * Exception class instance initializer
     * 
     * @param string $blueprint
     * 
     * @return void 
     */
    public function __construct(string $blueprint)
    {
        $message = "Error while building component using $blueprint: Component was not builded before serialization";
        parent::__construct($message);
    }
}
