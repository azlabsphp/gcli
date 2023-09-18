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

namespace Drewlabs\GCli\Contracts;

interface ControllerBuilder extends ComponentBuilder
{
    /**
     * Indicates whether the controller must be builded as a resource controller.
     *
     * @return self
     */
    public function asResourceController();

    /**
     * Indicates whether the controller is invokable.
     *
     * @return self
     */
    public function asInvokableController();

    /**
     * Bind a model class or name to the constructor. This is used in generating the controller name and some method.
     *
     * @param bool $asvm
     *
     * @return self
     */
    public function bindModel(string $value, $asvm = false);

    /**
     * Bind a view model to the controller that will be used for validation.
     *
     * @return self
     */
    public function bindViewModel(string $viewModelClass);

    /**
     * Bind a service class definition to the controller.
     *
     * @return self
     */
    public function bindService(string $serviceClass, string $type = null);

    /**
     * Set the name property of the controller.
     *
     * @return self
     */
    public function setName(string $value);

    /**
     * @return self
     */
    public function bindDTOObject(string $dtoClass);

    /**
     * Returns the name of controller actions route name.
     *
     * @return string
     */
    public function routeName();
}
