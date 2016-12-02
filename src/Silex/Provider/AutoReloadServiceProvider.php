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
use Silex\Application;
use Silex\Api\BootableProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Finder\Finder;

/**
 * Silex service provider for AutoReload functionality.
 *
 * @author Alexander Lokhman <alex.lokhman@gmail.com>
 * @link https://github.com/lokhman/silex-tools
 */
class AutoReloadServiceProvider implements ServiceProviderInterface, BootableProviderInterface {

    /**
     * {@inheritdoc}
     */
    public function register(Container $app) {
        $app['autoreload'] = false;
        $app['autoreload.interval'] = 60;
        $app['autoreload.uri'] = '/__autoreload';
        $app['autoreload.js_uri'] = '/__autoreload.js';
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app) {
        if (!$app['autoreload']) {
            return false;
        }

        if (!isset($app['autoreload']['dirs']) || !$app['autoreload']['dirs']) {
            throw new \RuntimeException('AutoReload must have "dirs" parameter.');
        }

        $finder = new Finder();
        $finder->ignoreUnreadableDirs();
        $finder->in($app['autoreload']['dirs']);

        if (isset($app['autoreload']['exclude'])) {
            $finder->exclude($app['autoreload']['exclude']);
        }

        if (isset($app['autoreload']['files'])) {
            $files = (array) $app['autoreload']['files'];
            array_walk($files, [$finder, 'name']);
        }

        $app->after(function(Request $request, Response $response) use ($app) {
            $contentType = $response->headers->get('Content-Type');
            if (strpos($contentType, 'text/html') !== 0) {
                return;
            }

            if (!$content = $response->getContent()) {
                return;
            }

            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML($content);
            libxml_clear_errors();
            $head = $dom->getElementsByTagName('head');
            if ($head->length !== 1) {
                return;
            }

            $script = $dom->createElement('script');
            $script->setAttribute('src', $app['autoreload.js_uri']);
            $head[0]->appendChild($script);

            $response->setContent($dom->saveHTML());
        }, Application::LATE_EVENT);

        $app->get($app['autoreload.uri'], function() use ($app, $finder) {
            set_time_limit(0);

            $tick = 0;
            $status = 304;
            $interval = (int) $app['autoreload.interval'];

            while ($tick < $interval) {
                $reload = false;
                foreach ($finder as $file) {
                    $key = '__ar'.crc32($file->getPathname()).':'.$file->getBasename();
                    if (apcu_fetch($key) !== $mtime = $file->getMTime()) {
                        apcu_store($key, $mtime);
                        $reload = true;
                    }
                }

                if ($tick++ && $reload) {
                    $status = 205;
                    break;
                }

                sleep(1);
            }

            return new Response(null, $status);
        });

        $app->get($app['autoreload.js_uri'], function() use ($app) {
            $content = <<<EOF
(function __autoreload(init) {
    var xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== XMLHttpRequest.DONE)
            return;

        switch (xhr.status) {
            case 205:
                console.log('AutoReload: reloading');
                window.location.reload();
                return;
            case 304:
            case 504:
                return __autoreload();
            default:
                var stderr = console.error || console.log;
                stderr('AutoReload: STATUS FAILED', xhr.status, xhr.statusText);
        }
    };

    xhr.open('GET', '{$app['autoreload.uri']}?ts=' + +new Date(), true);
    init && console.log('AutoReload: ready');
    xhr.send();
})(true);
EOF;
            return new Response($content, 200, [
                'Content-Type' => 'text/javascript',
            ]);
        });
    }

}
