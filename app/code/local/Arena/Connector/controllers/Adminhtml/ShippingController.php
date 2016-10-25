<?php

class Arena_Connector_Adminhtml_ShippingController extends Mage_Adminhtml_Controller_Action
{
    protected function _initAction()
    {
        $this->loadLayout()->_setActiveMenu("arena_connector/shipping")->_addBreadcrumb(Mage::helper("adminhtml")->__("Shipping  Manager"), Mage::helper("adminhtml")->__("Shipping Manager"));
        return $this;
    }

    public function indexAction()
    {
        $this->_title($this->__("Arena.pl Connector"));
        $this->_title($this->__("Shipping Manager"));

        /**
         * @var $client Arena_Connector_Model_Api_Client
         */
        $client = Mage::getSingleton('arena_connector/api_client');
        foreach (Mage::app()->getStores() as $store) {
            if (Mage::getStoreConfig('arena_api/arena_api_credentials/enabled', $store)) {
                $methods = $client->getShippingMethods($store);
                if ($methods->getStatus() == 200) {
                    $data = json_decode($methods->getBody(), true);
                    foreach ($data as $shippingMethod) {
                        foreach ($shippingMethod['boundaries'] as $boundary) {
                            /**
                             * @var $shippingsCollection Arena_Connector_Model_Mysql4_Shipping_Collection
                             */
                            $shippingsCollection = Mage::getModel('arena_connector/shipping')->getCollection();
                            $existingShippings = $shippingsCollection
                                ->addFieldToFilter('store_id', array('eq' => $store->getId()))
                                ->addFieldToFilter('arena_id', array('eq' => $boundary['id']))
                                ->load();

                            foreach ($existingShippings as $s) {
                                if ($s->getCashOnDelivery() && $shippingMethod['cash_on_delivery']) {
                                    $s->setArenaName($shippingMethod['name'] . ' / ' . $boundary['name'] . ' (pobranie)');
                                    $s->save();
                                } elseif ($s->getCashOnDelivery() && !$shippingMethod['cash_on_delivery']) {
                                    $s->delete();
                                } elseif (!$s->getCashOnDelivery()) {
                                    $s->setArenaName($shippingMethod['name'] . ' / ' . $boundary['name']);
                                    $s->save();
                                }
                            }

                            if (count($existingShippings) == 0) {
                                $shipping = Mage::getModel('arena_connector/shipping');
                                $shipping->setStoreId($store->getId());
                                $shipping->setArenaId($boundary['id']);
                                $shipping->setCashOnDelivery(0);
                                $shipping->setArenaName($shippingMethod['name'] . ' / ' . $boundary['name']);
                                $shipping->save();
                            } elseif (count($existingShippings) == 1 && $shippingMethod['cash_on_delivery'] == true) {
                                $shipping = Mage::getModel('arena_connector/shipping');
                                $shipping->setStoreId($store->getId());
                                $shipping->setArenaId($boundary['id']);
                                $shipping->setCashOnDelivery(1);
                                $shipping->setArenaName($shippingMethod['name'] . ' / ' . $boundary['name'] . ' (pobranie)');
                                $shipping->save();
                            }
                        }
                    }
                    /**
                     * @var $shippingsCollection Arena_Connector_Model_Mysql4_Shipping_Collection
                     */
                    $shippingsCollection = Mage::getModel('arena_connector/shipping')->getCollection();
                    $existingShippings = $shippingsCollection
                        ->addFieldToFilter('store_id', array('eq' => $store->getId()))
                        ->load();

                    foreach ($existingShippings as $shipping) {
                        $found = false;
                        foreach ($data as $sm) {
                            foreach ($sm['boundaries'] as $boundary) {
                                if ($boundary['id'] == $shipping->getArenaId()) {
                                    $found = true;
                                }
                            }
                        }

                        if (!$found) {
                            $shipping->delete();
                        }
                    }
                } else {
                    Mage::getSingleton('adminhtml/session')->addError($methods->getMessage());
                }
            }
        }
        $this->_initAction();
        $this->renderLayout();
    }

    public function editAction()
    {
        $this->_title($this->__("Arena.pl Connector"));
        $this->_title($this->__("Shipping"));
        $this->_title($this->__("Edit Item"));

        $id = $this->getRequest()->getParam("id");
        $model = Mage::getModel("arena_connector/shipping")->load($id);
        if ($model->getId()) {
            Mage::register("shipping_data", $model);
            $this->loadLayout();
            $this->_setActiveMenu("arena_connector/shipping");
            $this->_addBreadcrumb(Mage::helper("adminhtml")->__("Shipping Manager"), Mage::helper("adminhtml")->__("Shipping Manager"));
            $this->_addBreadcrumb(Mage::helper("adminhtml")->__("Shipping Description"), Mage::helper("adminhtml")->__("Shipping Description"));
            $this->getLayout()->getBlock("head")->setCanLoadExtJs(true);
            $this->_addContent($this->getLayout()->createBlock("arena_connector/adminhtml_shipping_edit"))->_addLeft($this->getLayout()->createBlock("arena_connector/adminhtml_shipping_edit_tabs"));
            $this->renderLayout();
        } else {
            Mage::getSingleton("adminhtml/session")->addError(Mage::helper("arena_connector")->__("Item does not exist."));
            $this->_redirect("*/*/");
        }
    }

    public function saveAction()
    {

        $postData = $this->getRequest()->getPost();


        if ($postData) {

            try {
                $model = Mage::getModel("arena_connector/shipping")
                    ->addData($postData)
                    ->setId($this->getRequest()->getParam("id"))
                    ->save();

                Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("Shipping was successfully saved"));
                Mage::getSingleton("adminhtml/session")->setShippingData(false);

                if ($this->getRequest()->getParam("back")) {
                    $this->_redirect("*/*/edit", array("id" => $model->getId()));
                    return;
                }
                $this->_redirect("*/*/");
                return;
            } catch (Exception $e) {
                Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
                Mage::getSingleton("adminhtml/session")->setShippingData($this->getRequest()->getPost());
                $this->_redirect("*/*/edit", array("id" => $this->getRequest()->getParam("id")));
                return;
            }

        }
        $this->_redirect("*/*/");
    }
}
