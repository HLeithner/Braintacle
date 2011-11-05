<?php
/**
 * Class representing a logical printer object
 *
 * $Id$
 *
 * Copyright (C) 2011 Holger Schletz <holger.schletz@web.de>
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
 * @filesource
 */
/**
 * A single printer object.
 *
 * Properties:
 * - <b>Name</b>
 * - <b>Driver</b>
 * - <b>Port</b>
 * - <b>Description</b>
 * @package Models
 */
class Model_Printer extends Model_ChildObject
{
    protected $_propertyMap = array(
        // Values from 'printers' table
        'Name' => 'name',
        'Driver' => 'driver',
        'Port' => 'port',
        'Description' => 'description',
    );
    protected $_xmlElementName = 'PRINTERS';
    protected $_xmlElementMap = array(
        'DESCRIPTION' => 'Description',
        'DRIVER' => 'Driver',
        'NAME' => 'Name',
        'PORT' => 'Port',
    );
    protected $_tableName = 'printers';
    protected $_preferredOrder = 'Name';

}
