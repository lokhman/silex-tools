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

namespace Lokhman\Silex\Doctrine\DBAL;

use Doctrine\DBAL\Driver\PDOStatement;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Wrapper class for PDOStatement.
 *
 * @author Alexander Lokhman <alex.lokhman@gmail.com>
 * @link https://github.com/lokhman/silex-tools
 */
class Statement extends PDOStatement implements \IteratorAggregate {

    protected $platform;
    protected $stmt;
    protected $mappings;

    public function __construct(AbstractPlatform $platform, PDOStatement $stmt, array $mappings = []) {
        $this->platform = $platform;
        $this->stmt = $stmt;
        $this->mappings = $mappings;
    }

    protected function convert($type, $value) {
        if (is_callable($type)) {
            return $type($value);
        }

        return $type->convertToPHPValue($value, $this->platform);
    }

    protected function transform(&$row) {
        if (!$this->mappings) {
            return $row;
        }

        if (is_array($row) || $row instanceof \ArrayAccess) {
            foreach ($this->mappings as $alias => $type) {
                $row[$alias] = $this->convert($type, $row[$alias]);
            }
        } elseif ($row instanceof \stdClass) {
            foreach ($this->mappings as $alias => $type) {
                $row->$alias = $this->convert($type, $row->$alias);
            }
        }
        return $row;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator() {
        return $this->iterate();
    }

    /**
     * Iterates result set.
     *
     * @param integer|null $fetchMode
     * @param integer|null $cursorOrientation
     * @param integer|null $cursorOffset
     *
     * @return \Generator
     */
    public function iterate($fetchMode = null, $cursorOrientation = null, $cursorOffset = null) {
        while ($row = $this->stmt->fetch($fetchMode, $cursorOrientation, $cursorOffset)) {
            yield $this->transform($row);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setFetchMode($fetchMode, $arg2 = null, $arg3 = null) {
        return $this->stmt->setFetchMode($fetchMode, $arg2, $arg3);
    }

    /**
     * {@inheritdoc}
     */
    public function bindValue($param, $value, $type = \PDO::PARAM_STR) {
        return $this->stmt->bindValue($param, $value, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function bindParam($column, &$variable, $type = \PDO::PARAM_STR, $length = null, $driverOptions = null) {
        return $this->stmt->bindParam($column, $variable, $type, $length, $driverOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function execute($params = null) {
        return $this->stmt->execute($params);
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($fetchMode = null, $cursorOrientation = null, $cursorOffset = null) {
        if ($fetchMode !== null && $fetchMode !== \PDO::FETCH_ASSOC && $fetchMode !== \PDO::FETCH_OBJ) {
            return $this->stmt->fetch($fetchMode, $cursorOrientation, $cursorOffset);
        }

        return $this->iterate($fetchMode, $cursorOrientation, $cursorOffset)->current();
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll($fetchMode = null, $fetchArgument = null, $ctorArgs = null) {
        if ($fetchMode !== null && $fetchMode !== \PDO::FETCH_ASSOC && $fetchMode !== \PDO::FETCH_OBJ) {
            return $this->stmt->fetchAll($fetchMode, $fetchArgument, $ctorArgs);
        }

        if ($fetchArgument === null && $ctorArgs === null) {
            return iterator_to_array($this->iterate($fetchMode));
        }

        $rows = $this->stmt->fetchAll($fetchMode, $fetchArgument, $ctorArgs);
        array_walk($rows, [$this, 'transform']);
        return $rows;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumn($columnIndex = 0) {
        return $this->stmt->fetchColumn($columnIndex);
    }

}
