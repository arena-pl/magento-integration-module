<?php

class Arena_Connector_Model_Shipping_Config extends Mage_Shipping_Model_Config
{
    public function getActiveCarriers($store = null)
    {
        $carriers = parent::getActiveCarriers($store);

        if (Mage::app()->getStore()->getCode() != 'admin') {
            $carriersCodes = array_keys($carriers);
            foreach ($carriersCodes as $carriersCode) {
                if ($carriersCode == Mage::getModel('arena_connector/carrier')->getCarrierCode()) {
                    unset($carriers[$carriersCode]);
                }
            }
        }

        return $carriers;
    }
}
