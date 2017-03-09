<?php
/**
 * Tools for Silex 2+ framework.
 *
 * @author Alexander Lokhman <alex.lokhman@gmail.com>
 *
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
use Silex\Provider\TwigServiceProvider as BaseTwigServiceProvider;

/**
 * Extended Twig service provider for Silex micro-framework.
 *
 * @author Alexander Lokhman <alex.lokhman@gmail.com>
 *
 * @link https://github.com/lokhman/silex-tools
 */
class TwigServiceProvider extends BaseTwigServiceProvider
{
    public function register(Container $app)
    {
        parent::register($app);

        // add support for namespaces in "twig.paths" option
        $app['twig.loader.filesystem'] = $app->extend('twig.loader.filesystem', function ($fs, $app) {
            if (isset($app['twig.paths']) && is_array($app['twig.paths'])) {
                foreach ($app['twig.paths'] as $namespace => $path) {
                    $fs->addPath($path, $namespace);
                }
            }

            return $fs;
        });
    }
}
