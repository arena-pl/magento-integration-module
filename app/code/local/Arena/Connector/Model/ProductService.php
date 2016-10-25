<?php

class Arena_Connector_Model_ProductService
{
    protected $connector;

    /**
     * @return Arena_Connector_Model_Api_Client
     */
    public function getConnector()
    {
        if ($this->connector === null) {
            $this->connector = Mage::getSingleton('arena_connector/api_client');
        }

        return $this->connector;
    }

    /**
     * @param Mage_Catalog_Model_Product $p
     * @param $storeId
     *
     * @return bool
     *
     * @throws Arena_Connector_Model_Api_Exception_UnexpectedResult
     */
    public function pushFullProductToArena($p, $storeId)
    {
        if (!Mage::getStoreConfig('arena_api/arena_api_credentials/enabled', $storeId)) {
            return false;
        }
        $product = Mage::getModel('catalog/product')->setStoreId($storeId)->load(is_object($p) ? $p->getId() : $p);

        if(!$product->getArenaId() && !$product->getArenaSyncFlag()){
            return false;
        }

        $this->pushAttributeGroup($product);
        $this->pushProduct($product);
        $this->pushPictures($product);

        return true;
    }

    public function pushAttributeGroup($product)
    {
        $connector = $this->getConnector();
        $attributeGroupTransport = Mage::getSingleton('arena_connector/transformer_productToAttributeSet')->transform($product);
        $result = $connector->updateAttributeGroup($attributeGroupTransport, $product->getStoreId());
        if ($result->getStatus() != 200) {
            throw new Arena_Connector_Model_Api_Exception_UnexpectedResult($result->getBody(), $product->getId());
        }
    }

    public function pushProduct($product)
    {
        $connector = $this->getConnector();
        $transformer = Mage::getSingleton('arena_connector/transformer_product');
        $parent = $transformer->getParent($product);

        if($parent){
            if(!$parent->getData('arena_id')){
                $this->pushFullProductToArena($parent, $product->getStoreId());
            }
        }
        $productTransport = $transformer->transform($product);
        //Mage::log(json_encode($productTransport),null,'arena_debug.log');
        $result = $connector->updateProduct($productTransport, $product->getStoreId());

        if ($result->getStatus() != 200) {
            throw new Arena_Connector_Model_Api_Exception_UnexpectedResult($result->getBody(), $product->getId());
        }

        $productJson = json_decode($result->getBody(), true);
        if (!$product->getData('arena_id')) {
            $product->setData('arena_id', $productJson['product']['arena_id']);
            Mage::getResourceModel('catalog/product_action')
                ->updateAttributes(
                    array($product->getId()),
                    array('arena_id' => $productJson['product']['arena_id']),
                    $product->getStoreId()
                );
        }
    }
    public function pushStatus($product)
    {
        $connector = $this->getConnector();
        $statusTransport = Mage::getSingleton('arena_connector/transformer_status')->transform($product);
        $result = $connector->updateStatus($statusTransport, $product->getStoreId());
        if ($result->getStatus() != 200) {
            throw new Arena_Connector_Model_Api_Exception_UnexpectedResult($result->getBody(), $product->getId());
        }
    }

    public function pushPictures($product)
    {
        $connector = $this->getConnector();

        $picturesTransport = Mage::getSingleton('arena_connector/transformer_pictures')->transform($product);
        $result = $connector->updatePictures($picturesTransport, $product->getStoreId());

        if ($result->getStatus() != 200) {
            throw new Arena_Connector_Model_Api_Exception_UnexpectedResult($result->getBody(), $product->getId());
        }
    }
}
