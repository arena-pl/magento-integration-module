<?php

class Arena_Connector_Model_Cron
{
    const TASK_FULL_PRODUCT = 'push_product';
    const TASK_STOCK = 'push_stock';
    const TASK_FULL_OFFER = 'offer';

    public function fetchOrdersToUpdate()
    {
        foreach (Mage::app()->getStores() as $store) {
            if (!Mage::getStoreConfig('arena_api/arena_api_credentials/enabled', $store->getId())) {
                continue;
            }

            $go = true;
            $page = 1;
            while ($go) {
                $orders = Mage::getModel('arena_connector/api_client')->getOrders($store, $page++, true);
                $foundOld = false;
                foreach ($orders as $o) {
                    /*
                     * @var Mage_Sales_Model_Resource_Order_Collection
                     */
                    $salesOrderCollection = Mage::getModel('sales/order')->getCollection();
                    $existingOrder = $salesOrderCollection->addFilter('ext_order_id', $o['id'])->setPageSize(1)->getIterator()->current();
                    //skip, will be fetched in fetchNewOrders method
                    if (!$existingOrder) {
                        continue;
                    }

                    $updatedAt = Varien_Date::formatDate(new Zend_Date($o['date_updated']));
                    if ($existingOrder->getData('arena_updated_at') == $updatedAt) {
                        $foundOld = true;
                        break;
                    }

                    $status = 'Status: '.$o['status'].'. Payment status: '.$o['payment_status'];
                    $existingOrder->addStatusHistoryComment($status);
                    $existingOrder->setData('arena_updated_at', $updatedAt);
                    $existingOrder->save();
                }
                if (count($orders) == 0 || $foundOld == true) {
                    $go = false;
                }
            }
        }
    }

    public function fetchNewOrders()
    {
        /*
         * @var Arena_Connector_Model_OrderCreator
         */
        $orderCreator = Mage::getModel('arena_connector/orderCreator');
        foreach (Mage::app()->getStores() as $store) {
            if (!Mage::getStoreConfig('arena_api/arena_api_credentials/enabled', $store->getId())) {
                continue;
            }

            $go = true;
            $page = 1;
            $foundExistingOne = false;

            while ($go) {
                $orders = Mage::getModel('arena_connector/api_client')->getOrders($store, $page++);
                foreach ($orders as $o) {
                    /*
                     * @var Mage_Sales_Model_Resource_Order_Collection
                     */
                    $salesOrderCollection = Mage::getModel('sales/order')->getCollection();
                    $existingOrder = $salesOrderCollection->addFilter('ext_order_id', $o['id'])->setPageSize(1)->getIterator()->current();

                    if ($existingOrder instanceof Mage_Sales_Model_Order) {
                        $foundExistingOne = true;
                        break;
                    }
                    $comments = array();
                    if (isset($o['need_invoice']) && $o['need_invoice'] == true) {
                        $comments[] = Mage::helper('arena_connector')->__('Invoice');
                    }

                    $comments[] = 'Status: '.$o['status'].'. Payment status: '.$o['payment_status'];

                    //tricky hack to guess if there is only one shipping method
                    $boundaries = array();
                    foreach ($o['positions'] as $position) {
                        $k = $position['shipping_method']['boundary']['id'].'_'.((int) $position['shipping_method']['cash_on_delivery']);
                        if (!isset($boundaries[$k])) {
                            $boundaries[$k] = array();
                        }
                        $boundaries[$k][] = $position;
                    }

                    $positions = array();
                    foreach ($o['positions'] as $position) {
                        $positions[$position['product']['seller_product_id']] = array(
                            'qty' => $position['quantity'],
                            'price' => $position['price'],
                            'full_position' => $position,
                            'id' => $position['product']['id']
                        );
                    }
                    $delivery = $o['delivery_address'];
                    $shipping = array(
                        'firstname' => $delivery['name'],
                        'lastname' => $delivery['surname'],
                        'street' => $delivery['street'],
                        'city' => $delivery['city'],
                        'postcode' => $delivery['post_code'],
                        'telephone' => $delivery['phone'] ? $delivery['phone'] : '00-000-000-000',
                        'country_id' => 'PL',
                    );

                    if (isset($o['invoice_address']) && isset($o['need_invoice']) && $o['need_invoice'] == true) {
                        $invoice = $o['invoice_address'];

                        $billing = array(
                            'firstname' => 'N/A',
                            'lastname' => 'N/A',
                            'company' => $invoice['company_name'],
                            'street' => $invoice['street'],
                            'city' => $invoice['city'],
                            'postcode' => $invoice['post_code'],
                            'vat_id' => $invoice['nip'],
                            'telephone' => $delivery['phone'] ? $delivery['phone'] : '00-000-000-000',
                            'country_id' => 'PL',
                        );
                    } else {
                        $billing = $shipping;
                    }
                    $orderCreator->createNewOrder(
                        $store->getStoreCode(),
                        $positions,
                        $o['id'],
                        $boundaries,
                        $comments,
                        $o['shipping_subtotal'],
                        $o['user_mail'],
                        $shipping,
                        $billing,
                        new Zend_Date($o['date_placed']),
                        new Zend_Date($o['date_updated'])
                    );
                }

                if (count($orders) == 0 || $foundExistingOne == true) {
                    $go = false;
                }
            }
        }
    }

    public function cleanFallbackQueue()
    {
        $queueCollection = Mage::getModel('arena_connector/queue')->getCollection();
        $queue = $queueCollection->addOrder('id', Arena_Connector_Model_Mysql4_Queue_Collection::SORT_ORDER_ASC)
            ->setPageSize(1)
            ->getIterator()
            ->current();

        if ($queue) {
            try {
                switch ($queue->getTask()) {
                    case self::TASK_FULL_PRODUCT:
                        $product = Mage::getModel('catalog/product')->load($queue->getParams());
                        foreach ($product->getWebsiteIds() as $websiteId) {
                            foreach (Mage::app()->getWebsite($websiteId)->getStoreIds() as $storeId) {
                                if (!Mage::getStoreConfig('arena_api/arena_api_credentials/enabled', $storeId)) {
                                    continue;
                                }
                                Mage::getSingleton('arena_connector/productService')->pushFullProductToArena($product, $storeId);
                            }
                        }
                        break;
                    case self::TASK_STOCK:
                        $product = Mage::getModel('catalog/product')->load($queue->getParams());
                        foreach ($product->getWebsiteIds() as $websiteId) {
                            foreach (Mage::app()->getWebsite($websiteId)->getStoreIds() as $storeId) {
                                if (!Mage::getStoreConfig('arena_api/arena_api_credentials/enabled', $storeId)) {
                                    continue;
                                }
                                $p = Mage::getSingleton('catalog/product')->setStoreId($storeId)->load($product->getId());
                                if ($product->getData('arena_id')) {
                                    Mage::getSingleton('arena_connector/productService')->pushStatus($p, $storeId);
                                } else {
                                    Mage::getSingleton('arena_connector/productService')->pushFullProductToArena($p, $storeId);
                                }
                            }
                        }
                        break;
                }
            } catch (Exception $e) {
                Mage::log($e->getMessage(), 'arena_integration_fallback.log');
            }
            $queue->delete();
        }
    }

    public function dailyExport()
    {
        foreach (Mage::app()->getStores() as $store) {
            if (!Mage::getStoreConfig('arena_api/arena_api_credentials/enabled', $store->getId())) {
                continue;
            }

            //parents first
            $collection = Mage::getResourceModel('catalog/product_collection')
                ->addStoreFilter($store->getId());
            $collection->addAttributeToFilter('type_id', 'configurable');
            $count = $collection->count();
            $pages = ceil($count / 100);
            $adapter = new Zend_ProgressBar_Adapter_Console();
            $progressBar = new Zend_ProgressBar($adapter, 0, $count);
            for ($i = 0; $i < $pages; ++$i) {
                $ids = $collection->getAllIds(100, $i * 100);
                foreach ($ids as $id) {
                    try {
                        Mage::getSingleton('arena_connector/productService')->pushFullProductToArena($id, $store->getId());
                    }catch(Exception $e){
                        if(preg_match('/time.*out/i',$e->getMessage())){
                            //retry once
                            Mage::getSingleton('arena_connector/productService')->pushFullProductToArena($id, $store->getId());
                        }
                    }
                    $progressBar->next();
                }
            }
            $progressBar->finish();

            $collection = Mage::getResourceModel('catalog/product_collection')
                ->addStoreFilter($store->getId());
            $collection->addAttributeToFilter('type_id', 'simple');

            $count = $collection->count();
            $pages = ceil($count / 100);
            $adapter = new Zend_ProgressBar_Adapter_Console();
            $progressBar = new Zend_ProgressBar($adapter, 0, $count);
            for ($i = 0; $i < $pages; ++$i) {
                $ids = $collection->getAllIds(100, $i * 100);
                foreach ($ids as $id) {
                    try {
                        Mage::getSingleton('arena_connector/productService')->pushFullProductToArena($id, $store->getId());
                    }catch(Exception $e){
                        if(preg_match('/time.*out/i',$e->getMessage())){
                            //retry once
                            Mage::getSingleton('arena_connector/productService')->pushFullProductToArena($id, $store->getId());
                        }
                    }
                    $progressBar->next();
                }
            }
            $progressBar->finish();
        }
    }
}
