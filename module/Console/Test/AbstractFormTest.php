<?php
/**
 * Abstract form test case
 *
 * Copyright (C) 2011-2016 Holger Schletz <holger.schletz@web.de>
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

namespace Console\Test;

/**
 * Abstract form test case
 *
 * This base class performs common setup and tests for all forms derived from
 * \Console\Form\Form.
 */
abstract class AbstractFormTest extends \PHPUnit_Framework_TestCase
{
    /**
     * HTML header to declare document encoding
     *
     * \DomDocument parses HTML input as ISO 8859-1 by default. This is a
     * problem when XPath queries test on non-ASCII-characters. The only way to
     * specify another encoding is a meta tag within the HTML code itself.
     * For HTML fragments, this header can be prepended to trick \DomDocument
     * (and \Zend\Dom\Document) to parse the fragment as UTF-8.
     */
    const HTML_HEADER = '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';

    /**
     * Form instance provided by setUp()
     * @var \Console\Form\Form
     */
    protected $_form;

    /**
     * Set up form instance
     */
    public function setUp()
    {
        $this->_form = $this->_getForm();
    }

    /**
     * Hook to provide form instance
     *
     * The default implementation instantiates an object of a class derived from
     * the test class name. Override this method to use another name or
     * construct the form instance manually. The overridden method is
     * responsible for calling init() on the form.
     */
    protected function _getForm()
    {
        $class = $this->_getFormClass();
        $form = new $class;
        $form->init();
        return $form;
    }

    /**
     * Get the name of the form class, derived from the test class name
     *
     * @return string Form class name
     */
    protected function _getFormClass()
    {
        // Derive form class from test class name (minus \Test namespace and 'Test' suffix)
        return substr(str_replace('\Test', '', get_class($this)), 0, -4);
    }

    /**
     * Create a view renderer
     *
     * A new view renderer instance is created on every call. If the state of
     * the renderer or a helper needs to be preserved, call this only once and
     * store it in a variable.
     *
     * @return \Zend\View\Renderer\PhpRenderer
     */
    protected function _createView()
    {
        $application = \Zend\Mvc\Application::init(\Library\Application::getApplicationConfig('Console'));
        $view = new \Zend\View\Renderer\PhpRenderer;
        $view->setHelperPluginManager($application->getServiceManager()->get('ViewHelperManager'));
        return $view;
    }

    /**
     * Test basic form properties (form class, "class" attribute, CSRF element)
     */
    public function testForm()
    {
        $this->assertInstanceOf('Console\Form\Form', $this->_form);
        $this->assertEquals(
            'form ' . substr(strtr(strtolower($this->_getFormClass()), '\\', '_'), 8),
            $this->_form->getAttribute('class')
        );
        $this->assertInstanceOf('\Zend\Form\Element\Csrf', $this->_form->get('_csrf'));
    }
}
