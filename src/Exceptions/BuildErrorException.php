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

namespace Drewlabs\GCli\Exceptions;

class BuildErrorException extends \Exception
{
    public function __construct(string $clazz)
    {
        $message = "Error while building component using $clazz: Component was not builded before serialization";
        parent::__construct($message);
    }
}
