<?php

class Arena_Connector_Block_Adminhtml_Shipping_GridRenderer extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    public function render(Varien_Object $row)
    {
        return '<a href="' . Mage::helper("adminhtml")->getUrl('*/*/edit', array('id' => $row->getData('id'))) . '">' . Mage::helper('adminhtml')->__('Create mapping') . '</a>';
    }

}