<?php
/**
 * Tests for NetworkDeviceTypes form
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

namespace Console\Test\Form;

use \Zend\Dom\Document\Query as Query;

/**
 * Tests for NetworkDeviceTypes form
 */
class NetworkDeviceTypesTest extends \Console\Test\AbstractFormTest
{
    /**
     * NetworkDeviceType mock object
     * @var \Model_NetworkDeviceType
     */
    protected $_types;

    public function setUp()
    {
        $types = array(
            array('Description' => 'name0', 'Count' => 0),
            array('Description' => 'name1', 'Count' => 1),
        );
        $this->_types = $this->getMock('Model_NetworkDeviceType');
        $this->_types->expects($this->once())
                     ->method('fetchAll')
                     ->will($this->returnValue($types));
        parent::setUp();
    }

    /** {@inheritdoc} */
    protected function _getForm()
    {
        $form = new \Console\Form\NetworkDeviceTypes(
            null,
            array('DeviceTypeModel' => $this->_types)
        );
        $form->init();
        return $form;
    }

    public function testInit()
    {
        $this->assertInstanceOf('Zend\Form\Element\Text', $this->_form->get('Add'));
        $this->assertInstanceOf('Library\Form\Element\Submit', $this->_form->get('Submit'));

        $types = $this->_form->get('Types');
        $this->assertCount(2, $types);

        $element = $types->get('name0');
        $this->assertInstanceOf('Zend\Form\Element\Text', $element);
        $this->assertEquals('name0', $element->getValue());

        $element = $types->get('name1');
        $this->assertInstanceOf('Zend\Form\Element\Text', $element);
        $this->assertEquals('name1', $element->getValue());
    }

    public function testInputFilterUnchangedNoAdd()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'Add' => '',
            'Types' => array(
                'name0' => ' name0 ',
                'name1' => ' name1 ',
            ),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $data = $this->_form->getData();
        $this->assertEquals('name0', $data['Types']['name0']);
        $this->assertEquals('name1', $data['Types']['name1']);
    }

    public function testInputFilterChangedNoAdd()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'Add' => '',
            'Types' => array(
                'name0' => ' NAME0 ',
                'name1' => ' name1 ',
            ),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $data = $this->_form->getData();
        $this->assertEquals('NAME0', $data['Types']['name0']);
        $this->assertEquals('name1', $data['Types']['name1']);
    }

    public function testInputFilterDuplicateNewNamesNoAdd()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'Add' => '',
            'Types' => array(
                'name0' => ' name_new ',
                'name1' => 'name_NEW',
            ),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = array(
            'Types' => array(
                'name0' => array('callbackValue' => 'The name already exists'),
                'name1' => array('callbackValue' => 'The name already exists'),
            ),
        );
        $this->assertEquals($messages, $this->_form->getMessages());
    }

    public function testInputFilterNewNameConflictsWithExistingNameNoAdd()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'Add' => '',
            'Types' => array(
                'name0' => 'name0',
                'name1' => ' NAME0 ',
            ),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = array(
            'Types' => array(
                'name1' => array('callbackValue' => 'The name already exists'),
            ),
        );
        $this->assertEquals($messages, $this->_form->getMessages());
    }

    public function testInputFilterNewNameConflictsWithRenamedNameNoAdd()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'Add' => '',
            'Types' => array(
                'name0' => 'name_new',
                'name1' => ' NAME0 ',
            ),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = array(
            'Types' => array(
                'name1' => array('callbackValue' => 'The name already exists'),
            ),
        );
        $this->assertEquals($messages, $this->_form->getMessages());
    }

    public function testInputFilterSwapNamesNoAdd()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'Add' => '',
            'Types' => array(
                'name0' => ' NAME1 ',
                'name1' => ' NAME0 ',
            ),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = array(
            'Types' => array(
                'name0' => array('callbackValue' => 'The name already exists'),
                'name1' => array('callbackValue' => 'The name already exists'),
            ),
        );
        $this->assertEquals($messages, $this->_form->getMessages());
    }

    public function testInputFilterUnchangedAddWhitespaceOnly()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'Add' => ' ',
            'Types' => array(
                'name0' => ' name0 ',
                'name1' => ' name1 ',
            ),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $data = $this->_form->getData();
        $this->assertEquals('', $data['Add']);
    }

    public function testInputFilterUnchangedAddValid()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'Add' => ' name2 ',
            'Types' => array(
                'name0' => ' name0 ',
                'name1' => ' name1 ',
            ),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $data = $this->_form->getData();
        $this->assertEquals('name2', $data['Add']);
    }

    public function testInputFilterUnchangedAddExistingName()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'Add' => ' NAME0',
            'Types' => array(
                'name0' => ' name0 ',
                'name1' => ' name1 ',
            ),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = array(
            'Add' => array('callbackValue' => 'The name already exists'),
        );
        $this->assertEquals($messages, $this->_form->getMessages());
    }

    public function testInputFilterChangedAddExistingNewName()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'Add' => ' NAME2',
            'Types' => array(
                'name0' => ' name2 ',
                'name1' => ' name1 ',
            ),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = array(
            'Add' => array('callbackValue' => 'The name already exists'),
        );
        $this->assertEquals($messages, $this->_form->getMessages());
    }

    public function testRenderFieldsetNoMessages()
    {
        $html = $this->_form->renderFieldset($this->_createView(), $this->_form);
        $document = new \Zend\Dom\Document($html);
        $this->assertCount(1, Query::execute('//div[@class="table"]', $document));
        $this->assertCount(
            1,
            Query::execute(
                '//input[@name="name0"]/following-sibling::a' .
                '[@href="/console/preferences/deletedevicetype/?name=name0"][text()="Delete"]',
                $document
            )
        );
        $this->assertCount(1, Query::execute('//input[@name="name1"]', $document));
        $this->assertCount(1, Query::execute('//input[@name="Add"]', $document));
        $this->assertCount(1, Query::execute('//a', $document));
        $this->assertCount(1, Query::execute('//input[@type="submit"]', $document));
        $this->assertCount(0, Query::execute('//input[@class="input-error"]', $document));
        $this->assertCount(0, Query::execute('//ul', $document));
    }

    public function testRenderFieldsetMessages()
    {
        $this->_form->get('Types')->get('name0')->setMessages(array('message_name0'));
        $this->_form->get('Add')->setMessages(array('message_add'));
        $html = $this->_form->renderFieldset($this->_createView(), $this->_form);
        $document = new \Zend\Dom\Document($html);
        $this->assertCount(
            1,
            Query::execute(
                '//input[@name="name0"]/following::ul[1][@class="error"]/li[text()="message_name0"]',
                $document
            )
        );
        $this->assertCount(
            1,
            Query::execute(
                '//input[@name="Add"]/following::ul[1][@class="error"]/li[text()="message_add"]',
                $document
            )
        );
        $this->assertCount(2, Query::execute('//input[@class="input-error"]', $document));
        $this->assertCount(2, Query::execute('//ul', $document));
    }

    public function testProcessRenameNoAdd()
    {
        $types = $this->getMock('Model_NetworkDeviceType');
        $types->expects($this->once())
              ->method('fetchAll')
              ->will($this->returnValue(array($types)));
        $types->expects($this->any())
              ->method('offsetGet')
              ->with('Description')
              ->will($this->returnValue('name'));
        $types->expects($this->never())
              ->method('add');
        $types->expects($this->once())
              ->method('rename')
              ->with('new_name');
        $data = array(
            'Add' => '',
            'Types' => array(
                'name' => 'new_name',
            ),
        );
        $form = $this->getMockBuilder('Console\Form\NetworkDeviceTypes')->setMethods(array('getData'))->getMock();
        $form->expects($this->once())
             ->method('getData')
             ->will($this->returnValue($data));
        $form->setOption('DeviceTypeModel', $types);
        $form->init();
        $form->process();
    }

    public function testProcessAdd()
    {
        $types = $this->getMock('Model_NetworkDeviceType');
        $types->expects($this->once())
              ->method('fetchAll')
              ->will($this->returnValue(array()));
        $types->expects($this->once())
              ->method('add')
              ->with('new_name');
        $types->expects($this->never())
              ->method('rename');
        $data = array(
            'Add' => 'new_name',
            'Types' => array(),
        );
        $form = $this->getMockBuilder('Console\Form\NetworkDeviceTypes')->setMethods(array('getData'))->getMock();
        $form->expects($this->once())
             ->method('getData')
             ->will($this->returnValue($data));
        $form->setOption('DeviceTypeModel', $types);
        $form->init();
        $form->process();
    }
}
