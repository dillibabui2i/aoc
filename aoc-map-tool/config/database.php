<?php

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Str;

$key = env('APP_CIPHER');
$encrypted = env('CREDENTIAL_ENCRYPTED');
$readHost = env('DB_READ_HOST', '127.0.0.1');
$readDB = env('DB_READ_DATABASE', 'forge');
$readUser = env('DB_READ_USERNAME', 'forge');
$readPassword = env('DB_READ_PASSWORD', '');
$host = env('DB_HOST', '127.0.0.1');
$db = env('DB_DATABASE', 'forge');
$user = env('DB_USERNAME', 'forge');
$password = env('DB_PASSWORD', '');

if ($key && $encrypted) {
    $encrypter = new Encrypter($key, 'AES-128-CBC');
    $host = $encrypter->decryptString($host);
    $db = $encrypter->decryptString($db);
    $user = $encrypter->decryptString($user);
    $password = $encrypter->decryptString($password);
}

$config = [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DATABASE_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        'mysql' => [
            'read' => [
                'host' => $readHost,
                'database' => $readDB,
                'username' => $readUser,
                'password' => $readPassword,
            ],
            'write' => [
                'host' => $host,
                'database' => $db,
                'username' => $user,
                'password' => $password,
            ],
            'sticky' => true,
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'port' => env('DB_PORT', '3306'),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],

		'snowflex' => [
            'driver' => 'odbc',
            'dsn' => env('TEST_DAAS_DSN'),
            'username' => env('TEST_DAAS_USERNAME'),
            'password' => env('TEST_DAAS_PASSWORD'),
            'database' => env('TEST_DAAS_DATABASE'),
        ],
		'daas' => [
            'driver' => 'odbc',
            'dsn' => env('DAAS_DSN'),
            'username' => env('DAAS_USERNAME'),
            'password' => env('DAAS_PASSWORD'),
            'database' => env('DAAS_DATABASE'),
			'options' => [
				\PDO::ODBC_ATTR_USE_CURSOR_LIBRARY => \PDO::ODBC_SQL_USE_DRIVER
			]
        ],
    ],
    'migrations' => 'migrations',
    'redis' => [
        'client' => env('REDIS_CLIENT', 'phpredis'),
        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => null,
        ],
        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],
        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '0'),
        ],
        'queue' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_QUEUE_DB', '0'),
        ],
    ],

];

if (env('DB_IGNORE_READ_REPLICA') === true) {
    unset($config['connections']['mysql']['read']);
    unset($config['connections']['mysql']['write']);
    $config['connections']['mysql']['host'] = $host;
    $config['connections']['mysql']['database'] = $db;
    $config['connections']['mysql']['username'] = $user;
    $config['connections']['mysql']['password'] = $password;
}

return $config;
