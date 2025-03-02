<?php

namespace Drewlabs\PHPSQLC\Eloquent\Builders;

use Drewlabs\PHPSQLC\Utils\Expression;

/**
 * This class constructs and produces following Query Builder methods :
 *
 *  update
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
class UpdateBuilder extends AbstractBuilder implements Builder
{

    /** @var RawBuilder */
    private $builder;

    /**
     * criterion statement builder class constructor
     * 
     * @param RawBuilder $builder 
     * @return void 
     */
    public function __construct(RawBuilder $builder)
    {
        $this->builder = $builder;
    }

    public function build(array $parts, array &$skipBag = []): string
    {
        $skipBag[] = 'SET';
        return '->update(' . $this->getRecords($parts['records']) . ')';
    }

    private function getRecords($records)
    {
        if (empty($records))
            return '[]';

        $array = '';
        foreach ($records as $record) {
            if (!empty($array)) {
                $array .= ', ';
            }
            $array .= (Expression::quote($record['field']) . ' => ') . $this->builder->build($record['value'], $record['raw_val']);
        }

        return '[' . $array . ']';
    }
}
