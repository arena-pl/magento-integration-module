<?php

class Arena_Connector_Block_Adminhtml_Shipping_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {

        parent::__construct();
        $this->_objectId = "id";
        $this->_blockGroup = "arena_connector";
        $this->_controller = "adminhtml_shipping";
        $this->_updateButton("save", "label", Mage::helper("arena_connector")->__("Save Item"));

        $this->_addButton("saveandcontinue", array(
            "label" => Mage::helper("arena_connector")->__("Save And Continue Edit"),
            "onclick" => "saveAndContinueEdit()",
            "class" => "save",
        ), -100);


        $this->_formScripts[] = "

							function saveAndContinueEdit(){
								editForm.submit($('edit_form').action+'back/edit/');
							}
						";
        $this->removeButton('delete');
    }

    public function getHeaderText()
    {
        if (Mage::registry("shipping_data") && Mage::registry("shipping_data")->getId()) {

            return Mage::helper("arena_connector")->__("Edit Item '%s'", $this->htmlEscape(Mage::registry("shipping_data")->getId()));

        } else {

            return Mage::helper("arena_connector")->__("Add Item");

        }
    }
}