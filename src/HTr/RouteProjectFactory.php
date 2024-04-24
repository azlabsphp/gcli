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

use Drewlabs\GCli\Models\RouteController;
use Drewlabs\Htr\Env;
use Drewlabs\Htr\Exceptions\AssertionException;
use Drewlabs\Htr\Project;

class RouteProjectFactory
{
    /**
     * @var RouteController
     */
    private $route;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * route prefix property.
     *
     * @var string
     */
    private $prefix;

    /**
     * @var RouteRequestBodyMap
     */
    private $map;

    /**
     * Create class instance.
     *
     * @return void
     */
    public function __construct(
        RouteController $route,
        RouteRequestBodyMap $map,
        string $prefix = null,
        string $baseUrl = 'http://127.0.0.1:8000'
    ) {
        $this->route = $route;
        $this->map = $map;
        $this->baseUrl = $baseUrl;
        $this->prefix = $prefix;
    }

    /**
     * Create the project instance from the route configurations.
     *
     * @throws AssertionException
     * @throws \InvalidArgumentException
     *
     * @return Project
     */
    public function create()
    {
        $name = $this->getRouteName();
        $requestBody = $this->map->get($name) ?? null;
        $relations = $requestBody ? $requestBody->getRelations() : [];

        // Instanciate the project builder instance
        $builder = new ProjectBuilder(sprintf('%s HTr Project', ucfirst(strtolower(str_replace(['-', '_'], ' ', $name)))));
        // Build the project using the project builder instance
        $builder->addBearerToken()
            ->addReqHost($this->baseUrl)
            ->addEnvironment(new Env('_id', 1))
            ->addComponent(
                (new RequestBuilder(
                    $name,
                    'GET',
                    $this->prefix,
                    $this->getNameFromPath($name),
                    sprintf('%s. %s', $this->getDescriptionFromPath($name), !empty($relations) ? sprintf('Note: This route currently support the following relations: %s', implode(', ', $relations)) : '')
                ))
                    ->setCookies([])
                    ->setParams([])
                    // ->setTests([])
                    ->build()
            )
            ->addComponent(
                (new RequestBuilder(
                    sprintf('%s/[_id]', $name),
                    'GET',
                    $this->prefix,
                    $this->getNameFromPath(sprintf('%s/[_id]', $name)),
                    sprintf('%s. %s', $this->getDescriptionFromPath(sprintf('%s/[_id]', $name)), !empty($relations) ? sprintf('Note: This route currently support the following relations: %s', implode(', ', $relations)) : '')
                ))
                    ->setCookies([])
                    ->setParams([])
                    // ->setTests([])
                    ->build()
            )
            ->addComponent(
                RequestBuilder::new($name, 'POST', $this->prefix)
                    ->setCookies([])
                    ->setParams([])
                    ->setBody($requestBody ? $requestBody->getPostBody() : [])
                    // ->setTests([])
                    ->build()
            )
            ->addComponent(
                RequestBuilder::new(sprintf('%s/[_id]', $name), 'PUT', $this->prefix)
                    ->setCookies([])
                    ->setParams([])
                    ->setBody($requestBody ? $requestBody->getPutBody() : [])
                    // ->setTests([])
                    ->build()
            )
            ->addComponent(
                RequestBuilder::new(sprintf('%s/[_id]', $name), 'DELETE', $this->prefix)
                    ->setCookies([])
                    ->setParams([])
                    // ->setTests([])
                    ->build()
            );

        // Build and return the project instance
        return $builder->build();
    }

    /**
     * return the route name for the factory.
     *
     * @return string
     */
    public function getRouteName()
    {
        return $this->route->getName() ?? 'tests';
    }

    /**
     * returns the request name.
     *
     * @return string|null
     */
    private function getNameFromPath(string $path)
    {
        $components = explode('/', ltrim($path, '/'));
        $name = $components[0] ?? null;

        return null !== $name ? str_replace(['-', '_'], ' ', $name) : $name;
    }

    /**
     * returns the request description from request parameters.
     *
     * @return string
     */
    private function getDescriptionFromPath(string $path, string $method = 'GET')
    {
        return sprintf('/%s %s', $method, $path);
    }
}
