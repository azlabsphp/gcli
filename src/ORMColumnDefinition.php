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

namespace Drewlabs\ComponentGenerators;

use Drewlabs\ComponentGenerators\Contracts\ORMModelColumnDefintion;
use Drewlabs\Core\EntityObject\ValueObject;

class ORMColumnDefinition extends ValueObject implements ORMModelColumnDefintion
{
    public function name()
    {
        return $this->name_ ?? 'column';
    }

    public function type()
    {
        return $this->type_ ?? 'string';
    }

    protected function getJsonableAttributes()
    {
        return [
            'name_' => 'name',
            'type_' => 'type',
        ];
    }
}
