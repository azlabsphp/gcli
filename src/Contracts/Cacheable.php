<?php

namespace Drewlabs\ComponentGenerators\Contracts;

use Drewlabs\Contracts\Support\ArrayableInterface;

interface Cacheable extends ArrayableInterface
{
    /**
     * Called for serialization
     * 
     * @return string
     */
    public function serialize();

    /**
     * 
     * @param string $value
     * @return self 
     */
    public function unserialize(string $value);
}