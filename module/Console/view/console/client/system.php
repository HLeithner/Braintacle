<?php
/**
 * Show RAM, controllers and slots
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

require 'header.php';

$client = $this->client;


// Show memory slots

$headers = array(
    'SlotNumber' => $this->translate('Slot number'),
    'Size' => $this->translate('Size'),
    'Type' => $this->translate('Type'),
    'Clock' => $this->translate('Clock'),
    'Serial' => $this->translate('Serial number'),
    'Caption' => $this->translate('Caption'),
    'Description' => $this->translate('Description'),
    'Purpose' => $this->translate('Purpose'),
);

$renderCallbacks = array (
    'Size' => function($view, $memorySlot) {
        $size = $memorySlot['Size'];
        if ($size) {
            $size .= '&nbsp;MB';
        } else {
            $size = ''; // Suppress literal '0'
        }
        return $size;
    },
    'Clock' => function($view, $memorySlot) {
        $clock = $view->escapeHtml($memorySlot['Clock']);
        if (is_numeric($clock)) {
            $clock .= '&nbsp;MHz';
        }
        return $clock;
    },
);

$memSlots = $client['MemorySlot'];
if (count($memSlots)) {
    print $this->htmlTag(
        'h2',
        $this->translate('Memory slots')
    );
    print $this->table(
        $memSlots,
        $headers,
        null,
        $renderCallbacks
    );
}


// Show controllers

$headers = array(
    'Type' => $this->translate('Type'),
    'Manufacturer' => $this->translate('Manufacturer'),
    'Name' => $this->translate('Name'),
);
if ($this->client['Windows'] instanceof \Model\Client\WindowsInstallation) { // Not available for other OS
    $headers['DriverVersion'] = $this->translate('Driver version');
}

$renderCallbacks = array (
    'Name' => function($view, $controller) {
        $name = $controller['Name'];
        $comment = $controller['Comment'];
        if ($name == $comment) {
            return $view->escapeHtml($name);
        } else {
            return $view->htmlTag(
                'span',
                $view->escapeHtml($name),
                array('title' => $comment)
            );
        }
    },
);
print $this->htmlTag(
    'h2',
    $this->translate('Controllers')
);
print $this->table(
    $client['Controller'],
    $headers,
    null,
    $renderCallbacks
);


// Show extension slots

$headers = array(
    'Name' => $this->translate('Name'),
    'Type' => $this->translate('Type'),
    'Description' => $this->translate('Description'),
    'Status' => $this->translate('Status'),
);

$extSlots = $client['ExtensionSlot'];
if (count($extSlots)) {
    print $this->htmlTag(
        'h2',
        $this->translate('Extension slots')
    );
    print $this->table(
        $extSlots,
        $headers
    );
}
