<?php
/**
 * Tests for DirectoryWritable validator
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

namespace Library\Test\Validator;

use Library\Validator\DirectoryWritable;
use \org\bovigo\vfs\vfsStream;

class DirectoryWritableTest extends \PHPUnit_Framework_TestCase
{
    /**
     * vfsStream root container
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    protected $_root;

    public function setUp()
    {
        $this->_root = vfsStream::setup('root');
    }

    public function testDirectoryWritable()
    {
        $url = vfsStream::newDirectory('test', 0777)->at($this->_root)->url();
        $validator = new DirectoryWritable;
        $this->assertTrue($validator->isValid($url));
    }

    public function testDirectoryReadOnly()
    {
        $url = vfsStream::newDirectory('test', 0000)->at($this->_root)->url();
        $validator = new DirectoryWritable;
        $this->assertFalse($validator->isValid($url));
        $this->assertEquals(
            array(DirectoryWritable::WRITABLE => "Verzeichnis '$url' ist nicht schreibbar"),
            $validator->getMessages()
        );
    }

    public function testFileWritable()
    {
        $url = vfsStream::newFile('test', 0777)->at($this->_root)->url();
        $validator = new DirectoryWritable;
        $this->assertFalse($validator->isValid($url));
        $this->assertEquals(
            array(DirectoryWritable::DIRECTORY => "'$url' ist kein Verzeichnis oder nicht zugänglich"),
            $validator->getMessages()
        );
    }

    public function testFileReadOnly()
    {
        $url = vfsStream::newFile('test', 0000)->at($this->_root)->url();
        $validator = new DirectoryWritable;
        $this->assertFalse($validator->isValid($url));
        $this->assertEquals(
            array(DirectoryWritable::DIRECTORY => "'$url' ist kein Verzeichnis oder nicht zugänglich"),
            $validator->getMessages()
        );
    }

    public function testNonExistent()
    {
        $url = $this->_root->url() . '/test';
        $validator = new DirectoryWritable;
        $this->assertFalse($validator->isValid($url));
        $this->assertEquals(
            array(DirectoryWritable::DIRECTORY => "'$url' ist kein Verzeichnis oder nicht zugänglich"),
            $validator->getMessages()
        );
    }

    public function testEmpty()
    {
        $url = '';
        $validator = new DirectoryWritable;
        $this->assertFalse($validator->isValid($url));
        $this->assertEquals(
            array(DirectoryWritable::DIRECTORY => "'$url' ist kein Verzeichnis oder nicht zugänglich"),
            $validator->getMessages()
        );
    }
}
