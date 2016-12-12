# ConfigServiceProvider

Simple and lightweight configuration provider for
[Silex](http://silex.sensiolabs.org/) micro-framework. It uses JSON files to
manage configuration. Library also supports different environments via setting
a global environment variable.

## Installation
You can install library through [Composer](http://getcomposer.org):

    composer require lokhman/silex-tools

## Usage
### File structure
First off, create a `config` folder in your application directory and add
configuration JSON files one per intended environment (default is `local`):

    /
      app/
        config/
          dev.json
          local.json
          prod.json
          staging.json
        logs/
      src/
      tests/
      vendor/
      web/
        index.php
      composer.json
      ...

### Config files
Next, add all your defaults to the config files, e.g.:

    {
        "env": "%__ENV__%",
        "debug": true,
        "dbs.options": {
            "default": {
                "driver": "pdo_mysql",
                "host": "localhost",
                "dbname": "database",
                "user": "root",
                "password": "",
                "charset": "utf8"
            }
        },
        "any": {
            "other": "constant"
        }
    }

### Register
Now register service provider in your Silex application:

    use Lokhman\Silex\Provider as ToolsProviders;

    $app->register(new ToolsProviders\ConfigServiceProvider(), [
        'config.dir' => __DIR__ . '/../app/config',
    ]);

`config.dir` parameter refers to a configuration folder path with JSON files.

### Global environment variable
Finally, you can set up your web server to add support of different deployment
environments. In order to do this, you have to set a global environmental
variable.

#### nginx + PHP-FPM

    fastcgi_param SILEX_ENV prod

#### Apache

    SetEnv SILEX_ENV prod

### CLI

    $ SILEX_ENV=prod bash -c "php bin/console migrations:status"

If you use [Console Application](../#console-application) together with
`ConfigServiceProvider` you can pass `--env` (`-e` in short) option to all
registered commands:

    $ php bin/console migrations:status --env=prod

## Parameters

`ConfigServiceProvider` supports the following parameters:

| Parameter                | Description                                                                                 | Default       |
|--------------------------|---------------------------------------------------------------------------------------------|---------------|
| `config.dir`             | Folder path with JSON files.                                                                | `null`        |
| `config.params`          | Array of replacement tokens to use in configuration.                                        | `[]`          |
| `config.env.default`     | Environment to use strictly on provider registration (ignores global environment variable). | `"local"`     |
| `config.varname.default` | Name of global environment variable.                                                        | `"SILEX_ENV"` |

By default, service provider embeds tokens `__DIR__` and `__ENV__`, as well as
all PHP environment variables (e.g. `REMOTE_ADDR`, `SERVER_NAME`, etc).

## Dynamic tokens

You can define tokens dynamically in the JSON files using property `$params`:

    local.json
    {
        "$params": {
            "SECRET": "3ecd45ff71c87269569e682f2f6b2ec4"
        },
        "settings": {
            "prop1": "%SECRET%",
            "prop2": "%secret%",
            "prop3": "%SeCrEt%"
        }
    }

**N.B.:** All tokens are case insensitive.

## Extending

You can extend JSON configuration (include one JSON file into another) simply
using root property `$extends`, that points to the file to extend (file
extension can be omitted). For example:

    local.json
    {
        "env": "%__ENV__%",
        "debug": true,
        "locale": "en"
    }

    prod.json
    {
        "$extends": "local",
        "debug": false
    }
