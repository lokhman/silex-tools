# silex-tools
Tools for [**Silex 2.0+**](http://silex.sensiolabs.org/) micro-framework.

## <a name="installation"></a>Installation
You can install `silex-tools` with [Composer](http://getcomposer.org):

    composer require lokhman/silex-tools

## <a name="components"></a>Components
- [Console Application](https://github.com/lokhman/silex-console)
- [Config Service Provider](https://github.com/lokhman/silex-config)
- [RESTful Service Provider](https://github.com/lokhman/silex-restful)
- [Application Container](#application-container)
- [Tools Trait](#tools-trait)

## <a name="suggested-components"></a>Suggested Components
- [AutoReload Service Provider](https://github.com/lokhman/silex-autoreload)
- [Hashids Service Provider](https://github.com/lokhman/silex-hashids)

### <a name="application-container"></a>Application Container
Class that overrides base `Silex\Application` and provides automatic registration of `ConfigServiceProvider`, error
handling and [Tools Trait](#tools-trait).

    use Lokhman\Silex\Application as BaseApplication;

    class Application extends BaseApplication {

        public function __construct(array $values = []) {
            $values['config.dir'] = __DIR__.'/../app/config';

            parent::__construct($values);

            // ...
        }

    }

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
