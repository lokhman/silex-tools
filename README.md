# silex-tools
Tools for [**Silex 2.0+**](http://silex.sensiolabs.org/) micro-framework.

## <a name="installation"></a>Installation
You can install `silex-tools` with [Composer](http://getcomposer.org):

    composer require lokhman/silex-tools

## <a name="components"></a>Components
- [Console Application](#console-application)
- [Config Service Provider](#config-service-provider)
- [Doctrine Service Provider](#doctrine-service-provider)
- [REST Service Provider](#rest-service-provider)
- [AutoReload Service Provider](#autoreload-service-provider)
- [Hashids Service Provider](#hashids-service-provider)
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
guess parameter types in `SELECT`, `INSERT`, `UPDATE` and `DELETE` statements.

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

Type mapping for `SELECT` statements can be disabled or overridden by passing
respectively `FALSE` or types `array` to `$app['db']->setMappings()` helper
method.

Module is compatible with PDO drivers only and requires
[`APCu`](http://php.net/manual/en/book.apcu.php) extension enabled for better
performance. To clear APCu cache use console command `cache:clear`, that is
registered automatically with [Console Application](#console-application).

### <a name="rest-service-provider"></a>REST Service Provider
Registering `RestServiceProvider` will easily extend your application routing
with JSON request/response methods, error handing and JSON parameter acceptance.
It works in the same way as Silex routing binding (`get`, `post`, etc methods),
supports own `controllers_factory` and `mount` functionality.

    use Lokhman\Silex\Provider as ToolsProviders;

    $app->register(new ToolsProviders\RestServiceProvider());

    // can return JSON object omitting $app->json() call
    $app['rest']->get('/api', function() { return ['version' => '1.0']; });

    // can mount controller collections
    $app['rest']->mount('/api/v2', function($api) {
        // accepts parameters from "application/json" body
        $api->post('/submit', function(Request $request) {
            return ['params' => $request->request->all()];
        });
    });

    // can mount controller providers
    class ApiBundle implements ControllerProviderInterface {
        function connect(Application $app) {
            $factory = $app['rest.controllers_factory'];

            $factory->get('/', function() {
                // will modify all exceptions to JSON compatible responses
                throw new GoneHttpException('API v3 is not supported anymore.');
            });

            return $factory->getControllerCollection();
        }
    }

    $app->mount('/api/v3', new ApiBundle());

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

### <a name="hashids-service-provider"></a>Hashids Service Provider
Enable `HashidsServiceProvider` from `Provider` namespace to support brilliant
[Hashids library](http://hashids.org/php/). You may setup either a single or
multiple profiles to use in your application.

    use Lokhman\Silex\Provider as ToolsProviders;

    $app->register(new ToolsProviders\HashidsServiceProvider(), [
        // default options for all profiles
        'hashids.options' => [
            'salt' => '',
            'min_length' => 0,
            'alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
        ],
        // optionally set multiple profiles
        'hashids.profiles' => [
            'profile1' => [
                'min_length' => 8,
            ],
            'profile2' => [
                'salt' => 'this is my salt',
                'alphabet' => 'abcdefghijklmnopqrstuvwxyz',
            ],
        ],
    ]);

    // single profile encoding
    $hash = $app['hashids']->encode(1, 2, 3);
    $app['hashids']->encode(1, 2, 3) === $hash;
    $app->hashids([1, 2, 3]) === $hash;

    // single profile decoding
    $id = $app['hashids']->decode($hash);
    $app['hashids']->decode($hash) === $id;
    $app->hashids($hash) === $id;

    // multiple profiles encoding
    $hash = $app['hashids']['profile1']->encode(1, 2, 3);
    $app['hashids:profile1']->encode(1, 2, 3) === $hash;
    $app->hashids([1, 2, 3], 'profile1') === $hash;

    // multiple profiles decoding
    $id = $app['hashids']['profile1']->decode($hash);
    $app['hashids:profile1']->decode($hash) === $id;
    $app->hashids($hash, 'profile1') === $id;

**N.B.:** To use `hashids()` application method, include `HashidsTrait` to your
`Application` class. This method returns `FALSE` on error, and if given hash
contains a single encoded `id`, integer is returned, otherwise array.

Additionally, the service provider adds support for auto-conversion of hashed
parameters in routes if you specify them with `__hashids_` prefix.

    // GET /users/jR (single profile)
    $app->get('/users/{__hashids_id}', function(Application $app, $id) {
        return $app->json(['id' => $id]);  // { "id": 1 }
    });

    // GET /users/olejRejN (multiple profiles)
    $app->get('/users/{__hashids_profile1_id}', function(Application $app, $id) {
        return $app->json(['id' => $id]);  // { "id": 1 }
    });

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
