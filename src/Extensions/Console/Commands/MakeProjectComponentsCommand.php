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

namespace Drewlabs\GCli\Extensions\Console\Commands;

use Drewlabs\GCli\Contracts\Writable;
use Drewlabs\GCli\DBAL\T\IteratorFactory;
use Drewlabs\GCli\Extensions\Helpers\CommandArguments;
use Drewlabs\GCli\Extensions\Helpers\ReverseEngineerTask;
use Drewlabs\GCli\Extensions\ProgressbarIndicator;
use Drewlabs\GCli\HTr\RouteProjectFactory;
use Drewlabs\GCli\HTr\RouteRequestBodyMap;
use Drewlabs\GCli\IO\Disk;
use Drewlabs\GCli\IO\Path;
use Drewlabs\GCli\Plugins\G;
use Drewlabs\GCli\Plugins\TSModule\V1\Plugin;
use Drewlabs\GCli\Validation\Fluent\RulesFactory;
use Illuminate\Console\Command;
use Illuminate\Container\Container;

/**
 * @property \Illuminate\Contracts\Console\Application|\Illuminate\Contracts\Foundation\Application $laravel
 * @property mixed output
 * 
 * 
 * @method bool confirm($question, $default = false)
 * @method array|string|boo|null option($key = null)
 * @method string|array choice($question, array $choices, $default = null, $attempts = null, $multiple = false)
 * @method string|array|bool|null option(string $key)
 * @method array options()
 * @method void info($string, $verbosity = null)
 * @method void warn($string, $verbosity = null)
 * @method void error($string, $verbosity = null)
 */
class MakeProjectComponentsCommand extends Command
{
    /** @var string[] */
    const CAMEL_CASE_CHOICES = [
        'Direct (ex: Post { label, post_type_id } -> PostDto {label, post_type_id } )',
        'Camelize (ex: Post { label, post_type_id } -> PostDto {label, postTypeId } )',
    ];

    const PROMPT = 'How should data transfert properties map to model attributes ?';

    /** @var string */
    protected $signature = 'gcli:make:project {--srcPath= : Path to the business logic component folder}'
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
        . '{--format=json : Input file extension or format. Supported input format are ex:json|yml|yaml}'
        . '{--schema= : Schema prefix to database tables}'
        . '{--http : Whether to generates controllers and routes}'
        . '{--no-model-accessors : Disable model property accessor generator }'
        . '{--force : Force rewrite of existing classes }'
        . '{--relations : Generates relations for model and relations casting entries for data transfer object }'
        . '{--manytomany=* :  List of many to many relations. (ex - lefttable->middletable->righttable) }'
        . '{--toones=* :  List of one to one relations. (ex - lefttable->righttable) }'
        . '{--manythroughs=* :  List of many through relations. (ex - lefttable->middletable->righttable) }'
        . '{--onethroughs=* :  List of one through relations. (ex - lefttable->middletable->righttable) }'
        . '{--only=* : Restrict the generator to generate code only for the specified table structures }'
        . '{--policies : Generates policies for the model }'
        . '{--htr : Enables project generator to generates htr test files }'
        . '{--htrDir= : Output directory for htr tests}'
        . '{--htrHost= : Base url for htr tests}'
        . '{--htrFormat=json : Htr output document format}'
        . '{--plugins=* : List of plugins to use by source code generator }';

    /** @var string */
    protected $description = 'Reverse engineer database table to a full mvc components definitions';

    /**
     * @var string
     */
    private $cachePath;

    /** @var string */
    private $routesCachePath;

    /**
     * Creates laravel command instance.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();
        // Initialize the cache path
        $basepath = rtrim(Path::new(drewlabs_component_generator_cache_path())->normalize()->__toString(), \DIRECTORY_SEPARATOR);
        $this->cachePath = sprintf('%s%s%s', $basepath, \DIRECTORY_SEPARATOR, '__components__.dump');
        $this->routesCachePath = sprintf('%s%s%s', $basepath, \DIRECTORY_SEPARATOR, '__routes__.dump');
    }

    /**
     * Handle drewlabs:mvc:create command execution.
     *
     * @throws \Exception
     *
     * @return void
     */
    public function handle()
    {
        $forLumen = drewlabs_code_generator_is_running_lumen_app(Container::getInstance());
        //#region local variables initialization
        $noAuth = (bool) $this->option('noAuth');
        $httpHandlers = (bool) $this->option('http');
        $commandoptions = $this->mergeCamelizeOption($this->choice(static::PROMPT, self::CAMEL_CASE_CHOICES, 0), $this->options() ?? []);
        $commandoptions = array_merge($commandoptions, []);
        $commandargs = new CommandArguments($commandoptions);
        $options = $commandargs->providesoptions($this->cachePath, $this->laravel->basePath($this->option('srcPath') ?? 'app'));
        $policies = $options->get('policies') ?? false;
        $namespace = $options->get('namespace.default') ?? 'App';
        $domain = $options->get('namespace.domain');
        $disableCache = !$options->get('cache', false);
        $excludes = array_merge($options->get('excludes', []) ?? [], ['migrations']);
        $includes = $options->get('includes', []);
        $tableNamespace = sprintf('%s\\%s', $namespace, ltrim(sprintf('%s%s', $domain ? "$domain\\" : '', 'Models')));
        $schema = $options->get('schema');
        //#endregion local variables initialization

        // Register ts-module plugin only if it's passed as argument
        if (in_array('ts-module', $options->get('plugins'))) {
            // We register the Ts-Module plugin
            G::getInstance()->addPlugin('ts-module', new Plugin($this->laravel->publicPath('assets/lib')));
        }

        $task = (new ReverseEngineerTask(new RulesFactory))
            ->setCamelize(boolval($options->get('models.attributes.camelize', false)))
            ->withRelations($options->get('models.relations.provides', false) ?? false)
            ->setToOnesRelations($options->get('models.relations.one-to-one', []))
            ->setManyToManyRelations($options->get('models.relations.many-to-many', []))
            ->setOnThroughRelations($options->get('models.relations.one-to-one-though', []))
            ->setManyThroughRelations($options->get('models.relations.one-to-many-though', []));
        $task = $policies ? $task->withPolicies() : $task;


        //#region Load database tables
        $dbOptions = $commandargs->providesdboptions(static function ($key, $default = null) {
            return config($key, $default);
        });
        $factory = new IteratorFactory($tableNamespace, $schema, $dbOptions, $includes, $excludes);
        $iterator = $factory->createIterator();
        //#endregion Load database tables

        $task->run(
            $iterator,
            $options->get('path'),
            $options->get('routes.filename', 'web.php'),
            $options->get('routes.prefix'),
            $options->get('routes.middleware'),
            $forLumen,
            $disableCache,
            $noAuth,
            $namespace,
            $domain,
            $schema,
            $httpHandlers,
            $options->get('models.no-accessors', false)
        )(
            $this->laravel->basePath('routes'),
            $this->cachePath,
            $this->routesCachePath,
            // Creates the progress indicator
            function ($values) {
                $this->info("Started reverse engineering process...\n");
                return new ProgressbarIndicator($this->output->createProgressBar(\count($values)));
            },
            function (string $message = null) {
                $this->info("\nReverse engineering completed successfully!\n");
                $this->warn($message ?? '');
            },
            function (Writable $writable) use ($options) {
                if ($this->option('force')) {
                    return true;
                }
                return $this->confirm(sprintf('Override existing class at %s? ', $options->get('path')) . \DIRECTORY_SEPARATOR . $writable->getPath());
            },
            function (array $routes, RouteRequestBodyMap $map, string $prefix = null) use ($options) {
                if ((bool) $options->get('htr')) {
                    foreach ($routes as $route) {
                        $factory = new RouteProjectFactory($route, $map, $prefix, $options->get('htr.host', 'http://127.0.0.1:8000'));
                        $project = $factory->create();
                        $format = $options->get('htr.format', 'json');
                        Disk::new($this->laravel->basePath($options->get('htr.directory') ?? 'htr'))->write($factory->getRouteName() . '.' . $format, $project->compile($format));
                    }
                }
            }
        );
    }

    /**
     * update $options parameters with based on user choice value.
     *
     * @param mixed $choice
     *
     * @return array
     */
    private function mergeCamelizeOption($choice, array $options)
    {
        if ($choice === self::CAMEL_CASE_CHOICES[0]) {
            return array_merge($options, ['camelize-attributes' => false]);
        }
        if ($choice === self::CAMEL_CASE_CHOICES[1]) {
            return array_merge($options, ['camelize-attributes' => true]);
        }

        return $options;
    }
}
