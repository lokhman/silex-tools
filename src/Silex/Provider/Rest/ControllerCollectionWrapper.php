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

namespace Lokhman\Silex\Provider\Rest;

use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Wrapper class for ControllerCollection.
 *
 * @author Alexander Lokhman <alex.lokhman@gmail.com>
 * @link https://github.com/lokhman/silex-tools
 */
class ControllerCollectionWrapper {

    protected $controllerCollection;
    protected $controllers;
    protected $static;

    public function __construct(Application $app, ControllerCollection $controllerCollection) {
        $this->controllerCollection = $controllerCollection;
        $this->controllers = $app['rest.controllers'];

        // callback to generate new instance of current class
        $this->static = function($controllerCollection) use ($app) {
            return new static($app, $controllerCollection);
        };

        // error handler must be executed here to match 405 Method Not Allowed
        $app->error(function(\Exception $ex, Request $request, $code) use ($app) {
            foreach ($this->controllers as $controller) {
                $route = $controller->getRoute()->getPath();
                if ($route == $request->getPathInfo()) {
                    return $app['rest.error_handler']($ex, $code);
                }
            }
        });
    }

    public function __call($method, $arguments) {
        $route = call_user_func_array([$this->controllerCollection, $method], $arguments);
        if (in_array($method, ['match', 'get', 'post', 'put', 'delete', 'options', 'patch'])) {
            $this->controllers[] = $route;

            // register early before middleware for route
            $route->before(function(Request $request, Application $app) {
                if ($request->getContentType() == 'json') {
                    $data = json_decode($request->getContent(), true);
                    $request->request->replace(is_array($data) ? $data : []);
                }

                // transform every returned data
                $app->view(function($data) use ($app, $request) {
                    $response = $app->json($data);
                    if ($request->query->has('callback')) {
                        try {
                            $response->setCallback($request->query->get('callback'));
                        } catch (\InvalidArgumentException $ex) {
                            throw new BadRequestHttpException($ex->getMessage(), $ex->getPrevious(), $ex->getCode());
                        }
                    }
                    return $response;
                });
            }, Application::EARLY_EVENT);
        }

        return $route;
    }

    /**
     * Mounts controllers under the given route prefix.
     *
     * @param string   $prefix   The route prefix
     * @param callable $callback A callable for defining routes
     *
     * @see ControllerCollection::mount()
     */
    public function mount($prefix, callable $callback) {
        $this->controllerCollection->mount($prefix, function($container) use ($callback) {
            $callback(call_user_func($this->static, $container));
        });
    }

    /**
     * Returns wrapped controller collection.
     *
     * @return ControllerCollection
     */
    public function getControllerCollection() {
        return $this->controllerCollection;
    }

}
