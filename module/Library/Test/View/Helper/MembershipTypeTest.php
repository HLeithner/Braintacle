<?php
/**
 * Tests for the MembershipType helper
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

namespace Library\Test\View\Helper;

/**
 * Tests for the MembershipType helper
 */
class MembershipTypeTest extends AbstractTest
{
    public function testTypeAutomatic()
    {
        $helper = $this->_getHelper();
        $this->assertEquals('automatic', $helper(\Model_GroupMembership::TYPE_DYNAMIC));
    }

    public function testTypeManual()
    {
        $helper = $this->_getHelper();
        $this->assertEquals('manual', $helper(\Model_GroupMembership::TYPE_STATIC));
    }

    public function testInvalidType()
    {
        $this->setExpectedException('InvalidArgumentException');
        $helper = $this->_getHelper();
        $helper('invalid');
    }
}
