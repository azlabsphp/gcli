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

namespace Drewlabs\GCli\HTr;

use Drewlabs\Htr\Contracts\ComponentInterface;
use Drewlabs\Htr\Env;
use Drewlabs\Htr\Project;

class ProjectBuilder
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $version = '0.2.0';

    /**
     * @var array
     */
    private $components = [];

    /**
     * @var array<string,string>
     */
    private $env = [];

    /**
     * @var string
     */
    private $schemaUri;

    /**
     * Creates new builder instance.
     */
    public function __construct(string $name, string $version = '0.2.0', ?string $schemaUri = null)
    {
        $this->name = $name;
        $this->version = $version ?? '0.2.0';
        $this->schemaUri = $schemaUri;
    }

    /**
     * Build an `HTr` porject instance.
     *
     * @throws \InvalidArgumentException
     *
     * @return Project
     */
    public function build()
    {
        // Create the project instance
        return Project::make($this->getEnvironments(), $this->getComponents(), $this->name ?? 'HTr Project', $this->version);
    }

    /**
     * Set project host url environment value.
     *
     * @return static
     */
    public function addReqHost(string $url)
    {
        return $this->addEnvironment(new Env('_host', $url));
    }

    /**
     * Add a bearer token environment variable.
     *
     * @return static
     */
    public function addBearerToken(string $token = '')
    {
        return $this->addEnvironment(new Env('_bearerToken', $token));
    }

    /**
     * Add a basic auth environment variable.
     *
     * @return static
     */
    public function addBasicAuth(string $user, string $password)
    {
        return $this->addEnvironment(new Env('_basicAuth', base64_encode("$user:$password")));
    }

    /**
     * Add a new component to the project.
     *
     * @return static
     */
    public function addComponent(ComponentInterface $component)
    {
        $this->components[] = $component;

        return $this;
    }

    /**
     * Set the component for the `htr` project.
     *
     * @return static
     */
    public function setComponents(array $components)
    {
        $this->components = $components;

        return $this;
    }

    /**
     * Returns the list of components.
     *
     * @return array
     */
    public function getComponents()
    {
        return $this->components ?? [];
    }

    /**
     * Add a new environment variable to the project instance.
     *
     * @return static
     */
    public function addEnvironment(Env $env)
    {
        $this->env[] = $env;

        return $this;
    }

    /**
     * Set the environment array of the project.
     *
     * @return static
     */
    public function setEnvironments(array $env)
    {
        $this->env = $env;

        return $this;
    }

    /**
     * Returns the list of environments.
     *
     * @return array
     */
    public function getEnvironments()
    {
        return $this->env ?? [];
    }
}
