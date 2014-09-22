<?php
/**
 * Base class for table objects
 *
 * Copyright (C) 2011-2014 Holger Schletz <holger.schletz@web.de>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

Namespace Database;

/**
 * Base class for table objects
 *
 * Table objects should be pulled from the service manager which provides the
 * Database\Table\ClassName services which will create and set up object
 * instances.
 */
abstract class AbstractTable extends \Zend\Db\TableGateway\AbstractTableGateway
{
    /**
     * Service manager
     * @var \Zend\ServiceManager\ServiceLocatorInterface
     */
    protected $_serviceLocator;

    /**
     * Constructor
     *
     * @param \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator Service manager instance
     * @codeCoverageIgnore
     */
    public function __construct(\Zend\ServiceManager\ServiceLocatorInterface $serviceLocator)
    {
        $this->_serviceLocator = $serviceLocator;
        if (!$this->table) {
            // If not set explicitly, derive table name from class name.
            $this->table = strtolower($this->_getClassName());
        }
        $this->adapter = $serviceLocator->get('Db');
        $this->initialize();
    }

    /**
     * Helper method to get class name without namespace
     * @internal
     * @return string Class name
     * @codeCoverageIgnore
     */
    protected function _getClassName()
    {
        return substr(get_class($this), strrpos(get_class($this), '\\') + 1);
    }

    /**
     * Create or update table according to schema file
     *
     * The schema file is located in ./data/ClassName.json and contains all
     * information required to create or alter the table.
     * @codeCoverageIgnore
     */
    public function setSchema()
    {
        $logger = $this->_serviceLocator->get('Library\Logger');
        $schema = \Zend\Config\Factory::fromFile(
            Module::getPath('data/' . $this->_getClassName() . '.json')
        );
        $database = $this->_serviceLocator->get('Database\Nada');

        if (in_array($this->table, $database->getTableNames())) {
            // Table exists
            // Update table and column comments
            $table = $database->getTable($this->table);
            if ($schema['comment'] != $table->getComment()) {
                $table->setComment($schema['comment']);
            }
            $columns = $table->getColumns();
            foreach ($schema['columns'] as $column) {
                if (isset($columns[$column['name']])) {
                    // Column exists. Set comment.
                    $columnObj = $table->getColumn($column['name']);
                    $columnObj->setComment($column['comment']);
                    // Change datatype if different.
                    if (
                        $columnObj->getDatatype() != $column['type'] or
                        $columnObj->getLength() != $column['length']
                    ) {
                        $logger->info(
                            "Setting column $this->table.$column[name] type to $column[type]($column[length])..."
                        );
                        $columnObj->setDatatype($column['type'], $column['length']);
                        $logger->info('done.');
                    }
                } else {
                    $logger->info("Creating column $this->table.$column[name]...");
                    $table->addColumnObject($database->createColumnFromArray($column));
                    $logger->info('done.');
                }
            }
        } else {
            // Table does not exist, create it
            $logger->info("Creating table '$this->table...");
            $table = $database->createTable($this->table, $schema['columns'], $schema['primary_key']);
            $table->setComment($schema['comment']);
            if ($database->isMySql()) {
                $table->setEngine($schema['mysql']['engine']);
                $table->setCharset('utf8');
            }
            $logger->info('done.');
        }

        // Create missing indexes. Ignore name for comparision with existing indexes.
        if (isset($schema['indexes'])) {
            foreach ($schema['indexes'] as $index) {
                if (!$table->hasIndex($index['columns'], $index['unique'])) {
                    $logger->info("Creating index '$index[name]'...");
                    $table->createIndex($index['name'], $index['columns'], $index['unique']);
                    $logger->info('done.');
                }
            }
        }

        $this->_postSetSchema();
    }

    /**
     * Hook to be called after creating/altering table schema
     * @codeCoverageIgnore
     */
    protected function _postSetSchema()
    {
    }

    /**
     * Fetch a single column as a flat array
     * 
     * @param string $name Column name
     * @return array
     */
    public function fetchCol($name)
    {
        $select = $this->sql->select();
        $select->columns(array($name), false);
        $col = array();
        foreach ($this->selectWith($select) as $row) {
            $col[] = $row[$name];
        }
        return $col;
    }
}
