<?php


class Arena_Connector_Block_Adminhtml_Shipping extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    public function __construct()
    {

        $this->_controller = "adminhtml_shipping";
        $this->_blockGroup = "arena_connector";
        $this->_headerText = Mage::helper("arena_connector")->__("Shipping Manager");
        parent::__construct();
        $this->removeButton('add');
    }

}