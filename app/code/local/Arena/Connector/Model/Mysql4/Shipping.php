<?php

class Arena_Connector_Model_Mysql4_Shipping extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init("arena_connector/shipping", "id");
    }
}