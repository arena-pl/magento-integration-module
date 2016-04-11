<?php

class Arena_Connector_Model_Observer_Product
{
    /**
     * @param $type string
     *
     * @return bool
     */
    protected function isSupportedType($type)
    {
        return in_array($type, array('simple', 'configurable'));
    }

    public function handle(Varien_Event_Observer $observer)
    {
        /*
         * @var Mage_Catalog_Model_Product
         */
        $product = $observer->getEvent()->getDataObject();

        //just leave if not supported
        if (!$this->isSupportedType($product->getTypeId())) {
            return true;
        }
        if(!$product->getData('synced_in_this_request')) {
            $this->pushProductToArena($product);
        }
        $product->setData('synced_in_this_request', true);
    }

    public function handleStock(Varien_Event_Observer $observer)
    {
        $product = Mage::getModel('catalog/product')->load($observer->getEvent()->getDataObject()->getProductId());

        if (!$this->isSupportedType($product->getTypeId())) {
            return true;
        }

        if($product->getData('synced_in_this_request')) {
            return true;
        }

        try {
            foreach ($product->getWebsiteIds() as $websiteId) {
                foreach (Mage::app()->getWebsite($websiteId)->getStoreIds() as $storeId) {
                    $p = Mage::getModel('catalog/product')->setStoreId($storeId)->load($product->getId());
                    if (!$p->getArenaId() && !$p->getArenaSyncFlag()) {
                        continue;
                    }

                    if ($product->getData('arena_id')) {
                        Mage::getSingleton('arena_connector/productService')->pushStatus($p, $storeId);
                    } else {
                        Mage::getSingleton('arena_connector/productService')->pushFullProductToArena($p, $storeId);
                    }
                }
            }
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, 'arena_integration.log');

            //retry in cron
            $queue = new Arena_Connector_Model_Queue();
            $queue->setData('task', Arena_Connector_Model_Cron::TASK_STOCK);
            $queue->setData('params', $product->getId());
            $queue->save();
        }

        $product->setData('synced_in_this_request', true);
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     */
    public function pushProductToArena(Mage_Catalog_Model_Product $p)
    {
        try {
            foreach ($p->getWebsiteIds() as $websiteId) {
                foreach (Mage::app()->getWebsite($websiteId)->getStoreIds() as $storeId) {
                    Mage::getSingleton('arena_connector/productService')->pushFullProductToArena($p, $storeId);
                }
            }
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, 'arena_integration.log');

            //retry in cron
            $queue = new Arena_Connector_Model_Queue();
            $queue->setData('task', Arena_Connector_Model_Cron::TASK_FULL_PRODUCT);
            $queue->setData('params', $p->getId());
            $queue->save();
        }
    }
}
