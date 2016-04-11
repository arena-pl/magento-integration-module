<?php

class Arena_Connector_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getActiveCarriersForStore($store)
    {
        $methods = Mage::getSingleton('shipping/config')->getActiveCarriers($store);

        $options = array();

        foreach ($methods as $_code => $_carrier) {
            if (!$_title = Mage::getStoreConfig('carriers/'.$_code.'/title', $store)) {
                $_title = $_code;
            }

            if ($_methods = $_carrier->getAllowedMethods()) {
                foreach ($_methods as $_mcode => $_method) {
                    $code = $_code.'_'.$_mcode;
                    $options[$code] = $_title.' ('.$code.')';
                }
            }
        }

        return $options;
    }

    public function getStoreViews()
    {
        $ret = array();
        foreach (Mage::app()->getStores() as $store) {
            $ret[$store->getId()] = $store->getWebsite()->getName().' -> '.$store->getName();
        }

        return $ret;
    }
}
