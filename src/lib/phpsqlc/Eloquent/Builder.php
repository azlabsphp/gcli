<?php

namespace Drewlabs\PHPSQLC\Eloquent;

use Drewlabs\PHPSQLC\AbstractBuilder;
use Drewlabs\PHPSQLC\Eloquent\Builders\CriterionBuilder;
use Drewlabs\PHPSQLC\Eloquent\Builders\DeleteBuilder;
use Drewlabs\PHPSQLC\Eloquent\Builders\FromBuilder;
use Drewlabs\PHPSQLC\Eloquent\Builders\GroupByBuilder;
use Drewlabs\PHPSQLC\Eloquent\Builders\HavingBuilder;
use Drewlabs\PHPSQLC\Eloquent\Builders\InsertBuilder;
use Drewlabs\PHPSQLC\Eloquent\Builders\JoinBuilder;
use Drewlabs\PHPSQLC\Eloquent\Builders\LimitBuilder;
use Drewlabs\PHPSQLC\Eloquent\Builders\OrderBuilder;
use Drewlabs\PHPSQLC\Eloquent\Builders\RawBuilder;
use Drewlabs\PHPSQLC\Eloquent\Builders\SelectBuilder;
use Drewlabs\PHPSQLC\Eloquent\Builders\UnionBuilder;
use Drewlabs\PHPSQLC\Eloquent\Builders\UpdateBuilder;
use Drewlabs\PHPSQLC\Extractors\CriterionExtractor;
use Drewlabs\PHPSQLC\Extractors\DeleteExtractor;
use Drewlabs\PHPSQLC\Extractors\FromExtractor;
use Drewlabs\PHPSQLC\Extractors\GroupByExtractor;
use Drewlabs\PHPSQLC\Extractors\HavingExtractor;
use Drewlabs\PHPSQLC\Extractors\InsertExtractor;
use Drewlabs\PHPSQLC\Extractors\JoinExtractor;
use Drewlabs\PHPSQLC\Extractors\LimitExtractor;
use Drewlabs\PHPSQLC\Extractors\OrderExtractor;
use Drewlabs\PHPSQLC\Extractors\SelectExtractor;
use Drewlabs\PHPSQLC\Extractors\UpdateExtractor;
use Drewlabs\PHPSQLC\Options;

/**
 * This class orchestrates the process between Extractors and Builders in order to produce parts of Query Builder and arranges them
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
class Builder extends AbstractBuilder
{
    /** @var \Drewlabs\PHPSQLC\Options */
    private $options;

    /** @var array */
    private $skipBag;

    /** @var bool */
    private $qbClosed;

    /** @var bool */
    private bool $inUnion = false;

    /** @var RawBuilder */
    private $rawExpressionBuilder;

    /**
     * builder class constructor
     * 
     * @param array $options 
     * @return void 
     */
    public function __construct(array $options = [])
    {
        $this->options = new Options($options);
        $this->skipBag = [];
        $this->rawExpressionBuilder = new RawBuilder($this->options['facade'] ?? 'DB::');
    }

    /**
     * build a select statement
     * 
     * @param mixed $value 
     * @param mixed $parsed 
     */
    public function select($value, $parsed)
    {
        // $this->isSelect = true;
        $extractor = new SelectExtractor($this->options['settings']['fns'] ?? []);
        $builder = new SelectBuilder($this->rawExpressionBuilder);

        $parts = $extractor->extract($value, $parsed);
        $result = $builder->build($parts, $this->skipBag);

        if ('eq' === ($queryType = $builder->getQueryType())) {
            $this->qb = $result;
        }

        if ($queryType === 'lastly') {
            $this->lastly = $result;
        }

        $this->qbClosed = $builder->getClosesQuery();
    }

    /**
     * build a from statement
     * 
     * @param mixed $value 
     * @param mixed $parsed 
     */
    public function from($value, $parsed)
    {
        $fromExtractor = new FromExtractor($this->options);
        $fromBuilder = new FromBuilder($this->rawExpressionBuilder);

        if ($this->isSingleTable($parsed)) {
            $fromParts = $fromExtractor->extractSingle($value);
            $this->qb = $fromBuilder->build($fromParts, $this->skipBag) . $this->qb;
            return;
        }
        // more than one table involved ?
        $fromParts = $fromExtractor->extract($value);
        if (isset($fromParts['joins'])) {
            throw new \Exception('Invalid join type found! ');
        }


        $this->qb = $fromBuilder->build($fromParts) . $this->qb;

        $joinExtractor = new JoinExtractor(new CriterionExtractor($this->options['group'] ?? false, $this->options['settings']['fns'] ?? []));
        $joinBuilder = new JoinBuilder($this->rawExpressionBuilder);

        $this->qb .= $joinBuilder->build($joinExtractor->extract($value));
    }

    /**
     * build a where statement
     * @param mixed $value 
     * @return void 
     */
    public function where($value)
    {
        $extractor = new CriterionExtractor($this->options['group'] ?? false, $this->options['settings']['fns'] ?? []);
        $builder = new CriterionBuilder($this->rawExpressionBuilder);

        $part = [];
        $q = $extractor->extractAsArray($value, $part) ? $builder->buildAsArray($part) : $builder->build($extractor->extract($value));
        $this->qb .= $q;
    }

    /**
     * build a group by statement
     * 
     * @param mixed $value 
     * @return void 
     */
    public function group_by($value)
    {
        $extractor = new GroupByExtractor($this->options);
        $builder = new GroupByBuilder;

        $parts = $extractor->extract($value);
        $q = $builder->build($parts);
        $this->qb .= $q;
    }

    /**
     * build a limit statement
     * 
     * @param mixed $value 
     * @return void 
     */
    public function limit($value)
    {
        $extractor = new LimitExtractor($this->options);
        $builder = new LimitBuilder;

        $parts = $extractor->extract($value);
        $q = $builder->build($parts);
        $this->qb .= $q;
    }

    /**
     * build a having statement
     * 
     * @param mixed $value 
     * @return void 
     */
    public function having($value)
    {
        $extractor = new HavingExtractor(new CriterionExtractor($this->options['group'] ?? false, $this->options['settings']['fns'] ?? []));
        $builder = new HavingBuilder($this->rawExpressionBuilder);

        $parts = $extractor->extract($value);
        $q = $builder->build($parts);
        $this->qb .= $q;
    }

    /**
     * build an order by statement
     * 
     * @param mixed $value 
     * @return void 
     */
    public function order($value)
    {
        $extractor = new OrderExtractor($this->options);
        $builder = new OrderBuilder;

        $parts = $extractor->extract($value);
        $q = $builder->build($parts);
        $this->qb .= $q;
    }

    /**
     * build an insert statement
     * 
     * @param mixed $value 
     * @param mixed $parsed 
     * @return void 
     */
    public function insert($value, $parsed)
    {
        $extractor = new InsertExtractor($this->options);
        $builder = new InsertBuilder;

        $parts = $extractor->extract($value, $parsed);
        $q = $builder->build($parts);
        $this->qb .= $q;

        unset($this->options['command']);
    }

    /**
     * build an update statement
     * 
     * @param mixed $value 
     * @param mixed $parsed 
     * @return void 
     */
    public function update($value, $parsed)
    {
        $extractor = new UpdateExtractor(new CriterionExtractor($this->options['group'] ?? false, $this->options['settings']['fns'] ?? []));
        $builder = new UpdateBuilder($this->rawExpressionBuilder);

        $parts = $extractor->extract($value, $parsed);
        $q = $builder->build($parts, $this->skipBag);
        $this->lastly = $q;
    }

    /**
     * build a delete statement
     * 
     * @param mixed $parsed 
     * @return void 
     */
    public function delete($parsed)
    {
        $extractor = new DeleteExtractor($this->options);
        $builder = new DeleteBuilder;
        $parts = $extractor->extract([], $parsed);
        $this->lastly = $builder->build($parts, $this->skipBag);
    }

    /**
     * build a union statement
     * 
     * @param mixed $parts 
     * @return void 
     */
    public function union($parts)
    {
        $builder = new UnionBuilder;
        $this->qb = $builder->build($parts);
    }

    public function build(array $sql, array $unions = [], bool $collect = false): AbstractBuilder
    {
        // output generated by `greenlion/php-sql-parser` will emit table name
        // as embeded field of INSERT or UPDATE keys (when executing INSERT/UPDATE)
        // queries. In order to provide consitency in sql builders, we add the table
        // name to `FROM` statement in the output
        foreach ($sql as $k => $values) {
            if ($k === 'UPDATE' || $k === 'INSERT') {
                foreach ($values as $value) {
                    if ($value['expr_type'] == 'table') {
                        $sql['FROM'][] = $value;
                    }
                }
            }
        }

        foreach ($sql as $k => $value) {
            if (in_array($k, $this->skipBag)) {
                continue;
            }

            switch ($k) {
                case 'SELECT':
                    $this->select($value, $sql);
                    break;
                case 'FROM':
                    $this->from($value, $sql);
                    break;
                case 'WHERE':
                    $this->where($value);
                    break;
                case 'GROUP':
                    $this->group_by($value);
                    break;
                case 'LIMIT':
                    $this->limit($value);
                    break;
                case 'HAVING':
                    $this->having($value);
                    break;
                case 'ORDER':
                    $this->order($value);
                    break;
                case 'INSERT':
                    $this->insert($value, $sql);
                    $this->qbClosed = true;
                    break;
                case 'REPLACE':
                    throw new \Exception('REPLACE statement is not supported');
                case 'UPDATE':
                    $this->update($value, $sql);
                    $this->qbClosed = true;
                    break;
                case "DELETE":
                    $this->qbClosed = true;
                    $this->delete($sql);
                    break;
                case "UNION":
                    $parts = [];
                    $this->inUnion = true;
                    foreach ($value as $key => $q) {
                        $this->resetQuery();
                        $singleParts = $this->build($q, $unions);
                        $part = ['str' => $singleParts];
                        if ($key > 0) {
                            $part['is_all'] = $unions[$key];
                        }
                        $parts[] = $part;
                    }
                    $this->inUnion = false;
                    $this->union($parts);
                    return $this;
                default:
                    break;
            }
        }

        return $this;
    }

    public function getQuery(bool $collect = false): string
    {
        $this->qb .= $this->lastly;
        if (!$this->qbClosed) {
            $this->qb .= $this->inUnion ? '' : ($collect ? '->get()' : '');
        }

        if (!$this->inUnion) {
            $this->qb .= ';';
        }

        return ($this->options['facade'] ?? '') . $this->qb;
    }
}
