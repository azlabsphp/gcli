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

use LogicException;

final class PropertyLogicalExpression
{
    /** @var string */
    private $property;

    /** @var string */
    private $value;

    /** @var string */
    private $operator = '===';

    /**
     * property change event trigger expression.
     *
     * @return void
     */
    public function __construct(string $property, ?string $value = null, string $operator = '===')
    {
        $this->property = $property;
        $this->value = $value;
        $this->operator = $operator ?? '===';
    }

    /**
     * 
     * @return array 
     */
    public static function compile(string $expr)
    {
    }

    public function __toString(): string
    {
        $expression = new LiteralLogicalExpression($this->property, $this->value, $this->operator);

        return $expression->__toString();
    }
}
