<?php
/**
 * Render headline and navigation for group details
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
 * @package ViewHelpers
 * @filesource
 */
/**
 * Render headline and navigation for group details
 * @package ViewHelpers
 */
class Zend_View_Helper_GroupHeader extends Zend_View_Helper_Abstract
{

    /**
     * Render headline and navigation for group details
     * @param Model_Group Group for which details are displayed
     * @return string HTML code with header and navigation
     */
    function groupHeader($group)
    {
        $output = $this->view->htmlTag(
            'h1',
            sprintf(
                $this->view->translate('Details for group \'%s\''),
                $this->view->escape($group->getName())
            )
        );

        $id = array('id' => $group->getId());
        $navigation = new Zend_Navigation;

        $page = new Zend_Navigation_Page_Mvc;
        $page->setLabel('General')
             ->setController('group')
             ->setAction('general')
             ->setParams($id);
        $navigation->addPage($page);

        $page = new Zend_Navigation_Page_Mvc;
        $page->setLabel('Members')
             ->setController('group')
             ->setAction('members')
             ->setParams($id);
        $navigation->addPage($page);

        $page = new Zend_Navigation_Page_Mvc;
        $page->setLabel('Packages')
             ->setController('group')
             ->setAction('packages')
             ->setParams($id);
        $navigation->addPage($page);

        $page = new Zend_Navigation_Page_Mvc;
        $page->setLabel('Delete')
             ->setController('group')
             ->setAction('delete')
             ->setParams($id);
        $navigation->addPage($page);

        $output .= $this->view->navigation()
            ->menu()
            ->setUlClass('navigation navigation_details')
            ->render($navigation);
        return $output;
    }

}
