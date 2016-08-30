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

    const ENV_VAR_NAME = 'SILEX_ENV';
    const FILE_EXTENSION = '.json';
    const DEFAULT_ENV = 'dev';

    /**
     * Target environment.
     *
     * @var string
     */
    protected $env;

    /**
     * Configuration parameters.
     *
     * @var array
     */
    protected $params;


    /**
     * Root directory.
     *
     * @var string
     */
    protected $dir;

    /**
     * Constructor.
     *
     * @param string $dir
     * @param array  $params
     * @param string $env
     */
    public function __construct($dir, array $params = [], $env = null) {
        $this->env = self::getenv($env);
        $this->dir = realpath($dir);
        $this->params = $params + [
            '%dir%' => $this->dir,
            '%env%' => $this->env,
        ];
    }

    /**
     * Gets environment name based on $env, $argv, or $_ENV.
     *
     * @param string|null $env
     *
     * @return string
     */
    protected static function getenv($env = null) {
        if ($env !== null) {
            return $env;
        }
        $opts = getopt('', ['env:']);
        if ($opts && isset($opts['env'])) {
            return $opts['env'];
        }
        return getenv(self::ENV_VAR_NAME) ? : self::DEFAULT_ENV;
    }

    /**
     * Loads file contents from the path.
     *
     * @param string $path
     *
     * @throws \RuntimeException
     * @return string
     */
    protected static function load($path) {
        if (!is_file($path) || !is_readable($path)) {
            throw new \RuntimeException('Unable to load config from ' . $path);
        }
        return file_get_contents($path);
    }

    /**
     * Converts file contents to PHP literal.
     *
     * @param string $str
     *
     * @throws \RuntimeException
     * @return mixed
     */
    protected static function parse($str) {
        $result = json_decode($str, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('JSON format is invalid');
        }
        return $result;
    }

    /**
     * Recursively replaces tokens.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    protected function replace($value) {
        if (!$this->params) {
            return $value;
        }
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->replace($v);
            }
            return $value;
        }
        if (is_string($value)) {
            return strtr($value, $this->params);
        }
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function register(Container $app) {
        $path = $this->dir . DIRECTORY_SEPARATOR . $this->env . self::FILE_EXTENSION;
        foreach (self::parse(self::load($path)) as $key => $value) {
            $app[$key] = $app->factory(function() use ($value) {
                return $this->replace($value);
            });
        }
    }

}
