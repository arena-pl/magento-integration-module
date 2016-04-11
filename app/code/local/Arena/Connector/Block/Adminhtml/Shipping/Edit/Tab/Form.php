<?php

class Arena_Connector_Block_Adminhtml_Shipping_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {

        $form = new Varien_Data_Form();
        $this->setForm($form);

        if (Mage::getSingleton("adminhtml/session")->getShippingData()) {
            $data = Mage::getSingleton("adminhtml/session")->getShippingData();
            Mage::getSingleton("adminhtml/session")->setShippingData(null);
        } elseif (Mage::registry("shipping_data")) {
            $data = Mage::registry("shipping_data")->getData();
        }

        $fieldset = $form->addFieldset("arena_connector_form", array("legend" => Mage::helper("arena_connector")->__("Item information")));


        $fieldset->addField("arena_id", "text", array(
            "label" => Mage::helper("arena_connector")->__("Arena ID"),
            "class" => "required-entry",
            "readonly" => true,
            "disabled" => true,
            "required" => true,
            "name" => "arena_id",
        ));

        $fieldset->addField("arena_name", "text", array(
            "label" => Mage::helper("arena_connector")->__("Arena Name"),
            "class" => "required-entry",
            "readonly" => true,
            "disabled" => true,
            "required" => true,
            "name" => "arena_name",
        ));

        $fieldset->addField("store_id", "select", array(
            "label" => Mage::helper("arena_connector")->__("Store"),
            "name" => "store_id",
            "class" => "required-entry",
            "readonly" => true,
            "disabled" => true,
            "required" => true,
            "values" => Mage::helper('arena_connector')->getStoreViews()
        ));


        $fieldset->addField("shipping_code", "select", array(
            "label" => Mage::helper("arena_connector")->__("Shipping Code"),
            "name" => "shipping_code",
            "class" => "required-entry",
            "values" => Mage::helper('arena_connector')->getActiveCarriersForStore($data['store_id'])
        ));

        $form->setValues($data);


        return parent::_prepareForm();
    }
}
