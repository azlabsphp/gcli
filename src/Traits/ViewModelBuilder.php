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

namespace Drewlabs\ComponentGenerators\Traits;

trait ViewModelBuilder
{
    /**
     * Indicates whether to add the updateRules() method to the generated class.
     *
     * @var bool
     */
    private $isSingleActionValidator_ = false;

    /**
     * List of rules to add to the view model.
     *
     * @var array
     */
    private $rules_ = [];

    /**
     * List of update rules to add to the view model.
     *
     * @var array
     */
    private $updateRules_ = [];

    /**
     * @var false
     */
    private $hasAuthenticatableTraits_ = false;

    /**
     * Provides methods or traits for writing inputs to / reading inputs from the viewmodel.
     *
     * @var bool
     */
    private $hasInputsTraits_ = false;

    /**
     * Provides methods or traits for writing files inputs to / reading inputs from the viewmodel.
     *
     * @var bool
     */
    private $hasFileInputsTraits_ = false;

    public function setRules(array $rules = [])
    {
        if (null !== $rules) {
            $this->rules_ = $rules;
        }

        return $this;
    }

    public function setUpdateRules(array $rules = [])
    {
        if (null !== $rules) {
            $this->updateRules_ = $rules;
        }

        return $this;
    }

    public function asSingleActionValidator()
    {
        $this->isSingleActionValidator_ = true;

        return $this;
    }

    public function addFileInputTraits()
    {
        $this->hasFileInputsTraits_ = true;

        return $this;
    }

    public function addInputsTraits()
    {
        $this->hasInputsTraits_ = true;

        return $this;
    }

    public function addAuthenticatableTraits()
    {
        $this->hasAuthenticatableTraits_ = true;

        return $this;
    }
}
