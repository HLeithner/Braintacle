<?php
/**
 * Bootstrap for unit tests
 *
 * Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
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

error_reporting(-1);

/**
 * A minimal stream wrapper to simulate I/O errors
 *
 * Only url_stat() is (partially) implemented to simulate file properties.
 * Files cannot be actually opened.
 */
class StreamWrapperStatOnly
{
    public function url_stat($path, $flags)
    {
        return array('size' => 42);
    }
}
stream_wrapper_register('statonly', 'StreamWrapperStatOnly');

require_once(__DIR__ . '/../../Library/Application.php');
\Library\Application::init('Model', false);

// Get absolute path to vfsStream library
\Zend\Loader\AutoloaderFactory::factory(
    array(
        '\Zend\Loader\StandardAutoloader' => array(
            'namespaces' => array(
                'org\bovigo\vfs' => stream_resolve_include_path('org/bovigo/vfs'),
            ),
        )
    )
);

// Replace global adapter with SQLite :memory: database.
$adapter = new \Zend\Db\Adapter\Adapter(
    array(
        'driver' => 'Pdo_Sqlite',
    )
);
$serviceManager = \Library\Application::getService('ServiceManager');
$serviceManager->setAllowOverride(true);
$serviceManager->setService('Db', $adapter);

// Unset temporary variables to prevent PHPUnit from backing them up which may
// cause errors.
unset($adapter);
unset($serviceManager);