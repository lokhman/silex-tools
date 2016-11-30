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

use Doctrine\DBAL\Connection as BaseConnection;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Types\Type;
use SqlParser\Parser;
use SqlParser\Statements as Statements;

/**
 * Wrapper class for DBAL Connection.
 *
 * @author Alexander Lokhman <alex.lokhman@gmail.com>
 * @link https://github.com/lokhman/silex-tools
 */
class Connection extends BaseConnection {

    protected $profile;

    /**
     * Gets connection profile.
     *
     * @return string
     */
    public function getProfile() {
        return $this->profile;
    }

    /**
     * Sets connection profile.
     *
     * @param string $profile
     *
     * @return Connection
     */
    public function setProfile($profile) {
        $this->profile = $profile;

        return $this;
    }

    protected function parseSql($sql) {
        $parser = new Parser($sql);
        if (!isset($parser->statements[0])) {
            throw new DBALException('No SQL queries provided.');
        }
        return $parser->statements[0];
    }

    protected function getColumnTypeNames($tableName) {
        foreach ($this->getSchemaManager()->listTableColumns($tableName) as $column) {
            $types[$column->getName()] = $column->getType()->getName();
        }
        if (!$types) {
            throw new DBALException(sprintf("Table '%s' does not exist.", $tableName));
        }
        return $types;
    }

    protected function getColumnTypes($tableName) {
        if (function_exists('apcu_fetch')) {
            $key = '__dbal:'.$this->profile.'.'.$tableName;
            if (false === $types = apcu_fetch($key)) {
                $types = $this->getColumnTypeNames($tableName);
                apcu_store($key, $types);
            }
        } else {
            $types = $this->getColumnTypeNames($tableName);
        }

        return array_map(function($name) {
            return Type::getType($name);
        }, $types);
    }

    protected function setParamTypes(array $params, array &$types) {
        foreach ($params as $key => $value) {
            if (isset($types[$key])) {
                continue;
            }

            if (is_bool($value)) {
                $types[$key] = Type::BOOLEAN;
            } elseif (is_float($value)) {
                $types[$key] = Type::FLOAT;
            } elseif (is_int($value)) {
                $types[$key] = Type::INTEGER;
            } elseif (is_array($value)) {
                $types[$key] = Type::TARRAY;
            } elseif (is_resource($value)) {
                $types[$key] = Type::BLOB;
            } elseif ($value instanceof \DateTime) {
                $types[$key] = Type::DATETIMETZ;
            } elseif ($value instanceof \Serializable) {
                $types[$key] = Type::OBJECT;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function executeQuery($query, array $params = [], $types = [], QueryCacheProfile $qcp = null) {
        $parsedSql = $this->parseSql($query);

        if (!$parsedSql instanceof Statements\SelectStatement) {
            throw new DBALException('SQL query should be SELECT.');
        }

        // guess param types
        $this->setParamTypes($params, $types);

        // FROM + JOIN expressions
        $tableExprs = $parsedSql->from;
        if ($parsedSql->join) {
            foreach ($parsedSql->join as $join) {
                $tableExprs[] = $join->expr;
            }
        }

        if (!$tableExprs) {  // SELECT 1
            $stmt = parent::executeQuery($query, $params, $types, $qcp);
            return new Statement($this->getDatabasePlatform(), $stmt);
        }

        $mappings = [];

        // collect table names
        foreach ($tableExprs as $expr) {
            if ($expr->alias) {
                $tableNames[$expr->alias] = $expr->table;
            }
            $tableNames[$expr->table] = $expr->table;
        }

        // fetch table column types
        foreach (array_unique($tableNames) as $tableName) {
            $columnTypes[$tableName] = $this->getColumnTypes($tableName);
        }

        // generate result mapping
        foreach ($parsedSql->expr as $expr) {
            if ($expr->alias) {
                if ($expr->table) {
                    // SELECT a.b AS c
                    $tableName = $tableNames[$expr->table];
                    if (isset($columnTypes[$tableName][$expr->column])) {
                        $mappings[$expr->alias] = $columnTypes[$tableName][$expr->column];
                    }
                } else {
                    // SELECT a AS c
                    foreach ($columnTypes as $tableTypes) {
                        if (isset($tableTypes[$expr->column])) {
                            $mappings[$expr->alias] = $tableTypes[$expr->column];
                            break;
                        }
                    }
                }
            } elseif ($expr->column) {
                if ($expr->table) {
                    // SELECT a.b
                    $tableName = $tableNames[$expr->table];
                    if (isset($columnTypes[$tableName][$expr->column])) {
                        $mappings[$expr->column] = $columnTypes[$tableName][$expr->column];
                    }
                } else {
                    // SELECT a
                    foreach ($columnTypes as $tableTypes) {
                        if (isset($tableTypes[$expr->column])) {
                            $mappings[$expr->column] = $tableTypes[$expr->column];
                            break;
                        }
                    }
                }
            } elseif ($expr->expr == '*') {
                // SELECT *
                foreach ($columnTypes as $tableTypes) {
                    $mappings = array_merge($mappings, $tableTypes);
                }
            } elseif (isset($tableNames[$expr->table]) && $expr->expr == $expr->table.'.*') {
                // SELECT a.*
                $mappings = array_merge($mappings, $columnTypes[$tableNames[$expr->table]]);
            } else {
                // SELECT a (reserved word)
                foreach ($columnTypes as $tableTypes) {
                    if (isset($tableTypes[$expr->expr])) {
                        $mappings[$expr->expr] = $tableTypes[$expr->expr];
                        break;
                    }
                }
            }
        }

        // generate result set
        $stmt = parent::executeQuery($query, $params, $types, $qcp);
        return new Statement($this->getDatabasePlatform(), $stmt, $mappings);
    }

    /**
     * {@inheritdoc}
     */
    public function executeUpdate($query, array $params = [], array $types = []) {
        // guess param types
        $this->setParamTypes($params, $types);

        // execute query and return result
        return parent::executeUpdate($query, $params, $types);
    }

}
