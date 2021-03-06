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

namespace Lokhman\Silex\Application;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tools trait.
 *
 * @author Alexander Lokhman <alex.lokhman@gmail.com>
 *
 * @link https://github.com/lokhman/silex-tools
 */
trait ToolsTrait
{
    /**
     * Redirects the user to route with the given parameters.
     *
     * @param string $route      The name of the route
     * @param mixed  $parameters An array of parameters
     * @param int    $status     The status code (302 by default)
     *
     * @return RedirectResponse
     */
    public function redirectToRoute($route, array $parameters = [], $status = 302)
    {
        return new RedirectResponse($this['url_generator']->generate($route, $parameters), $status);
    }

    /**
     * Forward the request to another controller by the URI.
     *
     * @param string $uri
     * @param string $method
     * @param array  $parameters
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function forward($uri, $method, array $parameters = [])
    {
        $request = Request::create($uri, $method, $parameters);

        return $this->handle($request, HttpKernelInterface::SUB_REQUEST);
    }

    /**
     * Forward the request to another controller by the route name.
     *
     * @param string $route
     * @param string $method
     * @param array  $parameters
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function forwardToRoute($route, $method, array $parameters = [])
    {
        return $this->forward($this['url_generator']->generate($route, $parameters), $method);
    }

    /**
     * Adds a flash message for type.
     *
     * @param string $type
     * @param string $message
     */
    public function addFlash($type, $message)
    {
        $this['session']->getFlashBag()->add($type, $message);
    }
}
