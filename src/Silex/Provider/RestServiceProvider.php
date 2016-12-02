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
 * Silex service provider for RESTful middleware.
 *
 * @author Alexander Lokhman <alex.lokhman@gmail.com>
 * @link https://github.com/lokhman/silex-tools
 */
class RestServiceProvider implements ServiceProviderInterface {

    /**
     * {@inheritdoc}
     */
    public function register(Container $app) {
        $app['rest.controller_collection_wrapper_class'] =
            'Lokhman\Silex\Provider\Rest\ControllerCollectionWrapper';

        $app['rest'] = function(Container $app) {
            $wrapperClass = $app['rest.controller_collection_wrapper_class'];
            return new $wrapperClass($app, $app['controllers']);
        };

        $app['rest.controllers_factory'] = $app->factory(function(Container $app) {
            $wrapperClass = $app['rest.controller_collection_wrapper_class'];
            return new $wrapperClass($app, $app['controllers_factory']);
        });

        $app['rest.controllers'] = function() {
            return new \ArrayObject();
        };

        $app['rest.error_handler'] = $app->protect(
            function(\Exception $ex, $code) use ($app) {
                return $app->json([
                    'status' => 'error',
                    'status_code' => $code,
                    'error' => $ex->getMessage(),
                ], $code);
            }
        );
    }

}
