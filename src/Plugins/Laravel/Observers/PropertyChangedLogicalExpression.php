<?php

namespace Drewlabs\GCli\Plugins\Laravel\Observers;

final class PropertyChangedLogicalExpression
{
    /** @var string */
    private $property;

    /** @var string */
    private $value;

    /** @var string */
    private $operator = '===';

    /**
     * property change event trigger expression
     * 
     * @param string $property 
     * @param string|null $value 
     * @param string $operator 
     * @return void 
     */
    public function __construct(string $property, ?string $value = null, string $operator = '===')
    {
        $this->property = $property;
        $this->value = $value;
        $this->operator = $operator ?? '===';
    }

    public function __toString(): string
    {
        $expression = new LogicalExpression($this->property, $this->value, $this->operator);
        return sprintf("\$model->wasChanged('%s') && %s", $this->property, $expression->__toString());
    }
}