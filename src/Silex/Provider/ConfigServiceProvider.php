<?php
/**
 * Tools for Silex 2+ framework.
 *
 * @author Alexander Lokhman <alex.lokhman@gmail.com>
 * @link https://github.com/lokhman/silex-tools
 *
 * Copyright (c) 2016 Alexander Lokhman <alex.lokhman@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Lokhman\Silex\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Silex service provider for lightweight JSON framework configuration.
 *
 * @author Alexander Lokhman <alex.lokhman@gmail.com>
 * @link https://github.com/lokhman/silex-tools
 */
class ConfigServiceProvider implements ServiceProviderInterface {

    const DEFAULT_ENV_VARNAME = 'SILEX_ENV';
    const DEFAULT_ENVIRONMENT = 'local';

    /**
     * Root directory.
     *
     * @var string
     */
    protected $dir;

    /**
     * Target environment.
     *
     * @var string
     */
    protected $env;

    /**
     * Additional parameters.
     *
     * @var array
     */
    protected $params;

    /**
     * Constructor.
     *
     * @param string $dir
     * @param array  $options
     */
    public function __construct($dir, array $options = []) {
        $this->dir = realpath($dir);

        if (isset($options['env'])) {
            $this->env = $options['env'];
        } else {
            $varname = self::DEFAULT_ENV_VARNAME;
            if (isset($options['env_varname'])) {
                $varname = $options['env_varname'];
            }
            $this->env = getenv($varname) ? : self::DEFAULT_ENVIRONMENT;
        }

        $this->params = ['__DIR__' => $this->dir, '__ENV__' => $this->env];
        if (isset($options['params']) && is_array($options['params'])) {
            $this->params += array_change_key_case($options['params'], CASE_UPPER);
        }
    }

    /**
     * Replaces tokens in the configuration.
     *
     * @param mixed $data
     *
     * @return mixed
     */
    protected function replaceTokens($data) {
        if (is_string($data)) {
            return preg_replace_callback('/%(\w+)%/', function($matches) {
                $token = strtoupper($matches[1]);
                if (isset($this->params[$token])) {
                    return $this->params[$token];
                }
                return getenv($token);
            }, $data);
        }

        if (is_array($data)) {
            array_walk($data, function(&$value) {
                $value = $this->replaceTokens($value);
            });
        }

        return $data;
    }

    /**
     * Reads configuration file.
     *
     * @param string $path
     *
     * @return mixed
     *
     * @throws \RuntimeException
     */
    protected function readFile($path) {
        $basename = preg_replace('/.json$/', '', ltrim($path, '/'));
        $path = $this->dir . DIRECTORY_SEPARATOR . $basename . '.json';

        if (!is_file($path) || !is_readable($path)) {
            throw new \RuntimeException(sprintf('Unable to load configuration from "%s".', $path));
        }

        $data = json_decode(file_get_contents($path), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Configuration JSON format is invalid.');
        }

        if (isset($data['$extends'])) {
            $data += $this->readFile($data['$extends']);
            unset($data['$extends']);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function register(Container $app) {
        foreach ($this->readFile($this->env) as $key => $value) {
            $app[$key] = $app->factory(function() use ($value) {
                return $this->replaceTokens($value);
            });
        }
    }

}
