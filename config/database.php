<?php
$schema='DPTYS_';
use Illuminate\Support\Str;

return [

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

    'default' => env($schema.'DB_CONNECTION', 'mysql'),

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
            'url' => env($schema.'DATABASE_URL'),
            'database' => env($schema.'DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env($schema.'DB_FOREIGN_KEYS', true),
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env($schema.'DATABASE_URL'),
            'host' => env($schema.'DB_HOST', '127.0.0.1'),
            'port' => env($schema.'DB_PORT', '3306'),
            'database' => env($schema.'DB_DATABASE', 'forge'),
            'username' => env($schema.'DB_USERNAME', 'forge'),
            'password' => env($schema.'DB_PASSWORD', ''),
            'unix_socket' => env($schema.'DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env($schema.'MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env($schema.'DATABASE_URL'),
            'host' => env($schema.'DB_HOST', '127.0.0.1'),
            'port' => env($schema.'DB_PORT', '5432'),
            'database' => env($schema.'DB_DATABASE', 'forge'),
            'username' => env($schema.'DB_USERNAME', 'forge'),
            'password' => env($schema.'DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env($schema.'DATABASE_URL'),
            'host' => env($schema.'DB_HOST', 'localhost'),
            'port' => env($schema.'DB_PORT', '1433'),
            'database' => env($schema.'DB_DATABASE', 'forge'),
            'username' => env($schema.'DB_USERNAME', 'forge'),
            'password' => env($schema.'DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'encrypt' => env($schema.'DB_ENCRYPT', 'yes'),
            'trust_server_certificate' => env($schema.'DB_TRUST_SERVER_CERTIFICATE', 'false'),
            // Devolver columnas numéricas como números (por defecto el driver las entrega como texto)
            'options' => extension_loaded('pdo_sqlsrv') ? [PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE => true] : [],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'client' => env($schema.'REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env($schema.'REDIS_CLUSTER', 'redis'),
            'prefix' => env($schema.'REDIS_PREFIX', Str::slug(env($schema.'APP_NAME', 'laravel'), '_').'_database_'),
        ],

        'default' => [
            'url' => env($schema.'REDIS_URL'),
            'host' => env($schema.'REDIS_HOST', '127.0.0.1'),
            'username' => env($schema.'REDIS_USERNAME'),
            'password' => env($schema.'REDIS_PASSWORD'),
            'port' => env($schema.'REDIS_PORT', '6379'),
            'database' => env($schema.'REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env($schema.'REDIS_URL'),
            'host' => env($schema.'REDIS_HOST', '127.0.0.1'),
            'username' => env($schema.'REDIS_USERNAME'),
            'password' => env($schema.'REDIS_PASSWORD'),
            'port' => env($schema.'REDIS_PORT', '6379'),
            'database' => env($schema.'REDIS_CACHE_DB', '1'),
        ],

    ],

];
