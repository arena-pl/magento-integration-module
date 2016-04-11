<?php

class Arena_Connector_Model_OrderCreator extends Mage_Core_Model_Abstract
{
    public function createNewOrder(
        $storeId,
        $productsArray,
        $orderId,
        $productsGroupedByBoundaries,
        $comments,
        $shippingCost,
        $customerMail,
        $addressData,
        $billing,
        $placeDate,
        $updateDate
    ) {
        /*
         * @var Mage_Sales_Model_Quote
         */
        $quote = Mage::getModel('sales/quote')
            ->setStoreId($storeId);
        $quote->setCustomerEmail($customerMail);

        $billingAddress = $quote->getBillingAddress()->addData($billing);
        $shippingAddress = $quote->getShippingAddress()->addData($addressData);
        $boundary = 'arenapl_notmapped';
        if (count($productsGroupedByBoundaries) == 1) {
            $arenaIds = array_keys($productsGroupedByBoundaries);
            list($arenaId, $cashOnDelivery) = explode('_', $arenaIds[0]);
            /*
             * @var Arena_Connector_Model_Mysql4_Shipping_Collection
             */
            $mappingCollection = Mage::getModel('arena_connector/shipping')->getCollection();
            $mappingCollection->addFilter('arena_id', $arenaId)
                ->addFilter('cash_on_delivery', $cashOnDelivery)
                ->setPageSize(1);
            $mapping = $mappingCollection->getIterator()->current();
            if ($mapping && $mapping->getShippingCode()) {
                $boundary = $mapping->getShippingCode();
            }
        } else {
            $boundary = 'arenapl_multishipping';
        }

        $productsToDelete = array();
        foreach ($productsArray as $id => $data) {
            $product = Mage::getModel('catalog/product')->load($id);
            $description = '';
            if (!$product->getId()) {
                $product = Mage::getModel('catalog/product');
                $stockItem = Mage::getModel('cataloginventory/stock_item');
                $stockItem->assignProduct($product);
                $stockItem->setData('is_in_stock', 1);
                $stockItem->setData('qty', $data['qty']);
                $product->setStockItem($stockItem);

                $product
                    ->setTypeId('simple')
                    ->setAttributeSetId(Mage::getModel('catalog/product')->getDefaultAttributeSetId())
                    ->setSku('arena-pl-'.trim($data['id']).'-'.microtime(true))
                    ->setArenaSyncFlag(false)
                    ->setName('Product not found: '.$data['full_position']['product']['name'])
                    ->setWeight(1)
                    ->setStatus(Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
                    ->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE)
                    ->setTaxClassId(0)
                    ->setPrice(0)
                    ->setStockItem($stockItem);
                $product->save();
            }
            $productsToDelete[] = $product;
            $buyInfo = array(
                'qty' => $data['qty'],
            );

            /*
             * @var Mage_Sales_Model_Quote_Item
             */
            $item = $quote->addProduct($product, new Varien_Object($buyInfo));
            $item->getProduct()->setIsSuperMode(true);
            $item->setOriginalCustomPrice($data['price'] / 100);
            if ($boundary == 'arenapl_notmapped' || $boundary == 'arenapl_multishipping') {
                $sm = $data['full_position']['shipping_method'];
                $description .= $sm['method']['name'].' / '.$sm['boundary']['name'];
                if ($sm['cash_on_delivery']) {
                    $description .= ' - '.Mage::helper('arena_connector')->__('cash on delivery');
                }
                $description .= '. ';
            }
            if (isset($sm['pickup_location']) && !empty($sm['pickup_location'])) {
                $description .= Mage::helper('arena_connector')->__('Pickup location: ');
                $description .= $sm['pickup_location']['street'].', '.$sm['pickup_location']['post_code'].' '.$sm['pickup_location']['city'];
            }
            $item->setDescription($description);
        }

        /*
         * @var $shippingAddress Arena_Connector_Model_Sales_Quote_Address
         */
        $shippingAddress->setCollectShippingRates(true)->collectShippingRates()
            ->setShippingMethod($boundary)
            ->setPaymentMethod('arena_pl');

        $rates = $shippingAddress->collectShippingRates()
            ->getGroupedAllShippingRates();
        foreach ($rates as $carrier) {
            foreach ($carrier as $rate) {
                $rate->setPrice((float) $shippingCost / 100);
            }
        }

        $shippingAddress->save();

        $quote->getPayment()->importData(array('method' => 'arena_pl'));
        $quote->setData('trigger_recollect', 0)->setTotalsCollectedFlag(false)->collectTotals();
        $quote->save();

        /*
         * @var Mage_Sales_Model_Service_Quote
         */
        $service = Mage::getModel('sales/service_quote', $quote);
        $service->submitAll();
        $order = $service->getOrder();
        $order->setExtOrderId($orderId);

        foreach ($comments as $comment) {
            $order->addStatusHistoryComment($comment);
        }
        $order->setData('arena_placed_at', Varien_Date::formatDate($placeDate));
        $order->setData('arena_updated_at', Varien_Date::formatDate($updateDate));
        $order->save();
        foreach ($productsToDelete as $p) {
            $p->delete();
        }
    }
}
