<?php
/**
 * Tests for PrintForm controller plugin
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

namespace Console\Test\Mvc\Controller\Plugin;

/**
 * Tests for PrintForm controller plugin
 */
class PrintFormTest extends \Library\Test\Mvc\Controller\Plugin\AbstractTest
{
    /**
     * Invoke plugin with various form types
     */
    public function testInvoke()
    {
        $plugin = $this->_getPlugin(false);

        // Set up \Zend_Form using default renderer
        $this->form = $this->getMock('Zend_Form');
        $this->form->expects($this->once())
                   ->method('__toString')
                   ->will($this->returnValue('\Zend_Form default renderer'));

        // Evaluate plugin return value
        $view = $plugin($this->form);
        $this->assertInstanceOf('Zend\View\Model\ViewModel', $view);
        $template = $view->getTemplate();
        $this->assertEquals('plugin/PrintForm.php', $template);

        // Invoke template and test output
        ob_start();
        require(\Console\Module::getPath('view/' . $template));
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertEquals('\Zend_Form default renderer', $output);
    }
}
