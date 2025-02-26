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

final class LiteralLogicalExpression
{
    /** @var string */
    private $variable;

    /** @var string */
    private $value;

    /** @var string */
    private $operator = '===';

    /**
     * creates new logical expression instance.
     *
     * @param string $operator
     *
     * @return void
     */
    public function __construct(string $variable, ?string $value = null, $operator = '===')
    {
        $this->variable = $variable;
        $this->value = $value;
        $this->operator = $operator ?? '===';
    }

    public function __toString(): string
    {
        if (null === $this->value) {
            return sprintf("!is_null(%s)", Property::create($this->variable));
        }

        if ('null' === strtolower($this->value)) {
            return sprintf("is_null(%s)", Property::create($this->variable));
        }

        $op = '=' === $this->operator || '==' === $this->operator ? '===' : $this->operator;

        return sprintf('%s %s %s', Property::create($this->variable), $op, Property::create($this->value));
    }
}
