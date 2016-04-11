<?php

class Arena_Connector_Model_Carrier
extends Mage_Shipping_Model_Carrier_Abstract
implements Mage_Shipping_Model_Carrier_Interface
{
    protected $_code = 'arenapl';
    protected $_isFixed = true;


    public function getAllowedMethods()
    {
        return array(
            'notmapped' => 'Not mapped',
            'multishipping' => 'Multishipping',
        );
    }

    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        $result = Mage::getModel('shipping/rate_result');
        $result->append($this->getNotMapped());
        $result->append($this->getMultishipping());

        return $result;
    }

    public function getNotMapped()
    {
        $rate = Mage::getModel('shipping/rate_result_method');

        $rate->setCarrier($this->_code);
        $rate->setCarrierTitle('Arena.pl');
        $rate->setMethod('notmapped');
        $rate->setMethodTitle('Not mapped');
        $rate->setPrice(0);
        $rate->setCost(0);

        return $rate;
    }
    public function getMultishipping()
    {
        $rate = Mage::getModel('shipping/rate_result_method');

        $rate->setCarrier($this->_code);
        $rate->setCarrierTitle('Arena.pl');
        $rate->setMethod('multishipping');
        $rate->setMethodTitle('Multishipping');
        $rate->setPrice(0);
        $rate->setCost(0);

        return $rate;
    }
}
