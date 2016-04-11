<?php

class Arena_Connector_Model_Payment extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'arena_pl';

    protected $_isInitializeNeeded = true;
    protected $_canUseInternal = true;
    protected $_canUseForMultishipping = false;
    protected $_canUseCheckout = false;
}
