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

final class PropertyChangedExpression
{
    /** @var string */
    private $property;

    /**
     * property change event trigger expression.
     *
     * @return void
     */
    public function __construct(string $property)
    {
        $this->property = $property;
    }

    public function __toString(): string
    {
        return sprintf("\$model->wasChanged('%s')", new PropertyName($this->property));
    }
}
