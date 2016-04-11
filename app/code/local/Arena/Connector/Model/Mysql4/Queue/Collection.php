<?php

class Arena_Connector_Model_Mysql4_Queue_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{

    public function _construct()
    {
        $this->_init("arena_connector/queue");
    }


}
	 