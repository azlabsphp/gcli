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

namespace Drewlabs\GCli\Console;

use Drewlabs\GCli\Console\Contracts\Progress;
use Symfony\Component\Console\Helper\ProgressBar as BaseProgressBar;

final class ProgressBar implements Progress
{
    /**
     * @var BaseProgressBar
     */
    private $bar;

    public function __construct(BaseProgressBar $bar)
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

    public function finish(): void
    {
        $this->bar->finish();
    }
}
