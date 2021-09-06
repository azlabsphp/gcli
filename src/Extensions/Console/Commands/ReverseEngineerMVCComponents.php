<?php

namespace Drewlabs\ComponentGenerators\Extensions\Console\Commands;

use Doctrine\DBAL\DriverManager;
use Illuminate\Console\Command;

use function Drewlabs\ComponentGenerators\Proxy\DatabaseSchemaReverseEngineeringRunner;
use function Drewlabs\Filesystem\Proxy\Path;

class ReverseEngineerMVCComponents extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drewlabs:mvc:create {--srcPath= : Path to the business logic component folder} {--connectionURL= : Database connection URL} {--dbname= : Database name} {--host= : Database host name} {--port= : Database host port number} {--user= : Database authentication user} {--password= : Database authentication password} {--driver= : Database driver name} {--server_version= : Database server version} {--charset= : Database Connection collation} {--unix_socket= : Unix socket to use for connections}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reverse engineer database table to a full mvc components definitions';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $srcPath = Path($this->option('srcPath') ?? 'app')->makeAbsolute($this->laravel->basePath())->__toString();
        if (null !== ($url = $this->option('connectionURL'))) {
            $options = [
                'url' => $url
            ];
        } else {
            $default_driver = config('database.default');
            $driver = $this->option('driver') ?
                (drewlabs_core_strings_starts_with(
                    $this->option('driver'),
                    'pdo'
                ) ? $this->option('driver') :
                    sprintf(
                        'pdo_%s',
                        $this->option('driver')
                    )) : sprintf("pdo_%s", $default_driver);
            $database = $this->option('dbname') ?? config("database.connections.$default_driver.database");
            $port = $this->option('port') ?? config("database.connections.$default_driver.port");
            $username = $this->option('user') ?? config("database.connections.$default_driver.username");
            $host = $this->option('host') ?? config("database.connections.$default_driver.host");
            $password = $this->option('password') ?? config("database.connections.$default_driver.password");
            $options = [
                "dbname" => $database,
                "host" => $host ?? '127.0.0.1',
                "port" => $port ?? 3306,
                "user" => $username,
                "password" => $password,
                "driver" => $driver ?? 'pdo_sqlite',
                "server_version" => $this->option('server_version') ?? null,
                "charset" => $this->option('charset') ?? ($driver === 'pdo_mysql' ? 'utf8mb4' : 'utf8'),
            ];
        }
        $connection = DriverManager::getConnection($options);
        $schemaManager =  $connection->createSchemaManager();
        // For Mariadb server
        $schemaManager->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
        // Execute the runner
        $traversable = DatabaseSchemaReverseEngineeringRunner(
            $schemaManager,
            $srcPath
        )->tableListFilterFunc(function ($table) {
            return !(drewlabs_core_strings_contains($table->getName(), 'auth_') ||
                drewlabs_core_strings_starts_with($table->getName(), 'acl_') ||
                ($table->getName() === 'accounts_verifications') ||
                drewlabs_core_strings_contains($table->getName(), 'file_authorization') ||
                drewlabs_core_strings_contains($table->getName(), 'uploaded_file') ||
                drewlabs_core_strings_contains($table->getName(), 'server_authorized_') ||
                drewlabs_core_strings_contains($table->getName(), 'shared_files') ||
                drewlabs_core_strings_contains($table->getName(), 'form_') ||
                ($table->getName() === 'forms') ||
                ($table->getName() === 'migrations') ||
                (drewlabs_core_strings_starts_with($table->getName(), 'log_model_')));
        })->run();

        $this->info(sprintf("Started reverse engineering process...\n"));
        $this->withProgressBar(
            iterator_count($traversable),
            function () {
            }
        );
        $this->info(sprintf("\nReverse engineering completed successfully!\n"));
    }
}
