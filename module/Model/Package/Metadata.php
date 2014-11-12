<?php
/**
 * Package metadata XML
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

namespace Model\Package;

/**
 * Package metadata XML
 *
 * The schema defines a single element with all metadata in its attributes.
 * These should be accessed via setPackageData().
 */
class Metadata extends \Library\DomDocument
{
    /** {@inheritdoc} */
    public function getSchemaFilename()
    {
        return \Model\Module::getPath('data/RelaxNG/PackageMetadata.rng');
    }

    /**
     * Set attributes from given package
     *
     * Attributes are populated with values from the given package and
     * hardcoded defaults.
     *
     * @param array $data Package data
     */
    public function setPackageData($data)
    {
        $node = $this->createElement('DOWNLOAD');

        $node->setAttribute('ID', $data['Timestamp']->get(\Zend_Date::TIMESTAMP));
        $node->setAttribute('PRI', $data['Priority']);
        $node->setAttribute('ACT', strtoupper($data['DeployAction']));
        $node->setAttribute('DIGEST', $data['Hash']);
        $node->setAttribute('PROTO', 'HTTP');
        $node->setAttribute('FRAGS', $data['NumFragments']);
        $node->setAttribute('DIGEST_ALGO', 'SHA1');
        $node->setAttribute('DIGEST_ENCODE', 'Hexa');
        $node->setAttribute('PATH', ($data['DeployAction'] == 'store' ? $data['ActionParam'] : ''));
        $node->setAttribute('NAME', ($data['DeployAction'] == 'launch' ? $data['ActionParam'] : ''));
        $node->setAttribute('COMMAND', ($data['DeployAction'] == 'execute' ? $data['ActionParam'] : ''));
        $node->setAttribute('NOTIFY_USER', $data['Warn'] ? '1' : '0');
        $node->setAttribute('NOTIFY_TEXT', $this->_escapeMessage($data['WarnMessage']));
        $node->setAttribute('NOTIFY_COUNTDOWN', $data['WarnCountdown']);
        $node->setAttribute('NOTIFY_CAN_ABORT', $data['WarnAllowAbort'] ? '1' : '0');
        $node->setAttribute('NOTIFY_CAN_DELAY', $data['WarnAllowDelay'] ? '1' : '0');
        $node->setAttribute('NEED_DONE_ACTION', $data['UserActionRequired'] ? '1' : '0');
        $node->setAttribute('NEED_DONE_ACTION_TEXT', $this->_escapeMessage($data['PostInstMessage']));
        $node->setAttribute('GARDEFOU', 'rien');

        if ($this->hasChildNodes()) {
            $this->replaceChild($node, $this->firstChild);
        } else {
            $this->appendChild($node);
        }
        if (!\Library\Application::isProduction()) {
            $this->forceValid();
        }
    }

    /**
     * Escape user notification messages
     *
     * The Windows agent interprets user notification messages as HTML. For this
     * reason, line breaks are converted to BR tags.
     *
     * The agent passes the message via command line internally. Its command
     * line parser does not handle double quotes properly which must be
     * transformed to HTML entities. Unfortunately this may not work well with
     * HTML attributes which should be enclosed in single quotes instead. There
     * is no easy way to distinct between attribute delimiters and literal
     * quotation marks.
     *
     * @param string $message User notification message
     * @return string Escaped string
     */
    protected function _escapeMessage($message)
    {
        $message = str_replace('"', '&quot;', $message);
        $message = str_replace(array("\r\n", "\n\r", "\n", "\r"), '<br>', $message);
        return $message;
    }
}
