<?php

declare(strict_types=1);

/*
 * This file is part of the drewlabs namespace.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\GCli;

/** @internal */
class DirectRelationTables
{
    /**
     * Table at the left hand side of the relation.
     *
     * @var string
     */
    private $left;

    /**
     * Table at the right hand side of the relation.
     * @var string
     */
    private $right;

    /** @var string */
    private $name;

    /**
     * Creates the tables relation instance.
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(string $left, string $right)
    {
        $this->left = $left;
        $this->right = $right;
    }

    /**
     * Returns the string representation of the relation.
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf('%s->%s', $this->left, $this->right);
    }

    /**
     * Creates an instance of the relation class.
     *
     * @throws \ReflectionException
     *
     * @return string
     */
    public static function create(string $value)
    {
        /**
         * @var self
         */
        $object = (new \ReflectionClass(__CLASS__))->newInstanceWithoutConstructor();
        $object->setTables($value);

        return $object;
    }

    /**
     * Returns the left table of the many through relation.
     *
     * @return string
     */
    public function leftTable()
    {
        return $this->left;
    }

    /**
     * Returns the right table of the many through relation.
     *
     * @return string
     */
    public function rightTable()
    {
        return $this->right;
    }

    /**
     * Get relation name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Relation tables setter.
     *
     * @throws \InvalidArgumentException
     */
    private function setTables(string $value)
    {
        $parts = explode('->', $value);
        if (2 !== \count($parts)) {
            throw new \InvalidArgumentException('1->1 or 1->* relation incorrectly formed, (ex: left_table->right_table)');
        }
        $this->left = $parts[0];
        list($right, $name) = array_pad(explode(':', $parts[1]), 2, null);
        $this->right = $right;
        $this->name = $name;
    }
}
