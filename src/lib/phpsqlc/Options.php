<?php

namespace Drewlabs\PHPSQLC;

use ArrayAccess;

class Options implements ArrayAccess
{
    /** @var array */
    private $options;

    /** @var array Default converter options */
    private const DEFAULTS = [
        'facade' => 'DB::', // change facade to builder
        'group' => true
    ];

    public function __construct(array  $options)
    {
        // override reserved options
        $options['settings'] = ['agg' => ['sum', 'min', 'max', 'avg', 'sum', 'count'], 'fns' => ['date', 'month', 'year', 'day', 'time']];
        foreach (static::DEFAULTS as $k => $v) {
            if (!key_exists($k, $options)) {
                $options[$k] = $v;
                continue;
            }

            if (gettype($options[$k]) != gettype(static::DEFAULTS[$k])) {
                throw new \Exception('Invalid type in options. [' . $k . ' param type must be ' . gettype(static::DEFAULTS[$k]) . ']');
            }
        }
        $this->options = $options;
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->options) && isset($this->options[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->options[$offset] ?? null;
    }

    public function offsetSet($offset,  $value): void
    {
        $this->options[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->options, $offset);
    }

    /**
     * returns list of configured options
     * 
     * @return array 
     */
    public function get()
    {
        return $this->options;
    }
}
