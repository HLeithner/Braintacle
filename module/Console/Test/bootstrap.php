<?php
/**
 * Bootstrap for unit tests
 *
 * Copyright (C) 2011-2019 Holger Schletz <holger.schletz@web.de>
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
ini_set('memory_limit', '400M');
date_default_timezone_set('Europe/Berlin');
\Locale::setDefault('de');

// Pretend to be not on a console to force choice of HTTP route over console route.
\Zend\Console\Console::overrideIsConsole(false);

$serviceManager = \Library\Application::init('Console')->getServiceManager();
$serviceManager->setService(
    'Library\UserConfig',
    array(
        'debug' => array(
            'display backtrace' => true,
            'report missing translations' => true,
        ),
    )
);
\Library\Test\View\Helper\AbstractTest::$serviceManager = $serviceManager;
unset($serviceManager);
