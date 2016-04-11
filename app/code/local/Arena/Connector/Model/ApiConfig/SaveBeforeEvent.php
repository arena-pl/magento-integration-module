<?php

class Arena_Connector_Model_ApiConfig_SaveBeforeEvent
{
    public function validateApiKey(Varien_Event_Observer $observer)
    {
        $data = $observer->getData();
        /**
         * @var $object Mage_Adminhtml_Model_Config_Data
         */
        $object = $data['event']->getObject();
        $objectData = $object->getData();
        if ($objectData['section'] == 'arena_api') {
            $groups = $objectData['groups'];
            if (isset($groups['arena_api_credentials'])) {
                $credentialsGroup = $groups['arena_api_credentials']['fields'];
                foreach (Mage::app()->getStores() as $store) {
                    if (
                        $store->getCode() === $objectData['store']
                        && $store->getWebsite()->getCode() === $objectData['website']
                    ) {
                        continue;
                    }

                    $key = Mage::getStoreConfig('arena_api/arena_api_credentials/api_key', $store);
                    $login = Mage::getStoreConfig('arena_api/arena_api_credentials/api_login', $store);
                    $endpoint = Mage::getStoreConfig('arena_api/arena_api_credentials/api_endpoint', $store);

                    if (
                        $credentialsGroup['api_login']['value'] === $login
                        && $credentialsGroup['api_key']['value'] === $key
                        && $credentialsGroup['api_endpoint']['value'] === $endpoint
                    ) {
                        Mage::throwException(
                            Mage::helper('arena_connector')->__('API key should be unique per endpoint and login.')
                        );
                    }
                }
            }
        }
    }
}