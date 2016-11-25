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

namespace Lokhman\Silex\Console;

use Silex\Application;
use Symfony\Component\Console\Application as BaseConsole;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Lokhman\Silex\Console\Provider\AbstractServiceProvider;

/**
 * Silex console application.
 *
 * @author Alexander Lokhman <alex.lokhman@gmail.com>
 * @link https://github.com/lokhman/silex-tools
 */
class Console extends BaseConsole {

    /**
     * @var Application
     */
    protected $container;

    /**
     * Constructor.
     *
     * @param Application $container Container application
     * @param string $name           The name of the application
     * @param string $version        The version of the application
     */
    public function __construct(Application $container, $name = 'UNKNOWN', $version = 'UNKNOWN') {
        $this->container = $container;

        set_time_limit(0);

        parent::__construct($name, $version);
    }

    /**
     * Returns container application.
     *
     * @return Application
     */
    public function getContainer() {
        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    public function run(InputInterface $input = null, OutputInterface $output = null) {
        $this->container->boot();

        parent::run($input, $output);
    }

    /**
     * Registers a service provider.
     *
     * @param AbstractServiceProvider $provider An AbstractServiceProvider instance
     * @param array                   $values   An array of values that customizes the provider
     *
     * @return Console
     */
    public function register(AbstractServiceProvider $provider, array $values = array()) {
        $this->container->register($provider->setConsole($this), $values);

        return $this;
    }

}
