<?php
/**
 * Tests for the FileObject class
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

namespace Library\Test;

use \Library\FileObject;
use \org\bovigo\vfs\vfsStream;

/**
 * Tests for the FileObject class
 */
class FileObjectTest extends \PHPUnit_Framework_TestCase
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

    public function testFileGetContentsSuccess()
    {
        $content = "testFileGetContentsSuccess\nline1\nline2\n";
        $url = vfsStream::newFile('test.txt')->withContent($content)->at($this->_root)->url();
        $this->assertEquals($content, FileObject::fileGetContents($url));
    }

    public function testFileGetContentsEmptyFile()
    {
        $content = '';
        $url = vfsStream::newFile('test.txt')->withContent($content)->at($this->_root)->url();
        $this->assertEquals($content, FileObject::fileGetContents($url));
    }

    public function testFileGetContentsError()
    {
        $this->setExpectedException('RuntimeException', 'Error reading from file vfs://root/test.txt');
        // Force error by requesting nonexistent file
        FileObject::fileGetContents('vfs://root/test.txt');
    }
}
