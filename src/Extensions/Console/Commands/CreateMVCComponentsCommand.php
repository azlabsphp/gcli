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

namespace Drewlabs\ComponentGenerators\Extensions\Console\Commands;

use Drewlabs\ComponentGenerators\Extensions\Helpers\ReverseEngineerTaskRunner;
use Drewlabs\ComponentGenerators\Extensions\ProgressbarIndicator;
use Drewlabs\ComponentGenerators\Helpers\ComponentBuilderHelpers;
use function Drewlabs\Filesystem\Proxy\Path;
use Illuminate\Console\Command;

use Illuminate\Container\Container;

class CreateMVCComponentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drewlabs:mvc:create {--srcPath= : Path to the business logic component folder}'
        . '{--package= : Package namespace for components}'
        . '{--subPackage= : Subpackage will group each component part in a subfolder}'
        . '{--connectionURL= : Database connection URL}'
        . '{--dbname= : Database name}'
        . '{--host= : Database host name}'
        . '{--port= : Database host port number}'
        . '{--user= : Database authentication user}'
        . '{--password= : Database authentication password}'
        . '{--driver= : Database driver name}'
        . '{--server_version= : Database server version}'
        . '{--charset= : Database Connection collation}'
        . '{--unix_socket= : Unix socket to use for connections}'
        . '{--routePrefix= : The prefix for the generated route definitions}'
        . '{--middleware= : Middleware group defined for the routes prefix}'
        . '{--routingfilename= : Routing filename (Default = web.php)}'
        . '{--excepts=* : List of tables not to be included in the generated output}'
        . '{--disableCache : Caching tables not supported}'
        . '{--noAuth : Indicates whether project controllers supports authentication}'
        . '{--schema= : Schema prefix to database tables}'
        . '{--http : Whether to generates controllers and routes}'
        . '{--only=* : Restrict the generator to generate code only for the specified table structures}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reverse engineer database table to a full mvc components definitions';

    /**
     * @var string
     */
    private $path_;

    /**
     * @var string
     */
    private $routesCachePath_;

    public function __construct()
    {
        parent::__construct();
        // Initialize the cache path
        $cachePath = Path(
            drewlabs_component_generator_cache_path()
        )->canonicalize()->__toString();
        $this->path_ = sprintf(
            '%s%s%s',
            $cachePath,
            \DIRECTORY_SEPARATOR,
            '__components__.dump'
        );
        $this->routesCachePath_ = sprintf(
            '%s%s%s',
            $cachePath,
            \DIRECTORY_SEPARATOR,
            '__routes__.dump'
        );
    }

    public function handle()
    {
        // TODO : Initialize local variables
        $forLumen = drewlabs_code_generator_is_running_lumen_app(Container::getInstance());
        $srcPath = Path($this->option('srcPath') ?? 'app')->makeAbsolute($this->laravel->basePath())->__toString();
        $routingfilename = $this->option('routingfilename') ?? 'web.php';
        $routePrefix = $this->option('routePrefix') ?? null;
        $middleware = $this->option('middleware') ?? null;
        $default_driver = config('database.default');
        $only = $this->option('only');
        $driver = $this->option('driver') ?
            (drewlabs_core_strings_starts_with(
                $this->option('driver'),
                'pdo'
            ) ? $this->option('driver') :
                sprintf(
                    'pdo_%s',
                    $this->option('driver')
                )) : sprintf('pdo_%s', $default_driver);
        $database = $this->option('dbname') ?? config("database.connections.$default_driver.database");
        $port = $this->option('port') ?? config("database.connections.$default_driver.port");
        $username = $this->option('user') ?? config("database.connections.$default_driver.username");
        $host = $this->option('host') ?? config("database.connections.$default_driver.host");
        $password = $this->option('password') ?? config("database.connections.$default_driver.password");
        $charset = $this->option('charset') ?? ('pdo_mysql' === $driver ? 'utf8mb4' : 'utf8');
        $server_version = $this->option('server_version') ?? null;

        $exceptions = $this->option('excepts') ?? [];
        $disableCache = $this->option('disableCache');
        if (!$disableCache) {
            // Get component definitions from cache
            $value = ComponentBuilderHelpers::getCachedComponentDefinitions((string) $this->path_);
            if (null !== $value) {
                $exceptions = array_merge($exceptions, $value->getTables());
            }
        }

        $noAuth = $this->option('noAuth');
        $namespace = $this->option('package') ?? 'App';
        $subPackage = $this->option('subPackage');
        $httpHandlers = $this->option('http');
        // !Ends Local variables initialization

        if (null !== ($url = $this->option('connectionURL'))) {
            $options = [
                'url' => $url,
            ];
        } else {
            $options = [
                'dbname' => $database,
                'host' => $host ?? '127.0.0.1',
                'port' => $port ?? 3306,
                'user' => $username,
                'password' => $password,
                'driver' => $driver ?? 'pdo_sqlite',
                'server_version' => $server_version,
                'charset' => $charset,
            ];
        }
        (new ReverseEngineerTaskRunner())
            ->except($exceptions ?? [])
            ->only($only ?? [])
            ->run(
                $options,
                $srcPath,
                $routingfilename,
                $routePrefix,
                $middleware,
                $forLumen,
                $disableCache,
                $noAuth,
                $namespace,
                $subPackage,
                $this->option('schema') ?? null,
                $httpHandlers
            )(
            $this->laravel->basePath('routes'),
            $this->path_,
            $this->routesCachePath_,
            // Creates the progress indicator
            function ($values) {
                $this->info("Started reverse engineering process...\n");

                return new ProgressbarIndicator($this->output->createProgressBar(\count($values)));
            },
            function () {
                $this->info("\nReverse engineering completed successfully!\n");
            }
        );
    }
}
