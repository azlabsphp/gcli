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

namespace Drewlabs\GCli;

class ThoughRelation
{
    /**
     * relation method name.
     *
     * @var string
     */
    private $name;

    /**
     * relationship type.
     *
     * @var string
     */
    private $type;

    /**
     * Left table in the relation.
     *
     * @var string
     */
    private $left;
    /**
     * Right table in the relation.
     *
     * @var string
     */
    private $right;
    /**
     * Though table in the relation.
     *
     * @var string
     */
    private $through;
    /**
     * Left table foreign key column name.
     *
     * @var string
     */
    private $leftforeignkey;
    /**
     * Right table foreign key column name.
     *
     * @var string
     */
    private $rightforeignkey;
    /**
     * Left table local key column name.
     *
     * @var string
     */
    private $leftlocalkey;

    /**
     * Right table local key column name.
     *
     * @var string
     */
    private $rightlocalkey;

    /**
     * relation casting class path.
     *
     * @var string
     */
    private $castclasspath;

    /**
     * Creates Many through relation instance.
     *
     * @param string $rightlocalkey
     * @param string $castclasspath
     */
    public function __construct(
        string $name,
        string $type,
        string $left,
        string $right,
        ?string $through = null,
        ?string $leftforeignkey = null,
        ?string $rightforeignkey = null,
        ?string $leftlocalkey = null,
        ?string $rightlocalkey = null,
        ?string $castclasspath = null
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->left = $left;
        $this->right = $right;
        $this->through = $through;
        $this->leftforeignkey = $leftforeignkey;
        $this->rightforeignkey = $rightforeignkey;
        $this->leftlocalkey = $leftlocalkey;
        $this->rightlocalkey = $rightlocalkey;
        $this->castclasspath = $castclasspath;
    }

    /**
     * name property getter method.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * type property getter method.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Left table property getter.
     *
     * @return string
     */
    public function getLeftTable()
    {
        return $this->left;
    }

    /**
     * Right table property getter.
     *
     * @return string
     */
    public function getRightTable()
    {
        return $this->right;
    }

    /**
     * Through table property getter.
     *
     * @return string
     */
    public function getThroughTable()
    {
        return $this->through;
    }

    /**
     * Left table foreign key name property getter.
     *
     * @return string
     */
    public function getLeftForeignKey()
    {
        return $this->leftforeignkey;
    }

    /**
     * Right table foreign key name property getter.
     *
     * @return string
     */
    public function getRightForeignKey()
    {
        return $this->rightforeignkey;
    }

    /**
     * Left table local key name property getter.
     *
     * @return string
     */
    public function getLeftLocalKey()
    {
        return $this->leftlocalkey;
    }

    /**
     * Right table local key name property getter.
     *
     * @return string
     */
    public function getRightLocalKey()
    {
        return $this->rightlocalkey;
    }

    /**
     * Returns the cast class path of the relation.
     *
     * @return string
     */
    public function getCastClassPath()
    {
        return $this->castclasspath;
    }
}
