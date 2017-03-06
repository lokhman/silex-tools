# silex-tools

[![StyleCI](https://styleci.io/repos/66957621/shield?branch=master)](https://styleci.io/repos/66957621)

Tools for [**Silex 2.0+**](http://silex.sensiolabs.org/) micro-framework.

## <a name="installation"></a>Installation
You can install `silex-tools` with [Composer](http://getcomposer.org):

    composer require lokhman/silex-tools

## <a name="components"></a>Components
- [Console Application](https://github.com/lokhman/silex-console)
- [Config Service Provider](https://github.com/lokhman/silex-config)
- [RESTful Service Provider](https://github.com/lokhman/silex-restful)
- [Application Class](#application-class)
- [Route Class](#route-class)
- [Tools Trait](#tools-trait)

## <a name="suggested-components"></a>Suggested Components
- [Assetic Service Provider](https://github.com/lokhman/silex-assetic)
- [Hashids Service Provider](https://github.com/lokhman/silex-hashids)
- [AutoReload Service Provider](https://github.com/lokhman/silex-autoreload)

### <a name="application-class"></a>Application Class
Class that overrides base `Silex\Application` class and provides automatic registration of `ConfigServiceProvider`,
error handling and [Tools Trait](#tools-trait).

    use Lokhman\Silex\Application as BaseApplication;

    class Application extends BaseApplication {

        public function __construct(array $values = []) {
            $values['config.dir'] = __DIR__.'/../app/config';

            parent::__construct($values);

            // ...
        }

    }

### <a name="route-class"></a>Route Class
Class that overrides base `Silex\Route` class and adds support for
[`SecurityTrait`](http://silex.sensiolabs.org/doc/2.0/providers/security.html#traits). You can enable it with:

    $app['route_class'] = 'Lokhman\Silex\Route';

### <a name="tools-trait"></a>Tools Trait
Trait to be included in overridden Silex `Application` class. Provides various useful methods to be used in the
container. This trait is automatically included into [Application Container](#application-container).

    use Silex\Application as BaseApplication;
    use Lokhman\Silex\Application\ToolsTrait;

    class Application extends BaseApplication {

        use ToolsTrait;

        public function __construct(array $values = []) {
            parent::__construct($values);
        }

    }

## <a name="license"></a>License
Library is available under the MIT license. The included LICENSE file describes this in detail.
