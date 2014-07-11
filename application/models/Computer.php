<?php
/**
 * Class representing a computer
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
 *
 * @package Models
 */
/**
 * A single computer which is inventoried by OCS agent
 *
 * Properties:
 *
 * - <b>Id:</b> primary key
 * - <b>ClientId:</b> Client-generated ID (name + timestamp, like 'COMPUTERNAME-2009-04-27-15-52-37')
 * - <b>Name:</b> computer name
 * - <b>Type:</b> computer type (Desktop, Notebook...) as reported by BIOS
 * - <b>Manufacturer:</b> system manufacturer
 * - <b>Model:</b> system model
 * - <b>Serial:</b> serial number
 * - <b>AssetTag:</b> asset tag
 * - <b>Workgroup:</b> Windows workgroup or domain
 * - <b>CpuClock:</b> CPU clock in MHz
 * - <b>CpuCores:</b> total number of CPUs/cores
 * - <b>CpuType:</b> CPU manufacturer and model
 * - <b>InventoryDate:</b> timestamp of last inventory
 * - <b>LastContactDate:</b> timestamp of last agent contact (may be newer than InventoryDate)
 * - <b>PhysicalMemory:</b> Amount of RAM as reported by OS. May be lower than actual RAM.
 * - <b>SwapMemory:</b> Amount of swap space in use
 * - <b>BiosManufacturer:</b> BIOS manufacturer
 * - <b>BiosVersion:</b> BIOS version
 * - <b>BiosDate:</b> BIOS date
 * - <b>IpAddress:</b> IP Adress
 * - <b>DnsServer:</b> IP Address of DNS server
 * - <b>DefaultGateway:</b> default gateway
 * - <b>OcsAgent:</b> name and version of OCS agent
 * - <b>OsName:</b> OS name (may be processed by getProperty())
 * - <b>OsVersionNumber:</b> internal OS version number
 * - <b>OsVersionString:</b> OS version (Service pack, kernel version etc...)
 * - <b>OsComment:</b> comment
 * - <b>UserName:</b> User logged in at time of inventory
 * - <b>Uuid</b> UUID, typically found in virtual machines
 *
 *
 * Properties containing a '.' character refer to child objects. These properties are:
 *
 * - <b>Package.Status</b> Deployment status (raw value from download_enable.tvalue)
 *
 *
 * Additionally, properties of child objects from a joined query are accessible
 * too. To make this work, an alias for the column has to be specified in the
 * form 'model_property'. Example:
 * <code>SELECT hardware.name, storages.disksize AS storagedevice_size...</code>
 * This would make the property 'StorageDevice.Size' available to this class.
 * Note that only properties defined by the model class will work.
 * The model prefix ensures that ambiguous properties/columns will not clash.
 *
 * If the 'MemberOf' filter is applied, the <b>Membership</b> property is
 * available which contains one of the {@link Model_GroupMembership} constants.
 *
 * Windows-specific information is available through the public 'windows' member
 * and through the 'Windows.*' property.
 *
 * The properties "Registry.*" refer to the combined value and data of a defined
 * registry value with the given name.
 *
 * @package Models
 */
class Model_Computer extends Model_ComputerOrGroup
{

    /** {@inheritdoc} */
    protected $_propertyMap = array(
        // Values from 'hardware' table
        'Id' => 'id',
        'ClientId' => 'deviceid',
        'Name' => 'name',
        'Workgroup' => 'workgroup',
        'CpuClock' => 'processors',
        'CpuCores' => 'processorn',
        'CpuType' => 'processort',
        'InventoryDate' => 'lastdate',
        'LastContactDate' => 'lastcome',
        'PhysicalMemory' => 'memory',
        'SwapMemory' => 'swap',
        'IpAddress' => 'ipaddr',
        'DnsServer' => 'dns',
        'DefaultGateway' => 'defaultgateway',
        'OcsAgent' => 'useragent',
        'OsName' => 'osname',
        'OsVersionNumber' => 'osversion',
        'OsVersionString' => 'oscomments',
        'OsComment' => 'description',
        'UserName' => 'userid',
        'InventoryDiff' => 'checksum',
        'Uuid' => 'uuid',
        // Values from 'bios' table
        'Manufacturer' => 'smanufacturer',
        'Model' => 'smodel',
        'Serial' => 'ssn',
        'Type' => 'type',
        'BiosManufacturer' => 'bmanufacturer',
        'BiosVersion' => 'bversion',
        'BiosDate' => 'bdate',
        'AssetTag' => 'assettag',
        // Values from assigned packages
        'Package.Status' => 'package_status',
        // Values from group memberships
        'Membership' => 'static'
    );

    /** {@inheritdoc} */
    protected $_types = array(
        'Id' => 'integer',
        'CpuClock' => 'integer',
        'CpuCores' => 'integer',
        'InventoryDate' => 'timestamp',
        'LastContactDate' => 'timestamp',
        'PhysicalMemory' => 'integer',
        'SwapMemory' => 'integer',
        'Membership' => 'enum',
    );

    /**
     * List of all child object types
     * @var array
     */
    private static $_childObjectTypes = array(
        'AudioDevice',
        'Controller',
        'Display',
        'DisplayController',
        'ExtensionSlot',
        'InputDevice',
        'Port',
        'MemorySlot',
        'Modem',
        'MsOfficeProduct',
        'NetworkInterface',
        'Printer',
        'RegistryData',
        'Software',
        'StorageDevice',
        'VirtualMachine',
        'Volume',
    );

    /**
     * Windows-specific information
     *
     * Object has undefined content for non-Windows systems.
     * @var Model_Windows
     **/
    public $windows;

    /**
     * Global cache for _getConfigGroups() results
     *
     * This is a 2-dimensional array: $_configGroups[computer ID][n] = group
     */
    protected static $_configGroups = array();

    /**
     * Global cache for getDefaultConfig() results
     *
     * This is a 2-dimensional array: $_configDefault[computer ID][option name] = value
     */
    protected static $_configDefault = array();

    /**
     * Global cache for getEffectiveConfig() results
     *
     * This is a 2-dimensional array: $_configEffective[computer ID][option name] = value
     */
    protected static $_configEffective = array();

    /**
     * Raw properties of child objects from joined queries.
     * @var array
     */
    private $_childProperties = array();

    /**
     * User defined information for this computer
     *
     * It can be 1 of 3 types:
     * 1. A fully populated Model_UserDefinedInfo object
     * 2. An associative array with a subset of available fields
     * 3. NULL if no value has been set yet.
     *
     * It is populated on demand internally. This allows caching the information,
     * efficiently feeding partial information from a query result and making an
     * extra query only if really needed.
     * @var mixed
     */
    private $_userDefinedInfo;

    /**
     * Content of registry value/data for registry search results
     * @var string
     */
    private $_registryContent;

    /**
     * Constructor
     **/
    public function __construct()
    {
        parent::__construct();

        // When instantiated from fetchObject(), __set() gets called before the
        // constructor is invoked, which may initialize the property. Don't
        // overwrite it in that case.
        if (!$this->windows) {
            $this->windows = new Model_Windows;
        };
    }

    /** Return a statement object with all computers matching criteria
     * @param array $columns Logical properties to be returned. If empty or null, return all properties.
     * @param string $order Property to sort by
     * @param string $direction One of [asc|desc]
     * @param string|array $filter Name or array of names of a pre-defined filter routine
     * @param string|array $search Search parameter(s) passed to the filter. May be case sensitive depending on DBMS.
     * @param bool|array $exact Force exact match on search parameter(s) (no wildcards, no substrings) (strings only)
     * @param bool|array $invert Invert query results (return all computers NOT matching criteria)
     * @param string|array $operator Comparision operator (numeric/date search only)
     * @param bool $addSearchColumns Add columns with search criteria (default).
     *                               Set to false to return only columns specified by $columns.
     * @param bool $query Perform query and return a Zend_Db_Statement object (default).
     *                    Set to false to return a Zend_Db_Select object.
     * @param bool $distinct Force distinct results.
     * @return Zend_Db_Statement|Zend_Db_Select Query result or Query
     * @throws LogicException if more than 2 tables are joined (only in development mode)
     */
    static function createStatementStatic(
        $columns=null,
        $order=null,
        $direction='asc',
        $filter=null,
        $search=null,
        $exact=null,
        $invert=null,
        $operator=null,
        $addSearchColumns=true,
        $query=true,
        $distinct=false
    )
    {
        $db = Model_Database::getAdapter();

        $dummy = new Model_Computer;
        $map = $dummy->getPropertyMap();

        if (empty($columns)) {
            $columns = array_keys($map); // Select all properties
        }
        foreach ($columns as $column) {
            switch ($column) {
                case 'Manufacturer':
                case 'Model':
                case 'Serial':
                case 'Type':
                case 'BiosManufacturer':
                case 'BiosVersion':
                case 'BiosDate':
                case 'AssetTag':
                    $fromBios[] = $map[$column];
                    break;
                case 'Package.Status':
                case 'Membership':
                    break; // columns are added later
                default:
                    if (array_key_exists($column, $map)) { // Other properties provided by this class
                        $fromHardware[] = $map[$column];
                    } else {
                        list ($model, $property) = explode('.', $column);
                        if ($model == 'Windows') {
                            $dummyWindows = new Model_Windows;
                            if ($dummyWindows->getTableName($property) == 'hardware') {
                                $fromHardware['windows_' . strtolower($property)] =
                                    $dummyWindows->getColumnName($property);
                            } else {
                                $fromWindows['windows_' . strtolower($property)] =
                                    $dummyWindows->getColumnName($property);
                            }
                        }
                    }
                    // ignore nonexistent columns
            }
        }
        // add PK if not already selected
        if (!in_array('id', $fromHardware)) {
            $fromHardware[] = 'id';
        }

        $select = $db->select()
            ->from('hardware', $fromHardware)
            ->order(self::getOrder($order, $direction, $map));
        if (isset($fromBios)) {
            // Use left join because there might be no matching row in the 'bios' table.
            $select->joinLeft('bios', 'hardware.id = bios.hardware_id', $fromBios);
        }
        if (isset($fromWindows)) {
            // Use left join because there might be no matching row in the 'braintacle_windows' table.
            $select->joinLeft('braintacle_windows', 'hardware.id = braintacle_windows.hardware_id', $fromWindows);
        }

        // apply filters
        if (!is_array($filter)) {
            // convert to array if necessary
            $filter = array($filter);
            $search = array($search);
            $exact = array($exact);
            $invert = array($invert);
        }
        foreach ($filter as $index => $type) {
            $arg = $search[$index];
            $matchExact = $exact[$index];
            $invertResult = $invert[$index];
            switch ($type) {
                case '':
                    break; // No filter requested
                case 'Id':
                    $select->where('id = ?', (int) $arg);
                    break;
                case 'Name':
                case 'Workgroup':
                case 'CpuType':
                case 'DnsServer':
                case 'DefaultGateway':
                case 'OcsAgent':
                case 'OsName':
                case 'OsVersionNumber':
                case 'OsVersionString':
                case 'OsComment':
                case 'UserName':
                case 'Manufacturer':
                case 'Model':
                case 'Serial':
                case 'AssetTag':
                case 'BiosVersion':
                case 'BiosDate':
                    $select = self::_findString(
                        $select,
                        'Computer',
                        $type,
                        $arg,
                        $matchExact,
                        $invertResult,
                        $addSearchColumns
                    );
                    break;
                case 'CpuClock':
                case 'CpuCores':
                case 'PhysicalMemory':
                case 'SwapMemory':
                    $select = self::_findInteger(
                        $select,
                        'Computer',
                        $type,
                        $arg,
                        $operator,
                        $invertResult,
                        $addSearchColumns
                    );
                    break;
                case 'InventoryDate':
                case 'LastContactDate':
                    $select = self::_findDate(
                        $select,
                        'Computer',
                        $type,
                        $arg,
                        $operator,
                        $invertResult,
                        $addSearchColumns
                    );
                    break;
                case 'PackageNonnotified':
                case 'PackageSuccess':
                case 'PackageNotified':
                case 'PackageError':
                    $select = Model_Computer::_filterByPackage($select, $type, $arg, $addSearchColumns);
                    break;
                case 'Software':
                    $select
                        ->distinct()
                        ->join(
                            'softwares',
                            $select->getAdapter()->quoteInto(
                                'hardware.id = softwares.hardware_id AND softwares.name = ?',
                                $arg
                            ),
                            $addSearchColumns ? array('software_version' => 'version') : null
                        );
                    break;
                case 'MemberOf':
                    // $arg is expected to be a Model_Group object.
                    $arg->update();

                    $select
                        ->join(
                            'groups_cache',
                            'hardware.id = groups_cache.hardware_id',
                            $addSearchColumns ? array('static') : null
                        )
                        ->where('groups_cache.group_id = ?', $arg->getId())
                        ->where(
                            'groups_cache.static IN (?)',
                            array(
                                Model_GroupMembership::TYPE_DYNAMIC,
                                Model_GroupMembership::TYPE_STATIC
                            )
                        );
                    break;
                case 'ExcludedFrom':
                    // $arg is expected to be a Model_Group object.
                    $select->where(
                        $db->quoteInto(
                            'id IN(SELECT hardware_id FROM groups_cache WHERE group_id = ? AND static = ?)',
                            $arg->getId(),
                            Zend_Db::INT_TYPE,
                            1
                        ),
                        Model_GroupMembership::TYPE_EXCLUDED
                    );
                    break;
                case 'Volume.Size':
                case 'Volume.FreeSpace':
                    // Generic integer filter
                    list($model, $property) = explode('.', $type);
                    $select = self::_findInteger(
                        $select,
                        $model,
                        $property,
                        $arg,
                        $operator,
                        $invertResult,
                        $addSearchColumns
                    );
                    break;
                default:
                    if (preg_match('#^UserDefinedInfo\\.(.*)#', $type, $matches)) {
                        $property = $matches[1];
                        switch (Model_UserDefinedInfo::getType($property)) {
                            case 'text':
                            case 'clob':
                                $select = self::_findString(
                                    $select,
                                    'UserDefinedInfo',
                                    $property,
                                    $arg,
                                    $matchExact,
                                    $invertResult,
                                    $addSearchColumns
                                );
                                break;
                            case 'integer':
                                $select = self::_findInteger(
                                    $select,
                                    'UserDefinedInfo',
                                    $property,
                                    $arg,
                                    $operator,
                                    $invertResult,
                                    $addSearchColumns
                                );
                                break;
                            case 'float':
                                $select = self::_findFloat(
                                    $select,
                                    'UserDefinedInfo',
                                    $property,
                                    $arg,
                                    $operator,
                                    $invertResult,
                                    $addSearchColumns
                                );
                                break;
                            case 'date':
                                $select = self::_findDate(
                                    $select,
                                    'UserDefinedInfo',
                                    $property,
                                    $arg,
                                    $operator,
                                    $invertResult,
                                    $addSearchColumns
                                );
                                break;
                            default:
                                throw new UnexpectedValueException(
                                    'Unexpected datatype for user defined information'
                                );
                        }
                    } elseif (preg_match('#^Registry\\.(.*)#', $type, $matches)) {
                        $property = $matches[1];
                        $select = self::_findString(
                            $select,
                            'Registry',
                            $property,
                            $arg,
                            $matchExact,
                            $invertResult,
                            $addSearchColumns
                        );
                    } elseif (preg_match('/^[a-zA-Z]+\.[a-zA-Z]+$/', $type)) {
                        list($model, $property) = explode('.', $type);
                        // apply a generic string filter.
                        $select = self::_findString(
                            $select,
                            $model,
                            $property,
                            $arg,
                            $matchExact,
                            $invertResult,
                            $addSearchColumns
                        );
                    } else {
                        // Filter must be of the form 'Model.Property'.
                        throw new UnexpectedValueException('Invalid filter: ' . $type);
                    }
            }
        }

        /* Try to optimize the query by removing unnecessary JOINs. The query
           can be rewritten if all of the following conditions are met:
            - There is the 'hardware' table (it is always present) and exactly 1
              other table.
            - The tables are joined by an inner join.
            - The only column from the 'hardware' table is 'id'
            In that case, hardware.id can be replaced by the 'hardware_id'
            column from the other table.

            If there is no inner join, the result may contain rows from the
            'hardware' table that describe groups. These need to be removed.

            The $distinct parameter only affects queries where the 'hardware'
            table has been optimized away. Queries based on 'hardware' always
            produce distinct results.
        */
        $queryTables = $select->getPart(Zend_Db_Select::FROM);
        switch(count($queryTables)) {
            case 1:
                $filterGroups = true;
                break;
            case 2:
                // Get the table that is not 'hardware'.
                unset($queryTables['hardware']);
                $table = array_pop($queryTables);
                if ($table['joinType'] == Zend_Db_Select::INNER_JOIN) {
                    $filterGroups = false;
                    $rewriteQuery = true;
                    $queryColumns = array();
                    foreach ($select->getPart(Zend_Db_Select::COLUMNS) as $column) {
                        if ($column[0] == 'hardware') {
                            if ($column[1] == 'id') {
                                // Rewrite column as otherTable.hardware_id
                                $column[0] = $table['tableName'];
                                $column[1] = 'hardware_id';
                                $column[2] = 'id'; // Alias, required for code that expects hardware.id
                            } else {
                                // Query cannot be rewritten because there are
                                // other columns from 'hardware'.
                                $rewriteQuery = false;
                                break;
                            }
                        }
                        // Append to new column list.
                        if ($column[2] === null) { // Column alias?
                            $queryColumns[] = $column[1];
                        } else {
                            $queryColumns[$column[2]] = $column[1];
                        }
                    }

                    if ($rewriteQuery) {
                        $select->reset(Zend_Db_Select::FROM);
                        $select->reset(Zend_Db_Select::COLUMNS);
                        $select->from($table['tableName'], $queryColumns);
                        if ($distinct) {
                            $select->distinct();
                        }
                    }
                } else {
                    $filterGroups = true;
                }
                break;
            default:
                // JOINs cannot be optimized for more than 2 tables. Only the
                // group filter can be ommitted if there is an inner join,
                // except for the PackageNonnotified filter which may yield
                // groups because it does not operate on child objects.
                $filterGroups = true;
                if (!in_array('PackageNonnotified', $filter)) {
                    foreach ($queryTables as $table) {
                        if ($table['joinType'] == Zend_Db_Select::INNER_JOIN) {
                            $filterGroups = false;
                            break;
                        }
                    }
                }
        }

        if ($filterGroups) {
            $select->where("deviceid != '_SYSTEMGROUP_'")
                   ->where("deviceid != '_DOWNLOADGROUP_'");
        }

        if ($query) {
            return $select->query();
        } else {
            return $select;
        }
    }

    /**
     * Get a Model_Computer object for the given primary key.
     * @param int $id Primary key
     * @return mixed Fully populated Model_Computer object, FALSE if no computer was found
     */
    static function fetchById($id)
    {
        return Model_Computer::createStatementStatic(null, null, null, 'Id', $id)
            ->fetchObject('Model_Computer');
    }

    /**
     * Retrieve a property by its logical name
     *
     * Mangles certain OS names to a nicer and shorter value.
     * Replaces certain meaningless manufacturer and model names with NULL.
     * Provides access to child object properties.
     */
    function getProperty($property, $rawValue=false)
    {
        try {
            $value = parent::getProperty($property, $rawValue);
        } catch (Exception $e) {
            if (array_key_exists($property, $this->_childProperties)) {
                // Call setProperty()/getProperty() on child object to enable processing of the value
                list($model, $property) = explode('.', $property);
                $childClass = "Model_$model";
                $childObject = new $childClass;
                $childObject->setProperty($property, $this->_childProperties["$model.$property"]);
                return $childObject->getProperty($property, $rawValue);
            } elseif (preg_match('#^UserDefinedInfo\\.(.*)#', $property, $matches)) {
                return $this->getUserDefinedInfo($matches[1]);
            } elseif (preg_match('#^Registry\\.#', $property)) {
                return $this->_registryContent;
            } elseif (preg_match('#^Windows\\.(\w+)$#', $property, $matches)) {
                return $this->windows->getProperty($matches[1]);
            } else {
                throw $e;
            }
        }

        if ($rawValue) {
            return $value;
        }

        switch ($property) {
            case 'OsName':
                // Some Unicode characters to be stripped from OS name
                $r  = chr(0xc2) . chr(0xae); // the (R) symbol
                $tm = chr(0xc2) . chr(0x99); // the TM symbol

                // strip 'Microsoft' prefix to conserve space. We know who made it...
                $value = preg_replace("/Microsoft[$r]* /", '', $this->osname, 1);
                // The TM symbol is not available with certain fonts. Ugly...
                $value = str_replace($tm, '', $value);
                break;
            case 'Manufacturer':
                if ($value == 'To Be Filled By O.E.M.'
                    or $value == 'System manufacturer'
                    or $value == 'System Manufacturer'
                ) {
                    $value = null;
                }
                break;
            case 'Model':
                if ($value == 'To Be Filled By O.E.M.'
                    or $value == 'System Name'
                    or $value == 'System Product Name'
                ) {
                    $value = null;
                }
                break;
        }

        return $value;
    }

    /**
     * Magic method to set a property directly
     *
     * This implementation handles columns from joined tables if they are
     * properly named ('model_property').
     */
    public function __set($property, $value)
    {
        try {
            // Parent's implementation will handle properties from Model_Computer
            parent::__set($property, $value);
        } catch (Exception $exception) {
            if ($property == 'registry_content') {
                $this->_registryContent = $value;
                return;
            }
            if (preg_match('#^userdefinedinfo_(.*)#', $property, $matches)) {
                // If _userDefinedInfo is already an object, do nothing - the
                // information is already there. Otherwise, _userDefinedInfo
                // will be an array with the given key/value pair.
                if (!($this->_userDefinedInfo instanceof Model_UserDefinedInfo)) {
                    $class = new Model_UserDefinedInfo;
                    $property = $class->getPropertyName($matches[1]);
                    if (
                        !is_null($value) and
                        Model_UserDefinedInfo::getType($property) == 'date'
                    ) {
                        $value = new Zend_Date($value);
                    }
                    $this->_userDefinedInfo[$property] = $value;
                }
                return;
            }

            // Only handle properly formatted column identifiers
            if (!preg_match('/^[a-z]+_[a-z]+$/', $property)) {
                throw $exception;
            }

            list($model, $property) = explode('_', $property);

            if ($model == 'windows') {
                // When instantiated from fetchObject(), this gets called before
                // __construct(). Initialize property if necessary.
                if (!$this->windows) {
                    $this->windows = new Model_Windows;
                }
                // Perform case insensitive search for the property inside the
                // property map.
                $map = $this->windows->getPropertyMap();
                foreach (array_keys($map) as $windowsProperty) {
                    if (strcasecmp($property, $windowsProperty) == 0) {
                        // Property is valid. Store the raw value in windows object.
                        $this->windows->$map[$windowsProperty] = $value;
                        return; // No further iteration necessary.
                    }
                }
                throw $exception; // Property is invalid.
            }

            // Since the column identifier is all lowercase, a case insensitive
            // search for a valid child object is necessary. The real class name
            // is determined from $_childObjectTypes.
            foreach (self::$_childObjectTypes as $childModel) {
                if (strcasecmp($model, $childModel) == 0) {
                    // Found the model name. Perform case insensitive search
                    // for the property inside the property map.
                    $childClass = "Model_$childModel";
                    $childObject = new $childClass;
                    foreach (array_keys($childObject->getPropertyMap()) as $childProperty) {
                        if (strcasecmp($property, $childProperty) == 0) {
                            // Property is valid. Store the raw value in $_childProperties.
                            $this->_childProperties["$childModel.$childProperty"] = $value;
                            return; // No further iteration necessary.
                        }
                    }
                }
            }
            throw $exception; // Either model or property is invalid.
        }
    }

    /**
     * Return the datatype of a property
     *
     * This implementation passes unknown properties to their matching child
     * object class if possible.
     */
    public function getPropertyType($property)
    {
        try {
            $type = parent::getPropertyType($property);
        } catch (Exception $exception) {
            if (preg_match('#^Registry\\.#', $property)) {
                return 'text';
            }
            if (preg_match('#^UserDefinedInfo\\.(.*)#', $property, $matches)) {
                $model = 'UserDefinedInfo';
                $property = $matches[1];
            } elseif (preg_match('/^[a-zA-Z]+\.[a-zA-Z]+$/', $property)) {
                // Property is of the form 'Model.Property'
                list($model, $property) = explode('.', $property);
            } else {
                // Invalid property. Re-throw exception.
                throw $exception;
            }
            // Pass property to the model class
            $model = "Model_$model";
            if (!class_exists($model)) {
                throw $exception;
            }
            $model = new $model;
            $type = $model->getPropertyType($property);
        }
        return $type;
    }

    /**
     * Get the real column name for a property
     * @param string $property Logical property name
     * @return string Column name to be used in SQL queries
     */
    public function getColumnName($property)
    {
        try {
            return parent::getColumnName($property);
        } catch(Exception $e) {
            if (preg_match('#^UserDefinedInfo\\.(.*)#', $property, $matches)) {
                $class = new Model_UserDefinedInfo;
                return $class->getColumnName($matches[1]);
            } elseif (preg_match('#^Registry\\.#', $property)) {
                return 'registry_content';
            } else {
                throw $e;
            }
        }
    }

    /**
     * Compose ORDER BY clause from logical identifier
     *
     * This implementation handles properties from child objects if they are
     * properly qualified ('Model.Property').
     */
    static function getOrder($order, $direction, $propertyMap)
    {
        try {
            // Parent's implementation will handle properties from Model_Computer
            return parent::getOrder($order, $direction, $propertyMap);
        } catch (Exception $exception) {
            if (preg_match('#^UserDefinedInfo\\.(.*)#', $order, $matches)) {
                $class = new Model_UserDefinedInfo;
                $order = 'userdefinedinfo_' . $class->getColumnName($matches[1]);
            } elseif (preg_match('#^Registry\\.#', $order)) {
                $order = 'registry_content';
            } elseif (preg_match('/^[a-zA-Z]+\.[a-zA-Z]+$/', $order)) {
                // Assume proper column alias ('model_property')
                $order = strtolower(strtr($order, '.', '_'));
            } else {
                throw $exception;
            }
            if ($direction) {
                $order .= ' ' . $direction;
            }
            return $order;
        }
    }

    /**
     * Get a statement object for all child objects of a given type belonging to this computer.
     *
     * @param string $type Object type to retrieve (name of model class without 'Model_' prefix)
     * @param string $order Property to sort by. If ommitted, the model's builtin default is used.
     * @param string $direction Sorting direction (asc|desc)
     * @param array Extra filters to pass to the child model's createStatement() method
     * @return Zend_Db_Statement Statement object with results
     */
    public function getChildObjects($type, $order=null, $direction=null, $filters=array())
    {
        $filters['Computer'] = $this->getId();
        // Apply extra filters.
        if ($type == 'Software' and !Model_Config::get('DisplayBlacklistedSoftware')) {
            $filters['Status'] = 'notIgnored';
        }
        $className = "Model_$type";
        $class = new $className;
        return $class->createStatement(
            null,
            $order,
            $direction,
            $filters
        );
    }

    /**
     * Retrieve the user defined fields for this computer
     *
     * If the $name argument is given, the value for the specific field is
     * returned. If $name is null (the default), a fully populated
     * Model_UserDefinedInfo object is returned.
     * @param string $name Field to retrieve (default: all fields)
     * @return mixed
     */
    public function getUserDefinedInfo($name=null)
    {
        // If _userDefinedInfo is undefined yet, retrieve all fields.
        if (!$this->_userDefinedInfo) {
            $this->_userDefinedInfo = new Model_UserDefinedInfo($this);
        }
        // From this point on, _userDefinedInfo is either an array or an object.

        // Always have an object if all fields are requested.
        if (is_null($name)) {
            if (is_array($this->_userDefinedInfo)) {
                $this->_userDefinedInfo = new Model_UserDefinedInfo($this);
            }
            return $this->_userDefinedInfo;
        }

        // isset() would not work here!
        if (is_array($this->_userDefinedInfo) and array_key_exists($name, $this->_userDefinedInfo)) {
            // Requested field is available in the array.
            return $this->_userDefinedInfo[$name];
        } else {
            // Requested field is not available in the array. Create object
            // instead.
            $this->_userDefinedInfo = new Model_UserDefinedInfo($this);
        }

        // At this point _userDefinedInfo is always an object.
        return $this->_userDefinedInfo->getProperty($name);
    }

    /**
     * Set values for the user defined fields for this computer.
     * @param array $values Associative array with field names as keys.
     */
    public function setUserDefinedInfo($values)
    {
        $this->getUserDefinedInfo()->setValues($values);
    }

    /**
     * Check if this computer runs any version of Windows
     *
     * The OS type is not stored directly in the database. This method tries to
     * determine it from different criteria (user agent, OS name).
     * @return bool
     */
    public function isWindows()
    {
        $agent = $this->getOcsAgent();

        // Check for suitable user agent identifier.
        if (stripos($agent, 'local') === false) {
            // Inventory was submitted directly by agent.
            // The agent identifier gives a reliable hint about OS type.
            return (stripos($agent, 'windows') !== false);
        } else {
            // Inventory was created locally and then uploaded manually.
            // The agent identifier ('OCS_local_nnnn') gives no clue about OS type.
            // Guess the type from OS name and hope for the best.
            return (strpos($this->getOsName(), 'Windows') === 0);
        }
    }

    /**
     * Update windows property for computer
     *
     * It is valid to call this on non-Windows computer objects in which case
     * the content of the object is undefined.
     * @return Model_Windows Updated windows property
     **/
    public function getWindows()
    {
        $this->windows = Model_Windows::getWindows($this);
        return $this->windows;
    }

    /**
     * Return TRUE if the serial number or asset tag is blacklisted,
     * i.e. ignored for detection of duplicates.
     *
     * @param string $criteria One of 'Serial' or 'AssetTag'
     * @return bool
     */
    public function isBlacklisted($criteria)
    {
        switch ($criteria) {
            case 'Serial':
                $table = 'blacklist_serials';
                $column = 'serial';
                $value = $this->getSerial();
                break;
            case 'AssetTag':
                if (!Model_Database::supportsAssetTagBlacklist()) {
                    return false; // It's not blacklisted if the table does not exist.
                }
                $table = 'braintacle_blacklist_assettags';
                $column = 'assettag';
                $value = $this->getAssetTag();
                break;
            default:
                throw new UnexpectedValueException(
                    'Invalid criteria for isBlacklisted(): ' . $criteria
                );
        }

        $db = Model_Database::getAdapter();

        return (bool) $db->fetchOne(
            "SELECT COUNT($column) FROM $table WHERE $column = ?",
            $value
        );
    }

    /** Get long description for a filter
     *
     * @param string $filter Name of a pre-defined filter routine
     * @param string $search Search parameter passed to the filter
     * @param integer $count Number of results for this filter
     * @return string Description ready to be inserted into HTML
     */
    static function getFilterDescription($filter, $search, $count)
    {
        $translate = Zend_Registry::get('Zend_Translate');

        // Multiple filters?
        if (is_array($filter)) {
            if ($filter[0] == 'NetworkInterface.Subnet' and
                $filter[1] == 'NetworkInterface.Netmask'
            ) {
                $description = $translate->_(
                    '%1$d computers with an interface in network \'%2$s\''
                );
                $network = $search[0] . Model_Subnet::getCidrSuffix($search[1]);
                return htmlspecialchars(sprintf($description, $count, $network));
            }
            // No other multi-filters defined.
            throw new UnexpectedValueException(
                'No description available for this set of multiple filters'
            );
        }

        // Single filter
        switch ($filter) {
            case 'PackageNonnotified':
                $description = $translate->_(
                    '%1$d computers waiting for notification of package \'%2$s\''
                );
                break;
            case 'PackageSuccess':
                $description = $translate->_(
                    '%1$d computers with package \'%2$s\' successfully deployed'
                );
                break;
            case 'PackageNotified':
                $description = $translate->_(
                    '%1$d computers with deployment of package \'%2$s\' in progress'
                );
                break;
            case 'PackageError':
                $description = $translate->_(
                    '%1$d computers where deployment of package \'%2$s\' failed'
                );
                break;
            case 'Software':
                $description = $translate->_(
                    '%1$d computers where software \'%2$s\' is installed'
                );
                break;
            default:
                throw(new Zend_Exception('No description available for filter ' . $filter));
        }
        return htmlspecialchars(sprintf($description, $count, $search));
    }

    /** Apply a package filter.
     *
     * @param Zend_Db_Select Object to apply the filter to
     * @param string $filter Name of a pre-defined filter routine
     * @param string $search Search parameter passed to the filter
     * @param bool $addSearchColumns Add columns with search criteria (Package.Status)
     * @return Zend_Db_Select Object with filter applied
     */
    protected static function _filterByPackage(Zend_Db_Select $select, $filter, $search, $addSearchColumns)
    {
        switch ($filter) {
            case 'PackageNonnotified':
                $condition = 'IS NULL';
                break;
            case 'PackageSuccess':
                $condition = '= \'SUCCESS\'';
                break;
            case 'PackageNotified':
                $condition = '= \'NOTIFIED\'';
                break;
            case 'PackageError':
                $condition = 'LIKE \'ERR%\'';
                break;
        }
        return $select->join(
            'devices',
            'hardware.id = devices.hardware_id AND devices.name=\'DOWNLOAD\' AND devices.tvalue ' . $condition,
            $addSearchColumns ? array('package_status' => 'tvalue') : null
        )
        ->join(
            'download_enable',
            'devices.ivalue = download_enable.id',
            null
        )
        ->join(
            'download_available',
            $select->getAdapter()->quoteInto(
                'download_enable.fileid = download_available.fileid AND download_available.name = ?',
                $search
            ),
            null
        );
    }

    /**
     * Common operations for all search types (string, integer...)
     *
     * This method determines the table and column name and adds them to $select
     * if necessary. The only part left is the WHERE clause as this depends on
     * the column datatype.
     * @param Zend_Db_Select Object to apply the filter to
     * @param string $model Name of model class (without 'Model_' prefix) where property can be found.
     * This must be either 'Computer' or a valid child object class. Every
     * other value will trigger an exception.
     * @param string $property Property to search in. Properties unknown to the model will trigger an exception.
     * @param bool $addSearchColumns Add columns with search criteria.
     * @return array Table and column of search criteria
     */
    protected static function _findCommon($select, $model, $property, $addSearchColumns)
    {
        // Determine table name and column alias
        if ($model == 'Computer') {
            switch ($property) {
                case 'Manufacturer':
                case 'Model':
                case 'Serial':
                case 'AssetTag':
                case 'BiosVersion':
                case 'BiosDate':
                    $table = 'bios';
                    break;
                default:
                    $table = 'hardware';
            }
            $class = new Model_Computer;
            $column = $class->getColumnName($property);
            $columnAlias = $column; // Zend_Db_Select will ignore this alias because the strings are identical
        } elseif ($model == 'UserDefinedInfo') {
            $table = 'accountinfo';
            $class = new Model_UserDefinedInfo;
            $column = $class->getColumnName($property);
            $columnAlias = 'userdefinedinfo_' . $column;
        } elseif ($model == 'Registry') {
            $table = 'registry';
            $column = 'regvalue';
            $columnAlias = 'registry_content';
            $select->where('registry.name = ?', $property);
        } elseif ($model == 'Windows') {
            $table = Model_Windows::getTableName($property);
            $class = new Model_Windows;
            $column = $class->getColumnName($property);
            $columnAlias = 'windows_' . strtolower($property); // safe, malicious content would have raised an exception
        } elseif (in_array($model, self::$_childObjectTypes)) {
            $className = "Model_$model";
            $class = new $className;

            $table = $class->getTableName();
            $column = $class->getColumnName($property);
            // Compose a column alias to avoid ambiguous identifiers (like
            // 'name' which is present in more than 1 table). This allows
            // identification of the column in a query result.
            // Properties not handled by Model_Computer will be passed to the
            // model class determined from the alias.
            $columnAlias = strtolower($model) . '_' . strtolower($property);
        } else {
            throw new UnexpectedValueException('Invalid model: ' . $model);
        }

        // Join table if not already present
        if ($table != 'hardware' and !array_key_exists($table, $select->getPart('from'))) {
            $select->join($table, "$table.hardware_id=hardware.id", array());
        }

        if ($addSearchColumns) {
            // Add column if not already present
            $columnPresent = false;
            foreach ($select->getPart('columns') as $columnPart) {
                if ($columnPart[0] == $table and $columnPart[1] == $column) {
                    $columnPresent = true;
                }
            }
            if (!$columnPresent) {
                $select->columns(array($columnAlias => $column), $table);
            }
        }

        return array($table, $column);
    }

    /**
     * Apply a filter for a string value.
     *
     * @param Zend_Db_Select Object to apply the filter to
     * @param string $model Name of model class (without 'Model_' prefix) where property can be found.
     * This must be either 'Computer' or a valid child object class. Every
     * other value will trigger an exception.
     * @param string $property Property to search in. Properties unknown to the model will trigger an exception.
     * @param string $arg String to search for
     * @param bool $matchExact Disable wildcards ('*', '?', '%', '_') and substring search.
     * @param bool $invertResult Return computers NOT matching criteria
     * @param bool $addSearchColumns Add columns with search criteria.
     * @return Zend_Db_Select Object with filter applied
     */
    protected static function _findString(
        $select,
        $model,
        $property,
        $arg,
        $matchExact,
        $invertResult,
        $addSearchColumns
    )
    {
        $arg = (string) $arg; // Treat NULL as empty string
        list($table, $column) = self::_findCommon($select, $model, $property, $addSearchColumns);

        // Determine comparision operator and prepare search argument
        if ($matchExact) {
            $operator = '=';
        } else {
            // Replace wildcards '*' and '?' with their SQL counterparts '%' and '_'.
            // If $arg contains '%' and '_', they are currently NOT escaped, i.e. they operate as wildcards too.
            // The result is encapsulated within '%' to support searching for arbitrary substrings.
            $arg = '%' . strtr($arg, '*?', '%_') . '%';
            $operator = Model_Database::getNada()->iLike();
        }

        if ($table == 'hardware') {
            // Columns from the 'hardware' table can be queried directly
            if ($invertResult) {
                if ($matchExact) {
                    $operator = '!=';
                } else {
                    $operator = 'NOT ' . $operator;
                }
            }
            $select = $select->where("$table.$column $operator ?", $arg);
        } else {
            // Columns from joined tables can be queried directly except for inverted search.
            // In that case, a subquery has to be built:
            // SELECT ... FROM ... WHERE hardware.id NOT IN (SELECT hardware_id FROM $table WHERE ...);
            if ($invertResult) {
                $subquery = new Zend_Db_Select($select->getAdapter());
                $subquery->from($table, 'hardware_id')
                         ->where("$column $operator ?", $arg);
                $select->where("hardware.id NOT IN ($subquery)");
            } else {
                $select->where("$table.$column $operator ?", $arg);
            }
        }

        return $select;
    }

    /**
     * Apply a filter for a numeric (integer/float) value
     *
     * Input is not validated. It's better to call {@link _findInteger()} or
     * {@link _findFloat()} instead that perform proper input validation.
     *
     * @param Zend_Db_Select Object to apply the filter to
     * @param string $model Name of model class (without 'Model_' prefix) where property can be found.
     * This must be either 'Computer' or a valid child object class. Every
     * other value will trigger an exception.
     * @param string $property Property to search in. Properties unknown to the model will trigger an exception.
     * @param string $arg Numeric operand
     * @param string $operator Comparision operator (= == != <> < <= > >= eq ne lt le gt ge)
     * @param bool $invertResult Return computers NOT matching criteria
     * @param bool $addSearchColumns Add columns with search criteria.
     * @return Zend_Db_Select Object with filter applied
     */
    protected static function _findNumber($select, $model, $property, $arg, $operator, $invertResult, $addSearchColumns)
    {
        // Convert abstract operator into SQL operator
        switch ($operator) {
            case '=':
            case '==':
            case 'eq':
                $operator = '=';
                break;
            case '!=':
            case '<>':
            case 'ne':
                $operator = '!=';
                break;
            case '<':
            case 'lt':
                $operator = '<';
                break;
            case '<=':
            case 'le':
                $operator = '<=';
                break;
            case '>':
            case 'gt':
                $operator = '>';
                break;
            case '>=':
            case 'ge':
                $operator = '>=';
                break;
            default:
                throw new UnexpectedValueException('Invalid numeric comparision operator: ' . $operator);
        }

        list($table, $column) = self::_findCommon($select, $model, $property, $addSearchColumns);

        $where = "$table.$column $operator ?";
        if ($invertResult) {
            $where = "($where) IS NOT TRUE"; // include NULL values
        }
        $select->where($where, $arg);

        return $select;
    }

    /**
     * Apply a filter for an integer value.
     *
     * @param Zend_Db_Select Object to apply the filter to
     * @param string $model Name of model class (without 'Model_' prefix) where property can be found.
     * This must be either 'Computer' or a valid child object class. Every
     * other value will trigger an exception.
     * @param string $property Property to search in. Properties unknown to the model will trigger an exception.
     * @param string $arg Integer operand (will be validated)
     * @param string $operator Comparision operator (= == != <> < <= > >= eq ne lt le gt ge)
     * @param bool $invertResult Return computers NOT matching criteria
     * @param bool $addSearchColumns Add columns with search criteria.
     * @return Zend_Db_Select Object with filter applied
     */
    protected static function _findInteger(
        $select,
        $model,
        $property,
        $arg,
        $operator,
        $invertResult,
        $addSearchColumns
    )
    {
        // Sanitize input
        if (!ctype_digit((string) $arg)) {
            throw new UnexpectedValueException('Non-integer value given: ' . $arg);
        }
        $arg = (integer) $arg;

        return self::_findNumber($select, $model, $property, $arg, $operator, $invertResult, $addSearchColumns);
    }

    /**
     * Apply a filter for a float value.
     *
     * @param Zend_Db_Select Object to apply the filter to
     * @param string $model Name of model class (without 'Model_' prefix) where property can be found.
     * This must be either 'Computer' or a valid child object class. Every
     * other value will trigger an exception.
     * @param string $property Property to search in. Properties unknown to the model will trigger an exception.
     * @param string $arg Float operand (will be validated)
     * @param string $operator Comparision operator (= == != <> < <= > >= eq ne lt le gt ge)
     * @param bool $invertResult Return computers NOT matching criteria
     * @param bool $addSearchColumns Add columns with search criteria.
     * @return Zend_Db_Select Object with filter applied
     */
    protected static function _findFloat($select, $model, $property, $arg, $operator, $invertResult, $addSearchColumns)
    {
        // Sanitize input
        if (!is_numeric($arg)) {
            throw new UnexpectedValueException('Non-numeric value given: ' . $arg);
        }
        $arg = (float) $arg;

        return self::_findNumber($select, $model, $property, $arg, $operator, $invertResult, $addSearchColumns);
    }

    /**
     * Apply a filter for a date value.
     *
     * @param Zend_Db_Select Object to apply the filter to
     * @param string $model Name of model class (without 'Model_' prefix) where property can be found.
     * This must be either 'Computer' or a valid child object class. Every
     * other value will trigger an exception.
     * @param string $property Timestamp property to search in. Unknown properties will trigger an exception.
     * @param mixed $arg date operand (Zend_Date object or 'yyyy-MM-dd' string). Time of day is ignored.
     * @param string $operator Comparision operator (= == != <> < <= > >= eq ne lt le gt ge)
     * @param bool $invertResult Return computers NOT matching criteria
     * @param bool $addSearchColumns Add columns with search criteria.
     * @return Zend_Db_Select Object with filter applied
     */
    protected static function _findDate($select, $model, $property, $arg, $operator, $invertResult, $addSearchColumns)
    {
        // This method compares date values (ignoring time part) to timestamp
        // columns. The comparision can not be made directly because the date
        // operand would be cast to a timestamp with time part set to midnight.
        // Timestamps with the same day but different time of day would be
        // considered not equal, giving surprising results.
        // Instead, values are expected equal if they have the same date,
        // regardless of time. Specifically, the column's value is considered
        // equal to the argument if it is >= 00:00:00 of the argument's day and
        // < 00:00:00 of the next day.
        // Other operations (<, >) are defined accordingly.

        $db = Model_Database::getAdapter();
        $nada = Model_Database::getNada();

        // Get beginning of day.
        if ($arg instanceof Zend_Date) {
            $dayStart = new Zend_Date($arg, Zend_Date::DATE_SHORT);
        } else {
            $dayStart = new Zend_Date($arg, 'yyyy-MM-dd');
        }
        $dayStart->setTime('00:00:00', 'HH:mm:ss');

        // Get beginning of next day
        $dayNext = clone $dayStart;
        $dayNext->addDay(1);

        $dayStart = $dayStart->get($nada->timestampFormatIso());
        $dayNext = $dayNext->get($nada->timestampFormatIso());

        list($table, $column) = self::_findCommon($select, $model, $property, $addSearchColumns);
        $operand = "$table.$column";

        switch ($operator) {
            case '=':
            case '==':
            case 'eq':
                $where[] = $db->quoteInto("$operand >= ?", $dayStart);
                $where[] = $db->quoteInto("$operand < ?", $dayNext);
                break;
            case '!=':
            case '<>':
            case 'ne':
                $expression1 = $db->quoteInto("$operand < ?", $dayStart);
                $expression2 = $db->quoteInto("$operand >= ?", $dayNext);
                $where[] = "$expression1 OR $expression2";
                break;
            case '<':
            case 'lt':
                $where[] = $db->quoteInto("$operand < ?", $dayStart);
                break;
            case '<=':
            case 'le':
                $where[] = $db->quoteInto("$operand < ?", $dayNext);
                break;
            case '>':
            case 'gt':
                $where[] = $db->quoteInto("$operand >= ?", $dayNext);
                break;
            case '>=':
            case 'ge':
                $where[] = $db->quoteInto("$operand >= ?", $dayStart);
                break;
            default:
                throw new UnexpectedValueException('Invalid date comparision operator: ' . $operator);
        }
        $where = implode(' AND ', $where);
        if ($invertResult) {
            $where = "($where) IS NOT TRUE"; // include NULL values
        }
        $select->where($where);

        return $select;
    }

    /**
     * Delete this computer and all associated child objects from the database
     * @param bool $reuseLock If this instance already has a lock, reuse it.
     * @param string $equivalent Inserted into deleted_equiv table if TraceDeleted is set.
     * @param bool $deleteInterfaces Delete interfaces from network listing
     * @return bool Success
     */
    public function delete($reuseLock=false, $equivalent=null, $deleteInterfaces=false)
    {
        // A lock is required
        if ((!$reuseLock or !$this->isLocked()) and !$this->lock()) {
            return false;
        }

        $db = Model_Database::getAdapter();
        $id = $this->getId();

        // Get list of tables for child objects
        foreach (self::$_childObjectTypes as $type) {
            if ($type == 'MsOfficeProduct' and !Model_Database::supportsMsOfficeKeyPlugin()) {
                // Skip table if not present
                continue;
            }
            $model = 'Model_' . $type;
            $model = new $model;
            $tables[] = $model->getTableName();
        }
        // Additional tables without associated Model_ChildObject class
        $tables[] = 'accountinfo';
        $tables[] = 'bios';
        $tables[] = 'devices';
        $tables[] = 'download_history';
        $tables[] = 'download_servers';
        $tables[] = 'groups_cache';
        $tables[] = 'itmgmt_comments';
        $tables[] = 'javainfo';
        $tables[] = 'journallog';

        // Start transaction to keep database consistent in case of errors
        // If a transaction is already in progress, an exception will be thrown
        // by PDO which has to be caught. The commit() and rollBack() methods
        // can only be called if the transaction has been started here.
        try{
            $db->beginTransaction();
            $transaction = true;
        } catch (Exception $exception) {
            $transaction = false;
        }

        try {
            // If requested, delete this computer's network interfaces from the
            // list of discovered interfaces. Also delete any manually entered
            // description for these interfaces, if present.
            if ($deleteInterfaces) {
                $db->delete(
                    'netmap',
                    array(
                        'mac IN (SELECT macaddr FROM networks WHERE hardware_id = ?)' => $id
                    )
                );
                $db->delete(
                    'network_devices',
                    array(
                        'macaddr IN (SELECT macaddr FROM networks WHERE hardware_id = ?)' => $id
                    )
                );
            }

            // Delete rows from child tables
            foreach ($tables as $table) {
                $db->delete($table, array('hardware_id=?' => $id));
            }

            // Delete attachments
            $db->delete(
                'temp_files',
                array(
                    'id_dde=?' => $id,
                    'table_name=?' => 'accountinfo'
                )
            );

            // Delete row in hardware table itself
            $db->delete('hardware', array('id=?' => $id));
            // Insert row into deleted_equiv if configured
            if (Model_Config::get('TraceDeleted')) {
                $db->insert(
                    'deleted_equiv',
                    array(
                        'date' => new Zend_Db_Expr('CURRENT_TIMESTAMP'),
                        'deleted' => $this->getClientId(),
                        'equivalent' => $equivalent
                    )
                );
            }
        } catch (Exception $exception) {
            if ($transaction) {
                $db->rollBack();
            }
            throw $exception;
        }

        if ($transaction) {
            $db->commit();
        }

        $this->unlock();
        return true;
    }

    /**
     * Find duplicate computers based on different criteria
     *
     * The criteria are: hostname, MAC address, serial number and asset tag.
     * The return value is either the number of duplicates ($count=TRUE) or a
     * Zend_Db_Statement object providing access to Id, Name, MacAddress, Serial
     * AssetTag and LastContactDate ($count=FALSE).
     *
     * @param string $criteria One of Name|MacAddress|Serial|AssetTag
     * @param bool $count Return only number of duplicates instead of full list
     * @param string $order Sorting order (ignored for $count=false)
     * @param string $direction One of asc|desc (ignored for $count=false)
     * @return mixed Number of duplicates or Zend_Db_Statement object, depending on $count
     */
    static function findDuplicates($criteria, $count, $order='Id', $direction='asc')
    {
        $db = Model_Database::getAdapter();
        $select = $db->select();

        // All duplicates are determined by a common method with just some
        // parameters depending on search criteria.
        switch ($criteria) {
            case 'Name':
                $table = 'hardware';
                $column = 'name';
                $select->where('deviceid NOT IN(\'_SYSTEMGROUP_\', \'_DOWNLOADGROUP_\')');
                break;
            case 'MacAddress':
                $table = 'networks';
                $column = 'macaddr';
                $select->where(
                    'macaddr NOT IN(SELECT macaddress FROM blacklist_macaddresses)'
                );
                break;
            case 'Serial':
                $table = 'bios';
                $column = 'ssn';
                $select->where(
                    'ssn NOT IN(SELECT serial FROM blacklist_serials)'
                );
                break;
            case 'AssetTag':
                $table = 'bios';
                $column = 'assettag';
                if (Model_Database::supportsAssetTagBlacklist()) {
                    $select->where(
                        'assettag NOT IN(SELECT assettag FROM braintacle_blacklist_assettags)'
                    );
                }
                break;
            default:
                throw new UnexpectedValueException('Invalid criteria: ' . $criteria);
        }

        $select->from($table, $column)
               ->group($column)
               ->having("COUNT($column) > 1");

        if ($count) {
            $outer = $db->select()
                ->from($table, new Zend_Db_Expr("COUNT($column)"))
                ->where("$column IN($select)");

            if ($table == 'hardware') {
                $outer->where(
                    'deviceid NOT IN(\'_SYSTEMGROUP_\', \'_DOWNLOADGROUP_\')'
                );
            }

            return $outer->query()->fetchColumn();
        } else {
            $dummy = new Model_Computer;
            $map = $dummy->getPropertyMap();

            $outer = $db->select()
                ->from(
                    'hardware',
                    array('id', 'name', 'lastcome')
                )
                ->joinLeft(
                    'networks',
                    'networks.hardware_id=hardware.id',
                    array('NetworkInterface_MacAddress' => 'macaddr')
                )
                ->joinLeft(
                    'bios',
                    'bios.hardware_id=hardware.id',
                    array('ssn', 'assettag')
                );
                if ($table == 'hardware') {
                    $outer->where(
                        'deviceid NOT IN(\'_SYSTEMGROUP_\', \'_DOWNLOADGROUP_\')'
                    );
                }
                return $outer->where("$column IN($select)")
                             ->order(self::getOrder($order, $direction, $map))
                             ->query();
        }
    }

    /**
     * Merge 2 or more computers
     *
     * This method is used to eliminate duplicates in the database. Based on the
     * last contact, the newest entry is preserved. All older entries are
     * deleted. Some information from the older entries can be preserved on the
     * remaining computer.
     *
     * @param array $computers IDs of computers to merge
     * @param bool $mergeUserdefined Preserve user supplied information from old computer
     * @param bool $mergeGroups Preserve manual group assignments from old computers
     * @param bool $mergePackages Preserve package assignments from old computers missing on new computer
     */
    static function mergeComputers($computers, $mergeUserdefined, $mergeGroups, $mergePackages)
    {
        if (is_null($computers)) { // Can happen if no items have been checked
            return;
        }

        if (!is_array($computers)) {
            throw new UnexpectedValueException('mergeComputers() expects array.');
        }

        // $computers may contain duplicate values if a computer has been marked more than once.
        $computers = array_unique($computers);
        if (count($computers) < 2) {
            return;
        }

        $db = Model_Database::getAdapter();
        $db->beginTransaction();
        try {
            // Lock all given computers and create a list sorted by LastContactDate.
            foreach ($computers as $id) {
                $computer = self::fetchById($id);
                if (!$computer or !$computer->lock()) {
                    return;
                }
                $timestamp = $computer->getLastContactDate()->get(Zend_Date::TIMESTAMP);
                $list[$timestamp] = $computer;
            }
            ksort($list);
            // Now that the list is sorted, renumber the indices
            $computers = array_values($list);

            // Newest computer will be the only one not to be deleted, remove it from the list
            $newest = array_pop($computers);

            // Copy the desired data
            if ($mergeUserdefined) {
                // Oldest computer will be the source for merged information
                $oldest = $computers[0];
                $newest->setUserDefinedInfo($oldest->getUserDefinedInfo()->getProperties());
            }

            if ($mergeGroups) {
                // Build list with all manual group assignments from old computers.
                // If more than 1 old computer is to be merged and the computers
                // have different assignments for the same group, the result may
                // me somewhat unpredictable.
                $groupList = array();
                foreach ($computers as $computer) {
                    $groups = $computer->getGroupMemberships(Model_GroupMembership::TYPE_MANUAL, null);
                    while ($group = $groups->fetchObject('Model_GroupMembership')) {
                        $groupList[$group->getGroupId()] = $group->getMembership();
                    }
                }
                $newest->setGroups($groupList);
            }

            if ($mergePackages) {
                // The simplest way to merge package assignments is to update
                // the hardware ID directly. If more than 2 computers are to be
                // merged, assignments from all computers are merged, not only
                // from the oldest one.

                // To prevent multiple assignment of the same package to the
                // remaining computer, do not merge packages that are already
                // assigned to the newest computer.
                $subquery = $db->quoteInto(
                    '(SELECT ivalue FROM devices WHERE hardware_id=? AND name=\'DOWNLOAD\')',
                    (int) $newest->getId()
                );
                foreach ($computers as $computer) {
                    $db->update(
                        'devices',
                        array('hardware_id' => $newest->getId()),
                        array(
                            'hardware_id=?' => $computer->getId(),
                            "name != 'DOWNLOAD_SWITCH'",
                            "name LIKE 'DOWNLOAD%'",
                            'ivalue NOT IN ' . $subquery
                        )
                    );
                }
            }

            // Delete all older computers
            foreach ($computers as $computer) {
                $computer->delete(true, $newest->getClientId());
            }
            // Unlock remaining computer
            $newest->unlock();
        } catch (Exception $exception) {
            $db->rollBack();
            throw ($exception);
        }
        $db->commit();
    }

    /**
     * Exclude a MAC address, serial or asset tag from being used as criteria
     * for duplicates search.
     * @param string $criteria One of 'MacAddress', 'Serial' or 'AssetTag'
     * @param string $value Value to be excluded
     */
    static function allowDuplicates($criteria, $value)
    {
        switch ($criteria) {
            case 'MacAddress':
                $table = 'blacklist_macaddresses';
                $column = 'macaddress';
                break;
            case 'Serial':
                $table = 'blacklist_serials';
                $column = 'serial';
                break;
            case 'AssetTag':
                $table = 'braintacle_blacklist_assettags';
                $column = 'assettag';
                break;
            default:
                throw new UnexpectedValueException(
                    'Invalid criteria for allowDuplicates(): ' . $criteria
                );
        }
        $db = Model_Database::getAdapter();
        // Check for existing record to avoid constraint violation
        if (!$db->fetchRow("SELECT $column FROM $table WHERE $column=?", $value)) {
            $db->insert($table, array($column => $value));
        }
    }

    /**
     * Retrieve group membership information for this computer
     * @param integer $membership Membership type to retrieve
     * @param string $order Property to sort by
     * @param string $direction Direction to sort by
     * @return Zend_Db_Statement
     */
    public function getGroupMemberships(
        $membership=Model_GroupMembership::TYPE_INCLUDED,
        $order='GroupName',
        $direction='asc'
    )
    {
        return Model_GroupMembership::createStatementStatic(
            $this->getId(),
            $membership,
            $order,
            $direction
        );
    }

    /**
     * Set group membership information for this computer
     *
     * The $newgroups argument is an array with group ID as key and the new
     * membership type as value. Groups which are not present in this array will
     * remain unchanged.
     * @param array $newGroups New group memberships
     */
    public function setGroups($newGroups)
    {
        $id = $this->getId();
        $db = Model_Database::getAdapter();

        // Create array with group ID as key and existing membership type as
        // value.
        $oldGroups = $db->fetchPairs(
            'SELECT group_id, static FROM groups_cache WHERE hardware_id = ?',
            $id
        );

        foreach ($newGroups as $group => $newMembership) {
            // If this computer does not match the group's query and has no
            // manual group assignment, the group will not be listed in
            // $oldGroups. In this case, $oldMembership is set to NULL.
            if (isset($oldGroups[$group])) {
                $oldMembership = (int) $oldGroups[$group];
            } else {
                $oldMembership = null;
            }

            // Determine action to be taken depending on old and new membership.
            $action = ''; // default: no action
            switch ($newMembership) {
                case Model_GroupMembership::TYPE_DYNAMIC:
                    if ($oldMembership === Model_GroupMembership::TYPE_STATIC or
                        $oldMembership === Model_GroupMembership::TYPE_EXCLUDED
                    ) {
                        $action = 'delete';
                    }
                    break;
                case Model_GroupMembership::TYPE_STATIC:
                    if ($oldMembership === Model_GroupMembership::TYPE_DYNAMIC or
                        $oldMembership === Model_GroupMembership::TYPE_EXCLUDED
                    ) {
                        $action = 'update';
                    } elseif ($oldMembership === null) {
                        $action = 'insert';
                    }
                    break;
                case Model_GroupMembership::TYPE_EXCLUDED:
                    if ($oldMembership === Model_GroupMembership::TYPE_DYNAMIC or
                        $oldMembership === Model_GroupMembership::TYPE_STATIC
                    ) {
                        $action = 'update';
                    } elseif ($oldMembership === null) {
                        $action = 'insert';
                    }
                    break;
            }

            switch ($action) {
                case 'insert':
                    $db->insert(
                        'groups_cache',
                        array(
                            'hardware_id' => $id,
                            'group_id' => $group,
                            'static' => $newMembership
                        )
                    );
                    break;
                case 'update':
                    $db->update(
                        'groups_cache',
                        array(
                            'static' => $newMembership
                        ),
                        array(
                            'hardware_id = ?' => $id,
                            'group_id = ?' => $group
                        )
                    );
                    break;
                case 'delete':
                    // Delete manual assignment. The group cache will be updated
                    // because this computer may be a candidate for automatic
                    // assignment.
                    $db->delete(
                        'groups_cache',
                        array(
                            'hardware_id = ?' => $id,
                            'group_id = ?' => $group
                        )
                    );
                    Model_Group::fetchById($group)->update(true);
                    break;
            }
        }
    }

    /**
     * Export computer as DOMDocument
     * @return Model_DomDocument_InventoryRequest
     */
    public function toDomDocument()
    {
        $document = new Model_DomDocument_InventoryRequest;
        $document->loadComputer($this);
        return $document;
    }

    /**
     * Get all packages from download history
     * @return array Package IDs (creation timestamps)
     */
    public function getDownloadedPackages()
    {
        $db = Model_Database::getAdapter();
        return $db->fetchCol(
            'SELECT pkg_id FROM download_history WHERE hardware_id=? ORDER BY pkg_id',
            $this->getId()
        );
    }

    /** {@inheritdoc} */
    public function getDefaultConfig($option)
    {
        $id = $this->getId();
        if (isset(self::$_configDefault[$id]) and array_key_exists($option, self::$_configDefault[$id])) {
            return self::$_configDefault[$id][$option];
        }

        if ($option == 'AllowScan') {
            if (Model_Config::get('ScannersPerSubnet') == 0) {
                $value = 0;
            } else {
                $value = 1;
            }
        } else {
            $value = null;
        }
        // Get default from groups if enabled. For AllowScan,
        // ScanningConfigurationInGroups is checked in addition to UseGroups.
        if (Model_Config::get('UseGroups') and
            !($option == 'AllowScan' and !Model_Config::get('ScanningConfigurationInGroups'))
        ) {
            $groupValues = array();
            foreach ($this->_getConfigGroups() as $group) {
                $groupValues[] = $group->getConfig($option);
            }
            switch ($option) {
                case 'InventoryInterval':
                    $value = Model_Config::get('InventoryInterval');
                    // Special values 0 and -1 always take precedence if
                    // configured globally.
                    if ($value >= 1) {
                        // Get smallest value of group and global settings
                        foreach ($groupValues as $groupValue) {
                            if ($groupValue !== null and $groupValue < $value) {
                                $value = $groupValue;
                            }
                        }
                    }
                    break;
                case 'ContactInterval':
                case 'DownloadMaxPriority':
                case 'DownloadTimeout':
                    // Get smallest value from groups
                    foreach ($groupValues as $groupValue) {
                        if ($value === null or ($groupValue !== null and $groupValue < $value)) {
                            $value = $groupValue;
                        }
                    }
                    break;
                case 'DownloadPeriodDelay':
                case 'DownloadCycleDelay':
                case 'DownloadFragmentDelay':
                    // Get largest value from groups
                    foreach ($groupValues as $groupValue) {
                        if ($groupValue > $value) {
                            $value = $groupValue;
                        }
                    }
                    break;
                case 'PackageDeployment':
                case 'ScanSnmp':
                case 'AllowScan':
                    // 0 if global setting or any group setting is 0, otherwise 1.
                    if ($option != 'AllowScan') { // already initialized for AllowScan
                        $value = Model_Config::get($option);
                    }
                    if ($value) {
                        foreach ($groupValues as $groupValue) {
                            if ($groupValue === 0) {
                                $value = 0;
                                break;
                            }
                        }
                    }
                    break;
            }
        }
        if ($value === null) {
            $value = Model_Config::get($option);
        }

        self::$_configDefault[$id][$option] = $value;
        return $value;
    }

    /**
     * Get effective configuration value
     *
     * This method returns the effective setting for an option. It is determined
     * from this computer's individual setting, the global setting and/or all
     * groups of which the computer is a member. The exact rules are:
     *
     * - PackageDeployment, AllowScan and ScanSnmp return 0 if the setting is
     *   disabled either globally, for any group or for the computer,
     *   otherwise 1.
     * - For InventoryInterval, if the global setting is one of the special
     *   values 0 or -1, this setting is returned. Otherwise, return the
     *   smallest value of the group and computer setting. If this is undefined,
     *   use global setting.
     * - ContactInterval, DownloadMaxPriority and DownloadTimeout evaluate (in
     *   that order): the computer setting, the smallest value of all group
     *   settings and the global setting. The first non-null result is returned.
     * - DownloadPeriodDelay, DownloadCycleDelay, DownloadFragmentDelay evaluate
     *   (in that order): the computer setting, the largest value of all group
     *   settings and the global setting. The first non-null result is returned.
     * - For any other setting, the computer's configured value is evaluated via
     *   getConfig().
     *
     * Group settings are only evaluated if the global UseGroups option is
     * enabled. Additionally, AllowScan evaluates the global
     * ScanningConfigurationInGroups option.
     *
     * @param string $option Option name
     * @return mixed Effective value or NULL
     */
    public function getEffectiveConfig($option)
    {
        $id = $this->getId();
        if (isset(self::$_configEffective[$id]) and array_key_exists($option, self::$_configEffective[$id])) {
            return self::$_configEffective[$id][$option];
        }

        switch ($option) {
            case 'InventoryInterval':
                $value = Model_Config::get('InventoryInterval');
                // Special values 0 and -1 always take precedence if configured
                // globally.
                if ($value >= 1) {
                    // Get smallest value of computer and group settings
                    $value = $this->getConfig('InventoryInterval');
                    if (Model_Config::get('UseGroups')) {
                        foreach ($this->_getConfigGroups() as $group) {
                            $groupValue = $group->getConfig('InventoryInterval');
                            if ($value === null or ($groupValue !== null and $groupValue < $value)) {
                                $value = $groupValue;
                            }
                        }
                    }
                    // Fall back to global default if not set anywhere else
                    if ($value === null) {
                        $value = Model_Config::get('InventoryInterval');
                    }
                }
                break;
            case 'ContactInterval':
            case 'DownloadPeriodDelay':
            case 'DownloadCycleDelay':
            case 'DownloadFragmentDelay':
            case 'DownloadMaxPriority':
            case 'DownloadTimeout':
                // Computer value takes precedence.
                $value = $this->getConfig($option);
                if ($value === null) {
                    $value = $this->getDefaultConfig($option);
                }
                break;
            case 'PackageDeployment':
            case 'AllowScan':
            case 'ScanSnmp':
                // If default is 0, return 0.
                // Otherwise override default if explicitly disabled.
                $default = $this->getDefaultConfig($option);
                if ($default and $this->getConfig($option) === 0) {
                    $value = 0;
                } else {
                    $value = $default;
                }
                break;
            default:
                $value = $this->getConfig($option);
        }

        self::$_configEffective[$id][$option] = $value;
        return $value;
    }

    /**
     * Get a list of all groups of which this is computer is a member, suitable for evaluating group config
     *
     * The returned group objects are not fully functional. They contain just
     * enough information to retrieve their configuration. They should not be
     * used for anything else.
     *
     * @return Model_Group[]
     */
    protected function _getConfigGroups()
    {
        $id = $this->getId();
        if (!isset(self::$_configGroups[$id])) {
            self::$_configGroups[$id] = array();
            $memberships = $this->getGroupMemberships();
            while ($membership = $memberships->fetchObject('Model_GroupMembership')) {
                $group = new Model_Group;
                $group->setId($membership->getGroupId());
                self::$_configGroups[$id][] = $group;
            }
        }
        return self::$_configGroups[$id];
    }
}
