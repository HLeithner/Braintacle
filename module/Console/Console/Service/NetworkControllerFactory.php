<?php
/**
 * Factory for NetworkController
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

namespace Console\Service;

/**
 * Factory for NetworkController
 */
class NetworkControllerFactory implements \Zend\ServiceManager\FactoryInterface
{
    /**
     * @internal
     */
    public function createService(\Zend\ServiceManager\ServiceLocatorInterface $serviceLocator)
    {
        $serviceManager = $serviceLocator->getServiceLocator();
        $formManager = $serviceManager->get('FormElementManager');
        return new \Console\Controller\NetworkController(
            $serviceManager->get('Model\Network\Device'),
            $serviceManager->get('Model\Network\DeviceManager'),
            $serviceManager->get('Model\Network\Subnet'),
            $formManager->get('Console\Form\Subnet'),
            $formManager->get('Console\Form\NetworkDevice')
        );
    }
}
