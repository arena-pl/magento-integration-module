<?php

class Arena_Connector_Model_Sales_Quote_Address extends Mage_Sales_Model_Quote_Address
{
    public function getShippingRatesCollection()
    {
        parent::getShippingRatesCollection();

        if (Mage::app()->getStore()->getCode() != 'admin') {
            $removeRates = array();
            foreach ($this->_rates as $key => $rate) {
                if ($rate->getCarrier() == Mage::getModel('arena_connector/carrier')->getCarrierCode()) {
                    $removeRates[] = $key;
                }
            }

            foreach ($removeRates as $key) {
                $this->_rates->removeItemByKey($key);
            }
        }

        return $this->_rates;
    }
}
