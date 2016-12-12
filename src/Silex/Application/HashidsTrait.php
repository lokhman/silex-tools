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

namespace Lokhman\Silex\Application;

/**
 * Hashids trait.
 *
 * @author Alexander Lokhman <alex.lokhman@gmail.com>
 * @link https://github.com/lokhman/silex-tools
 */
trait HashidsTrait {

    /**
     * Encodes or decodes the given value with Hashids library.
     *
     * @param mixed       $value   Can be <b>int</b>, <b>array</b> to encode,
     *                             any other type to decode
     * @param string|null $profile [optional] <p>Hashids profile name</p>
     *
     * @return mixed Always returns <b>FALSE</b> on error
     */
    public function hashids($value, $profile = null) {
        $hashids = $this['hashids'];
        if ($profile !== null) {
            $hashids = $hashids[$profile];
        }

        if (is_int($value) || is_array($value)) {
            return $hashids->encode($value);
        }

        $decoded = $hashids->decode($value);
        if (count($decoded) < 2) {
            return current($decoded);
        }

        return $decoded;
    }

}
