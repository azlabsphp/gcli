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
use Symfony\Component\Console\Helper\ProgressBar;

final class ProgressbarIndicator implements Progress
{
    /**
     * @var ProgressBar
     */
    private $bar;

    public function __construct(ProgressBar $bar)
    {
        $this->bar = $bar;
    }

    public function start(): void
    {
        $this->bar->start();
    }

    public function advance(): void
    {
        $this->bar->advance();
    }

    public function complete(): void
    {
        $this->bar->finish();
    }
}
