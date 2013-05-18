<?php
/**
 * Form for installing packages on a computer
 *
 * Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
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
 */
/**
 * Form for installing packages on a computer
 *
 * This form does not create its elements automatically. This only happens when
 * calling the {@link addPackages()} method. Note that this must also be done
 * before validating or retrieving data.
 *
 * It is advisable to use {@link getValues()} because it deals with the
 * encoding of package names in form elements. It only delivers checked items;
 * the computer ID must be retrieved via getValue() or directly from $_POST.
 * @package Forms
 */
class Form_AffectPackages extends Zend_Form
{

    /**
     * Only sets method. Elements are added by addPackages().
     */
    public function init()
    {
        $this->setMethod('post');
    }

    /**
     * Add checkboxes for installable packages for a given computer or group
     *
     * @param Model_ComputerOrGroup $object
     * @return integer Number of packages
     */
    public function addPackages(Model_ComputerOrGroup $object)
    {
        $statement = $object->getInstallablePackages();
        $numPackages = 0;
        $encoder = new Braintacle_Filter_FormElementNameEncode;

        while ($package = $statement->fetchObject('Model_Package')) {
            $name = $package->getName();
            // Encode element names to support arbitrary strings with otherwise
            // invalid characters.
            $element = new Zend_Form_Element_Checkbox($encoder->filter($name));
            $element->setDisableTranslator(true);
            $element->setLabel($name);
            $this->addElement($element);
            $numPackages++;
        }

        if ($numPackages) {
            $id = new Braintacle_Form_Element_Hidden('id');
            $id->setIgnore(true);
            $id->setValue($object->getId());
            $this->addElement($id);

            $submit = new Zend_Form_Element_Submit('submit');
            $submit->setRequired(false)
                ->setIgnore(true)
                ->setLabel('Install');
            $this->addElement($submit);
        }

        return $numPackages;
    }

    /**
     * Retrieve all form element values
     * @param bool $suppressArrayNotation
     * @return array
     */
    public function getValues($suppressArrayNotation=false)
    {
        // Return only checked items and decode their names.
        $values = parent::getValues($suppressArrayNotation);
        $result = array();
        $decoder = new Braintacle_Filter_FormElementNameDecode;

        foreach ($values as $name => $value) {
            if ($value) {
                $result[$decoder->filter($name)] = $value;
            }
        }

        return $result;
    }

}
