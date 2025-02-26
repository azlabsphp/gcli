<?php


namespace Drewlabs\GCli\Plugins\Laravel\Observers;

final class Property
{
    /** @var string */
    private $value;

    /** @var string */
    private $type;

    /** @var string cached `__toString` value of the current property */
    private $cached = null;

    /**
     * L-value expression class constructor
     * 
     * @param string $value 
     * @return void 
     */
    public function __construct(string $value, ?string $type = null)
    {
        $this->value = $value;
        $this->type  = $type;
    }

    /**
     * property class factory function form expression
     * 
     * @param string $expr 
     * @return static 
     */
    public static function create(string $expr)
    {
        $pos = strpos($expr, ':');
        $type = $pos !== false ? trim(substr($expr, 0, $pos)) : 'mixed';
        $value = $pos !== false ? trim(substr($expr, $pos + 1)) : $expr;
        return new static($value, $type);
    }

    public function __toString(): string
    {
        if (is_null($this->cached)) {
            $type = $this->type ?? 'mixed';
            if (((false !== ($offset_1 = strpos($this->value, '['))) && (false !== ($offset_2 = strpos($this->value, ']')))) || ((false !== ($offset_1 = strpos($this->value, '{'))) && (false !== ($offset_2 = strpos($this->value, '}'))))) {
                $this->cached = $this->getExpression($type, sprintf("\$model->getRawPropertyValue('%s')", trim(substr($this->value, $offset_1 + 1, $offset_2 - \strlen(substr($this->value, 0, $offset_1 + 1))))));
            } else {
                $this->cached = $this->getValueExpression($type, $this->value);
            }
        }
        return $this->cached;
    }

    private function getExpression(string $type, string $value)
    {
        switch (strtolower($type)) {
            case 'float':
            case 'decimal':
                return 'floatval(' . $value . ')';
            case 'int':
                return 'intval(' . $value . ')';
            case 'str':
            case 'string':
                return 'strval(' . $value . ')';
            case 'str:upper':
            case 'string::upper':
                return "\strtoupper(strval(" . $value . '))';
            case 'str:lower':
            case 'string::lower':
                return "\strtolower(strval(" . $value . '))';
            case 'date':
                return '\DateTimeImmutable::createFromTimestamp(strtotime(' . $value . '))';
            default:
                return $value;
        }
    }

    private function getValueExpression(string $type, string $value)
    {
        switch (strtolower($type)) {
            case 'float':
            case 'decimal':
                $pos_2 = strpos($value, ':');
                $p = $pos_2 ? trim(substr($value, 0, $pos_2)) : $value;
                $precision = $pos_2 ? (int) (empty($result = trim(substr($value, $pos_2 + 1))) ? 2 : $result) : 2;
                return sprintf('%.' . $precision . 'f', $p);
            case 'int':
                return sprintf('%s', intval($value));
            case 'str':
            case 'string':
                return sprintf("'%s'", $value);
            case 'str:upper':
            case 'string::upper':
                return sprintf("\strtoupper('%s')", $value);
            case 'str:lower':
            case 'string::lower':
                return sprintf("\strtolower('%s')", $value);
            case 'date':
                return sprintf("\DateTimeImmutable::createFromTimestamp(strtotime(%s))", $value);
            default:
                return $value;
        }
    }
}
