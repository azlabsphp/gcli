<?php

namespace Drewlabs\ComponentGenerators\Extensions\Console\Commands;

use Doctrine\DBAL\DriverManager;
use Illuminate\Console\Command;

class ReverseEngineerMVCComponents extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drewlabs:mvc:create {--connectionURL= : Database connection URL} {--dbname= : Database name} {--host= : Database host name} {--port= : Database host port number} {--user= : Database authentication user} {--password= : Database authentication password} {--driver= : Database driver name} {--server_version= : Database server version} {--charset= : Database Connection collation} {--unix_socket= : Unix socket to use for connections}';

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
        if (null !== ($url = $this->option('connectionURL'))) {
            $options = [
                'url' => $url
            ];
        } else {
            $options = [
                "dbname" => $this->option('dbname'),
                "host" => $this->option('host'),
                "port" => $this->option('port'),
                "user" => $this->option('user'),
                "password" => $this->option('password'),
                "driver" => $this->option('driver') ? (drewlabs_core_strings_starts_with(
                    $this->option('driver'),
                    'pdo'
                ) ? $this->option('driver') : sprintf('pdo_%s', $this->option('driver'))) : 'pdo_sqlite',
                "server_version" => $this->option('server_version'),
                "charset" => $this->option('charset') ?? '',
            ];
        }
        // Create a database connection using doctrine DBAL
        $connection = DriverManager::getConnection($options);
        // TODO: Read the database table using doctrine Database access layer

        // TODO : Foreach table create mvc components

        // Complete the command
    }

    private function createComponents()
    {
        // TODO : Create Model

        // TODO: Create View Model

        // TODO: Create Data Transfert object

        // TODO: Create Model service class

        // TODO : Create Model Controller
    }
}
