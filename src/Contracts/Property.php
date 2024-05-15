<?php

namespace Drewlabs\GCli\Contracts;

interface Property
{
    /**
     * returns property `name` value
     *
     * @return string
     */
    public function name(): string;

    /**
     * returns the raw type declaration of the property
     * 
     * @return string 
     */
    public function getRawType(): string;

    /**
     * boolean flag for required state of the property
     *
     * @return bool
     */
    public function required(): bool;
}
