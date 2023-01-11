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

use Drewlabs\ComponentGenerators\Contracts\Writable;
use Drewlabs\ComponentGenerators\DBDriverOptions;
use Drewlabs\ComponentGenerators\Extensions\Helpers\CommandArguments;
use Drewlabs\ComponentGenerators\Extensions\Helpers\ReverseEngineerTaskRunner;
use Drewlabs\ComponentGenerators\Extensions\ProgressbarIndicator;
use Drewlabs\Core\Helpers\Str;
use Drewlabs\Filesystem\Exceptions\ReadFileException;
use Drewlabs\Filesystem\Exceptions\UnableToRetrieveMetadataException;
use Drewlabs\Filesystem\Exceptions\FileNotFoundException;

use function Drewlabs\Filesystem\Proxy\Path;
use Illuminate\Console\Command;

use Illuminate\Container\Container;
use InvalidArgumentException;
use RuntimeException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\Console\Exception\InvalidArgumentException as ExceptionInvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;

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
        . '{--input= : Path to options configurations file}'
        . '{--format=json : Inpu file extension or format. Supported input format are ex:json|yml|yaml}'
        . '{--schema= : Schema prefix to database tables}'
        . '{--http : Whether to generates controllers and routes}'
        . '{--force : Force rewrite of existing classes }'
        . '{--relations : Generates relations for model and relations casting entries for data transfer object }'
        . '{--manytomany=* :  List of many to many relations. (ex - lefttable->middletable->righttable) }'
        . '{--toones=* :  List of one to one relations. (ex - lefttable->righttable) }'
        . '{--manythroughs=* :  List of many through relations. (ex - lefttable->middletable->righttable) }'
        . '{--onethroughs=* :  List of one through relations. (ex - lefttable->middletable->righttable) }'
        . '{--only=* : Restrict the generator to generate code only for the specified table structures }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reverse engineer database table to a full mvc components definitions';

    /**
     * @var string
     */
    private $cachePath;

    /**
     * @var string
     */
    private $routesCachePath;

    /**
     * Creates laravel command instance
     * 
     * @throws InvalidArgumentException 
     * @throws ExceptionInvalidArgumentException 
     * @throws LogicException 
     * @throws RuntimeException 
     */
    public function __construct()
    {
        parent::__construct();
        // Initialize the cache path
        $basepath = Path(drewlabs_component_generator_cache_path())->canonicalize()->__toString();
        $this->cachePath = sprintf(
            '%s%s%s',
            $basepath,
            \DIRECTORY_SEPARATOR,
            '__components__.dump'
        );
        $this->routesCachePath = sprintf(
            '%s%s%s',
            $basepath,
            \DIRECTORY_SEPARATOR,
            '__routes__.dump'
        );
    }

    /**
     * Handle drewlabs:mvc:create command execution
     * 
     * @return void 
     * @throws InvalidArgumentException 
     * @throws RuntimeException 
     * @throws ReadFileException 
     * @throws UnableToRetrieveMetadataException 
     * @throws FileNotFoundException 
     * @throws BindingResolutionException 
     * @throws NotFoundExceptionInterface 
     * @throws ContainerExceptionInterface 
     */
    public function handle()
    {
        $forLumen = drewlabs_code_generator_is_running_lumen_app(Container::getInstance());
        //#region command options
        $noAuth = boolval($this->option('noAuth'));
        $subPackage = $this->option('subPackage');
        $httpHandlers = boolval($this->option('http'));
        $commandargs = new CommandArguments($this->options());
        $options = $commandargs->providesoptions($this->cachePath, Path($this->option('srcPath') ?? 'app')->makeAbsolute($this->laravel->basePath())->__toString());
        //#endregion command options
        // !Ends Local variables initialization
        (new ReverseEngineerTaskRunner())
            ->except($options->get('excludes', []))
            ->only($options->get('includes'))
            ->run(
                $commandargs->providesdboptions(function ($key, $default = null) {
                    return config($key, $default);
                }),
                $options->get('path'),
                $options->get('routes.filename', 'web.php'),
                $options->get('routes.prefix'),
                $options->get('routes.middleware'),
                $forLumen,
                !$options->get('cache', false),
                $noAuth,
                $options->get('namespace.default'),
                $subPackage,
                $options->get('schema'),
                $httpHandlers,
                $options->get('models.relations.provides', false),
                $options->get('models.relations.one-to-one', []),
                $options->get('models.relations.many-to-many', []),
                $options->get('models.relations.one-to-one-though', []),
                $options->get('models.relations.one-to-many-though', []),
            )(
            $this->laravel->basePath('routes'),
            $this->cachePath,
            $this->routesCachePath,
            // Creates the progress indicator
            function ($values) {
                $this->info("Started reverse engineering process...\n");
                return new ProgressbarIndicator($this->output->createProgressBar(\count($values)));
            },
            function () {
                $this->info("\nReverse engineering completed successfully!\n");
            },
            function (Writable $writable) use ($options) {
                if ($this->option('force')) {
                    return true;
                }
                return $this->confirm(sprintf("Override existing class at %s? ", $options->get('path')) . \DIRECTORY_SEPARATOR . $writable->getPath());
            }
        );
    }
}
