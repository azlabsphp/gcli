<?php

namespace Drewlabs\ComponentGenerators\Traits;

trait HasNameAttribute
{
    /**
     * @var string
     */
    private $name_;

    public function setName(string $value)
    {
        $this->name_ = $value;
    }

    /**
     * Returns the component name property value
     * 
     * @return string 
     */
    public function name(): ?string
    {
        return $this->name_;
    }
}