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

namespace Drewlabs\ComponentGenerators\Extensions;

use Drewlabs\ComponentGenerators\Extensions\Contracts\Progress;

final class FakeProgressIndicator implements Progress
{
    /**
     * @var int
     */
    private $steps = 0;

    public function start(): void
    {
        printf('Progress started!');
    }

    public function advance(): void
    {
        ++$this->steps;
        printf('-');
    }

    public function complete(): void
    {
        printf(sprintf("\nProgress completed!\n"));
    }
}
