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

namespace Lokhman\Silex\Doctrine\DBAL\Types;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Type class to register ENUM database type.
 *
 * @author Alexander Lokhman <alex.lokhman@gmail.com>
 * @link https://github.com/lokhman/silex-tools
 */
class EnumType extends Type {

    const NAME = 'enum';

    protected $values;

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform) {
        if (!isset($fieldDeclaration['values']) || !is_array($fieldDeclaration['values'])) {
            var_dump($fieldDeclaration);
            throw new DBALException('Invalid ENUM declaration.');
        }

        $this->values = $fieldDeclaration['values'];

        return 'ENUM(' . implode(',', array_map(function($value) use ($platform) {
            return $platform->quoteStringLiteral($value);
        }, $this->values)) . ')';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform) {
        return $value;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform) {
        if (!in_array($value, $this->values)) {
            throw new \InvalidArgumentException('Invalid ENUM value.');
        }

        return $value;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform) {
        return true;
    }

    public function getName() {
        return self::NAME;
    }

}
