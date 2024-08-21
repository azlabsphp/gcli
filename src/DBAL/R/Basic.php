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

namespace Drewlabs\GCli\DBAL\R;

use Drewlabs\Core\Helpers\Str;
use Drewlabs\GCli\Contracts\ReversibleRelation;

class Basic implements ReversibleRelation
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

    /** @var string */
    private $module;

    /** @var bool */
    private $isInverse = false;

    /**
     * Creates an instance of relation class.
     * 
     * @param string $name 
     * @param string $model 
     * @param string $reference 
     * @param string $local 
     * @param string $type 
     * @param string|null $castclasspath 
     * @return void 
     */
    public function __construct(
        string $name,
        string $model,
        string $reference,
        string $local,
        string $type = Types::ONE_TO_MANY,
        string $castclasspath = null
    ) {
        $this->name = $name;
        $this->model = $model;
        $this->reference = $reference;
        $this->local = $local;
        $this->type = $type ?? Types::ONE_TO_MANY;
        $this->castclasspath = $castclasspath;
    }

    public function isInverse(): bool
    {
        return $this->isInverse;
    }

    /**
     * Creates a basic relation instance setting the reverse flag to true
     * 
     * @param string $name 
     * @param string $model 
     * @param string $reference 
     * @param string $local 
     * @param string $type 
     * @param string|null $castclasspath
     * 
     * @return static 
     */
    public static function reverse(...$args)
    {
        $self = new static(...$args);
        $self->isInverse = true;

        return $self;
    }

    public function withModuleName(string $name)
    {
        $this->module = $name;
        return $this;
    }

    public function getModuleName(): ?string
    {
        return $this->module;
    }

    public function multi(): bool
    {
        return in_array($this->type, [Types::MANY_TO_MANY, Types::ONE_TO_MANY, Types::ONE_TO_MANY_THROUGH]);
    }

    public function to(): string
    {
        return strpos($this->model, '\\') !== false ? Str::afterLast('\\', $this->model) : $this->model;
    }

    public function __toString()
    {
        return sprintf('%s', $this->getType());
    }


    public function getName(): string
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
