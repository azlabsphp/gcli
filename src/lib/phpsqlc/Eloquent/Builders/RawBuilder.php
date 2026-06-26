<?php

namespace Drewlabs\PHPSQLC\Eloquent\Builders;

use Drewlabs\PHPSQLC\Utils\Expression;

final class RawBuilder
{
    /** @var string */
    private $facade;

    /**
     * raw expression builder class constructor
     * 
     * @param string $facade 
     * @return void 
     */
    public function __construct(string $facade = 'DB::')
    {
        $this->facade = $facade;
    }

    public function build(string $value, bool $isRaw = false): string
    {
        $value = Expression::wrap($value);
        return $isRaw ? $this->facade . 'raw(' . $value . ')' : $value;
    }
}