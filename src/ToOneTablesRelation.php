<?php

declare(strict_types=1);

/*
 * This file is part of the Drewlabs package.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\ComponentGenerators;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;

class ToOneTablesRelation
{

    /**
     * Table at the left hand side of the relation
     * 
     * @var string
     */
    private $left;

    /**
     * Table at the right hand side of the relation
     * 
     * @var string
     */
    private $right;

    /**
     * 
     * @var string
     */
    private $name;

    /**
     * Creates the tables relation instance
     * 
     * @param string $left 
     * @param string $intermediary 
     * @param string $right 
     * @throws InvalidArgumentException 
     */
    public function __construct(string $left, string $right)
    {
        $this->left = $left;
        $this->right = $right;
    }

    /**
     * Creates an instance of the relation class
     * 
     * @param string $value 
     * @return string 
     * @throws ReflectionException 
     */
    public static function create(string $value)
    {
        /**
         * @var self
         */
        $object = (new ReflectionClass(__CLASS__))->newInstanceWithoutConstructor();
        $object->setTables($value);
        return $object;
    }

    /**
     * Relation tables setter
     * 
     * @param string $value
     * 
     * @throws InvalidArgumentException 
     */
    private function setTables(string $value)
    {
        $parts = explode('->', $value);
        if (count($parts) !== 2) {
            throw new InvalidArgumentException('one-to-one relation incorrectly formed, (ex: left_table->right_table)');
        }
        $this->left = $parts[0];
        $this->right = false === strpos($parts[1] ?? '', ':') ? $parts[1] : explode(':', $parts[1])[0];
        $this->name = false === strpos($parts[1] ?? '', ':') ? null : (($name = explode(':', $parts[1])[1]) ? $name : null);
    }

    /**
     * Returns the left table of the many through relation
     * 
     * @return string 
     */
    public function leftTable()
    {
        return $this->left;
    }

    /**
     * Returns the right table of the many through relation
     * 
     * @return string 
     */
    public function rightTable()
    {
        return $this->right;
    }

    /**
     * Get relation name
     * 
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the string representation of the relation
     * 
     * @return string 
     */
    public function __toString()
    {
        return sprintf("%s->%s", $this->left, $this->right);
    }
}
