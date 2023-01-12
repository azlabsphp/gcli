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

class ThroughRelationTables
{

    /**
     * Table at the left hand side of the relation
     * 
     * @var string
     */
    private $left;

    /**
     * Association table
     * 
     * @var string
     */
    private $intermediate;

    /**
     * Table at the right hand side of the relation
     * 
     * @var string
     */
    private $right;
    /**
     * Left table foreign key column name
     * 
     * @var string
     */
    private $leftforeignkey;
    /**
     * Right table foreign key column name
     * 
     * @var string
     */
    private $rightforeignkey;
    /**
     * Left table local key column name
     * 
     * @var string
     */
    private $leftlocalkey;

    /**
     * Right table local key column name
     * 
     * @var string
     */
    private $rightlocalkey;

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
    public function __construct(string $left, string $intermediary, string $right)
    {
        $this->left = $left;
        $this->intermediate = $intermediary;
        $this->right = $right;
    }

    /**
     * Creates an instance of the relation class
     * 
     * @param string $value 
     * @return self
     *  
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
        if (count($parts) !== 3) {
            throw new InvalidArgumentException('Many through relation incorrectly formed, (ex: left_table->middle_table->right_table)');
        }
        $this->left = $parts[0];
        $this->intermediate = $parts[1];
        $this->right = false === strpos($parts[2] ?? '', ':') ? $parts[2] : explode(':', $parts[2])[0];
        $this->name = false === strpos($parts[2] ?? '', ':') ? null : (($name = explode(':', $parts[2])[1]) ? trim($name) : null);
    }

    public function setLeftForeignKey(string $value)
    {
        $this->leftforeignkey = $value;
        return $this;
    }

    public function setRightForeignKey(string $value)
    {
        $this->rightforeignkey = $value;
        return $this;
    }

    public function setLeftLocalkey(string $value)
    {
        $this->leftlocalkey = $value;
        return $this;
    }

    public function setRightLocalkey(string $value)
    {
        $this->rightlocalkey = $value;
        return $this;
    }

    public function getLeftForeignKey()
    {
        return $this->leftforeignkey;
    }

    public function getRightForeignKey()
    {
        return $this->rightforeignkey;
    }

    public function getLeftLocalkey()
    {
        return $this->leftlocalkey;
    }

    public function getRightLocalkey()
    {
        return $this->rightlocalkey;
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
     * Returns the association table of the many through relation
     * 
     * @return string 
     */
    public function intermediateTable()
    {
        return $this->intermediate;
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
        return sprintf("%s->%s->%s", $this->left, $this->intermediate, $this->right);
    }
}
