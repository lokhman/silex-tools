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
use Symfony\Component\HttpFoundation\Request;
use Silex\Api\BootableProviderInterface;
use Silex\Application;
use Hashids\Hashids;

/**
 * Silex service provider for Hashids library.
 *
 * @author Alexander Lokhman <alex.lokhman@gmail.com>
 * @link https://github.com/lokhman/silex-tools
 */
class HashidsServiceProvider implements ServiceProviderInterface, BootableProviderInterface {

    /**
     * {@inheritdoc}
     */
    public function register(Container $app) {
        $app['hashids._route_pattern'] = '^__hashids_';

        $app['hashids.options.default'] = [
            'salt' => '',
            'min_length' => 0,
            'alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
        ];

        $app['hashids'] = function(Container $app) {
            $options = $app['hashids.options.default'];

            if (isset($app['hashids.options']) && $app['hashids.options']) {
                $options = array_replace($options, $app['hashids.options']);
            }

            if (isset($app['hashids.profiles']) && $app['hashids.profiles']) {
                $profiles = new Container();

                $pattern = '(';
                foreach ($app['hashids.profiles'] as $profile => $_options) {
                    $_options += $options;

                    $profiles[$profile] = new Hashids($_options['salt'],
                        $_options['min_length'], $_options['alphabet']);

                    $pattern .= preg_quote($profile).'|';
                }
                $app['hashids._route_pattern'] .= substr($pattern, 0, -1).')_';

                return $profiles;
            }

            return new Hashids($options['salt'], $options['min_length'], $options['alphabet']);
        };
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app) {
        if ($app['hashids'] instanceof Container) {
            foreach ($app['hashids']->keys() as $profile) {
                $app['hashids:'.$profile] = $app['hashids']->raw($profile);
            }
        }

        $app->before(function(Request $request) use ($app) {
            $pattern = '/'.$app['hashids._route_pattern'].'/';

            foreach ($request->attributes as $key => $value) {
                if (preg_match($pattern, $key, $matches)) {
                    $hashids = $app['hashids'];
                    if (isset($matches[1])) {
                        $hashids = $hashids[$matches[1]];
                    }

                    $key = substr($key, strlen($matches[0]));
                    $value = $hashids->decode($value);
                    if (count($value) < 2) {
                        $value = current($value);
                    }

                    $request->attributes->set($key, $value);
                }
            }
        }, Application::LATE_EVENT);
    }

}
