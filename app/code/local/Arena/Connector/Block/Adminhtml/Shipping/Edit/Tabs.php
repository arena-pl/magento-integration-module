<?php

class Arena_Connector_Block_Adminhtml_Shipping_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId("shipping_tabs");
        $this->setDestElementId("edit_form");
        $this->setTitle(Mage::helper("arena_connector")->__("Item Information"));
    }

    protected function _beforeToHtml()
    {
        $this->addTab("form_section", array(
            "label" => Mage::helper("arena_connector")->__("Item Information"),
            "title" => Mage::helper("arena_connector")->__("Item Information"),
            "content" => $this->getLayout()->createBlock("arena_connector/adminhtml_shipping_edit_tab_form")->toHtml(),
        ));
        return parent::_beforeToHtml();
    }

}
