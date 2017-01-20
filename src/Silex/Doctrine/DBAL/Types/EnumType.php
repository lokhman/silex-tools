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
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;

/**
 * Type class to register ENUM database type.
 *
 * @author Alexander Lokhman <alex.lokhman@gmail.com>
 * @link https://github.com/lokhman/silex-tools
 */
class EnumType extends Type {

    const NAME = 'enum';

    /**
     * Overridable method to return ENUM values.
     *
     * @static
     *
     * @return array
     */
    public static function getValues() {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform) {
        if (isset($fieldDeclaration['values'])) {
            $values = $fieldDeclaration['values'];
        } else {
            $values = static::getValues();
        }

        if (!$values || !is_array($values)) {
            throw new DBALException('Invalid ENUM declaration.');
        }

        $maxLength = 0;
        foreach ($values as $value) {
            $maxLength = max($maxLength, mb_strlen($value));
            $literals[] = $platform->quoteStringLiteral($value);
        }

        $literals = implode(', ', $literals);

        if ($platform instanceof SqlitePlatform) {
            return sprinf('TEXT CHECK(%s IN (%s))', $fieldDeclaration['name'], $literals);
        }

        if ($platform instanceof PostgreSqlPlatform || $platform instanceof SQLServerPlatform) {
            return sprintf('VARCHAR(%d) CHECK(%s IN (%s))', $maxLength, $fieldDeclaration['name'], $literals);
        }

        return sprintf('ENUM(%s)', $literals);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform) {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform) {
        if ($value === null) {
            return null;
        }

        if (($values = static::getValues()) && !in_array($value, $values)) {
            throw new \InvalidArgumentException(sprintf('Invalid ENUM value "%s". Possible values are: %s.',
                $value, implode(', ', $values)));
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform) {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return self::NAME;
    }

}
