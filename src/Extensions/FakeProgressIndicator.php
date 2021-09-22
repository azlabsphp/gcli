<?php

namespace Drewlabs\ComponentGenerators\Extensions;

use Drewlabs\ComponentGenerators\Extensions\Contracts\Progress;

final class FakeProgressIndicator implements Progress
{

    /**
     * 
     * @var int
     */
    private $steps = 0;

    public function start(): void
    {
        printf("Progress started!");
    }

    public function advance(): void
    {
        $this->steps += 1;
        printf('-');
    }

    public function complete(): void
    {
        dump("\nProgress completed!");
    }
}
