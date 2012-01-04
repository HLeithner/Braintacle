<?php
/**
 * Form for display/setting of 'display' preferences
 *
 * $Id$
 *
 * Copyright (C) 2011,2012 Holger Schletz <holger.schletz@web.de>
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
 * @package Forms
 * @filesource
 */
/**
 * Form for display/setting of 'display' preferences
 * @package Forms
 */
class Form_Preferences_Display extends Form_Preferences
{

    protected $_types = array(
        'DisplayBlacklistedSoftware' => 'bool',
    );

    /**
     * Translate labels before calling parent implementation
     */
    public function init()
    {
        $translate = Zend_Registry::get('Zend_Translate');
        $this->_labels = array(
            'DisplayBlacklistedSoftware' => $translate->_(
                'Display ignored software'
            ),
        );
        parent::init();
    }

}
