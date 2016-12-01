# silex-tools
Tools for [**Silex 2.0+**](http://silex.sensiolabs.org/) micro-framework.

## <a name="installation"></a>Installation
You can install `silex-tools` with [Composer](http://getcomposer.org):

    composer require lokhman/silex-tools

## <a name="components"></a>Components
- [Console Application](#console-application)
- [Config Service Provider](#config-service-provider)
- [Doctrine Service Provider](#doctrine-service-provider)
- [AutoReload Service Provider](#autoreload-service-provider)
- [Tools Trait](#tools-trait)

### <a name="console-application"></a>Console Application
A wrapper class for [Symfony Console](https://github.com/symfony/console)
application that registers console commands and service providers. Also supplies
`DoctrineServiceProvider` and `DoctrineMigrationsServiceProvider` classes to
register commands for [Doctrine DBAL](https://github.com/doctrine/dbal),
[Doctrine ORM](https://github.com/doctrine/doctrine2) and
[Doctrine Migrations](https://github.com/doctrine/migrations).

    #!/usr/bin/env php
    require __DIR__ . '/../vendor/autoload.php';

    use Silex\Application;
    use Silex\Provider as Providers;
    use Lokhman\Silex\Console\Console;
    use Lokhman\Silex\Console\Provider as ConsoleProviders;

    $app = new Application();
    $app->register(new Providers\DoctrineServiceProvider());

    $console = new Console($app);
    $console->registerServiceProvider(new ConsoleProviders\DoctrineServiceProvider());
    $console->registerServiceProvider(new ConsoleProviders\DoctrineMigrationsServiceProvider(), [
        'migrations.directory' => __DIR__ . '/../app/migrations',
        'migrations.namespace' => 'Project\Migrations',
    ]);
    $console->run();

Console supports [`ConfigServiceProvider`](#config-service-provider) and adds
`--env` (`-e` in short) option to all registered commands.

### <a name="config-service-provider"></a>[Config Service Provider](docs/config-service-provider.md)
Simple and lightweight configuration provider, which uses JSON files to manage
application configuration. Library supports different environments via setting
a global environment variable.

    use Lokhman\Silex\Provider as ToolsProviders;

    $app->register(new ToolsProviders\ConfigServiceProvider(), [
        'config.dir' => __DIR__ . '/../app/config',
    ]);

### <a name="doctrine-service-provider"></a>Doctrine Service Provider
Extended [`DoctrineServiceProvider`](http://silex.sensiolabs.org/doc/master/providers/doctrine.html)
with wrapper classes for Doctrine `Connection` and `Statement`. It overrides the
configuration and setup of the original service provider, but can automatically
convert fetched values as per Doctrine column types from the database schema and
guess parameter types in SELECT, INSERT, UPDATE and DELETE statements.

    use Lokhman\Silex\Provider as ToolsProviders;

    $app->register(new ToolsProviders\DoctrineServiceProvider(), [
        'dbs.options' => [
            'default' => [
                'driver' => 'pdo_mysql',
                'host' => 'localhost',
                'dbname' => 'database',
                'user' => 'root',
                'password' => '',
                'charset' => 'utf8',
            ],
        ],
    ]);

    // controller code
    $param = new \DateTime();  // parameter will be converted to `datetimetz`
    $user = $app['db']->fetchAssoc('SELECT * FROM tbl WHERE col > ?', [$param]);

    /**
     * array (size=3)
     *   'id' => int 1
     *   'name' => string 'Name'
     *   'created_at' => object(DateTime)
     */

Module is compatible with PDO drivers only and requires
[`APCu`](http://php.net/manual/en/book.apcu.php) extension enabled for better
performance. To clear APCu cache use console command `cache:clear`, that is
registered automatically with [Console Application](#console-application).

### <a name="autoreload-service-provider"></a>AutoReload Service Provider
Simple service provider for page auto-reload functionality. It will embed small
JavaScript file into every page with `text/html` content type, that will reload
the page once any (as per configuration) file in the tree is updated. Supports
directories, file name patterns and path exclusions.

    use Lokhman\Silex\Provider as ToolsProviders;

    $app->register(new ToolsProviders\AutoReloadServiceProvider(), [
        'autoreload.interval' => 60,
        'autoreload.uri' => '/__autoreload',
        'autoreload.js_uri' => '/__autoreload.js',
        'autoreload' => [
            'dirs' => ['/dir/to/watch1', '/dir/to/watch2'],
            'files' => ['*.twig', '*.css', '*.js'],
            'exclude' => ['node_modules'],
        ],
    ]);

Module can be switched off with setting `autoreload` parameter to `false`.

Requires [`APCu`](http://php.net/manual/en/book.apcu.php) extension enabled and
[`Symfony Finder`](https://github.com/symfony/finder) library.

### <a name="tools-trait"></a>Tools Trait
Trait to be included in overridden Silex `Application` class. Provides various
useful methods to be used in the container.

    use Silex\Application as BaseApplication;
    use Lokhman\Silex\Application\ToolsTrait;

    class Application extends BaseApplication {
        use ToolsTrait;

        public function __construct(array $values = []) {
            parent::__construct($values);
        }
    }

## <a name="license"></a>License
Library is available under the MIT license. The included LICENSE file describes
this in detail.
