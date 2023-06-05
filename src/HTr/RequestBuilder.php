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

use Drewlabs\Htr\Exceptions\AssertionException;
use Drewlabs\Htr\RandomID;
use Drewlabs\Htr\Request;
use Drewlabs\Htr\Testing\TestRunner;

class RequestBuilder
{
    /**
     * request name and description.
     *
     * @var string
     */
    private $description;

    /**
     * request parameters.
     *
     * @var array
     */
    private $reqParams = [];

    /**
     * request cookies.
     *
     * @var array
     */
    private $reqCookies = [];

    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $prefix;

    /**
     * Set the tests to be exected on the request.
     *
     * @var array
     */
    private $tests = ['[status] eq 200'];

    /**
     * request body sends for POST/PUT requests.
     *
     * @var array
     */
    private $reqBody = [];

    /**
     * @var string|null
     */
    private $name;

    /**
     * Creates a request builder instance.
     *
     * @param mixed       $path
     * @param string|null $prefix
     */
    public function __construct(string $path, string $method = 'GET', $prefix = null, ?string $name = null, ?string $description = null)
    {
        $this->path = $path;
        $this->method = $method;
        $this->prefix = $prefix;
        $this->name = $name ?? $this->getNameFromPath($path);
        $this->description = $description ?? $this->getDescriptionFromPath($path, $method ?? 'GET');
    }

    /**
     * Creates new class instance.
     *
     * @param mixed $prefix
     *
     * @return static
     */
    public static function new(string $path, string $method = 'GET', $prefix = null)
    {
        return new static($path, $method, $prefix);
    }

    /**
     * Set request body.
     *
     * @return static
     */
    public function setBody(array $values)
    {
        $this->reqBody = $values;

        return $this;
    }

    /**
     * Set request params.
     *
     * @return static
     */
    public function setParams(array $params)
    {
        $this->reqParams = $params;

        return $this;
    }

    /**
     * Set request cookies.
     *
     * @return static
     */
    public function setCookies(array $cookies)
    {
        $this->reqCookies = $cookies;

        return $this;
    }

    /**
     * Set the list of tests to execute on the request.
     *
     * @param mixed $tests
     *
     * @return static
     */
    public function setTests($tests)
    {
        $this->tests = \is_array($tests) ? $tests : [$tests];

        return $this;
    }

    /**
     * Build an `HTr` request instance.
     *
     * @throws AssertionException
     *
     * @return Request
     */
    public function build()
    {
        $request = new Request();

        // #region Prepare request path
        $path = (null !== $this->prefix ? sprintf('/%s', ltrim($this->prefix, '/')) : '');
        $path .= null === $this->path ? '/' : "/$this->path";
        // #endregion Prepare request path

        return $request->setParams($this->reqParams ?? [])
            ->setHeaders([
                ['name' => 'Content-Type', 'value' => 'application/json'],
                ['name' => 'Authorization', 'value' => 'Bearer [_bearerToken]'],
            ])
            ->setUrl(sprintf('[_host]%s', $path))
            ->setCookies($this->reqCookies ?? [])
            ->setTests(TestRunner::fromAttributes($this->tests ?? ['[status] eq 200']))
            ->setMethod($this->method)
            ->setBody($this->reqBody)
            ->setId(RandomID::new()->__invoke())
            ->setName($this->name)
            ->setDescription($this->description);
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
