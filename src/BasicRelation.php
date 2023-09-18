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

class BasicRelation
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
     * referenced table.
     *
     * @var string
     */
    private $model;

    /**
     * relation local column name.
     *
     * @var string
     */
    private $local;

    /**
     * relation referenced column.
     *
     * @var string
     */
    private $reference;

    /**
     * relation casting class path.
     *
     * @var string
     */
    private $castclasspath;

    /**
     * Creates an instance of relation class.
     */
    public function __construct(
        string $name,
        string $model,
        string $reference,
        string $local,
        string $type = RelationTypes::ONE_TO_MANY,
        string $castclasspath = null
    ) {
        $this->name = $name;
        $this->model = $model;
        $this->reference = $reference;
        $this->local = $local;
        $this->type = $type ?? RelationTypes::ONE_TO_MANY;
        $this->castclasspath = $castclasspath;
    }

    public function __toString()
    {
        return sprintf('%s', $this->getType());
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
     * model property getter method.
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * referenced column property getter method.
     *
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * local column property getter method.
     *
     * @return string
     */
    public function getLocal()
    {
        return $this->local;
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
     * Returns the cast class path of the relation.
     *
     * @return string
     */
    public function getCastClassPath()
    {
        return $this->castclasspath;
    }
}
