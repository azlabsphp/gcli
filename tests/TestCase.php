<?php

namespace Drewlabs\ComponentGenerators\Tests;

use PHPUnit\Framework\TestCase as FrameworkTestCase;

class TestCase extends FrameworkTestCase
{
    /**
     * @test
     */
    public function  skipTests()
    {
        $this->assertTrue(true);
    }
}